<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFit Staff Scanner</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/telegram/staff-scan.css', 'resources/js/telegram/telegram-utils.js'])
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Staff QR Scanner</h1>
        <p>Use this app from Staff Telegram bot to scan customer QR and register check-in.</p>
    </div>

    <div id="link-card" class="card hidden">
        <h1>Link Staff Account</h1>
        <p>Login with manager/admin account from ERP panel.</p>
        <input id="staff-email" class="input" type="email" placeholder="manager@example.com">
        <input id="staff-password" class="input" type="password" placeholder="Password">
        <button id="btn-link" class="btn">Login and Link</button>
    </div>

    <div id="scan-card" class="card hidden">
        <h1>Ready to Scan</h1>
        <p id="staff-meta" class="staff-meta"></p>
        <label for="schedule-select" class="mt-3">Schedule</label>
        <select id="schedule-select" class="input"></select>
        <button id="btn-scan" class="btn">Open Scanner</button>
        <button id="btn-refresh" class="btn secondary">Refresh Status</button>
    </div>

    <div id="selection-card" class="card hidden">
        <h1>Select Subscription</h1>
        <p>Customer has multiple active subscriptions.</p>
        <div id="option-list" class="option-list"></div>
    </div>

    <div id="msg" class="msg"></div>
</div>

<script>
    const tg = window.tg;

    const linkCard = document.getElementById('link-card');
    const scanCard = document.getElementById('scan-card');
    const selectionCard = document.getElementById('selection-card');
    const optionList = document.getElementById('option-list');
    const staffMeta = document.getElementById('staff-meta');
    const msg = document.getElementById('msg');
    const btnLink = document.getElementById('btn-link');
    const btnScan = document.getElementById('btn-scan');
    const btnRefresh = document.getElementById('btn-refresh');
    const emailInput = document.getElementById('staff-email');
    const passwordInput = document.getElementById('staff-password');
    const scheduleSelect = document.getElementById('schedule-select');


    let lastQrPayload = null;

    function showMsg(text, ok) {
        msg.className = 'msg ' + (ok ? 'ok' : 'err');
        msg.textContent = text;
    }

    function clearMsg() {
        msg.className = 'msg';
        msg.textContent = '';
    }

    function getInitData() {
        return tg?.initData || '';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });

        let data = {};
        try { data = await res.json(); } catch (_) {}

        if (!res.ok) {
            throw new Error(data.message || 'Request failed.');
        }

        return data;
    }

    function getSelectedScheduleId() {
        const value = scheduleSelect?.value || '';
        return value ? Number(value) : null;
    }

    async function loadSchedules() {
        if (!scheduleSelect) return;
        scheduleSelect.innerHTML = '';
        const initData = getInitData();
        if (!initData) return;

        try {
            const data = await postJson('/telegram/staff/scan/schedules', { init_data: initData });
            const list = data.schedules || [];

            const optAny = document.createElement('option');
            optAny.value = '';
            optAny.textContent = 'Any schedule (auto)';
            scheduleSelect.appendChild(optAny);

            for (const item of list) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label;
                scheduleSelect.appendChild(opt);
            }
        } catch (e) {
            // ignore schedule load errors for now
        }
    }
    async function loadMe() {
        clearMsg();
        const initData = getInitData();
        if (!initData) {
            showMsg('Open from Telegram Staff bot only.', false);
            return;
        }

        try {
            const data = await postJson('/telegram/staff/scan/me', { init_data: initData });

            if (!data.linked) {
                linkCard.classList.remove('hidden');
                scanCard.classList.add('hidden');
                selectionCard.classList.add('hidden');
                return;
            }

            linkCard.classList.add('hidden');
            scanCard.classList.remove('hidden');
            selectionCard.classList.add('hidden');
            staffMeta.textContent = `${data.staff?.name || 'Staff'} (${data.staff?.role || '-'})`;
            await loadSchedules();
        } catch (e) {
            showMsg(e.message || 'Failed to load scanner state.', false);
        }
    }

    async function linkStaff() {
        clearMsg();
        const initData = getInitData();
        if (!initData) {
            showMsg('Open from Telegram Staff bot only.', false);
            return;
        }

        try {
            const data = await postJson('/telegram/staff/scan/link', {
                init_data: initData,
                email: emailInput.value.trim(),
                password: passwordInput.value,
            });

            showMsg(data.message || 'Linked successfully.', true);
            await loadMe();
        } catch (e) {
            showMsg(e.message || 'Link failed.', false);
        }
    }

    async function consumeForSelection(customerSubscriptionId) {
        clearMsg();
        try {
            const data = await postJson('/telegram/staff/scan/consume', {
                init_data: getInitData(),
                qr_payload: lastQrPayload,
                customer_subscription_id: customerSubscriptionId,
                schedule_id: getSelectedScheduleId(),
            });

            selectionCard.classList.add('hidden');
            optionList.innerHTML = '';
            showMsg(data.message || 'Check-in registered.', true);
        } catch (e) {
            showMsg(e.message || 'Consume failed.', false);
        }
    }

    function renderOptions(options) {
        optionList.innerHTML = '';
        for (const item of options) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'option-btn';
            btn.innerHTML = `
                <div><strong>${item.subscription_name}</strong></div>
                <div class="hint mt-1">Valid: ${item.start_date} - ${item.end_date}</div>
                <div class="hint">Visits: ${item.remaining_visits_label}</div>
            `;
            btn.addEventListener('click', () => consumeForSelection(item.customer_subscription_id));
            optionList.appendChild(btn);
        }

        selectionCard.classList.remove('hidden');
    }

    async function onQrScanned(text) {
        clearMsg();
        lastQrPayload = text;

        try {
            const data = await postJson('/telegram/staff/scan/resolve', {
                init_data: getInitData(),
                qr_payload: text,
                schedule_id: getSelectedScheduleId(),
            });

            if (data.requires_selection) {
                renderOptions(data.options || []);
                return;
            }

            selectionCard.classList.add('hidden');
            optionList.innerHTML = '';
            showMsg(data.message || 'Check-in registered.', true);
        } catch (e) {
            showMsg(e.message || 'Resolve failed.', false);
        }
    }

    function openScanner() {
        clearMsg();
        if (!tg || typeof tg.showScanQrPopup !== 'function') {
            showMsg('Telegram scanner API is not available on this device.', false);
            return;
        }

        tg.showScanQrPopup({ text: 'Scan SmartFit customer QR' }, (text) => {
            if (typeof tg.closeScanQrPopup === 'function') {
                tg.closeScanQrPopup();
            }

            onQrScanned(text);
            return true;
        });
    }
    btnLink?.addEventListener('click', linkStaff);
    btnScan?.addEventListener('click', openScanner);
    btnRefresh?.addEventListener('click', loadMe);


    loadMe();
</script>
</body>
</html>
