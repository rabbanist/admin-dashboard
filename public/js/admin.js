/**
 * Admin Dashboard — Base JavaScript
 *
 * Published to public/vendor/admin-dashboard/js/.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ── Auto-dismiss flash alerts ────────────────────────
    document.querySelectorAll('.admin-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 300ms ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ── CSRF token injection for fetch requests ──────────
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (csrfToken) {
        window.adminFetch = (url, options = {}) => {
            return fetch(url, {
                ...options,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(options.headers || {}),
                },
            });
        };
    }
});
