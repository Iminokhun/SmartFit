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
    {{-- Skeleton --}}
    <div id="skeleton-block">
        <div class="sk-header">
            <div class="sk sk-title"></div>
            <div class="sk sk-avatar"></div>
        </div>
        <div class="sk sk-card-big"></div>
        <div class="sk-grid2">
            <div class="sk sk-box"></div>
            <div class="sk sk-box"></div>
        </div>
        <div class="sk-grid3">
            <div class="sk sk-btn"></div>
            <div class="sk sk-btn"></div>
            <div class="sk sk-btn"></div>
        </div>
        <div class="sk sk-section"></div>
    </div>

    <div id="dashboard-block" class="hidden">
        <div class="header">
            <div>
                <h2 class="header-title" id="welcome-title">Hello</h2>
                <p class="header-sub">SmartFit profile</p>
            </div>
            <div class="avatar-dot" id="avatar-dot">A</div>
        </div>

        <div id="debt-banner" class="debt-banner hidden">
            <div class="debt-icon">!</div>
            <div class="debt-body">
                <div class="debt-title">Outstanding balance</div>
                <div id="debt-amount" class="debt-amount"></div>
            </div>
        </div>

        <div id="smart-card" class="smart-card premium">
            <div class="smart-top-row">
                <div class="smart-muted">Your pass</div>
                <div id="subscription-status-badge" class="smart-badge hidden"></div>
            </div>
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
            <a href="{{ route('telegram.mini-app.my-subscriptions') }}" class="qa-btn" style="display:grid;place-items:center;text-decoration:none;" id="btn-my-subscriptions">My Pass</a>
            <a href="{{ route('telegram.mini-app.subscriptions') }}" class="qa-btn" style="display:grid;place-items:center;text-decoration:none;">Subscriptions</a>
            <button type="button" id="btn-show-qr" class="qa-btn" style="display:grid;place-items:center;text-decoration:none;">My QR</button>
        </div>

        <div id="subscriptions-card" class="card hidden" style="margin-top:12px;">
            <div class="kpi-label">Active Subscriptions</div>
            <div id="subscriptions-empty" class="kpi-value">No active subscriptions</div>
            <ul id="subscriptions-list" class="subs-list hidden"></ul>
        </div>

        <div id="qr-card" class="card hidden" style="margin-top:12px;">
            <div class="kpi-label">Check-in QR</div>
            <div id="qr-svg" class="qr-svg-wrap"></div>
            <div class="qr-progress-wrap" style="margin-top:10px;">
                <div id="qr-progress-fill" class="qr-progress-fill"></div>
            </div>
            <div id="qr-expire-text" class="qr-expire-text">Expires in: -</div>
            <div id="qr-status" class="qr-status" style="margin-top:8px;"></div>
        </div>

        <div class="card" style="margin-top:12px;">
            <div class="kpi-label" id="schedule-label">My Schedule</div>
            <div id="schedule-empty" class="schedule-empty-text">No classes today</div>
            <div id="schedule-list" class="hidden"></div>
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

                <button type="submit" id="submit-btn">Link Profile</button>
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

    const haptic = tg?.HapticFeedback;

    function hapticSelection() { haptic?.selectionChanged(); }
    function hapticSuccess() { haptic?.notificationOccurred('success'); }
    function hapticError() { haptic?.notificationOccurred('error'); }
    function hapticLight() { haptic?.impactOccurred('light'); }

    function tgAlert(text) {
        if (tg?.showAlert) {
            tg.showAlert(text);
        } else {
            alert(text);
        }
    }

    const form = document.getElementById('link-form');
    const msg = document.getElementById('msg');
    const submitBtn = document.getElementById('submit-btn');
    const registerBlock = document.getElementById('register-block');
    const dashboardBlock = document.getElementById('dashboard-block');
    const skeletonBlock = document.getElementById('skeleton-block');
    const welcomeTitle = document.getElementById('welcome-title');
    const avatarDot = document.getElementById('avatar-dot');
    const smartCard = document.getElementById('smart-card');
    const subscriptionValue = document.getElementById('subscription-value');
    const subscriptionEnd = document.getElementById('subscription-end');
    const subscriptionStatusBadge = document.getElementById('subscription-status-badge');
    const visitsValue = document.getElementById('visits-value');
    const visitsUsage = document.getElementById('visits-usage');
    const statusValue = document.getElementById('status-value');
    const debtBanner = document.getElementById('debt-banner');
    const debtAmount = document.getElementById('debt-amount');
    const scheduleEmpty = document.getElementById('schedule-empty');
    const scheduleList = document.getElementById('schedule-list');
    const scheduleLabel = document.getElementById('schedule-label');
    const btnShowQr = document.getElementById('btn-show-qr');
    const subscriptionsCard = document.getElementById('subscriptions-card');
    const subscriptionsEmpty = document.getElementById('subscriptions-empty');
    const subscriptionsList = document.getElementById('subscriptions-list');
    const qrCard = document.getElementById('qr-card');
    const qrSvg = document.getElementById('qr-svg');
    const qrProgressFill = document.getElementById('qr-progress-fill');
    const qrExpireText = document.getElementById('qr-expire-text');
    const qrStatus = document.getElementById('qr-status');
    let currentActiveSubscriptions = [];
    let currentVisitsLeft = null;
    let qrTimer = null;
    let qrPollTimer = null;
    const QR_TTL_SECONDS = 300;

    // MainButton setup
    function showMainButton() {
        if (!tg?.MainButton) return;
        tg.MainButton.setText('Link Profile');
        tg.MainButton.show();
        tg.MainButton.onClick(handleFormSubmit);
    }

    function hideMainButton() {
        if (!tg?.MainButton) return;
        tg.MainButton.hide();
        tg.MainButton.offClick(handleFormSubmit);
    }

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

        // Subscription card
        const sub = data?.subscription || {};
        const subStatus = sub.status || 'none';

        smartCard.classList.remove('premium', 'warning', 'frozen', 'no-sub');
        subscriptionStatusBadge.classList.add('hidden');
        subscriptionStatusBadge.className = 'smart-badge hidden';

        if (sub.has_active) {
            subscriptionValue.textContent = sub.name || 'Subscription';
            subscriptionEnd.textContent = sub.end_date || '-';

            if (subStatus === 'expiring') {
                smartCard.classList.add('warning');
                statusValue.textContent = 'Expiring Soon';
                subscriptionStatusBadge.textContent = sub.days_left != null ? `${sub.days_left}d left` : 'Expiring';
                subscriptionStatusBadge.classList.remove('hidden');
                subscriptionStatusBadge.classList.add('badge-warning');
            } else {
                smartCard.classList.add('premium');
                statusValue.textContent = 'Active';
                subscriptionStatusBadge.textContent = 'Active';
                subscriptionStatusBadge.classList.remove('hidden');
                subscriptionStatusBadge.classList.add('badge-active');
            }
        } else if (subStatus === 'frozen') {
            subscriptionValue.textContent = sub.name || 'Subscription';
            subscriptionEnd.textContent = sub.end_date || '-';
            smartCard.classList.add('frozen');
            statusValue.textContent = 'Frozen';
            subscriptionStatusBadge.textContent = 'Frozen';
            subscriptionStatusBadge.classList.remove('hidden');
            subscriptionStatusBadge.classList.add('badge-frozen');
        } else {
            subscriptionValue.textContent = 'No active subscription';
            subscriptionEnd.textContent = '-';
            smartCard.classList.add('no-sub');
            statusValue.textContent = 'No subscription';
        }

        // Visits
        if (data?.visits?.has_active) {
            visitsValue.textContent = data.visits.is_unlimited ? '∞' : String(data.visits.left ?? 0);
            visitsUsage.textContent = data.visits.is_unlimited ? 'Unlimited' : `${data.visits.left ?? 0} left`;
        } else {
            visitsValue.textContent = '-';
            visitsUsage.textContent = 'No active';
        }

        currentVisitsLeft = data?.visits?.is_unlimited ? Infinity : (data?.visits?.left ?? null);

        // Debt banner
        const debt = sub.debt ?? 0;
        if (debt > 0) {
            debtAmount.textContent = `${Number(debt).toLocaleString()} UZS`;
            debtBanner.classList.remove('hidden');
        } else {
            debtBanner.classList.add('hidden');
        }

        // Schedule
        const scheduleItems = data?.schedule?.items || [];
        const hasToday = scheduleItems.some((item) => item.is_today);
        scheduleLabel.textContent = hasToday ? 'My Schedule (Today)' : 'Upcoming Classes';

        if (scheduleItems.length > 0) {
            scheduleList.innerHTML = scheduleItems.map((item) => {
                const cardClass = item.is_past ? 'schedule-card sc-card--past'
                    : item.is_next ? 'schedule-card sc-card--next'
                    : 'schedule-card';

                const badge = item.is_past
                    ? `<span class="sc-badge sc-badge--past">Прошло</span>`
                    : item.is_next
                    ? `<span class="sc-badge sc-badge--next">След.</span>`
                    : '';

                return `
                <div class="${cardClass}">
                    <div class="sc-time-block">
                        <span class="sc-time-from">${escapeHtml(item.time_from)}</span>
                        <span class="sc-time-to">${escapeHtml(item.time_to)}</span>
                    </div>
                    <div class="sc-body">
                        <div class="sc-activity">${escapeHtml(item.activity)}</div>
                        <div class="sc-meta">
                            ${item.hall ? `<span class="sc-chip sc-chip-hall">📍 ${escapeHtml(item.hall)}</span>` : ''}
                            ${item.trainer ? `<span class="sc-chip sc-chip-trainer">${escapeHtml(item.trainer)}</span>` : ''}
                            ${!item.is_today && item.day ? `<span class="sc-chip sc-chip-day">${escapeHtml(item.day)}</span>` : ''}
                        </div>
                    </div>
                    ${badge}
                </div>`;
            }).join('');
            scheduleList.classList.remove('hidden');
            scheduleEmpty.classList.add('hidden');
        } else {
            scheduleList.innerHTML = '';
            scheduleList.classList.add('hidden');
            scheduleEmpty.textContent = 'No classes today';
            scheduleEmpty.classList.remove('hidden');
        }

        currentActiveSubscriptions = data?.active_subscriptions || [];
        renderActiveSubscriptions(currentActiveSubscriptions);

        skeletonBlock.classList.add('hidden');
        registerBlock.classList.add('hidden');
        dashboardBlock.classList.remove('hidden');
        hideMainButton();
    }

    function renderActiveSubscriptions(items) {
        if (!items.length) {
            subscriptionsList.innerHTML = '';
            subscriptionsList.classList.add('hidden');
            subscriptionsEmpty.classList.remove('hidden');
            return;
        }

        subscriptionsList.innerHTML = items.map((item) => {
            const debt = item.debt ?? 0;
            return `
            <li class="subs-item">
                <div class="subs-item-name">${escapeHtml(item.name || 'Subscription')}</div>
                <div class="subs-item-meta">Valid until: ${escapeHtml(item.end_date || '-')}</div>
                <div class="subs-item-meta">Visits left: ${escapeHtml(item.remaining_visits || '-')} | Payment: ${escapeHtml(item.payment_status || '-')}</div>
                ${debt > 0 ? `<div class="subs-item-debt">Debt: ${Number(debt).toLocaleString()} UZS</div>` : ''}
            </li>`;
        }).join('');

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

    function stopQrPoll() {
        if (qrPollTimer) { clearInterval(qrPollTimer); qrPollTimer = null; }
    }

    function startQrPoll() {
        stopQrPoll();
        const initData = tg?.initData || '';
        if (!initData || currentVisitsLeft === null) return;

        qrPollTimer = setInterval(async () => {
            try {
                const res = await fetch('/telegram/mini-app/me', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ init_data: initData }),
                });
                const data = await res.json();
                if (!res.ok || !data.linked) return;

                const newLeft = data?.visits?.is_unlimited ? Infinity : (data?.visits?.left ?? null);
                if (newLeft !== null && newLeft < currentVisitsLeft) {
                    stopQrPoll();
                    if (qrTimer) { clearInterval(qrTimer); qrTimer = null; }
                    qrStatus.className = 'qr-status success';
                    qrStatus.textContent = 'Check-in registered!';
                    qrProgressFill.style.width = '100%';
                    qrProgressFill.className = 'qr-progress-fill';
                    hapticSuccess();
                    currentVisitsLeft = newLeft;
                    // Update dashboard visits
                    visitsValue.textContent = newLeft === Infinity ? 'Unlimited' : String(newLeft);
                    visitsUsage.textContent = newLeft === Infinity ? 'Unlimited' : `${newLeft} left`;
                }
            } catch (_) {}
        }, 4000);
    }

    function startQrCountdown(expiresAtIso) {
        if (qrTimer) { clearInterval(qrTimer); qrTimer = null; }

        const expiresAt = new Date(expiresAtIso);
        if (Number.isNaN(expiresAt.getTime())) return;

        const tick = () => {
            const left = Math.floor((expiresAt.getTime() - Date.now()) / 1000);

            if (left <= 0) {
                clearInterval(qrTimer);
                qrTimer = null;
                stopQrPoll();
                qrExpireText.textContent = 'Expired — refreshing...';
                qrProgressFill.style.width = '0%';
                qrProgressFill.className = 'qr-progress-fill danger';
                setTimeout(() => {
                    if (!qrCard.classList.contains('hidden')) {
                        loadCheckinQr();
                    }
                }, 1200);
                return;
            }

            qrExpireText.textContent = `Expires in: ${formatSeconds(left)}`;

            const pct = (left / QR_TTL_SECONDS) * 100;
            qrProgressFill.style.width = pct + '%';
            if (pct > 40) {
                qrProgressFill.className = 'qr-progress-fill';
            } else if (pct > 15) {
                qrProgressFill.className = 'qr-progress-fill warn';
            } else {
                qrProgressFill.className = 'qr-progress-fill danger';
            }
        };

        tick();
        qrTimer = setInterval(tick, 1000);
    }

    async function loadCheckinQr() {
        const initData = tg?.initData || '';
        if (!initData) {
            tgAlert('Open this page only from Telegram bot.');
            hapticError();
            return;
        }

        qrExpireText.textContent = 'Generating...';
        qrProgressFill.style.width = '100%';
        qrProgressFill.className = 'qr-progress-fill';
        qrStatus.className = 'qr-status';
        qrStatus.textContent = '';

        try {
            const res = await fetch('/telegram/mini-app/checkin-qr', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ init_data: initData }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                hapticError();
                tgAlert(data.message || 'Failed to generate QR.');
                return;
            }

            qrSvg.innerHTML = data.qr_svg || '';
            qrCard.classList.remove('hidden');
            btnShowQr.classList.add('is-active');
            startQrCountdown(data.expires_at);
            qrStatus.className = 'qr-status scanning';
            qrStatus.textContent = 'Waiting for scan...';
            startQrPoll();
            hapticLight();
        } catch (_) {
            hapticError();
            tgAlert('Network error. Try again.');
        }
    }

    async function handleFormSubmit() {
        clearMessage();

        const initData = tg?.initData || '';
        if (!initData) {
            tgAlert('Open this page only from Telegram bot.');
            hapticError();
            return;
        }

        const payload = {
            init_data: initData,
            phone: document.getElementById('phone').value.trim(),
            birth_date: document.getElementById('birth_date').value,
        };

        if (tg?.MainButton) {
            tg.MainButton.showProgress(false);
            tg.MainButton.disable();
        }
        submitBtn.disabled = true;

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
                hapticError();
                showMessage(data.message || 'Link failed.', false);
                return;
            }

            hapticSuccess();
            showMessage(data.message || 'Linked successfully.', true);
            renderDashboard(data);
        } catch (_) {
            hapticError();
            tgAlert('Network error. Try again.');
        } finally {
            if (tg?.MainButton) {
                tg.MainButton.hideProgress();
                tg.MainButton.enable();
            }
            submitBtn.disabled = false;
        }
    }

    async function loadState() {
        const initData = tg?.initData || '';
        if (!initData) {
            skeletonBlock.classList.add('hidden');
            tgAlert('Open this page only from Telegram bot.');
            hapticError();
            return;
        }

        // Show skeleton while loading
        skeletonBlock.classList.remove('hidden');
        dashboardBlock.classList.add('hidden');
        registerBlock.classList.add('hidden');

        const res = await fetch('/telegram/mini-app/me', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ init_data: initData }),
        });

        const data = await res.json();
        skeletonBlock.classList.add('hidden');

        if (!res.ok || !data.ok) {
            showMessage(data.message || 'Failed to load profile.', false);
            return;
        }

        if (data.linked) {
            renderDashboard(data);
        } else {
            registerBlock.classList.remove('hidden');
            dashboardBlock.classList.add('hidden');
            showMainButton();
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit();
    });

    btnShowQr.addEventListener('click', () => {
        hapticSelection();
        const isHidden = qrCard.classList.contains('hidden');
        if (!isHidden) {
            qrCard.classList.add('hidden');
            btnShowQr.classList.remove('is-active');
            if (qrTimer) { clearInterval(qrTimer); qrTimer = null; }
            stopQrPoll();
            return;
        }
        loadCheckinQr();
    });

    loadState().catch(() => {
        hapticError();
        tgAlert('Failed to initialize Mini App.');
    });
</script>
</body>
</html>
