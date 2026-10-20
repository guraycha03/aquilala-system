/*
 * Web Push opt-in (Settings → Notifications).
 *
 * Flow: fetch the VAPID public key → ask permission → subscribe this
 * device via PushManager → register the subscription with the server.
 * Disabling reverses both sides. If permission was already granted on a
 * previous visit, the page silently re-subscribes so the server list stays
 * current (endpoints rotate).
 *
 * Every step fails quiet: no keys configured, an unsupported browser, a
 * denied prompt or a network error all just leave the toggle in its
 * neutral state — the app itself is never affected.
 */
(function () {
    'use strict';

    var cfg = {
        keyUrl:       '/push/public-key',
        subscribeUrl: '/push/subscribe',
        unsubscribeUrl: '/push/unsubscribe'
    };

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // applicationServerKey arrives base64url; PushManager wants bytes.
    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var output = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    function supported() {
        return 'serviceWorker' in navigator &&
               'PushManager' in window &&
               'Notification' in window &&
               window.isSecureContext;
    }

    // iOS Safari only exposes push to an installed (home-screen) PWA.
    function isIos() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    function isStandalone() {
        return window.navigator.standalone === true ||
               window.matchMedia('(display-mode: standalone)').matches;
    }

    function fetchKey() {
        return fetch(cfg.keyUrl, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (res) { return res.json(); })
          .then(function (data) { return data && data.key ? data.key : null; });
    }

    function getRegistration() {
        return navigator.serviceWorker.ready;
    }

    function registerWithServer(subscription) {
        var json = subscription.toJSON();
        return fetch(cfg.subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: { p256dh: json.keys.p256dh, auth: json.keys.auth }
            })
        });
    }

    // Subscribe this device and tell the server. Returns the subscription.
    function subscribe(key) {
        return getRegistration().then(function (registration) {
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(key)
            });
        }).then(function (subscription) {
            return registerWithServer(subscription).then(function () {
                return subscription;
            });
        });
    }

    // Public API used by the Settings toggle.
    window.AquilalaPush = {
        supported: function () {
            return supported();
        },

        // True when this device should be nudged to install on iOS.
        needsIosInstall: function () {
            return isIos() && !isStandalone();
        },

        // Current state: 'enabled' | 'off' | 'unavailable'.
        state: function () {
            if (!supported()) return Promise.resolve('unavailable');
            if (Notification.permission === 'denied') return Promise.resolve('unavailable');
            if (Notification.permission === 'default') return Promise.resolve('off');

            return getRegistration().then(function (registration) {
                return registration.pushManager.getSubscription();
            }).then(function (sub) {
                if (sub) return 'enabled';
                // FCM-only mode (Novu push, no VAPID keys on this install):
                // the device counts as enrolled while it holds an FCM token.
                if (window.AquilalaNovuPush && window.AquilalaNovuPush.enrolled()) return 'enabled';
                return 'off';
            }).catch(function () {
                return 'off';
            });
        },

        // User pressed Enable: ask permission, subscribe, register server-side.
        enable: function () {
            if (!supported()) return Promise.reject(new Error('unsupported'));

            return Notification.requestPermission().then(function (permission) {
                if (permission !== 'granted') {
                    return Promise.reject(new Error('denied'));
                }
                return fetchKey().then(function (key) {
                    if (!key) {
                        // No VAPID keys on this install: with Novu/FCM set up
                        // the just-granted permission alone is enough to
                        // enroll this device for browser push instead.
                        if (window.AquilalaNovuPush) {
                            return window.AquilalaNovuPush.enroll(false).then(function (ok) {
                                if (ok) return null;
                                return Promise.reject(new Error('not-configured'));
                            });
                        }
                        return Promise.reject(new Error('not-configured'));
                    }
                    return subscribe(key).then(function (subscription) {
                        // VAPID registered — mirror the device to Novu/FCM as
                        // well (silent, and harmless if it fails).
                        if (window.AquilalaNovuPush) window.AquilalaNovuPush.enroll(false);
                        return subscription;
                    });
                });
            });
        },

        // User pressed Disable: unregister server-side, then unsubscribe.
        disable: function () {
            if (!supported()) return Promise.resolve();

            return getRegistration().then(function (registration) {
                return registration.pushManager.getSubscription();
            }).then(function (sub) {
                // THIS device stops receiving either way — revoke its FCM
                // token (Novu push) beside the VAPID subscription below.
                var novuOff = window.AquilalaNovuPush ? window.AquilalaNovuPush.revoke() : Promise.resolve();

                if (!sub) return novuOff;
                var endpoint = sub.endpoint;
                return fetch(cfg.unsubscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ endpoint: endpoint })
                }).then(function () {
                    return sub.unsubscribe();
                }).then(function () {
                    return novuOff;
                });
            }).catch(function () {
                // Already gone server-side or offline — treat as disabled.
            });
        },

        // Silent keep-alive: permission already granted → make sure the
        // subscription is still current on the server. Called on Settings
        // load; never prompts.
        sync: function () {
            if (!supported() || Notification.permission !== 'granted') {
                return Promise.resolve();
            }

            return fetchKey().then(function (key) {
                if (!key) {
                    // FCM-only setup: keep this device's Novu token fresh.
                    if (window.AquilalaNovuPush) window.AquilalaNovuPush.enroll(false);
                    return null;
                }
                return getRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (sub) {
                        if (sub) return registerWithServer(sub);
                        return subscribe(key);
                    });
                }).then(function (result) {
                    if (window.AquilalaNovuPush) window.AquilalaNovuPush.enroll(false);
                    return result;
                });
            }).catch(function () {
                // Quiet by design.
            });
        }
    };
})();
