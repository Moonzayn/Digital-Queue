<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Code - {{ $settings['store_name'] ?? 'Digital Queue' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary:'#4F46E5','primary-dark':'#4338CA',surface:'#F8FAFC','text-main':'#1E293B','text-muted':'#94A3B8',success:'#10B981' }, fontFamily: { sans:['Inter','system-ui','sans-serif'] } } } }
    </script>
    <style>
        body { font-family:'Inter',system-ui,sans-serif; }
        .pulse-ring { animation: pulseRing 2s ease-in-out infinite; }
        @keyframes pulseRing {
            0%,100% { box-shadow: 0 0 0 0 rgba(79,70,229,0.3); }
            50% { box-shadow: 0 0 0 20px rgba(79,70,229,0); }
        }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from{opacity:0;transform:scale(0.95)} to{opacity:1;transform:scale(1)} }
    </style>
</head>
<body class="min-h-screen bg-white flex flex-col items-center justify-center p-6">
    <div class="text-center max-w-lg mx-auto fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-text-main tracking-tight">{{ $settings['store_name'] ?? 'Digital Queue' }}</h1>
            <p class="text-base sm:text-lg text-text-muted mt-2">Scan QR code to join the queue</p>
        </div>

        <!-- QR Code -->
        <div class="inline-block pulse-ring rounded-3xl">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border-2 border-gray-100 shadow-xl">
                <div id="qrCodeDisplay" class="w-64 h-64 sm:w-80 sm:h-80 flex items-center justify-center">
                    <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
                </div>
            </div>
        </div>

        <!-- Token -->
        <div class="mt-8">
            <p class="text-xs text-text-muted uppercase tracking-widest font-semibold mb-2">Or enter this code</p>
            <p id="qrTokenDisplay" class="text-4xl sm:text-5xl font-black text-primary tracking-[0.4em]">---</p>
        </div>

        <!-- Status Bar -->
        <div class="mt-8 flex items-center justify-center gap-6 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-success animate-pulse"></div>
                <span class="text-text-muted font-medium">Live</span>
            </div>
            <div class="text-text-muted">•</div>
            <div>
                <span class="text-text-muted font-medium">Now Serving: </span>
                <span id="nowServing" class="font-bold text-primary">---</span>
            </div>
            <div class="text-text-muted">•</div>
            <div>
                <span class="text-text-muted font-medium">Waiting: </span>
                <span id="waitingNum" class="font-bold text-text-main">0</span>
            </div>
        </div>

        <!-- Timer -->
        <div class="mt-6">
            <p class="text-xs text-text-muted">
                Token expires: <span id="expiresAt" class="font-semibold">--</span> •
                Auto-refreshes every 5 minutes
            </p>
        </div>

        <!-- Admin link -->
        <div class="mt-8">
            <a href="{{ route('admin.dashboard') }}" class="text-xs text-text-muted hover:text-primary transition-colors font-medium">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
        function loadQR(){
            fetch('/api/admin/qr-token',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
                if(!d.success)return;
                var c=document.getElementById('qrCodeDisplay');
                var qr=qrcode(0,'M');qr.addData(d.url);qr.make();
                var size=document.getElementById('qrCodeDisplay').offsetWidth||280;
                var cs=Math.floor(size/qr.getModuleCount());
                c.innerHTML=qr.createSvgTag(cs,0);
                var svg=c.querySelector('svg');if(svg){svg.style.width=size+'px';svg.style.height=size+'px';svg.style.display='block';}
                document.getElementById('qrTokenDisplay').textContent=d.token;
                document.getElementById('expiresAt').textContent=d.expires_at;
            }).catch(function(){});
        }

        function loadStatus(){
            fetch('/api/live-status',{headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
                if(!d.success)return;
                document.getElementById('nowServing').textContent=d.serving?d.serving.ticket_number:'---';
                document.getElementById('waitingNum').textContent=d.waiting_count;
            }).catch(function(){});
        }

        loadQR();
        loadStatus();
        setInterval(loadQR, 300000); // 5 min
        setInterval(loadStatus, 4000); // 4 sec
    </script>
</body>
</html>