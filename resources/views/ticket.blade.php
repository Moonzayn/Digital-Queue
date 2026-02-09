@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number . ' - Digital Queue')

@section('content')
<div class="min-h-screen bg-surface">
    <header class="sticky top-0 z-50 bg-white/80 glass border-b border-gray-100">
        <div class="max-w-lg mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-text-main hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span class="text-sm font-semibold">Back</span>
            </a>
            <div class="flex items-center gap-2">
                <div class="pulse-dot w-2 h-2 rounded-full bg-success" id="liveDot"></div>
                <span class="text-xs font-medium text-success" id="liveLabel">Live</span>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6 space-y-5">
        <div class="bg-white rounded-card shadow-elevated overflow-hidden fade-in">
            <div class="bg-gradient-to-br from-primary to-primary-dark p-8 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <p class="text-xs font-semibold uppercase tracking-widest text-white/60 mb-3 relative">Your Queue Number</p>
                <p class="text-7xl font-black tracking-tight number-glow relative">{{ $ticket->ticket_number }}</p>
                @if($ticket->customer_name)
                    <p class="text-base text-white/80 mt-2 font-semibold relative">{{ $ticket->customer_name }}</p>
                @endif
                <div class="mt-3 relative">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full {{ $ticket->type === 'online' ? 'bg-blue-500/30 text-blue-100' : 'bg-green-500/30 text-green-100' }}">
                        {{ $ticket->type === 'online' ? '🌐 Online Booking' : '🚶 Walk-in' }}
                    </span>
                </div>
            </div>

            <div class="relative h-0">
                <div class="absolute left-0 top-0 -translate-y-1/2 w-5 h-10 bg-surface rounded-r-full"></div>
                <div class="absolute right-0 top-0 -translate-y-1/2 w-5 h-10 bg-surface rounded-l-full"></div>
                <div class="border-t-2 border-dashed border-gray-200 mx-8"></div>
            </div>

            <div class="p-6 pt-8 space-y-5">
                <div class="text-center">
                    <div id="statusBadge" class="status-transition inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold bg-amber-50 text-amber-700">
                        <div id="statusDot" class="w-2.5 h-2.5 rounded-full bg-amber-500 pulse-dot"></div>
                        <span id="statusText" class="capitalize">{{ $ticket->status }}</span>
                    </div>
                </div>

                <div class="text-center py-2">
                    <p class="text-sm text-text-muted font-medium" id="positionLabel">People ahead of you</p>
                    <p id="positionNumber" class="text-5xl font-black text-text-main mt-1">
                        @if($ticket->status==='serving') 🎉 @else {{ max(0,$ticket->position-1) }} @endif
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-surface rounded-xl p-4 text-center">
                        <p class="text-xs text-text-muted font-medium mb-1">Created</p>
                        <p class="text-sm font-bold text-text-main">{{ $ticket->created_at->format('h:i A') }}</p>
                    </div>
                    @if($ticket->scheduled_at)
                    <div class="bg-surface rounded-xl p-4 text-center">
                        <p class="text-xs text-text-muted font-medium mb-1">Scheduled</p>
                        <p class="text-sm font-bold text-text-main">{{ $ticket->scheduled_at->format('h:i A') }}</p>
                    </div>
                    @else
                    <div class="bg-surface rounded-xl p-4 text-center">
                        <p class="text-xs text-text-muted font-medium mb-1">Type</p>
                        <p class="text-sm font-bold text-text-main capitalize">{{ $ticket->type }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-surface rounded-xl p-4 text-center">
                    <p class="text-xs text-text-muted font-medium mb-1">Currently Serving</p>
                    <p id="currentlyServing" class="text-3xl font-bold text-primary">---</p>
                    <p id="currentlyServingName" class="text-xs text-text-muted mt-0.5"></p>
                </div>

                <div id="cancelSection">
                    @if(in_array($ticket->status, ['waiting', 'reserved']))
                    <button onclick="cancelThisTicket()" class="w-full bg-red-50 hover:bg-red-100 text-danger font-semibold py-3.5 rounded-xl transition-all text-sm scale-press border border-red-100">Cancel Ticket</button>
                    @endif
                </div>

                <div class="text-center">
                    <p class="text-xs text-text-muted">🔊 Sound notification when it's your turn</p>
                </div>
            </div>
        </div>

        <div class="text-center pb-4">
            <p class="text-xs text-text-muted">Ticket ID: <span class="font-mono font-bold text-text-secondary">{{ strtoupper(substr($ticket->unique_code,0,8)) }}</span></p>
        </div>
    </main>
</div>
@endsection

@section('scripts')
<script>
    var CSRF=document.querySelector('meta[name="csrf-token"]').content;
    var UNIQUE_CODE='{{ $ticket->unique_code }}';
    var lastStatus='{{ $ticket->status }}';

    document.addEventListener('DOMContentLoaded',function(){
        updateStatusUI(lastStatus);
        startTicketPolling();
    });

    function updateStatusUI(status){
        var badge=document.getElementById('statusBadge'),dot=document.getElementById('statusDot'),text=document.getElementById('statusText'),posLabel=document.getElementById('positionLabel'),cancel=document.getElementById('cancelSection');
        text.textContent=status;
        badge.className='status-transition inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold';
        switch(status){
            case 'waiting':badge.className+=' bg-amber-50 text-amber-700';dot.className='w-2.5 h-2.5 rounded-full bg-amber-500 pulse-dot';posLabel.textContent='People ahead of you';cancel.innerHTML='<button onclick="cancelThisTicket()" class="w-full bg-red-50 hover:bg-red-100 text-danger font-semibold py-3.5 rounded-xl text-sm scale-press border border-red-100">Cancel Ticket</button>';break;
            case 'reserved':badge.className+=' bg-blue-50 text-blue-700';dot.className='w-2.5 h-2.5 rounded-full bg-blue-500 pulse-dot';posLabel.textContent='Reserved - waiting for activation';cancel.innerHTML='<button onclick="cancelThisTicket()" class="w-full bg-red-50 hover:bg-red-100 text-danger font-semibold py-3.5 rounded-xl text-sm scale-press border border-red-100">Cancel Ticket</button>';break;
            case 'serving':badge.className+=' bg-green-50 text-green-700';dot.className='w-2.5 h-2.5 rounded-full bg-green-500 pulse-dot';document.getElementById('positionNumber').textContent='🎉';posLabel.textContent="It's your turn!";cancel.innerHTML='';break;
            case 'completed':badge.className+=' bg-gray-100 text-gray-600';dot.className='w-2.5 h-2.5 rounded-full bg-gray-400';document.getElementById('positionNumber').textContent='✓';posLabel.textContent='Completed';cancel.innerHTML='';setInactive();break;
            case 'skipped':badge.className+=' bg-orange-50 text-orange-700';dot.className='w-2.5 h-2.5 rounded-full bg-orange-500';document.getElementById('positionNumber').textContent='⏭';posLabel.textContent='Skipped';cancel.innerHTML='';setInactive();break;
            case 'cancelled':badge.className+=' bg-red-50 text-red-600';dot.className='w-2.5 h-2.5 rounded-full bg-red-400';document.getElementById('positionNumber').textContent='✕';posLabel.textContent='Cancelled';cancel.innerHTML='';setInactive();break;
        }
    }

    function setInactive(){
        document.getElementById('liveDot').className='w-2 h-2 rounded-full bg-gray-400';
        document.getElementById('liveLabel').textContent='Ended';
        document.getElementById('liveLabel').className='text-xs font-medium text-gray-400';
    }

    function cancelThisTicket(){
        if(!confirm('Cancel this ticket?'))return;
        fetch('/api/ticket/'+UNIQUE_CODE+'/cancel',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(function(r){return r.json();}).then(function(d){
            if(d.success){localStorage.removeItem('dq_active_ticket');showToast('Cancelled.','info');updateStatusUI('cancelled');lastStatus='cancelled';}
            else showToast(d.message||'Failed','error');
        }).catch(function(){showToast('Network error.','error');});
    }

    function startTicketPolling(){
        setInterval(function(){
            if(document.hidden)return;
            fetch('/api/ticket/'+UNIQUE_CODE,{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
                if(!d.success||!d.ticket)return;
                var ns=d.ticket.status;
                if(ns!==lastStatus){
                    updateStatusUI(ns);
                    // 🔊 SOUNDS
                    if(ns==='serving'&&lastStatus!=='serving'){
                        DQ.playCalledSound();
                        showToast('🎉 It\'s your turn! Proceed to counter.','success');
                        if(navigator.vibrate)navigator.vibrate([200,100,200,100,300]);
                    } else if(ns==='completed'&&lastStatus!=='completed'){
                        DQ.playCompletedSound();
                        showToast('Visit complete! Thank you. ✓','success');
                        localStorage.removeItem('dq_active_ticket');
                    } else if(ns==='skipped'){
                        DQ.playSkipSound();
                        showToast('Your ticket was skipped.','warning');
                        localStorage.removeItem('dq_active_ticket');
                    } else if(ns==='cancelled'&&lastStatus!=='cancelled'){
                        DQ.playSkipSound();
                        showToast('Cancelled.','info');
                        localStorage.removeItem('dq_active_ticket');
                    }
                    lastStatus=ns;
                }
                if(ns==='waiting'||ns==='reserved'){
                    document.getElementById('positionNumber').textContent=Math.max(0,d.ticket.position-1);
                }
            }).catch(function(){});

            fetch('/api/live-status',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
                if(d.success){
                    document.getElementById('currentlyServing').textContent=d.serving?d.serving.ticket_number:'---';
                    document.getElementById('currentlyServingName').textContent=d.serving&&d.serving.customer_name?d.serving.customer_name:'';
                }
            }).catch(function(){});
        },3000);
    }
</script>
@endsection