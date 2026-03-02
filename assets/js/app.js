/**
 * Advanced URL Shortener — Client-side utilities.
 */

'use strict';

/* ── Flash alerts: auto-dismiss after 5s ──────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 5000);
    });
});

/* ── Generic clipboard copy ───────────────────────────────── */
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('btn-primary');
        setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('btn-primary');
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);

        const original = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = original, 2000);
    });
}

/* ── Confirm dangerous actions ────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
