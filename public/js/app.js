(function() {
    'use strict';

    // ==========================================
    // TOAST
    // ==========================================
    window.showToast = function(message, type, duration) {
        type = type || 'info'; duration = duration || 4000;
        var container = document.getElementById('toastContainer');
        if (!container) return;
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        container.appendChild(toast);
        var timer = setTimeout(function() {
            toast.style.animation = 'toastSlideOut 0.3s ease-in forwards';
            setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
        }, duration);
        toast.addEventListener('click', function() {
            clearTimeout(timer);
            toast.style.animation = 'toastSlideOut 0.3s ease-in forwards';
            setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
        });
    };

    // ==========================================
    // DQ NAMESPACE
    // ==========================================
    window.DQ = window.DQ || {};

    DQ.isPageVisible = true;
    document.addEventListener('visibilitychange', function() {
        DQ.isPageVisible = !document.hidden;
    });

    // ==========================================
    // SOUND EFFECTS
    // ==========================================

    /**
     * Play a bell/chime sound when it's your turn
     */
    DQ.playCalledSound = function() {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            var ctx = new AudioContext();

            // Three-note chime: C5 → E5 → G5
            var notes = [523.25, 659.25, 783.99];
            var startTime = ctx.currentTime;

            notes.forEach(function(freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.type = 'sine';
                osc.frequency.value = freq;

                var noteStart = startTime + (i * 0.2);
                gain.gain.setValueAtTime(0, noteStart);
                gain.gain.linearRampToValueAtTime(0.4, noteStart + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.01, noteStart + 0.6);

                osc.start(noteStart);
                osc.stop(noteStart + 0.7);
            });

            // Extra emphasis: low octave at the end
            setTimeout(function() {
                var osc2 = ctx.createOscillator();
                var gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.type = 'sine';
                osc2.frequency.value = 1046.50; // C6
                gain2.gain.setValueAtTime(0.3, ctx.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
                osc2.start(ctx.currentTime);
                osc2.stop(ctx.currentTime + 0.9);
            }, 650);
        } catch (e) {}
    };

    /**
     * Play a completion sound (softer, descending)
     */
    DQ.playCompletedSound = function() {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            var ctx = new AudioContext();

            // Two soft descending notes
            var notes = [659.25, 523.25]; // E5 → C5
            var startTime = ctx.currentTime;

            notes.forEach(function(freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'triangle';
                osc.frequency.value = freq;
                var noteStart = startTime + (i * 0.15);
                gain.gain.setValueAtTime(0.25, noteStart);
                gain.gain.exponentialRampToValueAtTime(0.01, noteStart + 0.4);
                osc.start(noteStart);
                osc.stop(noteStart + 0.5);
            });
        } catch (e) {}
    };

    /**
     * Play a skip/cancel sound (short buzz)
     */
    DQ.playSkipSound = function() {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            var ctx = new AudioContext();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'square';
            osc.frequency.value = 300;
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) {}
    };

    /**
     * Legacy compatibility
     */
    DQ.playNotification = DQ.playCalledSound;

    console.log('%c Digital Queue System %c Ready ',
        'background: #4F46E5; color: white; padding: 4px 12px; border-radius: 4px 0 0 4px; font-weight: bold;',
        'background: #10B981; color: white; padding: 4px 12px; border-radius: 0 4px 4px 0; font-weight: bold;'
    );
})();