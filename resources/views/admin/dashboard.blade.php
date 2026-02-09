@extends('layouts.app')
@section('title', 'Admin Dashboard - Digital Queue')

@section('content')
<div class="min-h-screen bg-surface">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/80 glass border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-text-main tracking-tight">Queue Control</h1>
                <p class="text-xs text-text-muted font-medium" id="headerTime"></p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="toggleStoreOpen()" id="storeToggle" class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors scale-press {{ ($settings['is_open'] ?? '1') === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ($settings['is_open'] ?? '1') === '1' ? '🟢 Open' : '🔴 Closed' }}
                </button>
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-text-muted hover:text-danger bg-gray-100 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-5 space-y-5">
        <!-- Stats -->
        <div class="grid grid-cols-4 gap-2.5 fade-in">
            <div class="bg-white rounded-xl shadow-card p-3 text-center">
                <p class="text-2xl font-bold text-primary" id="statWaiting">0</p>
                <p class="text-[10px] sm:text-xs text-text-muted font-medium">Waiting</p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-3 text-center">
                <p class="text-2xl font-bold text-success" id="statCompleted">{{ $completedToday }}</p>
                <p class="text-[10px] sm:text-xs text-text-muted font-medium">Done</p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-3 text-center">
                <p class="text-2xl font-bold text-warning" id="statSkipped">{{ $skippedToday }}</p>
                <p class="text-[10px] sm:text-xs text-text-muted font-medium">Skipped</p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-3 text-center">
                <p class="text-2xl font-bold text-text-main" id="statTotal">{{ $totalToday }}</p>
                <p class="text-[10px] sm:text-xs text-text-muted font-medium">Total</p>
            </div>
        </div>

        <!-- Now Serving -->
        <div class="bg-white rounded-card shadow-card p-6">
            <div class="text-center">
                <p class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-2">Now Serving</p>
                <div id="adminServingNumber" class="text-6xl font-black text-primary number-glow tracking-tight" style="transition:all 0.4s ease;">
                    {{ $currentServing ? $currentServing->ticket_number : '---' }}
                </div>
                <div id="adminServingMeta" class="mt-3">
                    @if($currentServing)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full {{ $currentServing->type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $currentServing->type === 'online' ? '🌐 Online' : '🚶 Walk-in' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="grid grid-cols-3 gap-2.5">
            <button onclick="adminCallNext(this)" class="bg-primary hover:bg-primary-dark text-white font-bold py-4 sm:py-5 px-3 rounded-card shadow-card transition-all scale-press text-center disabled:opacity-50">
                <svg class="w-7 h-7 sm:w-8 sm:h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                <span class="text-sm sm:text-lg font-bold">CALL NEXT</span>
            </button>
            <button onclick="adminSkip(this)" class="bg-white hover:bg-amber-50 text-warning border-2 border-warning/30 hover:border-warning font-bold py-4 sm:py-5 px-3 rounded-card shadow-card transition-all scale-press text-center disabled:opacity-50">
                <svg class="w-7 h-7 sm:w-8 sm:h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>
                <span class="text-sm sm:text-lg font-bold">SKIP</span>
            </button>
            <button onclick="adminCancelCurrent(this)" class="bg-white hover:bg-red-50 text-danger border-2 border-danger/30 hover:border-danger font-bold py-4 sm:py-5 px-3 rounded-card shadow-card transition-all scale-press text-center disabled:opacity-50">
                <svg class="w-7 h-7 sm:w-8 sm:h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="text-sm sm:text-lg font-bold">CANCEL</span>
            </button>
        </div>

        <!-- Waiting Queue List -->
        <div class="bg-white rounded-card shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-text-main">Waiting Queue</h3>
                    <p class="text-xs text-text-muted" id="queueListCount">0 tickets</p>
                </div>
                <button onclick="adminResetQueue()" class="text-xs font-semibold text-danger bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">Reset All</button>
            </div>
            <div id="waitingQueueList" class="divide-y divide-gray-50 max-h-[400px] overflow-y-auto">
                @forelse($waitingList as $item)
                <div class="px-5 py-3.5 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <span class="text-lg font-bold text-text-main">{{ $item->ticket_number }}</span>
                        @if($item->customer_name)
                            <span class="text-xs text-text-muted">{{ $item->customer_name }}</span>
                        @endif
                        <span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full {{ $item->type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $item->type === 'online' ? 'Online' : 'Walk-in' }}
                        </span>
                        @if($item->status === 'reserved')
                            <span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Reserved</span>
                        @endif
                        @if($item->scheduled_at)
                            <span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded">🕐 Scheduled {{ $item->scheduled_at->format('h:i A') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-text-muted hidden sm:inline">
                            joined {{ $item->created_at->setTimezone('Asia/Jakarta')->format('h:i A') }} 
                       </span>
                        <button onclick="adminCancelTicket({{ $item->id }})" class="text-text-muted hover:text-danger p-1.5 rounded-lg hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="px-5 py-14 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <p class="text-sm text-text-muted">No tickets waiting</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-card shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-bold text-text-main">Recent Activity</h3>
            </div>
            <div id="historyList" class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                <div class="px-5 py-10 text-center"><p class="text-sm text-text-muted">Loading...</p></div>
            </div>
        </div>

        <!-- QR Code Link -->
        <div class="bg-white rounded-card shadow-card p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-text-main">Walk-in QR Code</h3>
                        <p class="text-xs text-text-muted">Token: <span id="qrTokenPreview" class="font-bold text-primary">---</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="regenerateQR()" class="text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-2 rounded-lg transition-colors scale-press">
                        Regenerate
                    </button>
                    <a href="{{ route('admin.qr.display') }}" target="_blank" class="text-xs font-semibold text-white bg-primary hover:bg-primary-dark px-4 py-2 rounded-lg transition-colors scale-press">
                        Open QR Display →
                    </a>
                </div>
            </div>
        </div>

        <!-- Booked Slots (only show slots with bookings) -->
        <div class="bg-white rounded-card shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-bold text-text-main">📅 Today's Booked Slots</h3>
                <p class="text-xs text-text-muted">Only showing slots with bookings</p>
            </div>
            <div id="adminSlotsGrid" class="p-4">
                <div class="py-6 text-center"><p class="text-sm text-text-muted">Loading...</p></div>
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white rounded-card shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-text-main">⚙️ Store Settings</h3>
                    <p class="text-xs text-text-muted">Operating hours & booking config</p>
                </div>
                <button onclick="toggleSettings()" id="settingsToggleBtn" class="text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg transition-colors">
                    Edit
                </button>
            </div>
            <div id="settingsPanel" class="px-5 py-5 space-y-4">
                <!-- View Mode -->
                <div id="settingsView" class="grid grid-cols-2 gap-3">
                    <div class="bg-surface rounded-xl p-4">
                        <p class="text-xs text-text-muted font-medium mb-1">Open Hour</p>
                        <p class="text-lg font-bold text-text-main" id="viewOpenHour">{{ $settings['open_hour'] ?? '08:00' }}</p>
                    </div>
                    <div class="bg-surface rounded-xl p-4">
                        <p class="text-xs text-text-muted font-medium mb-1">Close Hour</p>
                        <p class="text-lg font-bold text-text-main" id="viewCloseHour">{{ $settings['close_hour'] ?? '17:00' }}</p>
                    </div>
                    <div class="bg-surface rounded-xl p-4">
                        <p class="text-xs text-text-muted font-medium mb-1">Slot Duration</p>
                        <p class="text-lg font-bold text-text-main"><span id="viewSlotDuration">{{ $settings['slot_duration'] ?? '15' }}</span> min</p>
                    </div>
                    <div class="bg-surface rounded-xl p-4">
                        <p class="text-xs text-text-muted font-medium mb-1">Max per Slot</p>
                        <p class="text-lg font-bold text-text-main" id="viewSlotCapacity">{{ $settings['slot_capacity'] ?? '4' }}</p>
                    </div>
                </div>
                <!-- Edit Mode -->
                <div id="settingsEdit" class="hidden space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-text-main mb-1">Open Hour</label>
                            <input type="time" id="editOpenHour" value="{{ $settings['open_hour'] ?? '08:00' }}" class="w-full px-3 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-text-main mb-1">Close Hour</label>
                            <input type="time" id="editCloseHour" value="{{ $settings['close_hour'] ?? '17:00' }}" class="w-full px-3 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-text-main mb-1">Slot Duration (min)</label>
                            <select id="editSlotDuration" class="w-full px-3 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-2 focus:ring-primary/10">
                                <option value="10" {{ ($settings['slot_duration'] ?? '15') == '10' ? 'selected' : '' }}>10 min</option>
                                <option value="15" {{ ($settings['slot_duration'] ?? '15') == '15' ? 'selected' : '' }}>15 min</option>
                                <option value="20" {{ ($settings['slot_duration'] ?? '15') == '20' ? 'selected' : '' }}>20 min</option>
                                <option value="30" {{ ($settings['slot_duration'] ?? '15') == '30' ? 'selected' : '' }}>30 min</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-text-main mb-1">Max per Slot</label>
                            <input type="number" id="editSlotCapacity" min="1" max="20" value="{{ $settings['slot_capacity'] ?? '4' }}" class="w-full px-3 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-2 focus:ring-primary/10">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="saveSettings()" class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all scale-press text-sm">
                            Save Changes
                        </button>
                        <button onclick="toggleSettings()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-text-main font-semibold py-3 rounded-xl transition-all text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="h-6"></div>
    </main>

    <!-- QR Fullscreen -->
    <div id="qrFullscreen" class="fixed inset-0 z-50 hidden bg-white items-center justify-center">
        <button onclick="closeFullscreenQR()" class="absolute top-6 right-6 w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="text-center">
            <p class="text-2xl font-bold text-text-main mb-1">Scan to Join Queue</p>
            <p class="text-sm text-text-muted mb-6">Point your camera at the QR code</p>
            <div id="qrFullDisplay" class="inline-block bg-white p-6 rounded-3xl border-2 border-gray-100 shadow-elevated">
                <div class="w-72 h-72 sm:w-80 sm:h-80"></div>
            </div>
            <p class="mt-6 text-2xl font-black text-primary tracking-[0.4em]" id="qrFullToken">---</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var currentQRToken = '';
    var settingsEditing = false;

    document.addEventListener('DOMContentLoaded', function() {
        updateClock();
        setInterval(updateClock, 30000);
        refreshAdminData();
        setInterval(function(){ if(!document.hidden) refreshAdminData(); }, 3000);
        loadQRCode();
        setInterval(loadQRCode, 300000);
        loadAdminSlots();
        setInterval(loadAdminSlots, 15000);
    });

    function updateClock() {
        var n = new Date(),
            d = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
            mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            h = n.getHours(), ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('headerTime').textContent =
            d[n.getDay()] + ', ' + mo[n.getMonth()] + ' ' + n.getDate() +
            ' • ' + h + ':' + String(n.getMinutes()).padStart(2,'0') + ' ' + ap;
    }

    function aPost(url, body, cb) {
        var opts = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            }
        };
        if (body) opts.body = JSON.stringify(body);
        fetch(url, opts)
            .then(r => r.json())
            .then(cb)
            .catch(() => showToast('Network error', 'error'));
    }

    // QUEUE ACTIONS
    function adminCallNext(b) {
        if (b) b.disabled = true;
        aPost('/api/admin/call-next', null, function(d) {
            if (b) setTimeout(() => { b.disabled = false; }, 500);
            if (d.success) {
                showToast(d.message || 'Called!', d.ticket ? 'success' : 'info');
                if (d.ticket) DQ.playNotification();
                refreshAdminData();
            } else {
                showToast(d.message, 'error');
            }
        });
    }

    function adminSkip(b) {
        if (!confirm('Skip current?')) return;
        if (b) b.disabled = true;
        aPost('/api/admin/skip', null, function(d) {
            if (b) setTimeout(() => { b.disabled = false; }, 500);
            showToast(d.message, d.success ? 'warning' : 'error');
            if (d.success) refreshAdminData();
        });
    }

    function adminCancelCurrent(b) {
        var sv = document.getElementById('adminServingNumber').textContent.trim();
        if (sv === '---') { showToast('No ticket served.', 'warning'); return; }
        if (!confirm('Cancel ' + sv + '?')) return;
        if (b) b.disabled = true;
        fetch('/api/admin/data', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(function(d) {
                if (d.success && d.serving && d.serving.id) {
                    aPost('/api/admin/cancel/' + d.serving.id, null, function(r) {
                        if (b) setTimeout(() => { b.disabled = false; }, 500);
                        showToast(r.message, r.success ? 'info' : 'error');
                        if (r.success) refreshAdminData();
                    });
                } else {
                    if (b) b.disabled = false;
                    showToast('No serving.', 'warning');
                }
            })
            .catch(() => { if (b) b.disabled = false; });
    }

    function adminCancelTicket(id) {
        if (!confirm('Cancel?')) return;
        aPost('/api/admin/cancel/' + id, null, function(d) {
            showToast(d.message, d.success ? 'info' : 'error');
            if (d.success) refreshAdminData();
        });
    }

    function adminResetQueue() {
        if (!confirm('Cancel ALL?')) return;
        if (!confirm('Sure?')) return;
        aPost('/api/admin/reset', null, function(d) {
            showToast(d.message, d.success ? 'info' : 'error');
            if (d.success) refreshAdminData();
        });
    }

    // REFRESH DASHBOARD
    function refreshAdminData() {
        fetch('/api/admin/data', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => { if (d.success) renderDash(d); })
            .catch(() => {});
    }

    function renderDash(d) {
        var sEl = document.getElementById('adminServingNumber');
        var sn = d.serving ? d.serving.ticket_number : '---';
        if (sEl.textContent.trim() !== sn) {
            sEl.style.opacity = '0';
            sEl.style.transform = 'scale(0.85)';
            setTimeout(() => {
                sEl.textContent = sn;
                sEl.style.opacity = '1';
                sEl.style.transform = 'scale(1)';
            }, 250);
        }

        var me = document.getElementById('adminServingMeta');
        if (d.serving) {
            var o = d.serving.type === 'online';
            me.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full ' +
                (o ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700') + '">' +
                (o ? '🌐 Online' : '🚶 Walk-in') + '</span>' +
                (d.serving.called_at ? '<span class="text-xs text-text-muted ml-2">' + d.serving.called_at + '</span>' : '');
        } else {
            me.innerHTML = '';
        }

        document.getElementById('statWaiting').textContent = d.stats.waiting_count;
        document.getElementById('statCompleted').textContent = d.stats.completed;
        document.getElementById('statSkipped').textContent = d.stats.skipped;
        document.getElementById('statTotal').textContent = d.stats.total;
        document.getElementById('queueListCount').textContent = d.stats.waiting_count + ' tickets';

        // Waiting Queue List
        var lEl = document.getElementById('waitingQueueList');
        if (!d.waiting || d.waiting.length === 0) {
            lEl.innerHTML = '<div class="px-5 py-14 text-center"><div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3"><svg class="w-7 h-7 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></div><p class="text-sm text-text-muted">No tickets waiting</p></div>';
        } else {
            var h = '';
            for (var i = 0; i < d.waiting.length; i++) {
                var w = d.waiting[i];
                var isOnline = w.type === 'online';
                var typeClass = isOnline ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700';
                var typeText = isOnline ? 'Online' : 'Walk-in';

                var scheduledBadge = '';
                if (isOnline && w.scheduled_at) {
                    scheduledBadge = '<span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded">🕐 Scheduled ' + w.scheduled_at + '</span>';
                }

                var nameBadge = w.customer_name ? '<span class="text-xs text-text-muted">' + w.customer_name + '</span>' : '';

                h += '<div class="px-5 py-3.5 flex items-center justify-between hover:bg-gray-50/50 transition-colors">';
                h += '<div class="flex items-center gap-2 sm:gap-3 flex-wrap">';
                h += '<span class="text-lg font-bold text-text-main">' + w.ticket_number + '</span>';
                h += nameBadge;
                h += '<span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full ' + typeClass + '">' + typeText + '</span>';
                if (w.status === 'reserved') {
                    h += '<span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Reserved</span>';
                }
                h += scheduledBadge;
                h += '</div>';
                h += '<div class="flex items-center gap-2">';
                h += '<span class="text-[10px] text-text-muted hidden sm:inline">joined ' + w.created_at + '</span>';
                h += '<button onclick="adminCancelTicket(' + w.id + ')" class="text-text-muted hover:text-danger p-1.5 rounded-lg hover:bg-red-50 transition-colors">';
                h += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                h += '</button>';
                h += '</div>';
                h += '</div>';
            }
            lEl.innerHTML = h;
        }

        // History
        var hEl = document.getElementById('historyList');
        if (!d.history || d.history.length === 0) {
            hEl.innerHTML = '<div class="px-5 py-10 text-center"><p class="text-sm text-text-muted">No activity yet</p></div>';
        } else {
            var hh = '';
            for (var j = 0; j < d.history.length; j++) {
                var hi = d.history[j], sc = '', si = '';
                if (hi.status === 'completed') { sc = 'text-green-700 bg-green-50'; si = '✓'; }
                else if (hi.status === 'skipped') { sc = 'text-amber-700 bg-amber-50'; si = '⏭'; }
                else { sc = 'text-red-600 bg-red-50'; si = '✕'; }
                var hTO = hi.type === 'online';
                hh += '<div class="px-5 py-3 flex items-center justify-between">';
                hh += '<div class="flex items-center gap-2">';
                hh += '<span class="text-sm font-bold text-text-main">' + hi.ticket_number + '</span>';
                hh += '<span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full ' + (hTO ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700') + '">' + (hTO ? 'Online' : 'Walk-in') + '</span>';
                hh += '</div>';
                hh += '<div class="flex items-center gap-2">';
                hh += '<span class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full ' + sc + '">' + si + ' ' + hi.status + '</span>';
                hh += '<span class="text-[10px] text-text-muted">' + hi.time_ago + '</span>';
                hh += '</div>';
                hh += '</div>';
            }
            hEl.innerHTML = hh;
        }
    }

    // QR CODE
    function loadQRCode() {
        fetch('/api/admin/qr-token', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (d.success) document.getElementById('qrTokenPreview').textContent = d.token;
            })
            .catch(() => {});
    }

    function renderQR(id, text, size) {
        var c = document.getElementById(id); if (!c) return;
        var qr = qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        var cs = Math.floor(size / qr.getModuleCount());
        c.innerHTML = qr.createSvgTag(cs, 0);
        var svg = c.querySelector('svg');
        if (svg) {
            svg.style.width = size + 'px';
            svg.style.height = size + 'px';
            svg.style.display = 'block';
        }
    }

    function regenerateQR() {
        aPost('/api/admin/qr-regenerate', null, function(d) {
            if (d.success) {
                showToast('QR regenerated!', 'success');
                loadQRCode();
            } else {
                showToast(d.message || 'Failed', 'error');
            }
        });
    }

    function fullscreenQR() {
        var m = document.getElementById('qrFullscreen');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeFullscreenQR() {
        var m = document.getElementById('qrFullscreen');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // ADMIN SLOTS
    function loadAdminSlots() {
        fetch('/api/time-slots', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                var g = document.getElementById('adminSlotsGrid');
                var booked = d.slots.filter(s => s.booked > 0);
                if (booked.length === 0) {
                    g.innerHTML = '<div class="py-8 text-center"><p class="text-sm text-text-muted">No bookings yet today</p></div>';
                    return;
                }
                var h = '<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">';
                for (var i = 0; i < booked.length; i++) {
                    var s = booked[i];
                    var pct = Math.round((s.booked / s.capacity) * 100);
                    var barColor = s.is_full ? 'bg-red-500' : (s.available <= 1 ? 'bg-amber-500' : 'bg-green-500');
                    var borderCls = s.is_full ? 'border-red-200 bg-red-50' : (s.available <= 1 ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50');
                    var textCls = s.is_full ? 'text-red-700' : (s.available <= 1 ? 'text-amber-700' : 'text-green-700');
                    h += '<div class="border-2 ' + borderCls + ' rounded-xl p-3">';
                    h += '<div class="flex items-center justify-between mb-2">';
                    h += '<span class="text-sm font-bold ' + textCls + '">' + s.time + '</span>';
                    h += '<span class="text-xs font-semibold ' + textCls + '">' + s.booked + '/' + s.capacity + '</span>';
                    h += '</div>';
                    h += '<div class="w-full bg-gray-200 rounded-full h-1.5"><div class="' + barColor + ' h-1.5 rounded-full" style="width:' + pct + '%"></div></div>';
                    var dots = '<div class="flex gap-1 mt-2">';
                    for (var j = 0; j < s.capacity; j++) {
                        dots += '<div class="w-2.5 h-2.5 rounded-full ' + (j < s.booked ? 'bg-red-400' : 'bg-green-400') + '"></div>';
                    }
                    dots += '</div>';
                    h += dots + '</div>';
                }
                h += '</div>';
                g.innerHTML = h;
            })
            .catch(() => {});
    }

    // SETTINGS
    function toggleSettings() {
        settingsEditing = !settingsEditing;
        document.getElementById('settingsView').classList.toggle('hidden', settingsEditing);
        document.getElementById('settingsEdit').classList.toggle('hidden', !settingsEditing);
        document.getElementById('settingsToggleBtn').textContent = settingsEditing ? 'Cancel' : 'Edit';
    }

    function saveSettings() {
        var data = {
            open_hour: document.getElementById('editOpenHour').value,
            close_hour: document.getElementById('editCloseHour').value,
            slot_duration: document.getElementById('editSlotDuration').value,
            slot_capacity: document.getElementById('editSlotCapacity').value,
        };
        aPost('/api/admin/settings', data, function(d) {
            if (d.success) {
                showToast('Settings saved!', 'success');
                document.getElementById('viewOpenHour').textContent = data.open_hour;
                document.getElementById('viewCloseHour').textContent = data.close_hour;
                document.getElementById('viewSlotDuration').textContent = data.slot_duration;
                document.getElementById('viewSlotCapacity').textContent = data.slot_capacity;
                settingsEditing = false;
                document.getElementById('settingsView').classList.remove('hidden');
                document.getElementById('settingsEdit').classList.add('hidden');
                document.getElementById('settingsToggleBtn').textContent = 'Edit';
                loadAdminSlots();
            } else {
                showToast(d.message || 'Failed', 'error');
            }
        });
    }

    function toggleStoreOpen() {
        var btn = document.getElementById('storeToggle');
        var isOpen = btn.textContent.includes('Open');
        var newVal = isOpen ? '0' : '1';
        aPost('/api/admin/settings', { is_open: newVal }, function(d) {
            if (d.success) {
                if (newVal === '1') {
                    btn.textContent = '🟢 Open';
                    btn.className = 'text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors scale-press bg-green-100 text-green-700';
                    showToast('Store OPENED', 'success');
                } else {
                    btn.textContent = '🔴 Closed';
                    btn.className = 'text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors scale-press bg-red-100 text-red-700';
                    showToast('Store CLOSED', 'warning');
                }
            }
        });
    }
</script>
@endsection