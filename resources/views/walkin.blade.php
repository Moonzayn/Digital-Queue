@extends('layouts.app')

@section('title', 'Walk-in - Digital Queue')

@section('content')
<div class="min-h-screen bg-surface flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        @if($valid)
        <div class="bg-white rounded-card shadow-elevated p-8 text-center fade-in" id="walkinCard">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-text-main mb-2">QR Code Valid</h2>
            <p class="text-sm text-text-muted mb-6">Tekan tombol di bawah untuk mengambil nomor antrian Anda.</p>

            <button onclick="claimWalkIn()" id="claimBtn" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-4 rounded-xl transition-all duration-200 scale-press text-lg disabled:opacity-50">
                Ambil Nomor Antrian
            </button>
        </div>

        <div class="hidden bg-white rounded-card shadow-elevated overflow-hidden fade-in" id="successCard">
            <div class="bg-gradient-to-br from-primary to-primary-dark p-8 text-center text-white">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/60 mb-3">Nomor Antrian Anda</p>
                <p id="resultNumber" class="text-6xl font-black tracking-tight number-glow"></p>
                <span id="resultType" class="inline-block mt-3 text-xs font-semibold px-3 py-1.5 rounded-full bg-green-500/30 text-green-100">🚶 Walk-in</span>
            </div>
            <div class="p-6 text-center space-y-4">
                <div class="bg-surface rounded-xl p-4">
                    <p class="text-xs text-text-muted mb-1">Orang di depan Anda</p>
                    <p id="resultAhead" class="text-3xl font-bold text-text-main"></p>
                </div>
                <a id="resultLink" href="#" class="block w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3.5 rounded-xl transition-all text-sm scale-press">
                    Lihat Detail Tiket →
                </a>
            </div>
        </div>
        @else
        <div class="bg-white rounded-card shadow-card p-8 text-center fade-in">
            <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-text-main mb-2">QR Tidak Valid</h2>
            <p class="text-sm text-text-muted mb-6">QR Code sudah kedaluwarsa atau tidak valid. Scan ulang QR di lokasi antrian.</p>
            <a href="/" class="block w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3.5 rounded-xl transition-all text-sm scale-press">
                Kembali ke Beranda
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').content;
var TOKEN = '{{ $token }}';

function claimWalkIn() {
    var btn = document.getElementById('claimBtn');
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    fetch('/api/walkin/' + TOKEN, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            localStorage.setItem('dq_active_ticket', JSON.stringify(data.ticket));
            document.getElementById('walkinCard').classList.add('hidden');
            var sc = document.getElementById('successCard');
            sc.classList.remove('hidden');
            document.getElementById('resultNumber').textContent = data.ticket.ticket_number;
            document.getElementById('resultAhead').textContent = data.ticket.waiting_ahead;
            document.getElementById('resultLink').href = '/ticket/' + data.ticket.unique_code;
            showToast('Tiket berhasil dibuat!', 'success');
        } else {
            btn.disabled = false;
            btn.textContent = 'Ambil Nomor Antrian';
            showToast(data.message || 'Gagal', 'error');
            if (data.has_existing && data.ticket) {
                localStorage.setItem('dq_active_ticket', JSON.stringify(data.ticket));
                setTimeout(function() { window.location.href = '/ticket/' + data.ticket.unique_code; }, 1500);
            }
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Ambil Nomor Antrian';
        showToast('Network error', 'error');
    });
}
</script>
@endsection