<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Digital Queue')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        'primary-dark': '#4338CA',
                        'primary-light': '#818CF8',
                        surface: '#F8FAFC',
                        'text-main': '#1E293B',
                        'text-secondary': '#64748B',
                        'text-muted': '#94A3B8',
                        success: '#10B981',
                        warning: '#F59E0B',
                        danger: '#EF4444',
                        info: '#3B82F6',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                        'card-hover': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
                        'elevated': '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
                    },
                    borderRadius: { 'card': '16px' }
                }
            }
        }
    </script>
    <style>
        *{-webkit-tap-highlight-color:transparent}
        body{font-family:'Inter',system-ui,sans-serif;background:#F8FAFC;color:#1E293B;-webkit-font-smoothing:antialiased}
        .fade-in{animation:fadeIn .4s ease-out}
        @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .slide-up{animation:slideUp .5s ease-out}
        @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        .pulse-dot{animation:pulseDot 2s ease-in-out infinite}
        @keyframes pulseDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
        .scale-press{transition:transform .15s ease}
        .scale-press:active{transform:scale(.97)}
        .glass{backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)}
        .number-glow{text-shadow:0 0 40px rgba(79,70,229,.15)}
        input:focus,select:focus{outline:none}
        ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:10px}
        .toast-container{position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:99999;display:flex;flex-direction:column;align-items:center;gap:8px;width:90%;max-width:400px;pointer-events:none}
        .toast{width:100%;padding:14px 20px;border-radius:14px;color:#fff;font-weight:600;font-size:14px;line-height:1.4;box-shadow:0 10px 40px -10px rgba(0,0,0,.3);animation:toastIn .35s ease-out;pointer-events:auto;cursor:pointer;text-align:center}
        .toast-success{background:linear-gradient(135deg,#10B981,#059669)}
        .toast-error{background:linear-gradient(135deg,#EF4444,#DC2626)}
        .toast-warning{background:linear-gradient(135deg,#F59E0B,#D97706)}
        .toast-info{background:linear-gradient(135deg,#3B82F6,#2563EB)}
        @keyframes toastIn{from{opacity:0;transform:translateY(-20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
        @keyframes toastOut{from{opacity:1;transform:translateY(0) scale(1)}to{opacity:0;transform:translateY(-20px) scale(.95)}}
    </style>
    @yield('styles')
</head>
<body class="min-h-screen bg-surface">
    <div class="toast-container" id="toastContainer"></div>
    @yield('content')
    <script src="/js/app.js"></script>
    @yield('scripts')
</body>
</html>