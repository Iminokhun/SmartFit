<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFit App</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/telegram/mini-app.css', 'resources/js/telegram/telegram-utils.js'])
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
                <p class="header-sub"></p>
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
                <div class="text-right">
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

        <div class="card mt-3">
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

<script type="module">
    const tg = window.tg;

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
    let currentVisitsLeft = null;

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

        skeletonBlock.classList.add('hidden');
        registerBlock.classList.add('hidden');
        dashboardBlock.classList.remove('hidden');
        hideMainButton();
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

    loadState().then(() => {
    }).catch(() => {
        hapticError();
        tgAlert('Failed to initialize Mini App.');
    });
</script>
<x-telegram.bottom-nav active="home" />

</body>
</html>
