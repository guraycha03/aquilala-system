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
 */
const VERSION = 'aquilala-v2';
const STATIC_CACHE = VERSION + '-static';
const PAGE_CACHE = VERSION + '-pages';
const PAGE_CACHE_LIMIT = 60;

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
        const fresh = await fetch(request);
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
        const fresh = await fetch(request);
        if (fresh && fresh.status === 200) {
            const cache = await caches.open(STATIC_CACHE);
            await cache.put(request, fresh.clone());
        }
        return fresh;
    } catch (err) {
        return new Response('', { status: 504, statusText: 'Offline' });
    }
}
