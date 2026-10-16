/*
 * PWA bootstrap: registers the service worker and clears cached pages on
 * logout so the next person on the device never sees the previous user's
 * screens while offline. Loaded with defer from every layout head.
 */
(function () {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function () {
            // Unsupported context (e.g. plain http on a LAN address) — the
            // app keeps working normally without offline/install support.
        });
    });

    function clearCachedPages() {
        try {
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_PAGES' });
            }
        } catch (e) { /* noop */ }

        try {
            if (window.caches) {
                caches.keys().then(function (keys) {
                    keys.filter(function (k) { return k.indexOf('-pages') !== -1; })
                        .forEach(function (k) { caches.delete(k); });
                }).catch(function () { /* noop */ });
            }
        } catch (e) { /* noop */ }
    }

    // Logout may be a link (GET via Turbo) or a form submit (POST).
    document.addEventListener('click', function (event) {
        var el = event.target && event.target.closest
            ? event.target.closest('a[href*="/logout"]')
            : null;
        if (el) clearCachedPages();
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form && form.action && form.action.indexOf('/logout') !== -1) clearCachedPages();
    }, true);
})();
