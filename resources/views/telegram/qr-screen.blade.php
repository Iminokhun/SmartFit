<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Check-in QR</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/js/telegram/telegram-utils.js'])
    <style>
        :root {
            --tg-bg:          var(--tg-theme-bg-color, #0f172a);
            --tg-text:        var(--tg-theme-text-color, #f8fafc);
            --tg-hint:        var(--tg-theme-hint-color, #64748b);
            --tg-button:      var(--tg-theme-button-color, #3b82f6);
            --tg-button-text: var(--tg-theme-button-text-color, #ffffff);
            --tg-sec-bg:      var(--tg-theme-secondary_bg_color, #1e293b);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%;
            height: 100%;
            background: var(--tg-bg);
            color: var(--tg-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            overflow: hidden;
        }

        .screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 24px 20px;
            gap: 0;
        }

        /* ── Header ── */
        .qr-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            margin-bottom: 32px;
        }

        .qr-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--tg-button);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
        }

        .qr-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .qr-subtitle {
            font-size: 13px;
            color: var(--tg-hint);
        }

        /* ── QR card ── */
        .qr-card {
            position: relative;
            padding: 20px;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08),
                        0 8px 40px rgba(0,0,0,0.35);
        }

        .qr-card.scanning::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 31px;
            background: conic-gradient(
                from 0deg,
                transparent 0%,
                var(--tg-button) 40%,
                transparent 60%
            );
            z-index: -1;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .qr-svg-wrap {
            width: 220px;
            height: 220px;
            display: grid;
            place-items: center;
        }

        .qr-svg-wrap svg {
            width: 100%;
            height: 100%;
        }

        .qr-skeleton {
            width: 220px;
            height: 220px;
            border-radius: 8px;
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
        }

        @keyframes shimmer {
            from { background-position: 200% 0; }
            to   { background-position: -200% 0; }
        }

        /* ── Info block ── */
        .qr-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
            width: 100%;
            max-width: 280px;
        }

        /* ── Countdown ── */
        .qr-countdown {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .qr-countdown-text {
            font-size: 13px;
            color: var(--tg-hint);
        }

        .qr-countdown-text span {
            font-weight: 600;
            color: var(--tg-text);
        }

        .progress-bar {
            width: 100%;
            height: 3px;
            background: rgba(255,255,255,0.1);
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--tg-button);
            border-radius: 99px;
            transition: width 1s linear, background 0.3s;
        }

        .progress-fill.expiring {
            background: #f59e0b;
        }

        .progress-fill.expired {
            background: #ef4444;
        }

        /* ── Status pill ── */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .status-pill.scanning {
            background: rgba(59,130,246,0.15);
            color: #60a5fa;
        }

        .status-pill.success {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
        }

        .status-pill.expired {
            background: rgba(239,68,68,0.12);
            color: #f87171;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-pill.scanning .status-dot {
            animation: pulse-dot 1.5s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.7); }
        }

        /* ── Refresh button ── */
        .btn-refresh {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--tg-button);
            color: var(--tg-button-text);
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity 0.2s;
        }

        .btn-refresh:active { opacity: 0.75; }
        .btn-refresh.visible { display: inline-flex; }

        /* ── Success overlay on QR card ── */
        .qr-success-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(34,197,94,0.92);
            border-radius: 28px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .qr-success-overlay.visible { display: flex; }

        .success-icon {
            width: 64px;
            height: 64px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="screen">
    <div class="qr-header">
        <div class="qr-logo">
            <svg width="26" height="26" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="5" y="5" width="3" height="3"/>
                <rect x="16" y="5" width="3" height="3"/>
                <rect x="5" y="16" width="3" height="3"/>
                <path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/>
            </svg>
        </div>
        <div class="qr-title">Check-in QR</div>
        <div class="qr-subtitle">Show this code at reception</div>
    </div>

    <div class="qr-card" id="qr-card">
        <div class="qr-svg-wrap" id="qr-svg">
            <div class="qr-skeleton"></div>
        </div>
        <div class="qr-success-overlay" id="success-overlay">
            <div class="success-icon">
                <svg width="32" height="32" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="qr-info">
        <div class="qr-countdown" id="countdown-wrap">
            <div class="qr-countdown-text">Expires in: <span id="expire-time">—</span></div>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
        </div>

        <div class="status-pill scanning" id="status-pill">
            <div class="status-dot"></div>
            <span id="status-text">Generating...</span>
        </div>

        <button class="btn-refresh" id="btn-refresh" type="button">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M3 12a9 9 0 0 1 15-6.7L21 8M3 16l3-3 3 3"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
            </svg>
            New QR Code
        </button>
    </div>
</div>

<script type="module">
    const tg = window.tg;

    const qrCard    = document.getElementById('qr-card');
    const qrSvg     = document.getElementById('qr-svg');
    const expireEl  = document.getElementById('expire-time');
    const progress  = document.getElementById('progress-fill');
    const pill      = document.getElementById('status-pill');
    const statusTxt = document.getElementById('status-text');
    const btnRefresh = document.getElementById('btn-refresh');
    const successOverlay = document.getElementById('success-overlay');

    let timer = null;
    let pollInterval = null;
    let expiresAt = null;
    let totalTtl = 300;
    let checkinDone = false;

    // Back button
    if (tg?.BackButton) {
        tg.BackButton.show();
        tg.BackButton.onClick(() => history.back());
    }

    function setStatus(type, text) {
        pill.className = 'status-pill ' + type;
        statusTxt.textContent = text;
    }

    function formatSeconds(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return `${m}:${String(sec).padStart(2, '0')}`;
    }

    function startCountdown(expiresIso) {
        expiresAt = new Date(expiresIso).getTime();
        if (timer) clearInterval(timer);

        const tick = () => {
            const left = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
            const pct  = Math.min(100, Math.round((left / totalTtl) * 100));

            expireEl.textContent = formatSeconds(left);
            progress.style.width = pct + '%';

            if (pct <= 20) {
                progress.classList.add('expiring');
            } else {
                progress.classList.remove('expiring');
            }

            if (left <= 0) {
                clearInterval(timer);
                progress.classList.replace('expiring', 'expired');
                setStatus('expired', 'QR expired');
                btnRefresh.classList.add('visible');
                stopPoll();
            }
        };

        tick();
        timer = setInterval(tick, 1000);
    }

    function startPoll(token) {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            try {
                const res = await fetch('/telegram/mini-app/checkin-qr/status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ init_data: tg?.initData || '', token }),
                });
                const data = await res.json();
                if (data.used) {
                    stopPoll();
                    showSuccess();
                }
            } catch (_) {}
        }, 2500);
    }

    function stopPoll() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }

    function showSuccess() {
        checkinDone = true;
        if (timer) clearInterval(timer);
        qrCard.classList.remove('scanning');
        successOverlay.classList.add('visible');
        setStatus('success', 'Checked in!');
        document.getElementById('countdown-wrap').style.visibility = 'hidden';
        tg?.HapticFeedback?.notificationOccurred?.('success');
    }

    async function loadQr() {
        checkinDone = false;
        btnRefresh.classList.remove('visible');
        successOverlay.classList.remove('visible');
        qrSvg.innerHTML = '<div class="qr-skeleton"></div>';
        qrCard.classList.remove('scanning');
        setStatus('scanning', 'Generating...');
        document.getElementById('countdown-wrap').style.visibility = 'visible';

        const initData = tg?.initData || '';
        if (!initData) {
            setStatus('expired', 'Open from Telegram bot');
            return;
        }

        try {
            const res = await fetch('/telegram/mini-app/checkin-qr', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ init_data: initData }),
            });
            const data = await res.json();

            if (!res.ok || !data.ok) {
                setStatus('expired', data.message || 'Failed to generate QR');
                btnRefresh.classList.add('visible');
                return;
            }

            totalTtl = Math.max(1, Math.round((new Date(data.expires_at) - Date.now()) / 1000));
            qrSvg.innerHTML = data.qr_svg || '';
            qrCard.classList.add('scanning');
            setStatus('scanning', 'Waiting for scan...');
            startCountdown(data.expires_at);
            startPoll(data.token);
            tg?.HapticFeedback?.impactOccurred?.('light');
        } catch (_) {
            setStatus('expired', 'Network error. Try again.');
            btnRefresh.classList.add('visible');
        }
    }

    btnRefresh.addEventListener('click', loadQr);

    loadQr();
</script>
</body>
</html>
