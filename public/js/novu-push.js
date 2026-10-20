/*
 * Firebase Cloud Messaging enrolment — Novu's web push provider.
 *
 * When Firebase is configured (window.AquilalaNovuPushConfig, injected by
 * the layout from .env), this browser gets an FCM device token and posts it
 * to /push/novu-token, which stores it and syncs the account's full token
 * list to Novu. Novu's Push step then delivers outside-the-app notices.
 *
 * Enrolment is ALWAYS silent: it reuses a notification permission that is
 * already granted — the actual permission prompt stays with the Settings
 * toggle (push-subscribe.js), so nothing ever pops up unprompted. Every
 * step fails quiet: an unsupported browser, a blocked CDN or an offline
 * server just leave the device unenrolled; the app is never affected.
 */
(function () {
    'use strict';

    var cfg = window.AquilalaNovuPushConfig;
    if (!cfg || !cfg.firebase || !cfg.firebase.apiKey) return;

    var FB_VERSION = '13.0.0';
    var TOKEN_KEY = 'aquilala_fcm_token';
    var loading = null;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('script-load-failed')); };
            document.head.appendChild(s);
        });
    }

    // Firebase compat SDKs from the official CDN — loaded once, on demand
    // (never on page load unless there is actually a permission to enroll
    // with). The same single service worker we already run is reused.
    function firebase() {
        if (loading) return loading;
        var base = 'https://www.gstatic.com/firebasejs/' + FB_VERSION + '/';
        loading = loadScript(base + 'firebase-app-compat.js')
            .then(function () { return loadScript(base + 'firebase-messaging-compat.js'); })
            .then(function () {
                if (!window.firebase) throw new Error('firebase-missing');
                if (!window.firebase.apps.length) window.firebase.initializeApp(cfg.firebase);
                return window.firebase;
            })
            .catch(function (err) { loading = null; throw err; });
        return loading;
    }

    function post(body) {
        return fetch(cfg.tokenUrl || '/push/novu-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (res) { return res.ok; });
    }

    function storedToken() {
        try { return localStorage.getItem(TOKEN_KEY); } catch (e) { return null; }
    }

    // askPermission=true only from the Settings toggle (it has just shown
    // the browser's own prompt flow); every other path stays silent.
    function enroll(askPermission) {
        if (!('serviceWorker' in navigator) || !('Notification' in window)) {
            return Promise.resolve(false);
        }

        var permission = askPermission
            ? Notification.requestPermission()
            : Promise.resolve(Notification.permission);

        return permission.then(function (state) {
            if (state !== 'granted') return false;

            return navigator.serviceWorker.ready.then(function (reg) {
                return firebase().then(function (fb) {
                    return fb.messaging().getToken({
                        vapidKey: cfg.firebase.vapidKey || undefined,
                        serviceWorkerRegistration: reg
                    });
                });
            }).then(function (token) {
                if (!token) return false;
                return post({ token: token }).then(function (ok) {
                    if (ok) {
                        try { localStorage.setItem(TOKEN_KEY, token); } catch (e) {}
                    }
                    return ok;
                });
            });
        }).catch(function () {
            return false;
        });
    }

    function revoke() {
        var token = storedToken();
        if (!token) return Promise.resolve();

        return post({ token: token, revoke: true }).then(function () {
            try { localStorage.removeItem(TOKEN_KEY); } catch (e) {}
            return firebase().then(function (fb) {
                try { return fb.messaging().deleteToken(token); } catch (e) { return null; }
            });
        }).catch(function () {
            // Quiet by design — the row may already be gone.
        });
    }

    window.AquilalaNovuPush = {
        configured: function () { return true; },
        enrolled: function () { return !!storedToken(); },
        enroll: function (askPermission) { return enroll(askPermission === true); },
        revoke: revoke
    };

    // Already-granted devices (this browser opted in before) enroll on load.
    // Never prompts — the Settings toggle owns the permission ask.
    if (Notification.permission === 'granted') {
        enroll(false);
    }
})();
