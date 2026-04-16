<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/telegram/mini-app.css', 'resources/js/telegram/telegram-utils.js'])
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="toolbar">
            <h1>Subscriptions</h1>
        </div>
        <p class="muted">Choose a plan to purchase.</p>

        <div id="msg" class="msg"></div>

        <div class="filters">
            <input id="search" type="text" placeholder="Search plan or activity...">
            <select id="activity-filter">
                <option value="">All activities</option>
            </select>
            <select id="visits-filter">
                <option value="">All visits types</option>
                <option value="limited">Limited</option>
                <option value="unlimited">Unlimited</option>
            </select>
        </div>

        <div id="plans-empty" class="kpi-value mt-2">Loading...</div>
        <div id="sections" class="hidden">
            <div class="section-block">
                <div class="section-head">Popular</div>
                <div id="popular-list" class="carousel-list"></div>
            </div>
            <div class="section-block">
                <div class="section-head">New</div>
                <div id="new-list" class="carousel-list"></div>
            </div>
            <div class="section-block">
                <div class="section-head">All Plans</div>
                <button type="button" id="toggle-all-plans" class="back-link mb-2">Show all plans</button>
                <div id="all-list" class="plans-list hidden"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const tg = window.tg;

    if (tg?.BackButton) tg.BackButton.hide();

    const msg = document.getElementById('msg');
    const searchInput = document.getElementById('search');
    const activityFilter = document.getElementById('activity-filter');
    const visitsFilter = document.getElementById('visits-filter');
    const plansEmpty = document.getElementById('plans-empty');
    const sections = document.getElementById('sections');
    const popularList = document.getElementById('popular-list');
    const newList = document.getElementById('new-list');
    const allList = document.getElementById('all-list');
    const toggleAllPlansBtn = document.getElementById('toggle-all-plans');

    let rawPlans = [];
    let allPlansVisible = false;

    function showMessage(text, ok) {
        msg.className = 'msg ' + (ok ? 'ok' : 'err');
        msg.textContent = text;
    }

    async function sendInvoice(btn, subscriptionId, planName, finalPrice) {
        const initData = tg?.initData || '';
        if (!initData) {
            tgAlert('Open this page only from Telegram bot.');
            hapticError();
            return;
        }

        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = 'Sending...';

        try {
            const res = await fetch('/telegram/mini-app/purchase/invoice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    init_data: initData,
                    subscription_id: subscriptionId,
                }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                hapticError();
                showMessage(data.message || 'Failed to send invoice.', false);
                return;
            }

            hapticSuccess();
            showMessage(data.message || 'Invoice sent to your Telegram chat.', true);
        } catch (_) {
            hapticError();
            tgAlert('Network error while sending invoice.');
        } finally {
            btn.disabled = false;
            btn.textContent = oldText;
        }
    }

    function bindBuyButtons(scope = document) {
        scope.querySelectorAll('[data-buy-id]').forEach((btn) => {
            btn.addEventListener('click', () => {
                hapticMedium();

                const initData = tg?.initData || '';
                if (!initData) {
                    tgAlert('Open this page only from Telegram bot.');
                    hapticError();
                    return;
                }

                const subscriptionId = Number(btn.dataset.buyId || 0);
                const planName = btn.dataset.planName || 'this plan';
                const finalPrice = btn.dataset.finalPrice || '0';

                if (!subscriptionId) {
                    showMessage('Invalid subscription.', false);
                    hapticError();
                    return;
                }

                tgConfirm(
                    `Buy "${planName}" for ${formatMoney(finalPrice)} UZS?`,
                    (confirmed) => {
                        if (confirmed) {
                            sendInvoice(btn, subscriptionId, planName, finalPrice);
                        }
                    }
                );
            });
        });
    }

    function planCardHtml(plan, options = {}) {
            const compact = Boolean(options.compact);
            const badge = options.badge ? String(options.badge) : '';
            const visits = plan.visits_limit === null ? 'Unlimited' : `${plan.visits_limit} visits`;
            const discount = Number(plan.discount || 0);
            const compactClass = compact ? ' plan-card-compact' : '';
            const badgeHtml = badge
                ? `<span class="plan-badge">${escapeHtml(badge)}</span>`
                : '';

            return `
                <div class="plan-card${compactClass}">
                    <div class="plan-head">
                        <div class="plan-title">${escapeHtml(plan.name)}</div>
                        ${badgeHtml}
                    </div>
                    <div class="plan-chip-wrap">
                        <span class="plan-chip">${escapeHtml(visits)}</span>
                        <span class="plan-chip">${escapeHtml(plan.duration_days)} days</span>
                        <span class="plan-chip">${escapeHtml(plan.activity)}</span>
                    </div>
                    <div class="plan-price-row">
                        <div>
                            ${discount > 0 ? `<div class="plan-price-old">UZS ${formatMoney(plan.price)}</div>` : ''}
                            <div class="plan-price-main">${formatMoney(plan.final_price)} <span class="plan-price-currency">UZS</span></div>
                        </div>
                        <button
                            type="button"
                            class="plan-cta-btn"
                            data-buy-id="${plan.id}"
                            data-plan-name="${escapeHtml(plan.name)}"
                            data-final-price="${escapeHtml(String(plan.final_price))}"
                        >Choose</button>
                    </div>
                </div>
            `;
    }

    function renderPlans(plans) {
        if (!plans.length) {
            plansEmpty.textContent = 'No subscriptions found';
            sections.classList.add('hidden');
            popularList.innerHTML = '';
            newList.innerHTML = '';
            allList.innerHTML = '';
            return;
        }

        const popular = [...plans]
            .sort((a, b) => Number(b.final_price || 0) - Number(a.final_price || 0))
            .slice(0, 8);

        const newest = [...plans]
            .sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
            .slice(0, 8);

        popularList.innerHTML = popular.map((plan) => planCardHtml(plan, { compact: true, badge: 'Popular' })).join('');
        newList.innerHTML = newest.map((plan) => planCardHtml(plan, { compact: true, badge: 'New' })).join('');
        allList.innerHTML = plans.map((plan) => planCardHtml(plan, { compact: false })).join('');
        allList.classList.toggle('hidden', !allPlansVisible);
        toggleAllPlansBtn.textContent = allPlansVisible ? 'Hide all plans' : 'Show all plans';

        plansEmpty.textContent = '';
        sections.classList.remove('hidden');
        bindBuyButtons(sections);
    }

    function applyFilters() {
        const search = searchInput.value.trim().toLowerCase();
        const activity = activityFilter.value.trim().toLowerCase();
        const visits = visitsFilter.value.trim();

        const filtered = rawPlans.filter((plan) => {
            const matchSearch = search === ''
                || plan.name.toLowerCase().includes(search)
                || plan.activity.toLowerCase().includes(search);

            const matchActivity = activity === '' || plan.activity.toLowerCase() === activity;

            let matchVisits = true;
            if (visits === 'limited') {
                matchVisits = plan.visits_limit !== null;
            } else if (visits === 'unlimited') {
                matchVisits = plan.visits_limit === null;
            }

            return matchSearch && matchActivity && matchVisits;
        });

        renderPlans(filtered);
    }

    function fillActivityOptions(activities) {
        activities.forEach((activity) => {
            const option = document.createElement('option');
            option.value = activity;
            option.textContent = activity;
            activityFilter.appendChild(option);
        });
    }

    async function loadCatalog() {
        const initData = tg?.initData || '';
        if (!initData) {
            tgAlert('Open this page only from Telegram bot.');
            hapticError();
            plansEmpty.textContent = 'No data';
            return;
        }

        const res = await fetch('/telegram/mini-app/catalog', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ init_data: initData }),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            showMessage(data.message || 'Failed to load subscriptions.', false);
            hapticError();
            plansEmpty.textContent = 'No data';
            return;
        }

        rawPlans = data.plans || [];
        fillActivityOptions(data.activities || []);
        applyFilters();
    }

    searchInput.addEventListener('input', () => {
        hapticSelection();
        applyFilters();
    });
    activityFilter.addEventListener('change', () => {
        hapticSelection();
        applyFilters();
    });
    visitsFilter.addEventListener('change', () => {
        hapticSelection();
        applyFilters();
    });
    toggleAllPlansBtn.addEventListener('click', () => {
        hapticSelection();
        allPlansVisible = !allPlansVisible;
        allList.classList.toggle('hidden', !allPlansVisible);
        toggleAllPlansBtn.textContent = allPlansVisible ? 'Hide all plans' : 'Show all plans';
    });

    loadCatalog().catch(() => {
        hapticError();
        tgAlert('Failed to initialize Mini App.');
        plansEmpty.textContent = 'No data';
    });
</script>
<x-telegram.bottom-nav active="store" />
</body>
</html>
