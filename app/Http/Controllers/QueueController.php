<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\QrToken;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class QueueController extends Controller
{
// ==========================================
    // USER PAGES
    // ==========================================

    public function index()
    {
        $this->processOnlineBookings();
        $currentServing = Queue::today()->serving()->first();
        $waitingCount = Queue::today()->waiting()->count();
        $reservedCount = Queue::today()->byStatus('reserved')->count();
        $settings = Setting::allAsArray();

        return view('index', compact('currentServing', 'waitingCount', 'reservedCount', 'settings'));
    }
public function booking()
    {
        $slots = $this->getTimeSlots()->getData()->slots;
        return view('booking', ['slots' => $slots]);
    }

    public function showTicket(string $uniqueCode)
    {
        $ticket = Queue::where('unique_code', $uniqueCode)->firstOrFail();
        return view('ticket', compact('ticket'));
    }

    public function scanLanding(string $token)
    {
        $valid = QrToken::where('token', strtoupper(trim($token)))
            ->where('expires_at', '>', now())
            ->first();

        if (!$valid) {
            return redirect('/')->with('error', 'QR code expired atau tidak valid.');
        }

        return view('scan', ['token' => strtoupper(trim($token))]);
    }

    // ==========================================
    // ONLINE SCANNING
    // ==========================================

    public function onlineScan(Request $request): JsonResponse
    {
        $request->validate([
            'unique_code' => 'required|string|max:64',
            'customer_name' => 'required|string|max:100|min:2',
        ]);

        $ticket = Queue::where('unique_code', $request->input('unique_code'))
            ->where('type', 'online')
            ->where('status', 'reserved')
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket tidak ditemukan atau tidak valid.'], 404);
        }

        $scanTime = now();
        $isOnTime = $scanTime->lte($ticket->scheduled_at);

        // Update ticket with scan time
        $ticket->update([
            'scan_time' => $scanTime,
            'customer_name' => trim($request->input('customer_name')),
        ]);

        // If scanned late, treat as walk-in
        if (!$isOnTime) {
            $ticket->update(['status' => 'waiting']);
            return response()->json([
                'success' => true,
                'message' => 'Ticket discan (terlambat). Diperlakukan sebagai walk-in.',
                'ticket' => $this->fmt($ticket),
            ]);
        }

        // If scanned on time, keep as reserved until 10 min before
        return response()->json([
            'success' => true,
            'message' => 'Ticket discan (on time).',
            'ticket' => $this->fmt($ticket),
        ]);
    }

    // ==========================================
    // WALK-IN (QR TOKEN) - NO IP CHECK
    // ==========================================

    public function walkIn(Request $request): JsonResponse
    {
        $request->validate([
            'qr_token' => 'required|string|max:20',
            'customer_name' => 'required|string|max:100|min:2',
        ]);

        $ip = $request->ip();

        // Rate limit only (no IP-based duplicate check)
        $rateKey = 'qrl_' . md5($ip);
        if (Cache::get($rateKey, 0) >= 10) {
            return response()->json(['success' => false, 'message' => 'Terlalu banyak permintaan. Tunggu beberapa saat.'], 429);
        }

        // Validate QR token
        $token = strtoupper(trim($request->input('qr_token')));
        $valid = QrToken::where('token', $token)->where('expires_at', '>', now())->first();
        if (!$valid) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa. Scan QR terbaru.'], 422);
        }

        // Check operating hours
        if (!Setting::isStoreOpen()) {
            $open = Setting::getOpenTime();
            $close = Setting::getCloseTime();
            return response()->json([
                'success' => false,
                'message' => "Toko sedang tutup. Jam operasional: {$open} - {$close}.",
            ], 422);
        }

        $customerName = trim($request->input('customer_name'));

        $ticket = Queue::create([
            'ticket_number' => Queue::generateTicketNumber(),
            'customer_name' => $customerName,
            'unique_code' => Queue::generateUniqueCode(),
            'type' => 'walk-in',
            'status' => 'waiting',
            'ip_address' => $ip,
        ]);

        Cache::put($rateKey, Cache::get($rateKey, 0) + 1, now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuat!',
            'ticket' => $this->fmt($ticket),
        ]);
    }

    // ==========================================
    // ONLINE BOOKING - NO IP CHECK
    // ==========================================

    public function bookOnline(Request $request): JsonResponse
    {
        $request->validate([
            'scheduled_time' => 'required|date_format:H:i',
            'customer_name' => 'required|string|max:100|min:2',
        ]);

        $ip = $request->ip();

        // Rate limit only
        $rateKey = 'qrl_' . md5($ip);
        if (Cache::get($rateKey, 0) >= 10) {
            return response()->json(['success' => false, 'message' => 'Terlalu banyak permintaan.'], 429);
        }

        // Check store accepting bookings
        if (Setting::get('is_open', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Toko sedang tutup untuk hari ini.'], 422);
        }

        $openTime = Setting::getOpenTime();
        $closeTime = Setting::getCloseTime();
        $slotDuration = Setting::getSlotDuration();
        $slotCapacity = Setting::getSlotCapacity();

        $scheduledAt = Carbon::today()->setTimeFromTimeString($request->scheduled_time);

        // Must be 30 min in future
        if ($scheduledAt->lte(now()->addMinutes(29))) {
            return response()->json(['success' => false, 'message' => 'Booking minimal 30 menit dari sekarang.'], 422);
        }

        // Operating hours
        $openToday = Carbon::today()->setTimeFromTimeString($openTime);
        $closeToday = Carbon::today()->setTimeFromTimeString($closeTime);

        if ($scheduledAt->lt($openToday) || $scheduledAt->gte($closeToday)) {
            return response()->json([
                'success' => false,
                'message' => "Di luar jam operasional ({$openTime} - {$closeTime}).",
            ], 422);
        }

        // Align to slot
        $minute = (int) $scheduledAt->format('i');
        $aligned = (int) floor($minute / $slotDuration) * $slotDuration;
        $scheduledAt->minute($aligned)->second(0);

        // Check capacity
        $slotStart = $scheduledAt->copy();
        $slotEnd = $scheduledAt->copy()->addMinutes($slotDuration);

        $booked = Queue::today()
            ->where('type', 'online')
            ->whereIn('status', ['waiting', 'reserved', 'serving'])
            ->where('scheduled_at', '>=', $slotStart)
            ->where('scheduled_at', '<', $slotEnd)
            ->count();

        if ($booked >= $slotCapacity) {
            return response()->json([
                'success' => false,
                'message' => "Slot ini penuh ({$booked}/{$slotCapacity}). Pilih slot lain.",
            ], 422);
        }

        $customerName = trim($request->input('customer_name'));

        $ticket = Queue::create([
            'ticket_number' => Queue::generateTicketNumber(),
            'customer_name' => $customerName,
            'unique_code' => Queue::generateUniqueCode(),
            'type' => 'online',
            'status' => 'reserved',
            'scheduled_at' => $scheduledAt,
            'ip_address' => $ip,
        ]);

        Cache::put($rateKey, Cache::get($rateKey, 0) + 1, now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil!',
            'ticket' => $this->fmt($ticket),
        ]);
    }

    // ==========================================
    // TIME SLOTS API
    // ==========================================

    public function getTimeSlots(): JsonResponse
    {
        $openTime = Setting::getOpenTime();
        $closeTime = Setting::getCloseTime();
        $duration = Setting::getSlotDuration();
        $capacity = Setting::getSlotCapacity();
        $isOpen = Setting::get('is_open', '1') === '1';

        $slots = [];
        $now = now();

        $current = Carbon::today()->setTimeFromTimeString($openTime);
        $end = Carbon::today()->setTimeFromTimeString($closeTime);

        while ($current->lt($end)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($duration);

            $booked = Queue::today()
                ->where('type', 'online')
                ->whereIn('status', ['waiting', 'reserved', 'serving'])
                ->where('scheduled_at', '>=', $slotStart)
                ->where('scheduled_at', '<', $slotEnd)
                ->count();

            $isPast = $slotStart->lte($now->copy()->addMinutes(29));

            if ($booked > 0 || !$isPast) {
                $slots[] = [
                    'time' => $slotStart->format('h:i A'),
                    'value' => $slotStart->format('H:i'),
                    'booked' => $booked,
                    'capacity' => $capacity,
                    'available' => max(0, $capacity - $booked),
                    'is_past' => $isPast,
                    'is_full' => $booked >= $capacity,
                ];
            }

            $current->addMinutes($duration);
        }

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'slot_duration' => $duration,
            'slot_capacity' => $capacity,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'is_open' => $isOpen,
        ]);
    }

    // ==========================================
    // USER STATUS APIs
    // ==========================================

    public function ticketStatus(string $uniqueCode): JsonResponse
    {
        $ticket = Queue::where('unique_code', $uniqueCode)->first();
        if (!$ticket) return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        return response()->json(['success' => true, 'ticket' => $this->fmt($ticket)]);
    }

    public function liveStatus(): JsonResponse
    {
        $this->processOnlineBookings();
        $serving = Queue::today()->serving()->first();
        return response()->json([
            'success' => true,
            'serving' => $serving ? $this->fmt($serving) : null,
            'waiting_count' => Queue::today()->waiting()->count(),
            'reserved_count' => Queue::today()->byStatus('reserved')->count(),
            'is_open' => Setting::isStoreOpen(),
        ]);
    }
    /**
     * Public queue list - no admin auth needed
     */
        public function publicQueueList(): JsonResponse
    {
        $this->processOnlineBookings();

        $serving = Queue::today()->serving()->first();
        $waiting = Queue::today()
            ->whereIn('status', ['waiting', 'reserved'])
            ->orderByRaw("CASE WHEN type = 'online' AND scheduled_at IS NOT NULL THEN scheduled_at ELSE created_at END ASC")
            ->get()
            ->map(fn($t) => $this->fmt($t));

        return response()->json([
            'success' => true,
            'serving' => $serving ? $this->fmt($serving) : null,
            'waiting' => $waiting,
            'waiting_count' => $waiting->count(),
        ]);
    }
    public function cancelTicket(Request $request, string $uniqueCode): JsonResponse
    {
        $ticket = Queue::where('unique_code', $uniqueCode)->first();
        if (!$ticket) return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        if (!in_array($ticket->status, ['waiting', 'reserved'])) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa dibatalkan.'], 422);
        }
        $ticket->update(['status' => 'cancelled', 'completed_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Dibatalkan.', 'ticket' => $this->fmt($ticket)]);
    }

    // ==========================================
    // ADMIN PAGES
    // ==========================================

    public function adminLogin()
    {
        if (session('admin_authenticated') === true) return redirect()->route('admin.dashboard');
        return view('admin.login');
    }

    public function adminAuthenticate(Request $request)
    {
        $request->validate(['pin' => 'required|string|max:20']);
        if ($request->pin === env('ADMIN_PIN', '1234')) {
            $request->session()->put('admin_authenticated', true);
            return redirect()->route('admin.dashboard');
        }
        return back()->withErrors(['pin' => 'PIN salah.']);
    }

    public function adminLogout(Request $request)
    {
        $request->session()->forget('admin_authenticated');
        return redirect()->route('admin.login');
    }

        public function adminDashboard()
    {
        if (session('admin_authenticated') !== true) return redirect()->route('admin.login');
        $this->processOnlineBookings();

        $currentServing = Queue::today()->serving()->first();
        $waitingList = Queue::today()
            ->whereIn('status', ['waiting', 'reserved'])
            ->orderByRaw("CASE WHEN type = 'online' AND scheduled_at IS NOT NULL THEN scheduled_at ELSE created_at END ASC")
            ->get();
        $completedToday = Queue::today()->byStatus('completed')->count();
        $skippedToday = Queue::today()->byStatus('skipped')->count();
        $cancelledToday = Queue::today()->byStatus('cancelled')->count();
        $totalToday = Queue::today()->count();
        $settings = Setting::allAsArray();

        return view('admin.dashboard', compact(
            'currentServing', 'waitingList', 'completedToday',
            'skippedToday', 'cancelledToday', 'totalToday', 'settings'
        ));
    }

    /**
     * Separate QR Display Page (public, but needs valid admin session)
     */
    public function qrDisplayPage()
    {
        if (session('admin_authenticated') !== true) return redirect()->route('admin.login');
        $settings = Setting::allAsArray();
        return view('admin.qr-display', compact('settings'));
    }

    // ==========================================
    // ADMIN APIs
    // ==========================================

    public function adminData(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $this->processOnlineBookings();

        $serving = Queue::today()->serving()->first();
        $waiting = Queue::today()->whereIn('status', ['waiting', 'reserved'])->orderByRaw("CASE WHEN type = 'online' AND scheduled_at IS NOT NULL THEN scheduled_at ELSE created_at END ASC")->get()
            ->map(fn($t) => $this->fmt($t));
        $history = Queue::today()->whereIn('status', ['completed', 'skipped', 'cancelled'])
            ->orderBy('updated_at', 'desc')->limit(15)->get()
            ->map(fn($t) => $this->fmt($t));

        return response()->json([
            'success' => true,
            'serving' => $serving ? $this->fmt($serving) : null,
            'waiting' => $waiting,
            'history' => $history,
            'stats' => [
                'completed' => Queue::today()->byStatus('completed')->count(),
                'skipped' => Queue::today()->byStatus('skipped')->count(),
                'cancelled' => Queue::today()->byStatus('cancelled')->count(),
                'total' => Queue::today()->count(),
                'waiting_count' => $waiting->count(),
            ],
        ]);
    }

        public function callNext(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $current = Queue::today()->serving()->first();
        if ($current) $current->update(['status' => 'completed', 'completed_at' => now()]);

        $next = Queue::today()
            ->where('status', 'waiting')
            ->orderByRaw("CASE WHEN type = 'online' AND scheduled_at IS NOT NULL THEN scheduled_at ELSE created_at END ASC")
            ->first();

        if (!$next) return response()->json(['success' => true, 'message' => 'Antrian kosong.', 'ticket' => null]);

        $next->update(['status' => 'serving', 'called_at' => now()]);
        $name = $next->customer_name ? " ({$next->customer_name})" : '';
        return response()->json(['success' => true, 'message' => 'Memanggil ' . $next->ticket_number . $name, 'ticket' => $this->fmt($next)]);
    }

    public function skipCurrent(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $current = Queue::today()->serving()->first();
        if (!$current) return response()->json(['success' => false, 'message' => 'Tidak ada yang dilayani.'], 422);
        $current->update(['status' => 'skipped', 'completed_at' => now()]);
        return response()->json(['success' => true, 'message' => $current->ticket_number . ' dilewati.']);
    }

    public function completeCurrent(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $current = Queue::today()->serving()->first();
        if (!$current) return response()->json(['success' => false, 'message' => 'Tidak ada yang dilayani.'], 422);
        $current->update(['status' => 'completed', 'completed_at' => now()]);
        return response()->json(['success' => true, 'message' => $current->ticket_number . ' selesai.']);
    }

    public function adminCancelTicket(int $id): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $ticket = Queue::find($id);
        if (!$ticket) return response()->json(['success' => false, 'message' => 'Tidak ditemukan.'], 404);
        if (!in_array($ticket->status, ['waiting', 'reserved', 'serving'])) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa dibatalkan.'], 422);
        }
        $ticket->update(['status' => 'cancelled', 'completed_at' => now()]);
        return response()->json(['success' => true, 'message' => $ticket->ticket_number . ' dibatalkan.']);
    }

    public function resetQueue(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $count = Queue::today()->active()->count();
        Queue::today()->active()->update(['status' => 'cancelled', 'completed_at' => now()]);
        return response()->json(['success' => true, 'message' => $count . ' tiket dibatalkan.']);
    }

    // ==========================================
    // ADMIN QR TOKEN
    // ==========================================

    public function getQrToken(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        $token = QrToken::getOrCreate();
        return response()->json([
            'success' => true,
            'token' => $token->token,
            'url' => url('/walk-in/' . $token->token),
            'expires_at' => $token->expires_at->format('h:i A'),
        ]);
    }

    public function regenerateQrToken(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        QrToken::where('expires_at', '>', now())->update(['expires_at' => now()]);
        $token = QrToken::createNew();
        return response()->json([
            'success' => true,
            'token' => $token->token,
            'url' => url('/walk-in/' . $token->token),
            'expires_at' => $token->expires_at->format('h:i A'),
        ]);
    }

    // ==========================================
    // ADMIN SETTINGS
    // ==========================================

    public function getSettings(): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);
        return response()->json(['success' => true, 'settings' => Setting::allAsArray()]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        if (session('admin_authenticated') !== true) return response()->json(['success' => false], 401);

        $request->validate([
            'open_hour' => 'sometimes|date_format:H:i',
            'close_hour' => 'sometimes|date_format:H:i',
            'slot_duration' => 'sometimes|integer|in:10,15,20,30',
            'slot_capacity' => 'sometimes|integer|min:1|max:20',
            'store_name' => 'sometimes|string|max:100',
            'is_open' => 'sometimes|in:0,1',
        ]);

        $allowedKeys = ['open_hour', 'close_hour', 'slot_duration', 'slot_capacity', 'store_name', 'is_open'];
        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                Setting::set($key, (string) $request->input($key));
            }
        }
        Setting::clearCache();

        return response()->json(['success' => true, 'message' => 'Pengaturan disimpan.', 'settings' => Setting::allAsArray()]);
    }

    // ==========================================
    // PRIVATE
    // ==========================================

        private function processOnlineBookings(): void
    {
        // Reserved → Waiting: 10 min before scheduled time
        Queue::today()
            ->where('type', 'online')
            ->where('status', 'reserved')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now()->addMinutes(10))
            ->where('scheduled_at', '>', now())
            ->update(['status' => 'waiting']);

        // Auto-cancel: 10 min AFTER scheduled time and still reserved
        Queue::today()
            ->where('type', 'online')
            ->where('status', 'reserved')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

        // Also auto-cancel waiting online tickets that are way past schedule (30 min)
        Queue::today()
            ->where('type', 'online')
            ->where('status', 'waiting')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now()->subMinutes(30))
            ->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);
    }

      private function fmt(Queue $ticket): array
{
    return [
        'id' => $ticket->id,
        'ticket_number' => $ticket->ticket_number,
        'customer_name' => $ticket->customer_name,
        'display_name' => $ticket->display_name,
        'unique_code' => $ticket->unique_code,
        'type' => $ticket->type,
        'status' => $ticket->status,
        'position' => $ticket->position,

        // Format untuk tampilan langsung (sudah benar)
        'scheduled_at'      => $ticket->scheduled_at ? $ticket->scheduled_at->format('h:i A') : null,
        'called_at'         => $ticket->called_at ? $ticket->called_at->format('h:i A') : null,
        'created_at'        => $ticket->created_at->format('h:i A'),

        // Tambahkan versi ISO dengan timezone info (untuk JS / debug)
        'created_at_iso'    => $ticket->created_at->toISOString(),
        'scheduled_at_iso'  => $ticket->scheduled_at ? $ticket->scheduled_at->toISOString() : null,
        'called_at_iso'     => $ticket->called_at ? $ticket->called_at->toISOString() : null,

        // versi lain yang mungkin berguna
        'created_at_full'   => $ticket->created_at->format('M d, Y h:i A'),
        'time_ago'          => $ticket->created_at->diffForHumans(),
        'sort_time'         => $ticket->type === 'online' && $ticket->scheduled_at
            ? $ticket->scheduled_at->format('H:i')
            : $ticket->created_at->format('H:i'),
    ];
}
}