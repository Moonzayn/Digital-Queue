@extends('layouts.app')

@section('title', 'Book Online - Digital Queue')

@section('content')
<div class="min-h-screen bg-surface">
    <header class="sticky top-0 z-50 bg-white/80 glass border-b border-gray-100">
        <div class="max-w-lg mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-text-main hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span class="text-sm font-semibold">Back</span>
            </a>
            <span class="text-sm font-bold text-text-main">Book Online</span>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-5">
        <!-- Info -->
        <div class="bg-blue-50 border border-blue-100 rounded-card p-4 fade-in">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-blue-800">Cara Booking Online</p>
                    <p class="text-xs text-blue-600 mt-1">Pilih slot waktu → Datang 10 menit sebelum jadwal → Otomatis masuk antrian. Jika telat lebih dari {{ config('queue_settings.late_tolerance_minutes', 10) }} menit, booking dibatalkan otomatis.</p>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 px-1">
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-green-100 border border-green-300"></div>
                <span class="text-xs text-text-muted">Available</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-amber-100 border border-amber-300"></div>
                <span class="text-xs text-text-muted">Almost Full</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-red-100 border border-red-300"></div>
                <span class="text-xs text-text-muted">Full</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300"></div>
                <span class="text-xs text-text-muted">Past</span>
            </div>
        </div>

        <!-- Slots Grid -->
        <div class="bg-white rounded-card shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-bold text-text-main">Available Time Slots</h3>
                <p class="text-xs text-text-muted mt-0.5">{{ now()->format('l, M d, Y') }}</p>
            </div>
            <div id="slotsContainer" class="p-4 grid grid-cols-2 gap-2.5">
                @foreach($slots as $slot)
                @php
                    $bgClass = 'bg-gray-50 border-gray-200 opacity-50 cursor-not-allowed';
                    $textClass = 'text-gray-400';
                    $badgeClass = 'bg-gray-200 text-gray-500';
                    if (!$slot['is_past']) {
                        if ($slot['available'] <= 0) {
                            $bgClass = 'bg-red-50 border-red-200 opacity-70 cursor-not-allowed';
                            $textClass = 'text-red-400';
                            $badgeClass = 'bg-red-100 text-red-600';
                        } elseif ($slot['fill_percent'] >= 70) {
                            $bgClass = 'bg-amber-50 border-amber-200 hover:border-amber-400 hover:shadow-md cursor-pointer';
                            $textClass = 'text-amber-700';
                            $badgeClass = 'bg-amber-100 text-amber-700';
                        } else {
                            $bgClass = 'bg-green-50 border-green-200 hover:border-green-400 hover:shadow-md cursor-pointer';
                            $textClass = 'text-green-700';
                            $badgeClass = 'bg-green-100 text-green-700';
                        }
                    }
                    if ($slot['is_current']) {
                        $bgClass = str_replace('opacity-50', '', $bgClass);
                        $bgClass .= ' ring-2 ring-primary/30';
                    }
                @endphp
                <button
                    class="slot-btn p-3.5 rounded-xl border-2 text-left transition-all duration-200 {{ $bgClass }}"
                    data-key="{{ $slot['key'] }}"
                    data-can-book="{{ $slot['can_book'] ? '1' : '0' }}"
                    {{ !$slot['can_book'] ? 'disabled' : '' }}
                    onclick="selectSlot(this)"
                >
                    <p class="text-sm font-bold {{ $textClass }}">{{ $slot['start_display'] }}</p>
                    <p class="text-[10px] text-text-muted">to {{ $slot['end_display'] }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $badgeClass }}">
                            {{ $slot['available'] }}/{{ $slot['capacity'] }} left
                        </span>
                        @if($slot['is_current'])
                        <span class="text-[10px] font-bold text-primary">NOW</span>
                        @endif
                    </div>
                    <!-- Progress bar -->
                    <div class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ $slot['fill_percent'] >= 70 ? ($slot['fill_percent'] >= 100 ? 'bg-red-400' : 'bg-amber-400') : 'bg-green-400' }}" style="width: {{ min(100, $slot['fill_percent']) }}%"></div>
                    </div>
                </button>
                @endforeach
            </div>
        </div>
    </main>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 glass" onclick="hideConfirmModal()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 pb-8 max-w-lg mx-auto slide-up">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-6"></div>
            <h3 class="text-lg font-bold text-text-main mb-1">Konfirmasi Booking</h3>
            <p class="text-sm text-text-muted mb-5">Anda akan booking slot waktu:</p>
            <div class="bg-surface rounded-xl p-4 mb-5 text-center">
                <p id="confirmSlotTime" class="text-2xl font-bold text-primary"></p>
                <p id="confirmSlotDate" class="text-sm text-text-muted mt-1"></p>
            </div>
