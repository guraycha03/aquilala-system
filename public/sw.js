/*
 * Aquilala PWA service worker.
 *
 * Strategy:
 *   - Static assets (css/js/images/fonts): cache-first, filled lazily — the
 *     app's assets are same-origin and stable, so a cached copy is always
 *     safe and makes repeat launches instant.
 *   - HTML pages / Turbo visits: network-first — while online you always get
 *     a fresh server-rendered page (no staleness, no auth surprises); only
 *     the successful, non-redirected responses are kept, and they are used
 *     purely as the offline fallback.
 *   - Anything else (API/data fetches): untouched, straight to the network.
 *
 * Caches are versioned; the page cache is also cleared on logout by
 * pwa-register.js so one user's screens are never left behind for the next.
 *
 * Push: the server's pushes arrive here even when the app is closed — the
 * payload carries the notification text plus the in-app page to open, and a
 * click focuses an already-open window (navigating it) or opens a fresh one.
 */
const VERSION = 'aquilala-v5';
const STATIC_CACHE = VERSION + '-static';
const PAGE_CACHE = VERSION + '-pages';
const PAGE_CACHE_LIMIT = 60;

// A fetch with no timeout is a hang: if the device sleeps / the connection
// drops while the app sits idle, the pending navigation request can stay
// unresolved forever and the whole page looks frozen until a manual refresh.
// Bound every network wait so the strategies below can always fall back.
const PAGE_TIMEOUT_MS = 12000;
const STATIC_TIMEOUT_MS = 12000;

function fetchWithTimeout(request, timeoutMs) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('network-timeout')), timeoutMs);
        fetch(request).then(
            (res) => { clearTimeout(timer); resolve(res); },
            (err) => { clearTimeout(timer); reject(err); }
        );
    });
}

const PRECACHE = [
    '/offline.html',
    '/manifest.json',
    '/favicon.ico',
    '/images/logo.png',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
    '/images/pwa/icon-192-maskable.png',
    '/images/pwa/icon-512-maskable.png',
    '/images/pwa/apple-touch-icon.png'
];

const STATIC_EXT = /\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf|eot|map)$/i;

self.addEventListener('install', (event) => {
    // New worker takes over immediately: push handlers only exist in the
    // new version, so waiting for every tab to close would delay notifications.
    self.skipWaiting();
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .catch(() => undefined)
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.indexOf(VERSION) !== 0)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CLEAR_PAGES') {
        caches.delete(PAGE_CACHE);
    }
});

// ── Web Push ──────────────────────────────────────────────────────
// A push is shown by the service worker itself, so the notification
// appears even when the app is closed. The payload is our own shape:
//   { title, body, url, tag }
// Parsed tolerantly so a slightly different envelope still lands the
// user on the notice's page instead of being dropped.
self.addEventListener('push', (event) => {
    let data = { title: 'Aquilala', body: '', url: '/', tag: 'aquilala' };

    if (event.data) {
        try {
            const parsed = event.data.json();
            if (parsed && typeof parsed === 'object') {
                const notif = parsed.notification || {};
                const extra = parsed.data || {};
                data = {
                    title: parsed.title || notif.title || data.title,
                    body:  parsed.body  || notif.body  || data.body,
                    url:   parsed.url   || parsed.link || extra.url || extra.link || data.url,
                    tag:   parsed.tag   || extra.tag   || data.tag
                };
            }
        } catch (err) {
            // Plain-text push — show it verbatim rather than dropping it.
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body:  data.body,
            icon:  '/images/pwa/icon-192.png',
            badge: '/images/pwa/icon-192-maskable.png',
            tag:   data.tag,
            renotify: false,
            data:  { url: data.url }
        })
    );
});

// Clicking the notification opens (or reuses) a window on the target page —
// the "opens straight to the page in the app" behaviour.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(
        (event.notification.data && event.notification.data.url) || '/',
        self.location.origin
    ).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            // Reuse the first app window already open: focus it and, when we
            // can, navigate it straight to the notification's page.
            for (const client of windows) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.focus();
                    if ('navigate' in client) {
                        return client.navigate(target).catch(() => undefined);
                    }
                    return undefined;
                }
            }

            return clients.openWindow(target);
        })
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    const isNavigate = request.mode === 'navigate' || request.destination === 'document';
    const acceptsHtml = (request.headers.get('accept') || '').indexOf('text/html') !== -1;

    if (isNavigate || acceptsHtml) {
        event.respondWith(pageStrategy(request, isNavigate));
        return;
    }

    if (STATIC_EXT.test(url.pathname)) {
        event.respondWith(staticStrategy(request));
    }
    // Everything else (JSON endpoints, API calls, cross-origin data) passes
    // through untouched so online behaviour is identical to no service worker.
});

async function pageStrategy(request, isNavigate) {
    try {
        const fresh = await fetchWithTimeout(request, PAGE_TIMEOUT_MS);
        const contentType = (fresh.headers.get('content-type') || '').toLowerCase();

        const cacheable =
            fresh.status === 200 &&
            !fresh.redirected &&
            contentType.indexOf('text/html') !== -1 &&
            contentType.indexOf('turbo-stream') === -1 &&
            new URL(request.url).pathname.indexOf('/logout') === -1;

        if (cacheable) {
            const cache = await caches.open(PAGE_CACHE);
            await cache.put(request, fresh.clone());

            const keys = await cache.keys();
            if (keys.length > PAGE_CACHE_LIMIT) {
                await caches.delete(PAGE_CACHE);
            }
        }
        return fresh;
    } catch (err) {
        const cached = await caches.match(request);
        if (cached) return cached;

        if (isNavigate) {
            const offline = await caches.match('/offline.html');
            if (offline) return offline;
        }
        return new Response('You are offline.', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

async function staticStrategy(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const fresh = await fetchWithTimeout(request, STATIC_TIMEOUT_MS);
        if (fresh && fresh.status === 200) {
            const cache = await caches.open(STATIC_CACHE);
            await cache.put(request, fresh.clone());
        }
        return fresh;
    } catch (err) {
        return new Response('', { status: 504, statusText: 'Offline' });
    }
}
