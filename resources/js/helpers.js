export function debounce(fn, wait = 300) {
    let timer;

    const wrapped = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };

    wrapped.cancel = () => clearTimeout(timer);

    return wrapped;
}

export function toast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const styles = {
        success: 'border-tertiary',
        error: 'border-danger',
        info: 'border-secondary/50',
    };

    const el = document.createElement('div');
    el.className =
        `pointer-events-auto message-enter rounded-md border bg-surface px-4 py-2 text-sm text-primary shadow-lg ${styles[type] ?? styles.info}`;
    el.textContent = message;

    container.appendChild(el);

    setTimeout(() => {
        el.style.transition = 'opacity .3s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 320);
    }, 2600);
}

export async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
}

let audioCtx = null;

export function playBeep() {
    try {
        audioCtx = audioCtx ?? new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.12);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.12);
    } catch {
        // abaikan error audio
    }
}