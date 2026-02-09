@extends('layouts.app')

@section('title', 'Queue Display Board')

@section('styles')
<style>
    body { background: #0F172A; }
    .display-glow { text-shadow: 0 0 60px rgba(79,70,229,0.3); }
    .row-animate { animation: rowFade 0.5s ease-out; }
    @keyframes rowFade { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
</style>
@endsection

@section('content')
<div class="min-h-screen bg-gray-950 text-white p-6 md:p-10">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight">Digital Queue</h1>
            <p class="text-sm text-gray-400 font-medium" id="displayTime"></p>
        </div>
        <div class="flex items-center gap-2">
            <div class="pulse-dot w-3 h-3 rounded-full bg-green-400"></div>
            <span class="text-sm font-semibold text-green-400">LIVE</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- NOW SERVING - Big -->
        <div class="lg:col-span-1">
            <div class="bg-gradient-to-br from-primary to-primary-dark rounded-3xl p-8 md:p-12 text-center h-full flex flex-col justify-center">
                <p class="text-sm md:text-base font-bold uppercase tracking-[0.2em] text-white/50 mb-4">Now Serving</p>
                <p id="displayServing" class="text-8xl md:text-[10rem] font-black tracking-tight display-glow leading-none" style="transition:all .5s ease">---</p>
                <div id="displayServingType" class="mt-4"></div>
            </div>
        </div>

        <!-- WAITING LIST -->
        <div class="lg:col-span-2">
            <div class="bg-gray-900/50 rounded-3xl p-6 md:p-8 border border-gray-800 h-full">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl md:text-2xl font-bold">Waiting Queue</h2>
                    <span id="displayWaitCount" class="text-sm font-bold text-gray-400 bg-gray-800 px-3 py-1 rounded-full">0</span>
                </div>
                <div id="displayWaitList" class="space-y-2 max-h-[50vh] overflow-hidden">
                    <p class="text-gray-500 text-center py-10">No tickets waiting</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="mt-6 grid grid-cols-4 gap-4">
        <div class="bg-gray-900/50 rounded-2xl p-4 text-center border border-gray-800">
            <p id="dStatWaiting" class="text-3xl font-bold text-primary">0</p>
            <p class="text-xs text-gray-500 font-medium mt-1">Waiting</p>
        </div>
        <div class="bg-gray-900/50 rounded-2xl p-4 text-center border border-gray-800">
            <p id="dStatReserved" class="text-3xl font-bold text-blue-400">0</p>
            <p class="text-xs text-gray-500 font-medium mt-1">Reserved</p>
        </div>
        <div class="bg-gray-900/50 rounded-2xl p-4 text-center border border-gray-800">
            <p id="dStatCompleted" class="text-3xl font-bold text-green-400">0</p>
            <p class="text-xs text-gray-500 font-medium mt-1">Completed</p>
        </div>
        <div class="bg-gray-900/50 rounded-2xl p-4 text-center border border-gray-800">
            <p id="dStatRecent" class="text-3xl font-bold text-gray-400">-</p>
            <p class="text-xs text-gray-500 font-medium mt-1">Last Called</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateDisplayTime() {
    var now = new Date();
    var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var h = now.getHours(), ampm = h>=12?'PM':'AM'; h = h%12||12;
    document.getElementById('displayTime').textContent = days[now.getDay()]+', '+now.toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})+' • '+h+':'+String(now.getMinutes()).padStart(2,'0')+' '+ampm;
}
updateDisplayTime(); setInterval(updateDisplayTime, 30000);

var prevServing = '';
function refreshDisplay() {
    fetch('/api/live-status',{headers:{'Accept':'application/json'}}).then(function(r){return r.json()}).then(function(d){
        if(!d.success) return;
        var s = d.serving ? d.serving.ticket_number : '---';
        var el = document.getElementById('displayServing');
        if (s !== prevServing) {
            el.style.opacity='0'; el.style.transform='scale(0.7)';
            setTimeout(function(){ el.textContent=s; el.style.opacity='1'; el.style.transform='scale(1)'; }, 300);
            prevServing = s;
            if (d.serving) try{DQ.playNotification()}catch(e){}
        }
        var stEl = document.getElementById('displayServingType');
        if (d.serving) {
            var isO = d.serving.type==='online';
            stEl.innerHTML = '<span class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-full '+(isO?'bg-blue-500/30 text-blue-200':'bg-green-500/30 text-green-200')+'">'+(isO?'🌐 Online':'🚶 Walk-in')+'</span>';
        } else { stEl.innerHTML = ''; }

        document.getElementById('displayWaitCount').textContent = d.waiting_count + ' tickets';
        document.getElementById('dStatWaiting').textContent = d.waiting_count;
        document.getElementById('dStatReserved').textContent = d.reserved_count;
        document.getElementById('dStatCompleted').textContent = d.completed_count;
        document.getElementById('dStatRecent').textContent = d.recent_served && d.recent_served.length ? d.recent_served[0].ticket_number : '-';

        var wl = document.getElementById('displayWaitList');
        if (!d.waiting_list || !d.waiting_list.length) {
            wl.innerHTML = '<p class="text-gray-500 text-center py-10 text-lg">No tickets waiting</p>';
        } else {
            var h = '';
            d.waiting_list.forEach(function(t, i) {
                var isO = t.type === 'online';
                var bg = isO ? 'border-blue-500/20 bg-blue-500/5' : 'border-green-500/20 bg-green-500/5';
                var tc = isO ? 'text-blue-400' : 'text-green-400';
                h += '<div class="flex items-center justify-between p-4 rounded-xl border '+bg+' row-animate" style="animation-delay:'+(i*50)+'ms">' +
                    '<div class="flex items-center gap-4">' +
                    '<span class="text-xs font-bold text-gray-500 w-6">#'+(i+1)+'</span>' +
                    '<span class="text-2xl md:text-3xl font-black text-white">'+t.ticket_number+'</span></div>' +
                    '<span class="text-xs font-bold px-3 py-1 rounded-full '+tc+' bg-gray-800">'+(isO?'Online':'Walk-in')+'</span></div>';
            });
            wl.innerHTML = h;
        }
    }).catch(function(){});
}
refreshDisplay();
setInterval(refreshDisplay, 3000);
</script>
@endsection