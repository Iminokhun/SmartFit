<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFit App</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite('resources/css/telegram/mini-app.css')
</head>
<body>
<div class="wrap">
    <div id="dashboard-block" class="hidden">
        <div class="header">
            <div>
                <h2 class="header-title" id="welcome-title">Hello</h2>
                <p class="header-sub">SmartFit profile</p>
            </div>
            <div class="avatar-dot" id="avatar-dot">A</div>
        </div>

        <div id="smart-card" class="smart-card premium">
            <div class="smart-muted">Your pass</div>
            <div id="subscription-value" class="smart-main">No active subscription</div>

            <div class="smart-row">
                <div>
                    <div class="smart-muted">Valid until</div>
                    <div id="subscription-end" class="smart-value" style="font-size:16px;font-weight:700;">-</div>
                </div>
                <div style="text-align:right;">
                    <div class="smart-muted">Visits left</div>
                    <div id="visits-value" class="smart-value">-</div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
                <div class="kpi-label">Visits Usage</div>
                <div id="visits-usage" class="kpi-value">-</div>
            </div>
            <div class="card">
                <div class="kpi-label">Current Status</div>
                <div id="status-value" class="kpi-value">Rest</div>
            </div>
        </div>

        <div class="quick-actions">
            <button type="button" id="btn-my-subscriptions" class="qa-btn">My subscriptions</button>
            <a href="{{ route('telegram.mini-app.subscriptions') }}" class="qa-btn" style="display:grid;place-items:center;text-decoration:none;">Subscription</a>
            <button type="button" id="btn-show-qr" class="qa-btn">My QR</button>
        </div>

        <div id="subscriptions-card" class="card hidden" style="margin-top:12px;">
            <div class="kpi-label">Active Subscriptions</div>
            <div id="subscriptions-empty" class="kpi-value">No active subscriptions</div>
            <ul id="subscriptions-list" class="subs-list hidden"></ul>
        </div>

        <div id="qr-card" class="card hidden" style="margin-top:12px;">
            <div class="kpi-label">Check-in QR</div>
            <div id="qr-svg" class="qr-svg-wrap"></div>
            <div id="qr-expire-text" class="qr-expire-text">Expires in: -</div>
        </div>

        <div class="card" style="margin-top:12px;">
            <div class="kpi-label">My Schedule (Today)</div>
            <div id="schedule-empty" class="kpi-value">No classes today</div>
            <ul id="schedule-value" class="schedule-list hidden"></ul>
        </div>
    </div>

    <div id="register-block" class="card">
        <div>
            <h1>Link Your Profile</h1>
            <p class="muted">Enter the same data as in ERP customer card.</p>

            <form id="link-form">
                <label for="phone">Phone</label>
                <input id="phone" name="phone" placeholder="+998901234567" required>

                <label for="birth_date">Birth Date</label>
                <input id="birth_date" name="birth_date" type="date" required>

                <button type="submit">Link Profile</button>
            </form>
        </div>

        <div id="msg" class="msg"></div>
    </div>
</div>

