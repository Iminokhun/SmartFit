/**
 * Shared Telegram Mini App utilities.
 * Loaded before every page script — exposes tg, haptic, and helper functions
 * as window globals so page-level scripts can use them without redeclaring.
 */

window.tg     = window.Telegram?.WebApp ?? null;
window.haptic = window.tg?.HapticFeedback ?? null;

if (window.tg) {
    window.tg.ready();
    window.tg.expand();
}

/* ── Haptic helpers ── */
function hapticSelection() { window.haptic?.selectionChanged(); }
function hapticSuccess()   { window.haptic?.notificationOccurred('success'); }
function hapticError()     { window.haptic?.notificationOccurred('error'); }
function hapticLight()     { window.haptic?.impactOccurred('light'); }
function hapticMedium()    { window.haptic?.impactOccurred('medium'); }

/* ── Dialog helpers ── */
function tgAlert(text) {
    if (window.tg?.showAlert) {
        window.tg.showAlert(text);
    } else {
        alert(text);
    }
}

function tgConfirm(text, callback) {
    if (window.tg?.showConfirm) {
        window.tg.showConfirm(text, callback);
    } else {
        callback(confirm(text));
    }
}

/* ── String / number helpers ── */
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

window.hapticSelection = hapticSelection;
window.hapticSuccess   = hapticSuccess;
window.hapticError     = hapticError;
window.hapticLight     = hapticLight;
window.hapticMedium    = hapticMedium;
window.tgAlert         = tgAlert;
window.tgConfirm       = tgConfirm;
window.escapeHtml      = escapeHtml;
window.formatMoney     = formatMoney;
