import { copyText, debounce, playBeep, toast } from './helpers';

export function initRoom() {
    const app = document.getElementById('chat-app');
    if (!app) return;

    const code = app.dataset.code;
    const expiredAt = new Date(app.dataset.expiredAt);
    const currentUser = app.dataset.currentUser;
    const base = `/room/${code}`;

    const messagesEl = document.getElementById('messages');
    const emptyState = document.getElementById('empty-state');
    const onlineCountEl = document.getElementById('online-count');
    const countdownEl = document.getElementById('countdown');
    const typingIndicator = document.getElementById('typing-indicator');
    const typingNamesEl = document.getElementById('typing-names');
    const form = document.getElementById('send-form');
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const copyBtn = document.getElementById('copy-link-btn');
    const waBtn = document.getElementById('share-wa-btn');
    const tgBtn = document.getElementById('share-tg-btn');
    const soundToggle = document.getElementById('sound-toggle');
    const leaveBtn = document.getElementById('leave-btn');

    let lastId = 0;
    let soundOn = localStorage.getItem('tempchat_sound') !== 'off';

    const renderSound = () => {
        soundToggle.textContent = soundOn ? '🔊' : '🔇';
    };

    const renderMessage = (m) => {
        if (m.is_system) {
            const el = document.createElement('div');
            el.className = 'message-enter py-1 text-center font-display text-xs text-secondary';
            el.textContent = m.message;
            return el;
        }

        const mine = m.user_name === currentUser;
        const wrap = document.createElement('div');
        wrap.className = `message-enter flex flex-col ${mine ? 'items-end' : 'items-start'}`;

        if (!mine) {
            const name = document.createElement('div');
            name.className = 'mb-1 font-display text-[11px] text-tertiary';
            name.textContent = m.user_name;
            wrap.append(name);
        }

        const bubble = document.createElement('div');
        bubble.className = mine
            ? 'max-w-[75%] rounded-md rounded-tr-sm bg-tertiary px-3.5 py-2 text-on-tertiary'
            : 'max-w-[75%] rounded-md rounded-tl-sm border border-secondary/30 bg-surface px-3.5 py-2 text-primary';

        const text = document.createElement('div');
        text.className = 'whitespace-pre-wrap break-words text-sm';
        text.textContent = m.message;

        const time = document.createElement('div');
        time.className = `mt-1 text-right font-display text-[10px] ${mine ? 'text-on-tertiary/70' : 'text-secondary'}`;
        time.textContent = m.time;

        bubble.append(text, time);
        wrap.append(bubble);

        return wrap;
    };

    const isNearBottom = () =>
        messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;

    const scrollToBottom = () => {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const fetchMessages = async () => {
        const { data } = await axios.get(`${base}/messages`, { params: { after: lastId } });

        if (data.messages.length === 0) return;

        for (const m of data.messages) {
            if (lastId > 0 && !m.is_system && m.user_name !== currentUser && soundOn) {
                playBeep();
            }
        }

        const nearBottom = isNearBottom();
        emptyState?.remove();

        for (const m of data.messages) {
            messagesEl.appendChild(renderMessage(m));
        }

        lastId = data.last_id;

        if (nearBottom) scrollToBottom();
    };

    const fetchPresence = async () => {
        const { data } = await axios.get(`${base}/users`);
        onlineCountEl.textContent = `${data.online_count} online`;
    };

    const fetchTypingStatus = async () => {
        const { data } = await axios.get(`${base}/typing/status`);

        if (data.typing.length === 0) {
            typingIndicator.classList.add('hidden');
            return;
        }

        typingNamesEl.textContent = `${data.typing.join(', ')} sedang mengetik`;
        typingIndicator.classList.remove('hidden');
    };

    const poll = () => Promise.allSettled([fetchMessages(), fetchPresence(), fetchTypingStatus()]);

    const tickCountdown = () => {
        const diff = expiredAt - Date.now();

        if (diff <= 0) {
            countdownEl.textContent = '00:00:00';
            countdownEl.className = 'text-danger';
            return;
        }

        const pad = (n) => String(n).padStart(2, '0');
        const h = Math.floor(diff / 3.6e6);
        const m = Math.floor((diff % 3.6e6) / 6e4);
        const s = Math.floor((diff % 6e4) / 1000);

        countdownEl.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
        countdownEl.className = h < 1 ? 'text-danger' : 'text-secondary';
    };

    const resizeInput = () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 140)}px`;
    };

    const sendTyping = debounce(async () => {
        if (!input.value.trim()) return;
        try {
            await axios.post(`${base}/typing`);
        } catch {
            // abaikan — jika gagal hanya indikator tidak tampil
        }
    }, 300);

    // ---- Events ----
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const text = input.value.trim();
        if (!text) return;

        sendBtn.disabled = true;

        try {
            await axios.post(`${base}/messages`, { message: text });
            input.value = '';
            resizeInput();
            input.focus();
            scrollToBottom();
        } catch (err) {
            const status = err.response?.status;
            if (status === 429) toast('Terlalu cepat — kirim sekali lagi, ya.', 'error');
            else if (status === 403) toast('Bergabung dulu untuk kirim pesan.', 'error');
            else if (status === 422) toast('Pesan tidak valid.', 'error');
            else toast('Gagal mengirim pesan.', 'error');
        } finally {
            sendBtn.disabled = false;
        }
    });

    input.addEventListener('input', () => {
        sendTyping();
        resizeInput();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    copyBtn.addEventListener('click', async () => {
        await copyText(window.location.href);
        toast('Link room disalin!', 'success');
    });

    waBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const text = `Mari ngobrol di TempChat: ${window.location.href}`;
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
    });

    tgBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const url = window.location.href;
        const text = 'Mari ngobrol di TempChat';
        window.open(
            `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`,
            '_blank',
            'noopener',
        );
    });

    soundToggle.addEventListener('click', () => {
        soundOn = !soundOn;
        localStorage.setItem('tempchat_sound', soundOn ? 'on' : 'off');
        renderSound();
    });

    leaveBtn.addEventListener('click', async () => {
        if (!confirm('Tinggalkan room ini?')) return;
        try {
            await axios.post(`${base}/leave`);
        } catch {
            // tetap redirect walau request gagal
        }
        window.location.href = '/';
    });

    // ---- Boot ----
    renderSound();
    tickCountdown();
    setInterval(tickCountdown, 500);
    setInterval(poll, 2000);
    poll();
    scrollToBottom();
}