<div class="bg-amber-50 border border-amber-100 rounded-xl p-3 mb-5">
                <p class="text-xs text-amber-700 font-medium">⚠️ Harap datang tepat waktu. Booking otomatis dibatalkan jika telat {{ config('queue_settings.late_tolerance_minutes', 10) }} menit.</p>
            </div>
            <button onclick="confirmBooking()" id="confirmBookBtn" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-4 rounded-xl transition-all scale-press text-base disabled:opacity-50">
                Konfirmasi Booking
            </button>
            <button onclick="hideConfirmModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-text-main font-semibold py-3.5 rounded-xl transition-all text-sm mt-2">
                Batal
            </button>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingOverlay" class="fixed inset-0 z-[60] hidden items-center justify-center bg-white/80 glass">
        <div class="text-center">
            <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mx-auto"></div>
            <p class="mt-4 text-sm font-medium text-text-muted">Memproses...</p>
        </div>
    </div>
</div>

<!-- Scan Modal -->
<div id="scanModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 glass" onclick="hideScanModal()"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 pb-8 max-w-lg mx-auto slide-up">
        <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-6"></div>
        <h3 class="text-lg font-bold text-text-main mb-3">Scan QR Code</h3>
        <p class="text-sm text-text-muted mb-4">Scan QR code dari tiket booking Anda untuk mengamankan prioritas.</p>
        <div class="bg-surface rounded-xl p-4 mb-4">
            <p class="text-sm font-medium text-text-main mb-2">Petunjuk:</p>
            <ul class="text-sm text-text-muted space-y-1">
                <li>Scan QR code dari tiket booking Anda</li>
                <li>Pastikan scan dilakukan minimal 10 menit sebelum jadwal</li>
                <li>Scan terlambat akan kehilangan prioritas</li>
            </ul>
        </div>
        <div class="flex gap-3">
            <button onclick="startScan()" class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Mulai Scan
            </button>
            <button onclick="hideScanModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-text-main font-semibold py-3 rounded-xl transition-all">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- Scan Result Modal -->
<div id="scanResultModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 glass" onclick="hideScanResultModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 id="scanResultTitle" class="text-lg font-bold text-text-main text-center mb-2">Scan Berhasil</h3>
            <p id="scanResultMessage" class="text-sm text-text-muted text-center mb-4"></p>
            <button onclick="hideScanResultModal()" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all">
                Tutup
            </button>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').content;
var selectedSlotKey = null;
var selectedSlotLabel = '';

function selectSlot(btn) {
    if (btn.dataset.canBook !== '1') return;

    var stored = localStorage.getItem('dq_active_ticket');
    if (stored) {
        try {
            var t = JSON.parse(stored);
            if (t && t.unique_code) {
                showToast('Anda sudah punya tiket aktif: ' + t.ticket_number, 'warning');
                return;
            }
        } catch(e) { localStorage.removeItem('dq_active_ticket'); }
    }

    selectedSlotKey = btn.dataset.key;
    var timeLabel = btn.querySelector('.text-sm').textContent;
    selectedSlotLabel = timeLabel;

    document.getElementById('confirmSlotTime').textContent = timeLabel;
    document.getElementById('confirmSlotDate').textContent = '{{ now()->format("l, M d, Y") }}';
    document.getElementById('confirmModal').classList.remove('hidden');
}

function hideConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    selectedSlotKey = null;
}

function confirmBooking() {
    if (!selectedSlotKey) return;
    var btn = document.getElementById('confirmBookBtn');
    btn.disabled = true;
    hideConfirmModal();

    var lo = document.getElementById('loadingOverlay');
    lo.classList.remove('hidden'); lo.classList.add('flex');

    fetch('/api/book-slot', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ slot_key: selectedSlotKey })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        lo.classList.add('hidden'); lo.classList.remove('flex');
        btn.disabled = false;

        if (data.success) {
            localStorage.setItem('dq_active_ticket', JSON.stringify(data.ticket));
            showToast(data.message, 'success');
            setTimeout(function() {
                window.location.href = '/ticket/' + data.ticket.unique_code;
            }, 1000);
        } else {
            showToast(data.message || 'Booking gagal', 'error');
            if (data.has_existing && data.ticket) {
                localStorage.setItem('dq_active_ticket', JSON.stringify(data.ticket));
            }
            refreshSlots();
        }
    })
    .catch(function() {
        lo.classList.add('hidden'); lo.classList.remove('flex');
        btn.disabled = false;
        showToast('Network error', 'error');
    });
}

