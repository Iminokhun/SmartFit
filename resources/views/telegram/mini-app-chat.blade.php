<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Fitness Assistant</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/telegram/mini-app.css', 'resources/js/telegram/telegram-utils.js'])
</head>
<body>
<div class="wrap chat-wrap">

    <div class="chat-header">
        <div class="chat-avatar-wrap">
            <div class="chat-header-icon">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="16" cy="16" r="16" fill="var(--tg-button)"/>
                    <path d="M16 8.5l1.5 3.5 3.5.5-2.5 2.5.5 3.5L16 17l-3 1.5.5-3.5L11 12.5l3.5-.5L16 8.5z" fill="white" opacity="0.9"/>
                    <path d="M22 19l.8 1.8 1.8.3-1.3 1.3.3 1.8L22 23.3l-1.6.9.3-1.8-1.3-1.3 1.8-.3L22 19z" fill="white" opacity="0.7"/>
                    <path d="M10.5 18l.6 1.4 1.4.2-1 1 .2 1.4-1.2-.7-1.2.7.2-1.4-1-1 1.4-.2L10.5 18z" fill="white" opacity="0.6"/>
                </svg>
            </div>
            <span class="chat-online-dot"></span>
        </div>
        <div>
            <div class="chat-header-title">AI Fitness Assistant</div>
            <div class="chat-header-sub">SmartFit · fitness & nutrition only</div>
        </div>
    </div>

    <div class="chat-messages" id="chat-messages">
        <div class="chat-bubble model">
            <div class="bubble-text">Hi! I'm SmartFit AI assistant 💪 Ask me about workouts, nutrition or calories. You can also send a food photo — I'll calculate the macros.</div>
        </div>
    </div>

    <div class="chat-footer">
<div class="photo-preview-wrap" id="photo-preview-wrap">
            <img id="photo-preview-img" class="photo-preview-img" src="" alt="preview">
            <div>
                <div class="photo-preview-label">Food photo ready to send</div>
            </div>
            <button class="photo-preview-cancel" id="btn-cancel-photo" title="Cancel">✕</button>
        </div>
        <div class="chat-input-row">
            <textarea
                id="chat-input"
                class="chat-textarea"
                placeholder="Ask about workouts or nutrition..."
                rows="1"
                maxlength="500"
            ></textarea>
            <button class="chat-btn-photo" id="btn-photo" title="Send food photo">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
            </button>
            <button class="chat-btn-send" id="btn-send" title="Send">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M12 19V5M5 12l7-7 7 7"/>
                </svg>
            </button>
        </div>
        <input type="file" id="photo-input" accept="image/*" class="hidden">
    </div>

    <x-telegram.bottom-nav active="chat" />

</div>

<script type="module">
    const tg = window.tg;

    const messagesEl     = document.getElementById('chat-messages');
    const inputEl        = document.getElementById('chat-input');
    const btnSend        = document.getElementById('btn-send');
    const btnPhoto       = document.getElementById('btn-photo');
    const photoInput     = document.getElementById('photo-input');
    const previewWrap    = document.getElementById('photo-preview-wrap');
    const previewImg     = document.getElementById('photo-preview-img');
    const btnCancelPhoto = document.getElementById('btn-cancel-photo');

    let pendingPhotoFile = null;

    function scrollBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function addBubble(role, text) {
        const div = document.createElement('div');
        div.className = 'chat-bubble ' + role;
        const inner = document.createElement('div');
        inner.className = 'bubble-text';
        inner.textContent = text;
        div.appendChild(inner);
        messagesEl.appendChild(div);
        scrollBottom();
        return div;
    }

    function addTyping() {
        const div = document.createElement('div');
        div.className = 'chat-bubble model typing-bubble';
        div.innerHTML = '<div class="bubble-typing"><span></span><span></span><span></span></div>';
        messagesEl.appendChild(div);
        scrollBottom();
        return div;
    }

    function setLoading(on) {
        inputEl.disabled  = on;
        btnPhoto.disabled = on;
        btnSend.disabled  = on;
        btnSend.classList.toggle('loading', on);
    }

    /* ── Photo preview ── */
    function showPreview(file) {
        pendingPhotoFile = file;
        const url = URL.createObjectURL(file);
        previewImg.src = url;
        previewWrap.classList.add('visible');
        hapticLight();
    }

    function clearPreview() {
        pendingPhotoFile = null;
        previewWrap.classList.remove('visible');
        previewImg.src = '';
        photoInput.value = '';
    }

    btnCancelPhoto.addEventListener('click', () => {
        clearPreview();
        hapticLight();
    });

    /* ── Send text message ── */
    async function sendMessage() {
        // If photo is pending — send it instead
        if (pendingPhotoFile) {
            await doSendPhoto(pendingPhotoFile);
            return;
        }

        const message = inputEl.value.trim();
        if (!message) return;

        const initData = tg?.initData || '';
        if (!initData) { alert('Open this page from the Telegram bot.'); return; }

        addBubble('user', message);
        inputEl.value = '';
        inputEl.style.height = 'auto';
        setLoading(true);
        const typing = addTyping();

        try {
            const res = await fetch('/telegram/mini-app/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ init_data: initData, message }),
            });
            const data = await res.json();
            typing.remove();

            if (!res.ok || !data.ok) {
                hapticError();
                addBubble('model', data.message || 'An error occurred. Please try again.');
            } else {
                hapticSuccess();
                addBubble('model', data.reply);
            }
        } catch {
            typing.remove();
            hapticError();
            addBubble('model', 'Network error. Check your connection.');
        } finally {
            setLoading(false);
        }
    }

    /* ── Send photo ── */
    async function doSendPhoto(file) {
        const initData = tg?.initData || '';
        if (!initData) { alert('Open this page from the Telegram bot.'); return; }

        const mimeType = file.type || 'image/jpeg';

        const reader = new FileReader();
        reader.onload = async (e) => {
            const base64 = e.target.result.split(',')[1];

            clearPreview();
            addBubble('user', '📷 Photo sent');
            setLoading(true);
            const typing = addTyping();

            try {
                const res = await fetch('/telegram/mini-app/chat/photo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ init_data: initData, image: base64, mime_type: mimeType }),
                });
                const data = await res.json();
                typing.remove();

                if (!res.ok || !data.ok) {
                    hapticError();
                    addBubble('model', data.message || 'An error occurred.');
                } else {
                    hapticSuccess();
                    addBubble('model', data.reply);
                }
            } catch {
                typing.remove();
                hapticError();
                addBubble('model', 'Network error. Check your connection.');
            } finally {
                setLoading(false);
            }
        };
        reader.readAsDataURL(file);
    }

    /* ── Event listeners ── */
    btnSend.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    inputEl.addEventListener('input', () => {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
    });

btnPhoto.addEventListener('click', () => photoInput.click());

    photoInput.addEventListener('change', () => {
        const file = photoInput.files[0];
        if (file) showPreview(file);
    });

    scrollBottom();
</script>
</body>
</html>
