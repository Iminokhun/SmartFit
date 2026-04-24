<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/telegram/mini-app.css', 'resources/js/telegram/telegram-utils.js'])
</head>
<body class="premium-subs">
<div class="wrap">

    {{-- Skeleton --}}
    <div id="skeleton-block">
        <div class="sk-header">
            <div class="sk sk-title"></div>
            <div class="sk sk-avatar"></div>
        </div>
        <div class="sk sk-card-big" style="height:48px;border-radius:12px;margin-bottom:14px;"></div>
        <div class="sk sk-card-big" style="height:300px;border-radius:24px;"></div>
    </div>

    <div id="main-block" class="hidden">
        {{-- Header --}}
        <div class="header mb-4">
            <div class="card-title">My Subscriptions</div>
        </div>

        {{-- Dropdown --}}
        <div id="dropdown-wrap" class="hidden mb-4">
            <select id="sub-select" class="sub-select"></select>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden card text-center" style="padding:32px 16px;">
            <div class="mb-3" style="font-size:40px;">📋</div>
            <div class="mb-3" style="font-size:16px;font-weight:700;">No subscriptions</div>
            <div class="hint-text">You have no active, pending subscriptions.</div>
        </div>

        {{-- Subscription card (innerHTML replaced by JS) --}}
        <div id="detail-card"></div>

        {{-- Attendance --}}
        <div id="att-section" class="hidden mt-4">
            <div class="att-header">
                <span class="att-title">Attendance</span>
            </div>
            <div class="premium-card" style="padding:14px 18px;">
                <div id="att-list"></div>
            </div>
        </div>
    </div>

    <div id="error-block" class="hidden card text-center" style="padding:24px;">
        <div id="error-msg" class="error-text"></div>
    </div>
</div>