<script>
    const tg = window.Telegram?.WebApp;
    if (tg) {
        tg.ready();
        tg.expand();
    }

    const form = document.getElementById('link-form');
    const msg = document.getElementById('msg');
    const registerBlock = document.getElementById('register-block');
    const dashboardBlock = document.getElementById('dashboard-block');
    const welcomeTitle = document.getElementById('welcome-title');
    const avatarDot = document.getElementById('avatar-dot');
    const smartCard = document.getElementById('smart-card');
    const subscriptionValue = document.getElementById('subscription-value');
    const subscriptionEnd = document.getElementById('subscription-end');
    const visitsValue = document.getElementById('visits-value');
    const visitsUsage = document.getElementById('visits-usage');
    const statusValue = document.getElementById('status-value');
    const scheduleEmpty = document.getElementById('schedule-empty');
    const scheduleValue = document.getElementById('schedule-value');
    const btnMySubscriptions = document.getElementById('btn-my-subscriptions');
    const btnShowQr = document.getElementById('btn-show-qr');
    const subscriptionsCard = document.getElementById('subscriptions-card');
    const subscriptionsEmpty = document.getElementById('subscriptions-empty');
    const subscriptionsList = document.getElementById('subscriptions-list');
    const qrCard = document.getElementById('qr-card');
    const qrSvg = document.getElementById('qr-svg');
    const qrExpireText = document.getElementById('qr-expire-text');
    let currentActiveSubscriptions = [];
    let qrTimer = null;

    function showMessage(text, ok) {
        msg.className = 'msg ' + (ok ? 'ok' : 'err');
        msg.textContent = text;
    }

    function clearMessage() {
        msg.className = 'msg';
        msg.textContent = '';
    }

    function renderDashboard(data) {
        const customerName = data?.customer?.full_name || 'Customer';
        welcomeTitle.textContent = customerName;
        avatarDot.textContent = (customerName[0] || 'C').toUpperCase();

        if (data?.subscription?.has_active) {
            subscriptionValue.textContent = data.subscription.name || 'Subscription';
            subscriptionEnd.textContent = data.subscription.end_date || '-';
        } else {
            subscriptionValue.textContent = 'No active subscription';
            subscriptionEnd.textContent = '-';
        }

        if (data?.visits?.has_active) {
            visitsValue.textContent = data.visits.is_unlimited ? 'Unlimited' : String(data.visits.left ?? 0);
        } else {
            visitsValue.textContent = 'No active subscription';
        }

        const endDate = data?.subscription?.end_date ? new Date(data.subscription.end_date) : null;
        if (endDate && !Number.isNaN(endDate.getTime())) {
            const now = new Date();
            const diffDays = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
            smartCard.classList.remove('premium', 'warning');
            smartCard.classList.add(diffDays > 7 ? 'premium' : 'warning');
            statusValue.textContent = diffDays > 7 ? 'In Club' : 'Expiring Soon';
        } else {
            smartCard.classList.remove('warning');
            smartCard.classList.add('premium');
            statusValue.textContent = 'Rest';
        }

        visitsUsage.textContent = data?.visits?.is_unlimited
            ? 'Unlimited'
            : (data?.visits?.has_active ? `${data?.visits?.left ?? 0} left` : 'No active');

        const scheduleItems = data?.schedule?.items || [];
        if (scheduleItems.length > 0) {
            scheduleValue.innerHTML = scheduleItems.map((item) => `<li>${item}</li>`).join('');
            scheduleValue.classList.remove('hidden');
            scheduleEmpty.classList.add('hidden');
        } else {
            scheduleValue.innerHTML = '';
            scheduleValue.classList.add('hidden');
            scheduleEmpty.classList.remove('hidden');
        }

        currentActiveSubscriptions = data?.active_subscriptions || [];
        renderActiveSubscriptions(currentActiveSubscriptions);

        registerBlock.classList.add('hidden');
        dashboardBlock.classList.remove('hidden');
    }

    function renderActiveSubscriptions(items) {
        if (!items.length) {
            subscriptionsList.innerHTML = '';
            subscriptionsList.classList.add('hidden');
            subscriptionsEmpty.classList.remove('hidden');
            return;
        }

        subscriptionsList.innerHTML = items.map((item) => `
            <li class="subs-item">
                <div class="subs-item-name">${escapeHtml(item.name || 'Subscription')}</div>
                <div class="subs-item-meta">Valid until: ${escapeHtml(item.end_date || '-')}</div>
                <div class="subs-item-meta">Visits left: ${escapeHtml(item.remaining_visits || '-')} | Payment: ${escapeHtml(item.payment_status || '-')}</div>
            </li>
        `).join('');

        subscriptionsList.classList.remove('hidden');
        subscriptionsEmpty.classList.add('hidden');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatSeconds(total) {
        const safe = Math.max(0, total);
        const min = Math.floor(safe / 60);
        const sec = safe % 60;
        return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    }

    function startQrCountdown(expiresAtIso) {
        if (qrTimer) {
            clearInterval(qrTimer);
        }

        const expiresAt = new Date(expiresAtIso);
        if (Number.isNaN(expiresAt.getTime())) {
            qrExpireText.textContent = 'Expires in: -';
            return;
        }

        const tick = () => {
            const left = Math.floor((expiresAt.getTime() - Date.now()) / 1000);
            if (left <= 0) {
                qrExpireText.textContent = 'QR expired. Tap My QR to refresh.';
                clearInterval(qrTimer);
                qrTimer = null;
                return;
            }

            qrExpireText.textContent = `Expires in: ${formatSeconds(left)}`;
        };

        tick();
        qrTimer = setInterval(tick, 1000);
    }

    async function loadCheckinQr() {
        const initData = tg?.initData || '';
        if (!initData) {
            showMessage('Open this page only from Telegram bot.', false);
            return;
        }

        try {
            const res = await fetch('/telegram/mini-app/checkin-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ init_data: initData }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                showMessage(data.message || 'Failed to generate QR.', false);
                return;
            }

            qrSvg.innerHTML = data.qr_svg || '';
            qrCard.classList.remove('hidden');
            btnShowQr.classList.add('is-active');
            startQrCountdown(data.expires_at);
        } catch (_) {
            showMessage('Network error. Try again.', false);
        }
    }

    async function loadState() {
        const initData = tg?.initData || '';
        if (!initData) {
            showMessage('Open this page only from Telegram bot.', false);
            return;
        }

        const res = await fetch('/telegram/mini-app/me', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ init_data: initData }),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            showMessage(data.message || 'Failed to load profile.', false);
            return;
        }

        if (data.linked) {
            renderDashboard(data);
        } else {
            registerBlock.classList.remove('hidden');
            dashboardBlock.classList.add('hidden');
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessage();

        const initData = tg?.initData || '';
        if (!initData) {
            showMessage('Open this page only from Telegram bot.', false);
            return;
        }

        const payload = {
            init_data: initData,
            phone: document.getElementById('phone').value.trim(),
            birth_date: document.getElementById('birth_date').value,
        };

        try {
            const res = await fetch('/telegram/mini-app/link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                showMessage(data.message || 'Link failed.', false);
                return;
            }

            showMessage(data.message || 'Linked successfully.', true);
            renderDashboard(data);
        } catch (_) {
            showMessage('Network error. Try again.', false);
        }
    });

    btnMySubscriptions.addEventListener('click', () => {
        const isHidden = subscriptionsCard.classList.contains('hidden');
        subscriptionsCard.classList.toggle('hidden', !isHidden);
        btnMySubscriptions.classList.toggle('is-active', isHidden);
        if (isHidden) {
            renderActiveSubscriptions(currentActiveSubscriptions);
        }
    });

    btnShowQr.addEventListener('click', () => {
        const isHidden = qrCard.classList.contains('hidden');
        if (!isHidden) {
            qrCard.classList.add('hidden');
            btnShowQr.classList.remove('is-active');
            return;
        }

        loadCheckinQr();
    });

    loadState().catch(() => showMessage('Failed to initialize Mini App.', false));
</script>
</body>
</html>
