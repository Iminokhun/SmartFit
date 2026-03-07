<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite('resources/css/telegram/mini-app.css')
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="toolbar">
            <a href="{{ route('telegram.mini-app.show') }}" class="back-link">Back</a>
            <h1 style="margin: 0;">Subscriptions</h1>
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

        <div id="plans-empty" class="kpi-value" style="margin-top: 10px;">Loading...</div>
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
                <button type="button" id="toggle-all-plans" class="back-link" style="margin-bottom:8px;">Show all plans</button>
                <div id="all-list" class="plans-list hidden"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const tg = window.Telegram?.WebApp;
    if (tg) {
        tg.ready();
        tg.expand();
    }

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

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatMoney(value) {
        const num = Number(value || 0);
        return num.toLocaleString('en-US');
    }

    function bindBuyButtons(scope = document) {
        scope.querySelectorAll('[data-buy-id]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const initData = tg?.initData || '';
                if (!initData) {
                    showMessage('Open this page only from Telegram bot.', false);
                    return;
                }

                const subscriptionId = Number(btn.dataset.buyId || 0);
                if (!subscriptionId) {
                    showMessage('Invalid subscription.', false);
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
                        showMessage(data.message || 'Failed to send invoice.', false);
                        return;
                    }

                    showMessage(data.message || 'Invoice sent.', true);
                } catch (_) {
                    showMessage('Network error while sending invoice.', false);
                } finally {
                    btn.disabled = false;
                    btn.textContent = oldText;
                }
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
                        <button type="button" class="plan-cta-btn" data-buy-id="${plan.id}">Choose</button>
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
            showMessage('Open this page only from Telegram bot.', false);
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
            plansEmpty.textContent = 'No data';
            return;
        }

        rawPlans = data.plans || [];
        fillActivityOptions(data.activities || []);
        applyFilters();
    }

    searchInput.addEventListener('input', applyFilters);
    activityFilter.addEventListener('change', applyFilters);
    visitsFilter.addEventListener('change', applyFilters);
    toggleAllPlansBtn.addEventListener('click', () => {
        allPlansVisible = !allPlansVisible;
        allList.classList.toggle('hidden', !allPlansVisible);
        toggleAllPlansBtn.textContent = allPlansVisible ? 'Hide all plans' : 'Show all plans';
    });

    loadCatalog().catch(() => {
        showMessage('Failed to initialize Mini App.', false);
        plansEmpty.textContent = 'No data';
    });
</script>
</body>
</html>