function refreshSlots() {
    fetch('/api/slots', { headers: { 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) return;
        var container = document.getElementById('slotsContainer');
        var html = '';
        data.slots.forEach(function(s) {
            var bg, tc, bc;
            if (s.is_past) {
                bg = 'bg-gray-50 border-gray-200 opacity-50 cursor-not-allowed';
                tc = 'text-gray-400'; bc = 'bg-gray-200 text-gray-500';
            } else if (s.available <= 0) {
                bg = 'bg-red-50 border-red-200 opacity-70 cursor-not-allowed';
                tc = 'text-red-400'; bc = 'bg-red-100 text-red-600';
            } else if (s.fill_percent >= 70) {
                bg = 'bg-amber-50 border-amber-200 hover:border-amber-400 hover:shadow-md cursor-pointer';
                tc = 'text-amber-700'; bc = 'bg-amber-100 text-amber-700';
            } else {
                bg = 'bg-green-50 border-green-200 hover:border-green-400 hover:shadow-md cursor-pointer';
                tc = 'text-green-700'; bc = 'bg-green-100 text-green-700';
            }
            if (s.is_current) bg += ' ring-2 ring-primary/30';
            var pbc = s.fill_percent >= 100 ? 'bg-red-400' : (s.fill_percent >= 70 ? 'bg-amber-400' : 'bg-green-400');
            var nowLabel = s.is_current ? '<span class="text-[10px] font-bold text-primary">NOW</span>' : '';

            html += '<button class="slot-btn p-3.5 rounded-xl border-2 text-left transition-all duration-200 ' + bg + '" data-key="' + s.key + '" data-can-book="' + (s.can_book ? '1' : '0') + '" ' + (!s.can_book ? 'disabled' : '') + ' onclick="selectSlot(this)">' +
                '<p class="text-sm font-bold ' + tc + '">' + s.start_display + '</p>' +
                '<p class="text-[10px] text-text-muted">to ' + s.end_display + '</p>' +
                '<div class="mt-2 flex items-center justify-between">' +
                '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded ' + bc + '">' + s.available + '/' + s.capacity + ' left</span>' +
                nowLabel + '</div>' +
                '<div class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden"><div class="h-full rounded-full transition-all ' + pbc + '" style="width:' + Math.min(100, s.fill_percent) + '%"></div></div></button>';
        });
        container.innerHTML = html;
    });
}

setInterval(function() { if (!document.hidden) refreshSlots(); }, 10000);

// Scan functionality
function startScan() {
    hideScanModal();
    hideScanResultModal();

    // Simulate QR code scanning (in real implementation, this would use a QR scanner)
    var ticketCode = prompt("Masukkan kode unik tiket Anda:");
    if (!ticketCode) return;

    var lo = document.getElementById('loadingOverlay');
    lo.classList.remove('hidden'); lo.classList.add('flex');

    fetch('/api/online-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ unique_code: ticketCode, customer_name: '' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        lo.classList.add('hidden'); lo.classList.remove('flex');

        if (data.success) {
            document.getElementById('scanResultTitle').textContent = 'Scan Berhasil';
            document.getElementById('scanResultMessage').textContent = data.message;
            localStorage.setItem('dq_active_ticket', JSON.stringify(data.ticket));
        } else {
            document.getElementById('scanResultTitle').textContent = 'Scan Gagal';
            document.getElementById('scanResultMessage').textContent = data.message || 'Terjadi kesalahan saat scan.';
        }
        document.getElementById('scanResultModal').classList.remove('hidden');
    })
    .catch(function() {
        lo.classList.add('hidden'); lo.classList.remove('flex');
        document.getElementById('scanResultTitle').textContent = 'Scan Gagal';
        document.getElementById('scanResultMessage').textContent = 'Network error';
        document.getElementById('scanResultModal').classList.remove('hidden');
    });
}

function hideScanModal() {
    document.getElementById('scanModal').classList.add('hidden');
}

function hideScanResultModal() {
    document.getElementById('scanResultModal').classList.add('hidden');
}
</script>
@endsection
