@extends('layouts.app')

@section('title', 'Join Queue - Walk-in')

@section('content')
<div class="min-h-screen bg-surface flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- Join Form -->
        <div id="joinSection" class="bg-white rounded-card shadow-elevated p-8 fade-in">
            <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-text-main text-center mb-2">QR Code Verified!</h1>
            <p class="text-sm text-text-muted text-center mb-6">Enter your name to get a queue number</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-text-main mb-2">Your Name</label>
                    <input type="text" id="scanNameInput" maxlength="100" autocomplete="name" placeholder="e.g. John Doe"
                        class="w-full px-4 py-4 bg-surface border-2 border-gray-200 rounded-xl text-text-main font-semibold text-lg focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all placeholder:text-text-muted placeholder:font-normal placeholder:text-base"
                        autofocus>
                </div>
                <button onclick="joinQueue()" id="joinBtn"
                    class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-4 rounded-xl transition-all scale-press text-lg disabled:opacity-60">
                    Join Queue
                </button>
            </div>
        </div>

        <!-- Result -->
        <div id="resultSection" class="hidden">
            <div class="bg-white rounded-card shadow-elevated overflow-hidden fade-in">
                <div class="bg-gradient-to-br from-primary to-primary-dark p-8 text-center text-white relative">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/60 mb-2">Your Queue Number</p>
                    <p id="resultNum" class="text-6xl font-black tracking-tight"></p>
                    <p id="resultName" class="text-sm text-white/80 mt-2"></p>
                </div>
                <div class="p-6 space-y-3">
                    <div class="bg-surface rounded-xl p-4 text-center">
                        <p class="text-sm text-text-muted">Position in queue</p>
                        <p id="resultPos" class="text-3xl font-bold text-text-main mt-1"></p>
                    </div>
                    <a id="resultLink" href="/" class="block bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl text-center text-sm transition-colors scale-press">
                        View Full Ticket Details →
                    </a>
                    <button onclick="joinAnother()" class="w-full bg-gray-100 hover:bg-gray-200 text-text-main font-semibold py-3 rounded-xl text-sm transition-colors">
                        Join Another Person
                    </button>
                </div>
            </div>
        </div>

        <a href="/" class="block text-center mt-6 text-sm text-text-muted hover:text-primary transition-colors font-medium">
            ← Back to Home
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var TOKEN = '{{ $token }}';
    var isProcessing = false;

    // Enter key to submit
    document.getElementById('scanNameInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') joinQueue();
    });

    function joinQueue() {
        if (isProcessing) return;
        var name = document.getElementById('scanNameInput').value.trim();
        if (!name || name.length < 2) {
            showToast('Masukkan nama Anda (min 2 karakter).', 'warning');
            document.getElementById('scanNameInput').focus();
            return;
        }

        isProcessing = true;
        var btn = document.getElementById('joinBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        fetch('/queue/walk-in', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_token: TOKEN, customer_name: name }),
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            isProcessing = false;
            if (d.success) {
                localStorage.setItem('dq_active_ticket', JSON.stringify(d.ticket));
                showToast('Ticket: ' + d.ticket.ticket_number, 'success');
                showResult(d.ticket);
            } else {
                showToast(d.message || 'Failed', 'warning');
                btn.disabled = false;
                btn.textContent = 'Join Queue';
            }
        }).catch(function() {
            isProcessing = false;
            showToast('Network error.', 'error');
            btn.disabled = false;
            btn.textContent = 'Join Queue';
        });
    }

    function showResult(t) {
        document.getElementById('joinSection').classList.add('hidden');
        document.getElementById('resultSection').classList.remove('hidden');
        document.getElementById('resultNum').textContent = t.ticket_number;
        document.getElementById('resultName').textContent = t.customer_name || '';
        var ahead = t.position > 1 ? t.position - 1 : 0;
        document.getElementById('resultPos').textContent = ahead === 0 ? 'You are next!' : ahead + ' ahead';
        document.getElementById('resultLink').href = '/ticket/' + t.unique_code;
    }

    function joinAnother() {
        document.getElementById('joinSection').classList.remove('hidden');
        document.getElementById('resultSection').classList.add('hidden');
        document.getElementById('scanNameInput').value = '';
        document.getElementById('scanNameInput').focus();
        var btn = document.getElementById('joinBtn');
        btn.disabled = false;
        btn.textContent = 'Join Queue';
    }
</script>
@endsection