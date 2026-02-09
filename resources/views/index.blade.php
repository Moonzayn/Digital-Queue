@extends('layouts.app')

@section('title', 'Digital Queue - Take Your Number')

@section('styles')
<style>
    .serving-pulse { animation: servingPulse 2s ease-in-out infinite; }
    @keyframes servingPulse { 0%,100%{box-shadow:0 0 0 0 rgba(79,70,229,0.15);} 50%{box-shadow:0 0 0 10px rgba(79,70,229,0);} }
    .you-badge { animation: youPop 0.6s ease-out; }
    @keyframes youPop { 0%{transform:scale(0);} 50%{transform:scale(1.2);} 100%{transform:scale(1);} }
    .mini-queue-item { animation: miniSlide 0.25s ease-out; }
    @keyframes miniSlide { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
    .glow-you { box-shadow: 0 0 0 2px rgba(79,70,229,0.3), 0 2px 8px rgba(79,70,229,0.15); }
</style>
@endsection

@section('content')
<div class="min-h-screen bg-surface">
    <header class="sticky top-0 z-50 bg-white/80 glass border-b border-gray-100">
        <div class="max-w-lg mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-text-main tracking-tight">Digital Queue</h1>
                <p class="text-xs text-text-muted font-medium">Smart Queue Management</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="pulse-dot w-2 h-2 rounded-full bg-success"></div>
                <span class="text-xs font-medium text-success">Live</span>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-4">
        <!-- Now Serving -->
        <div class="bg-white rounded-card shadow-card overflow-hidden fade-in serving-pulse">
            <div class="p-5">
                <div class="text-center">
                    <p class="text-[10px] font-semibold text-text-muted uppercase tracking-widest mb-1.5">Now Serving</p>
                    <div id="servingNumber" class="text-5xl font-black text-primary number-glow tracking-tight" style="transition:all 0.4s ease;">
                        {{ $currentServing ? $currentServing->ticket_number : '---' }}
                    </div>
                    <p id="servingName" class="text-sm text-text-secondary font-semibold mt-0.5" style="transition:all 0.3s ease;">
                        {{ $currentServing && $currentServing->customer_name ? $currentServing->customer_name : '' }}
                    </p>
                    <div id="servingTypeBadge" class="mt-2">
                        @if($currentServing)
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $currentServing->type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                {{ $currentServing->type === 'online' ? '🌐 Online' : '🚶 Walk-in' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 px-5 py-3">
                <div class="flex items-center justify-center gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-text-main" id="waitingCount">{{ $waitingCount }}</p>
                        <p class="text-[10px] text-text-muted font-medium">Waiting</p>
                    </div>
                    <div class="w-px h-8 bg-gray-200"></div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-text-main" id="reservedCount">{{ $reservedCount }}</p>
                        <p class="text-[10px] text-text-muted font-medium">Reserved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ACTIVE TICKET + MINI QUEUE (when has ticket) -->
        <!-- ========================================== -->
        <div id="activeTicketSection" class="hidden space-y-3">
            <!-- Your Ticket Compact -->
            <div id="activeTicketCard" class="bg-gradient-to-br from-primary to-primary-dark text-white rounded-card shadow-elevated overflow-hidden">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/60">Your Ticket</p>
                        <span id="activeTicketType" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/20"></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Number -->
                        <div class="flex-shrink-0">
                            <p id="activeTicketNumber" class="text-4xl font-black tracking-tight"></p>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p id="activeTicketName" class="text-sm font-semibold text-white/90 truncate"></p>
                            <p id="activeTicketStatus" class="text-xs text-white/70 capitalize mt-0.5"></p>
                            <p id="activeTicketPosition" class="text-xs text-white/50 mt-0.5"></p>
                        </div>
                        <!-- Actions -->
                        <div class="flex flex-col gap-1.5 flex-shrink-0">
                            <a id="activeTicketLink" href="#" class="bg-white/20 hover:bg-white/30 text-white text-center px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors">
                                Detail
                            </a>
                            <button onclick="cancelActiveTicket()" id="cancelActiveBtn" class="bg-white/10 hover:bg-red-500/40 text-white text-center px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mini Queue Card -->
            <div id="miniQueueCard" class="bg-white rounded-card shadow-card overflow-hidden">
                <!-- Header (toggleable) -->
                <button onclick="toggleMiniQueue()" class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <span class="text-xs font-bold text-text-main">Queue</span>
                        <span id="miniQueueBadge" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-primary/10 text-primary min-w-[20px] text-center">0</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="miniQueueYourPos" class="text-[10px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full hidden">You: #-</span>
                        <svg id="miniQueueArrow" class="w-3.5 h-3.5 text-text-muted transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <!-- Compact Queue List (default: collapsed) -->
                <div id="miniQueueBody" class="hidden border-t border-gray-50">
                    <!-- Now Serving Mini -->
                    <div id="miniServing" class="hidden px-4 py-2 bg-primary/5 flex items-center justify-between border-b border-primary/10">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded bg-primary flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span id="miniServingNum" class="text-xs font-bold text-primary">---</span>
                            <span id="miniServingName" class="text-[10px] text-primary/60 truncate max-w-[100px]"></span>
                        </div>
                        <span class="text-[9px] font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded">NOW</span>
                    </div>

                    <!-- Waiting Items -->
                    <div id="miniQueueList" class="max-h-48 overflow-y-auto">
                        <div class="px-4 py-4 text-center"><p class="text-[11px] text-text-muted">Loading...</p></div>
                    </div>

                    <!-- Show more / less -->
                    <div id="miniQueueMore" class="hidden border-t border-gray-50">
                        <button onclick="toggleShowAll()" id="miniQueueMoreBtn" class="w-full px-4 py-2 text-[11px] font-semibold text-primary hover:bg-primary/5 transition-colors text-center">
                            Show all
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ACTIONS (when no active ticket) -->
        <!-- ========================================== -->
        <div class="space-y-3 slide-up" id="actionButtons">
            <button onclick="openQRScanner()"
                class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-4 px-5 rounded-card shadow-card hover:shadow-card-hover transition-all scale-press flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div class="text-left"><p class="text-sm font-semibold">Walk-in (Scan QR)</p><p class="text-[11px] text-white/70">Scan QR code at the counter</p></div>
                </div>
                <svg class="w-4 h-4 text-white/40 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <button onclick="showTokenModal()"
                class="w-full bg-white hover:bg-gray-50 text-text-main font-semibold py-3 px-5 rounded-card shadow-card transition-all scale-press flex items-center justify-between group border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div class="text-left"><p class="text-xs font-semibold">Enter Token Manually</p><p class="text-[10px] text-text-muted">Can't scan? Type the code</p></div>
                </div>
                <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div class="flex items-center gap-3"><div class="flex-1 h-px bg-gray-200"></div><span class="text-[10px] font-semibold text-text-muted uppercase tracking-wider">or book ahead</span><div class="flex-1 h-px bg-gray-200"></div></div>

            <button onclick="showBookingModal()"
                class="w-full bg-white hover:bg-gray-50 text-text-main font-semibold py-4 px-5 rounded-card shadow-card transition-all scale-press flex items-center justify-between group border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-left"><p class="text-sm font-semibold">Book Online</p><p class="text-[11px] text-text-muted">Pick a time, see availability</p></div>
                </div>
                <svg class="w-4 h-4 text-text-muted group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        <div class="text-center py-3">
            <p class="text-[10px] text-text-muted">Auto-updates • 🔊 Sound when called</p>
        </div>
    </main>

    <!-- QR Scanner -->
    <div id="qrScannerModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60" onclick="closeQRScanner()"></div>
        <div class="absolute inset-x-0 top-0 bottom-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-elevated slide-up">
                <div class="p-4 text-center border-b border-gray-100"><h3 class="text-base font-bold text-text-main">Scan QR Code</h3><p class="text-[11px] text-text-muted mt-0.5">Point camera at QR on counter</p></div>
                <div class="relative bg-black" style="aspect-ratio:1;min-height:260px;"><div id="qr-reader" style="width:100%;"></div></div>
                <div class="p-4 space-y-2">
                    <button onclick="showTokenModal();closeQRScanner();" class="w-full bg-gray-100 hover:bg-gray-200 text-text-main font-semibold py-2.5 rounded-xl text-xs">Enter Token Manually</button>
                    <button onclick="closeQRScanner()" class="w-full text-text-muted hover:text-text-main font-medium py-2 text-xs">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Token Modal -->
    <div id="tokenModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 glass" onclick="hideTokenModal()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 pb-8 max-w-lg mx-auto slide-up">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-5"></div>
            <h3 class="text-base font-bold text-text-main mb-1">Walk-in Queue</h3>
            <p class="text-xs text-text-muted mb-4">Enter name and token from counter</p>
            <div class="space-y-3">
                <div><label class="block text-xs font-semibold text-text-main mb-1">Your Name</label><input type="text" id="tokenNameInput" maxlength="100" autocomplete="name" placeholder="e.g. John" class="w-full px-4 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all placeholder:font-normal placeholder:text-text-muted text-sm"></div>
                <div><label class="block text-xs font-semibold text-text-main mb-1">Token</label><input type="text" id="tokenInput" maxlength="8" autocomplete="off" placeholder="AB12CD34" class="w-full px-4 py-3 bg-surface border-2 border-gray-200 rounded-xl text-center text-lg font-bold text-text-main tracking-[0.3em] uppercase focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all placeholder:text-text-muted placeholder:text-sm placeholder:tracking-normal placeholder:font-normal placeholder:normal-case"></div>
                <button onclick="submitWalkInToken()" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3.5 rounded-xl transition-all scale-press text-sm">Join Queue</button>
                <button onclick="hideTokenModal()" class="w-full bg-gray-100 text-text-main font-semibold py-2.5 rounded-xl text-xs">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 glass" onclick="hideBookingModal()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-w-lg mx-auto slide-up" style="max-height:90vh;display:flex;flex-direction:column;">
            <div class="flex-shrink-0 px-6 pt-5 pb-3 border-b border-gray-50">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="text-base font-bold text-text-main mb-0.5">Book Your Time</h3>
                <p class="text-xs text-text-muted">Fill name, pick a slot</p>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                <div><label class="block text-xs font-semibold text-text-main mb-1">Your Name</label><input type="text" id="bookingNameInput" maxlength="100" autocomplete="name" placeholder="e.g. John" class="w-full px-4 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all placeholder:font-normal placeholder:text-text-muted text-sm"></div>
                <div><label class="block text-xs font-semibold text-text-main mb-1">Select Time</label><input type="time" id="bookingTimeInput" class="w-full px-4 py-3 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-bold text-lg focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" onchange="onTimeSelected()"><p class="text-[10px] text-text-muted mt-1">{{ $settings['open_hour'] ?? '08:00' }} - {{ $settings['close_hour'] ?? '17:00' }} • Min 30 min</p></div>
                <div id="slotInfoCard" class="hidden"><div id="slotInfoContent" class="bg-surface border-2 border-gray-200 rounded-xl p-3"></div></div>
                <div>
                    <div class="flex items-center justify-between mb-2"><p class="text-xs font-semibold text-text-main">Availability</p>
                        <div class="flex items-center gap-2 text-[9px] font-medium text-text-muted"><span class="flex items-center gap-0.5"><span class="w-1.5 h-1.5 rounded-full bg-success"></span>Open</span><span class="flex items-center gap-0.5"><span class="w-1.5 h-1.5 rounded-full bg-warning"></span>Filling</span><span class="flex items-center gap-0.5"><span class="w-1.5 h-1.5 rounded-full bg-danger"></span>Full</span></div>
                    </div>
                    <div id="allSlotsGrid" class="grid grid-cols-4 gap-1.5"><div class="col-span-4 py-6 text-center"><div class="w-6 h-6 border-2 border-primary/20 border-t-primary rounded-full animate-spin mx-auto"></div></div></div>
                </div>
            </div>
            <div class="flex-shrink-0 border-t border-gray-100 px-6 py-3 space-y-2 bg-white">
                <button onclick="handleBookOnline()" id="confirmBookBtn" disabled class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3.5 rounded-xl transition-all scale-press text-sm disabled:opacity-40">Select a Time</button>
                <button onclick="hideBookingModal()" class="w-full bg-gray-100 text-text-main font-semibold py-2.5 rounded-xl text-xs">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingOverlay" class="fixed inset-0 z-[60] hidden items-center justify-center bg-white/80 glass">
        <div class="text-center"><div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin mx-auto"></div><p class="mt-3 text-xs font-medium text-text-muted">Processing...</p></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var CSRF=document.querySelector('meta[name="csrf-token"]').content;
var isProcessing=false, html5QrCode=null, selectedTime=null, allSlotsData=[];
var lastTicketStatus=null, myTicketCode=null, miniQueueOpen=false, showAllQueue=false;
var MINI_QUEUE_LIMIT=5;

document.addEventListener('DOMContentLoaded',function(){
    checkActiveTicket();
    startLivePolling();
    setDefaultBookingTime();
});

function setDefaultBookingTime(){
    var n=new Date();n.setMinutes(n.getMinutes()+35);
    var h=String(n.getHours()).padStart(2,'0'),m=Math.ceil(n.getMinutes()/15)*15;
    if(m>=60){h=String(parseInt(h)+1).padStart(2,'0');m=0;}
    document.getElementById('bookingTimeInput').value=h+':'+String(m).padStart(2,'0');
}

// ==========================================
// ACTIVE TICKET
// ==========================================
function checkActiveTicket(){
    var s=localStorage.getItem('dq_active_ticket');
    if(!s){showActions();return;}
    var t;try{t=JSON.parse(s);}catch(e){localStorage.removeItem('dq_active_ticket');showActions();return;}
    if(!t||!t.unique_code){localStorage.removeItem('dq_active_ticket');showActions();return;}
    myTicketCode=t.unique_code;lastTicketStatus=t.status;
    fetch('/api/ticket/'+t.unique_code,{headers:{'Accept':'application/json'}})
    .then(function(r){return r.json();}).then(function(d){
        if(d.success&&d.ticket&&['waiting','reserved','serving'].includes(d.ticket.status)){
            localStorage.setItem('dq_active_ticket',JSON.stringify(d.ticket));
            myTicketCode=d.ticket.unique_code;lastTicketStatus=d.ticket.status;
            showTicketUI(d.ticket);
        } else {localStorage.removeItem('dq_active_ticket');myTicketCode=null;showActions();}
    }).catch(function(){showActions();});
}

function showTicketUI(t){
    document.getElementById('actionButtons').classList.add('hidden');
    document.getElementById('activeTicketSection').classList.remove('hidden');
    document.getElementById('activeTicketNumber').textContent=t.ticket_number;
    document.getElementById('activeTicketName').textContent=t.customer_name||'';
    document.getElementById('activeTicketType').textContent=t.type==='walk-in'?'🚶 Walk-in':'🌐 Online';
    document.getElementById('activeTicketLink').href='/ticket/'+t.unique_code;

    var statusText=t.status==='serving'?'🎉 Sekarang Giliran Kamu!':t.status.charAt(0).toUpperCase()+t.status.slice(1);
    document.getElementById('activeTicketStatus').textContent=statusText;

    var pos=document.getElementById('activeTicketPosition');
    if(t.status==='serving') pos.textContent='Proceed to counter';
    else if(t.status==='reserved') pos.textContent='Scheduled: '+(t.scheduled_at||'');
    else{var a=t.position>1?t.position-1:0;pos.textContent=a===0?'You\'re next!':a+' ahead of you';}
    document.getElementById('cancelActiveBtn').classList.toggle('hidden',t.status==='serving');

    // Glow effect when serving
    var card=document.getElementById('activeTicketCard');
    if(t.status==='serving'){
        card.classList.add('ring-2','ring-green-400','ring-offset-2');
    } else {
        card.classList.remove('ring-2','ring-green-400','ring-offset-2');
    }
}

function showActions(){
    document.getElementById('actionButtons').classList.remove('hidden');
    document.getElementById('activeTicketSection').classList.add('hidden');
}

function showLoading(){var e=document.getElementById('loadingOverlay');e.classList.remove('hidden');e.classList.add('flex');}
function hideLoading(){var e=document.getElementById('loadingOverlay');e.classList.add('hidden');e.classList.remove('flex');}

// ==========================================
// MINI QUEUE
// ==========================================
function toggleMiniQueue(){
    miniQueueOpen=!miniQueueOpen;
    document.getElementById('miniQueueBody').classList.toggle('hidden',!miniQueueOpen);
    document.getElementById('miniQueueArrow').style.transform=miniQueueOpen?'rotate(180deg)':'';
}

function toggleShowAll(){
    showAllQueue=!showAllQueue;
    renderMiniQueue(window._lastQueueData||{});
}

function renderMiniQueue(data){
    window._lastQueueData=data;
    var total=(data.serving?1:0)+(data.waiting?data.waiting.length:0);
    document.getElementById('miniQueueBadge').textContent=total;

    // Your position badge
    var posEl=document.getElementById('miniQueueYourPos');
    if(myTicketCode&&data.waiting){
        var myPos=-1;
        for(var i=0;i<data.waiting.length;i++){
            if(data.waiting[i].unique_code===myTicketCode){myPos=i+1;break;}
        }
        if(data.serving&&data.serving.unique_code===myTicketCode){
            posEl.textContent='NOW';posEl.classList.remove('hidden');
        } else if(myPos>0){
            posEl.textContent='#'+myPos;posEl.classList.remove('hidden');
        } else posEl.classList.add('hidden');
    } else posEl.classList.add('hidden');

    // Serving
    var servEl=document.getElementById('miniServing');
    if(data.serving){
        servEl.classList.remove('hidden');
        document.getElementById('miniServingNum').textContent=data.serving.ticket_number;
        document.getElementById('miniServingName').textContent=data.serving.customer_name||'';
    } else servEl.classList.add('hidden');

    // Waiting list
    var listEl=document.getElementById('miniQueueList');
    var moreEl=document.getElementById('miniQueueMore');
    var moreBtn=document.getElementById('miniQueueMoreBtn');

    if(!data.waiting||data.waiting.length===0){
        listEl.innerHTML='<div class="px-4 py-4 text-center"><p class="text-[11px] text-text-muted">No one waiting</p></div>';
        moreEl.classList.add('hidden');
        return;
    }

    var items=data.waiting;
    var showing=showAllQueue?items.length:Math.min(items.length,MINI_QUEUE_LIMIT);
    var hasMore=items.length>MINI_QUEUE_LIMIT;

    var html='';
    for(var i=0;i<showing;i++){
        var w=items[i];
        var isMe=myTicketCode&&w.unique_code===myTicketCode;
        var rowCls=isMe?'bg-primary/5 glow-you':'';
        var tO=w.type==='online';

        html+='<div class="px-4 py-2 flex items-center justify-between '+rowCls+' mini-queue-item">';
        html+='<div class="flex items-center gap-2.5 min-w-0">';
        // Position circle
        html+='<div class="w-6 h-6 rounded-full '+(isMe?'bg-primary text-white':'bg-gray-100 text-text-muted')+' flex items-center justify-center flex-shrink-0"><span class="text-[10px] font-bold">'+(i+1)+'</span></div>';
        // Info
        html+='<div class="min-w-0">';
        html+='<div class="flex items-center gap-1.5">';
        html+='<span class="text-xs font-bold text-text-main">'+w.ticket_number+'</span>';
        if(isMe) html+='<span class="you-badge text-[8px] font-black px-1 py-px rounded bg-primary text-white leading-tight">YOU</span>';
        html+='</div>';
        if(w.customer_name) html+='<p class="text-[10px] text-text-muted truncate max-w-[120px]">'+w.customer_name+'</p>';
        html+='</div></div>';
        // Right side
        html+='<div class="flex items-center gap-1.5 flex-shrink-0">';
        if(tO&&w.scheduled_at) html+='<span class="text-[9px] text-text-muted">'+w.scheduled_at+'</span>';
        html+='<span class="w-1.5 h-1.5 rounded-full '+(tO?'bg-blue-400':'bg-green-400')+'"></span>';
        html+='</div></div>';
    }
    listEl.innerHTML=html;

    // Show more button
    if(hasMore){
        moreEl.classList.remove('hidden');
        if(showAllQueue){
            moreBtn.textContent='Show less ↑';
        } else {
            moreBtn.textContent='Show all '+(items.length)+' →';
        }
    } else moreEl.classList.add('hidden');
}

// ==========================================
// QR SCANNER
// ==========================================
function openQRScanner(){document.getElementById('qrScannerModal').classList.remove('hidden');setTimeout(startCamera,300);}
function closeQRScanner(){document.getElementById('qrScannerModal').classList.add('hidden');stopCamera();}
function startCamera(){stopCamera();html5QrCode=new Html5Qrcode("qr-reader");html5QrCode.start({facingMode:"environment"},{fps:10,qrbox:{width:200,height:200}},function(d){stopCamera();closeQRScanner();var m=d.match(/\/walk-in\/([A-Za-z0-9]+)/);if(m&&m[1])window.location.href='/walk-in/'+m[1];else showToast('Invalid QR.','error');},function(){}).catch(function(){showToast('Camera denied.','warning');});}
function stopCamera(){if(html5QrCode){try{html5QrCode.stop().catch(function(){});}catch(e){}html5QrCode=null;}}

// ==========================================
// TOKEN
// ==========================================
function showTokenModal(){document.getElementById('tokenModal').classList.remove('hidden');setTimeout(function(){document.getElementById('tokenNameInput').focus();},300);}
function hideTokenModal(){document.getElementById('tokenModal').classList.add('hidden');}
function submitWalkInToken(){
    var name=document.getElementById('tokenNameInput').value.trim(),token=document.getElementById('tokenInput').value.trim().toUpperCase();
    if(!name||name.length<2){showToast('Enter your name.','warning');return;}
    if(!token||token.length<4){showToast('Enter valid token.','warning');return;}
    hideTokenModal();processWalkIn(token,name);
}
function processWalkIn(token,name){
    if(isProcessing)return;isProcessing=true;showLoading();
    fetch('/queue/walk-in',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({qr_token:token,customer_name:name})})
    .then(function(r){return r.json();}).then(function(d){
        hideLoading();isProcessing=false;
        if(d.success){localStorage.setItem('dq_active_ticket',JSON.stringify(d.ticket));myTicketCode=d.ticket.unique_code;lastTicketStatus=d.ticket.status;showToast('Ticket: '+d.ticket.ticket_number,'success');showTicketUI(d.ticket);document.getElementById('tokenInput').value='';document.getElementById('tokenNameInput').value='';}
        else showToast(d.message||'Failed','warning');
    }).catch(function(){hideLoading();isProcessing=false;showToast('Network error.','error');});
}

// ==========================================
// BOOKING
// ==========================================
function showBookingModal(){selectedTime=null;document.getElementById('confirmBookBtn').disabled=true;document.getElementById('confirmBookBtn').textContent='Select a Time';document.getElementById('slotInfoCard').classList.add('hidden');document.getElementById('bookingModal').classList.remove('hidden');setDefaultBookingTime();loadAllSlots();}
function hideBookingModal(){document.getElementById('bookingModal').classList.add('hidden');}

function loadAllSlots(){
    var g=document.getElementById('allSlotsGrid');
    g.innerHTML='<div class="col-span-4 py-5 text-center"><div class="w-6 h-6 border-2 border-primary/20 border-t-primary rounded-full animate-spin mx-auto"></div></div>';
    fetch('/api/time-slots',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
        if(!d.success)return;allSlotsData=d.slots;renderSlotGrid();
        if(document.getElementById('bookingTimeInput').value)onTimeSelected();
    }).catch(function(){});
}

function renderSlotGrid(){
    var g=document.getElementById('allSlotsGrid'),h='';
    for(var i=0;i<allSlotsData.length;i++){var s=allSlotsData[i],c,l;
        if(s.is_past){c='bg-gray-50 text-gray-300 cursor-not-allowed';l='Past';}
        else if(s.is_full){c='bg-red-50 text-red-400 border border-red-200 cursor-not-allowed';l='Full';}
        else if(s.available<=1){c='bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer hover:border-amber-400';l=s.available+' left';}
        else{c='bg-green-50 text-green-700 border border-green-200 cursor-pointer hover:border-green-400';l=s.available+' left';}
        var ck=(!s.is_past&&!s.is_full)?' onclick="pickSlot(\''+s.value+'\')"':'';
        var hl=(selectedTime===s.value)?' ring-2 ring-primary ring-offset-1':'';
        h+='<div class="rounded-lg p-1.5 text-center transition-all '+c+hl+'"'+ck+'><p class="text-[10px] font-bold leading-tight">'+s.time+'</p><p class="text-[8px] mt-px">'+l+'</p></div>';
    }
    g.innerHTML=h;
}

function pickSlot(v){document.getElementById('bookingTimeInput').value=v;onTimeSelected();}

function onTimeSelected(){
    var tv=document.getElementById('bookingTimeInput').value;if(!tv)return;
    var p=tv.split(':'),al=Math.floor(parseInt(p[1])/15)*15;
    selectedTime=String(parseInt(p[0])).padStart(2,'0')+':'+String(al).padStart(2,'0');
    var ms=null;for(var i=0;i<allSlotsData.length;i++){if(allSlotsData[i].value===selectedTime){ms=allSlotsData[i];break;}}
    var ic=document.getElementById('slotInfoCard'),co=document.getElementById('slotInfoContent'),btn=document.getElementById('confirmBookBtn');
    ic.classList.remove('hidden');
    if(!ms){co.className='bg-red-50 border-2 border-red-200 rounded-xl p-3';co.innerHTML='<p class="text-xs font-semibold text-red-700">Outside hours</p>';btn.disabled=true;btn.textContent='Not Available';renderSlotGrid();return;}
    if(ms.is_past){co.className='bg-gray-100 border-2 border-gray-200 rounded-xl p-3';co.innerHTML='<p class="text-xs font-semibold text-gray-600">'+ms.time+' — Too soon</p>';btn.disabled=true;btn.textContent='Too Soon';renderSlotGrid();return;}
    if(ms.is_full){co.className='bg-red-50 border-2 border-red-200 rounded-xl p-3';co.innerHTML='<p class="text-xs font-semibold text-red-700">'+ms.time+' — Full</p>';btn.disabled=true;btn.textContent='Full';renderSlotGrid();return;}
    var tc=ms.available<=1?'text-amber-700':'text-green-700',bc=ms.available<=1?'border-amber-200 bg-amber-50':'border-green-200 bg-green-50',bar=ms.available<=1?'bg-amber-500':'bg-green-500',pct=Math.round((ms.booked/ms.capacity)*100);
    var dots='';for(var j=0;j<ms.capacity;j++){dots+='<div class="w-2.5 h-2.5 rounded-full '+(j<ms.booked?'bg-red-400':'bg-green-400')+'"></div>';}
    co.className=bc+' border-2 rounded-xl p-3';
    co.innerHTML='<div class="flex items-center justify-between mb-2"><div><p class="text-xs font-bold '+tc+'">'+ms.time+'</p><p class="text-[10px] text-text-muted">'+ms.available+'/'+ms.capacity+' open</p></div><p class="text-xl font-black '+tc+'">'+ms.available+'</p></div><div class="w-full bg-gray-200 rounded-full h-1.5 mb-1.5"><div class="'+bar+' h-1.5 rounded-full" style="width:'+pct+'%"></div></div><div class="flex justify-center gap-1">'+dots+'</div>';
    btn.disabled=false;btn.textContent='Book — '+ms.time;renderSlotGrid();
}

function handleBookOnline(){
    if(isProcessing||!selectedTime)return;
    var name=document.getElementById('bookingNameInput').value.trim();
    if(!name||name.length<2){showToast('Enter your name.','warning');document.getElementById('bookingNameInput').focus();return;}
    isProcessing=true;hideBookingModal();showLoading();
    fetch('/queue/book-online',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({scheduled_time:selectedTime,customer_name:name})})
    .then(function(r){return r.json();}).then(function(d){
        hideLoading();isProcessing=false;
        if(d.success){localStorage.setItem('dq_active_ticket',JSON.stringify(d.ticket));myTicketCode=d.ticket.unique_code;lastTicketStatus=d.ticket.status;showToast('Booking confirmed!','success');showTicketUI(d.ticket);selectedTime=null;}
        else showToast(d.message||'Failed','warning');
    }).catch(function(){hideLoading();isProcessing=false;showToast('Network error.','error');});
}

// ==========================================
// CANCEL
// ==========================================
function cancelActiveTicket(){
    var s=localStorage.getItem('dq_active_ticket');if(!s)return;
    if(!confirm('Cancel your ticket?'))return;
    var t;try{t=JSON.parse(s);}catch(e){localStorage.removeItem('dq_active_ticket');showActions();return;}
    showLoading();
    fetch('/api/ticket/'+t.unique_code+'/cancel',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(function(r){return r.json();}).then(function(d){
        hideLoading();if(d.success){localStorage.removeItem('dq_active_ticket');myTicketCode=null;lastTicketStatus=null;showToast('Cancelled.','info');showActions();}
        else showToast(d.message,'error');
    }).catch(function(){hideLoading();showToast('Network error.','error');});
}

// ==========================================
// POLLING + SOUNDS
// ==========================================
function startLivePolling(){
    // First fetch
    fetchAll();
    setInterval(function(){if(!document.hidden)fetchAll();},4000);
}

function fetchAll(){
    // Live status
    fetch('/api/live-status',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
        if(!d.success)return;
        var el=document.getElementById('servingNumber'),n=d.serving?d.serving.ticket_number:'---';
        if(el.textContent.trim()!==n){el.style.opacity='0';el.style.transform='scale(0.8)';setTimeout(function(){el.textContent=n;el.style.opacity='1';el.style.transform='scale(1)';},250);}
        document.getElementById('servingName').textContent=d.serving&&d.serving.customer_name?d.serving.customer_name:'';
        var badge=document.getElementById('servingTypeBadge');
        if(d.serving){var o=d.serving.type==='online';badge.innerHTML='<span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full '+(o?'bg-blue-100 text-blue-700':'bg-green-100 text-green-700')+'">'+(o?'🌐 Online':'🚶 Walk-in')+'</span>';}else badge.innerHTML='';
        document.getElementById('waitingCount').textContent=d.waiting_count;
        document.getElementById('reservedCount').textContent=d.reserved_count;
    }).catch(function(){});

        // Queue data (for mini queue) - PUBLIC endpoint
    fetch('/api/queue-list',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
        if(d.success) renderMiniQueue(d);
    }).catch(function(){});

    // Active ticket check
    var s=localStorage.getItem('dq_active_ticket');
    if(s){
        var t;try{t=JSON.parse(s);}catch(e){return;}
        if(!t||!t.unique_code)return;
        fetch('/api/ticket/'+t.unique_code,{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
            if(!d.success||!d.ticket)return;
            var ns=d.ticket.status;
            if(['waiting','reserved','serving'].includes(ns)){
                localStorage.setItem('dq_active_ticket',JSON.stringify(d.ticket));
                showTicketUI(d.ticket);
                if(ns==='serving'&&lastTicketStatus!=='serving'){
                    DQ.playCalledSound();showToast('🎉 Your turn! Go to counter.','success');
                    if(navigator.vibrate)navigator.vibrate([200,100,200,100,300]);
                }
            } else {
                localStorage.removeItem('dq_active_ticket');myTicketCode=null;showActions();
                if(ns==='completed'&&lastTicketStatus!=='completed'){DQ.playCompletedSound();showToast('Complete! Thank you. ✓','success');}
                else if(ns==='skipped'){DQ.playSkipSound();showToast('Ticket skipped.','warning');}
                else if(ns==='cancelled'&&lastTicketStatus!=='cancelled'){DQ.playSkipSound();showToast('Ticket cancelled.','info');}
            }
            lastTicketStatus=ns;
        }).catch(function(){});
    }
}
</script>
@endsection