<script type="module">
    const tg = window.tg;

    const skeletonBlock = document.getElementById('skeleton-block');
    const mainBlock     = document.getElementById('main-block');
    const errorBlock    = document.getElementById('error-block');
    const errorMsg      = document.getElementById('error-msg');
    const emptyState    = document.getElementById('empty-state');
    const dropdownWrap  = document.getElementById('dropdown-wrap');
    const subSelect     = document.getElementById('sub-select');
    const detailCard    = document.getElementById('detail-card');

    const STATUS_CONFIG = {
        active:  { label: 'Active',    icon: '✓', cls: 'banner-active'  },
        pending: { label: 'In queue',  icon: '⏳', cls: 'banner-pending' },
        frozen:  { label: 'Frozen',    icon: '❄',  cls: 'banner-frozen'  },
        expired: { label: 'Expired',   icon: '✕', cls: 'banner-expired' },
    };

    // ISO 1=Mon … 7=Sun
    const WEEK_DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat'];

    let allSubscriptions = [];

    function formatDate(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function renderSubscription(sub) {
        const cfg = STATUS_CONFIG[sub.status] || { label: sub.status, icon: '•', cls: 'badge-neutral' };

        const startDate = sub.start_date ? new Date(sub.start_date) : null;
        const endDate   = sub.end_date   ? new Date(sub.end_date)   : null;
        const now       = new Date();

        let validityText = '—';
        let daysLeftText = '';
        let progressClass = '';

        if (startDate && endDate) {
            const totalMs  = endDate - startDate;
            const passedMs = now - startDate;
            const daysLeft = Math.max(0, Math.ceil((endDate - now) / 86400000));
            validityText = `${formatDate(sub.start_date)} → ${formatDate(sub.end_date)}`;

            if (daysLeft == 0) {
                daysLeftText = 'Expired';
                progressClass = 'is-expired';
            } else {
                daysLeftText = `${daysLeft} day${daysLeft !== 1 ? 's' : ''} left`;
                progressClass = daysLeft <= 7 ? 'is-warn' : '';
            }
        }

        // Visits
        let visitsHtml = '';
        if (sub.is_unlimited) {
            visitsHtml = `
            <div class="premium-block">
                <div class="premium-label">Visits</div>
                <div class="premium-unlimited">Unlimited</div>
                <div class="premium-subtext">No visit restrictions</div>
            </div>`;
        } else {
            const remaining = Number(sub.remaining_visits ?? 0);
            const total     = Number(sub.total_visits ?? remaining);
            const used      = Math.max(0, total - remaining);
            const usedPct   = total > 0 ? Math.min(100, (used / total) * 100) : 0;
            const warn      = remaining <= 3 ? 'is-warn' : '';
            visitsHtml = `
            <div class="premium-block">
                <div class="premium-row">
                    <div>
                        <div class="premium-label">Visits</div>
                    </div>
                    <div class="premium-visits">
                        <span class="premium-visits-num">${remaining}</span>
                        <span class="premium-visits-den">/ ${total}</span>
                    </div>
                </div>
                <div class="premium-progress">
                    <div class="premium-progress-fill ${warn}" style="width:${usedPct}%;"></div>
                </div>
            </div>`;
        }

        // Details
        const activity = sub.activity || '—';
        const hall = sub.hall || '—';
        const trainer = sub.trainer || '—';
        const trainerInitials = (sub.trainer || '').split(' ').filter(Boolean).slice(0,2).map(s => s[0]).join('').toUpperCase();

        // Weekdays
        const activeDays = sub.weekdays || [];
        let daysHtml = '';
        if (!activeDays.length) {
            daysHtml = '<span class="premium-pill">Every day</span>';
        } else {
            daysHtml = activeDays.map((d) => {
                const label = WEEK_DAYS[(d - 1) % 7];
                return `<span class="premium-pill">${label}</span>`;
            }).join('');
        }

        const timeHtml = (sub.time_from || sub.time_to)
            ? `<span class="premium-time">${sub.time_from || '—'} – ${sub.time_to || '—'}</span>`
            : '';

        // Payment
        const price = Number(sub.agreed_price ?? 0);
        const paid  = Number(sub.paid_amount ?? 0);
        const debt  = Number(sub.debt ?? 0);
        const fullyPaid = debt <= 0 && paid >= price && price > 0;

        let paymentHtml = '';
        if (fullyPaid) {
            paymentHtml = `
            <div class="premium-footer">
                <div class="premium-paid">
                    <span class="premium-dot"></span>
                    <span>Fully paid</span>
                </div>
                <div class="premium-footer-amount">${formatMoney(price)}</div>
            </div>`;
        } else {
            paymentHtml = `
            <div class="premium-footer">
                <div>
                    <div class="premium-label">Price</div>
                    <div class="premium-footer-amount">${formatMoney(price)}</div>
                </div>
                <div class="premium-footer-meta">
                    <div><span class="premium-muted">Paid</span> ${formatMoney(paid)}</div>
                    <div><span class="premium-muted">Status</span> ${escapeHtml(sub.payment_status || '')}</div>
                </div>
            </div>`;
        }

        detailCard.innerHTML = `
        <div class="premium-card">
            <div class="premium-head">
                <div>
                    <div class="premium-title">${escapeHtml(sub.name || 'Subscription')}</div>
                    <div class="premium-sub">${escapeHtml(activity)}</div>
                </div>
                <div class="premium-badge ${cfg.cls}">
                    <span class="premium-badge-dot"></span>
                    <span>${escapeHtml(cfg.label)}</span>
                </div>
            </div>

            <div class="premium-divider"></div>

            <div class="premium-row">
                <div>
                    <div class="premium-label">Validity</div>
                    <div class="premium-value">${validityText}</div>
                </div>
                <div class="premium-right">
                    ${daysLeftText ? `<span class="premium-pill ${progressClass}">${daysLeftText}</span>` : ''}
                </div>
            </div>

            <div class="premium-divider"></div>

            ${visitsHtml}

            <div class="premium-divider"></div>

            <div class="premium-grid">
                <div>
                    <div class="premium-label">Activity</div>
                    <div class="premium-item">🏋️ ${escapeHtml(activity)}</div>
                </div>
                <div>
                    <div class="premium-label">Trainer</div>
                    <div class="premium-item">
                        <span class="premium-avatar">${trainerInitials || '—'}</span>
                        ${escapeHtml(trainer)}
                    </div>
                </div>
                <div>
                    <div class="premium-label">Hall</div>
                    <div class="premium-item">📍 ${escapeHtml(hall)}</div>
                </div>
            </div>

            <div class="premium-divider"></div>

            <div>
                <div class="premium-label">Access schedule</div>
                <div class="premium-days">${daysHtml}</div>
                ${timeHtml}
            </div>

            ${paymentHtml}
        </div>
    `;

        detailCard.classList.remove('hidden');
    }


    function handlePay() {
        if (tg?.showAlert) {
            tg.showAlert('Please contact the gym to make a payment.');
        }
    }

    async function loadSubscriptions() {
        const initData = tg?.initData || '';
        if (!initData) {
            skeletonBlock.classList.add('hidden');
            errorMsg.textContent = 'Please open this page from the Telegram bot.';
            errorBlock.classList.remove('hidden');
            hapticError();
            return;
        }

        try {
            const res = await fetch('/telegram/mini-app/my-subscriptions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ init_data: initData }),
            });

            const data = await res.json();
            skeletonBlock.classList.add('hidden');

            if (!res.ok || !data.ok) {
                errorMsg.textContent = data.message || 'Failed to load subscriptions.';
                errorBlock.classList.remove('hidden');
                hapticError();
                return;
            }

            allSubscriptions = data.subscriptions || [];
            mainBlock.classList.remove('hidden');

            if (allSubscriptions.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }

            if (allSubscriptions.length > 1) {
                const STATUS_LABEL = { active: 'Active', pending: 'In queue', frozen: 'Frozen', expired: 'Expired' };
                subSelect.innerHTML = allSubscriptions.map((s, i) =>
                    `<option value="${i}">${escapeHtml(s.name)} — ${STATUS_LABEL[s.status] || s.status}</option>`
                ).join('');
                dropdownWrap.classList.remove('hidden');
            }

            renderSubscription(allSubscriptions[0]);
            loadAttendance(allSubscriptions[0].id);

        } catch (e) {
            skeletonBlock.classList.add('hidden');
            errorMsg.textContent = 'Network error. Please try again.';
            errorBlock.classList.remove('hidden');
            hapticError();
        }
    }

    async function loadAttendance(subscriptionId) {
        const attSection = document.getElementById('att-section');
        const attList    = document.getElementById('att-list');

        attList.innerHTML = '<div class="att-loading">Loading visits…</div>';
        attSection.classList.remove('hidden');

        const initData = tg?.initData || '';
        try {
            const res  = await fetch('/telegram/mini-app/my-visits', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ init_data: initData, subscription_id: subscriptionId || null }),
            });
            const data = await res.json();

            if (!res.ok || !data.ok) {
                attList.innerHTML = '<div class="att-empty">Could not load visits.</div>';
                return;
            }

            if (!data.visits.length) {
                attList.innerHTML = '<div class="att-empty">No visits yet.</div>';
                return;
            }

            attList.innerHTML = data.visits.map(v => `
                <div class="att-item">
                    <div class="att-dot"></div>
                    <div class="att-body">
                        <div class="att-date">${escapeHtml(v.date || '—')}</div>
                        <div class="att-meta">
                            <span class="att-time">${escapeHtml(v.time || '')}</span>
                            ${v.hall ? `<span class="att-hall">· ${escapeHtml(v.hall)}</span>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

        } catch {
            attList.innerHTML = '<div class="att-empty">Network error.</div>';
        }
    }

    subSelect.addEventListener('change', () => {
        hapticSelection();
        const idx = parseInt(subSelect.value, 10);
        if (allSubscriptions[idx]) {
            renderSubscription(allSubscriptions[idx]);
            loadAttendance(allSubscriptions[idx].id);
        }
    });

    loadSubscriptions();
</script>
<x-telegram.bottom-nav active="my-pass" />
</body>
</html>
