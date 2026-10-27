/*
 * Aquilala PWA service worker.
 *
 * Strategy:
 *   - Static assets (css/js/images/fonts): cache-first, filled lazily — the
 *     app's assets are same-origin and stable, so a cached copy is always
 *     safe and makes repeat launches instant.
 *   - HTML pages / Turbo visits: network-first, with ONE retry — while online
 *     you always get a fresh server-rendered page (no staleness, no auth
 *     surprises), and a single failed or timed-out request no longer drops you
 *     into a dead end. Only the successful, non-redirected responses
 *     are kept, and they are used purely as the offline fallback.
 *   - Anything else (API/data fetches): untouched, straight to the network.
 *
 * When the network answers "no", nothing takes the screen over:
 *   - a Turbo visit or XHR is simply failed, so the page already on screen
 *     stays exactly as it is and js/net-status.js reports it in the strip at
 *     the top of the header;
 *   - a browser navigation has already left that page, so it is redirected
 *     to a page that is provably cached — the dashboard they had open, or the
 *     sign-in screen captured at install time — never a dead-end notice.
 * offline.html remains only for a browser with no cache whatsoever, which
 * the shell fallback above makes effectively unreachable in normal use.
 *
 * Every page fetch outcome is also posted to the open windows as
 * { type: 'AQ_NET', ok } so js/net-status.js can raise its top-of-screen
 * connection strip the moment a navigation fails — instead of the user
 * discovering it only when the next click does nothing.
 *
 * Caches are versioned; the page cache is also cleared on logout by
 * pwa-register.js so one user's screens are never left behind for the next.
 *
 * Push: the server's pushes arrive here even when the app is closed — the
 * payload carries the notification text plus the in-app page to open, and a
 * click focuses an already-open window (navigating it) or opens a fresh one.
 */
const VERSION = 'aquilala-v12';
const STATIC_CACHE = VERSION + '-static';
const PAGE_CACHE = VERSION + '-pages';
const SHELL_CACHE = VERSION + '-shell';
const PAGE_CACHE_LIMIT = 60;

// Captured once at install and deliberately never removed by CLEAR_PAGES
// (logout): a public screen with no user data on it, so a navigation that
// finds an empty page cache still lands inside something that looks like the
// system instead of a dead-end notice.
const SHELL_URL = '/login';

// Where a browser navigation goes when its own URL is not cached. First
// entry that is actually in the cache wins, so a lost user lands on a screen
// they recognise. `/dashboard` is a role-based redirect and is therefore
// rarely stored, but it costs nothing to offer.
const FALLBACK_PAGES = [
    '/farmer/dashboard',
    '/admin/dashboard',
    '/store/dashboard',
    '/dashboard',
    '/login'
];

// A fetch with no timeout is a hang: if the device sleeps / the connection
// drops while the app sits idle, the pending navigation request can stay
// unresolved forever and the whole page looks frozen until a manual refresh.
// Bound every network wait so the strategies below can always fall back.
const PAGE_TIMEOUT_MS = 12000;
const STATIC_TIMEOUT_MS = 12000;

// Gap before the single retry below. Deliberately short: on a device with no
// connection at all the first attempt rejects almost immediately, so the
// retry is free; on a slow link it is the difference between loading the page
// and being handed the offline takeover.
const PAGE_RETRY_DELAY_MS = 400;

function fetchWithTimeout(request, timeoutMs) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('network-timeout')), timeoutMs);
        fetch(request).then(
            (res) => { clearTimeout(timer); resolve(res); },
            (err) => { clearTimeout(timer); reject(err); }
        );
    });
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

// Tell the page what this fetch just learned, so the connection strip at the
// top of the screen reacts the instant a navigation fails instead of waiting
// for the next heartbeat. Fails silently where messaging is unavailable.
function notifyPages(ok) {
    try {
        self.clients.matchAll({ type: 'window' }).then((list) => {
            list.forEach((client) => {
                try { client.postMessage({ type: 'AQ_NET', ok: ok }); } catch (e) { /* noop */ }
            });
        });
    } catch (e) { /* noop */ }
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
            // ...and one public page, kept in its own cache so logging out
            // never removes it. This is what a navigation with an empty page
            // cache falls back to instead of an offline screen.
            .then(() => caches.open(SHELL_CACHE))
            .then((shell) => shell.add(new Request(SHELL_URL, { credentials: 'same-origin' })))
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
        event.respondWith(pageStrategy(request, isNavigate, isPrefetch(request)));
        return;
    }

    if (STATIC_EXT.test(url.pathname)) {
        event.respondWith(staticStrategy(request));
    }
    // Everything else (JSON endpoints, API calls, cross-origin data) passes
    // through untouched so online behaviour is identical to no service worker.
});

// Turbo labels its hover-prefetch and <a data-turbo-preload> requests, and
// the browser labels its own speculative ones. These are background work, so
// they are handled differently below: Turbo turns a *rejected* prefetch into
// a turbo:fetch-request-error, which the app answers by reloading the page —
// hovering a link while offline would then reload in a loop. A plain non-2xx
// response makes the prefetch drop quietly instead.
function isPrefetch(request) {
    const purpose = request.headers.get('x-sec-purpose')
        || request.headers.get('sec-purpose')
        || request.headers.get('purpose')
        || '';

    return purpose.toLowerCase().indexOf('prefetch') !== -1;
}

// Keep a successful server-rendered page around purely as the offline
// fallback. Only clean 200s are stored — never a redirect, a Turbo stream or
// a logout — so a cached copy can never leak one user's session to the next.
async function cachePage(request, fresh) {
    const contentType = (fresh.headers.get('content-type') || '').toLowerCase();

    const cacheable =
        fresh.status === 200 &&
        !fresh.redirected &&
        contentType.indexOf('text/html') !== -1 &&
        contentType.indexOf('turbo-stream') === -1 &&
        new URL(request.url).pathname.indexOf('/logout') === -1;

    if (!cacheable) return;

    const cache = await caches.open(PAGE_CACHE);
    await cache.put(request, fresh.clone());

    // Trim the oldest entries only — never the whole cache. Wiping it here
    // would throw away the copy just stored, which is precisely the page a
    // browser that loses its connection a moment later most needs.
    const keys = await cache.keys();
    for (let i = 0; keys.length - i > PAGE_CACHE_LIMIT; i++) {
        await cache.delete(keys[i]);
    }
}

// ── Cache lookup ───────────────────────────────────────────────────
// The Cache API matches on URL *and* on every header named in the stored
// response's Vary. A browser navigation arrives with different headers
// (Sec-Fetch-*, Accept, Upgrade-Insecure-Requests) than the Turbo visit that
// stored the page, so the obvious lookup misses a page that is sitting right
// there. Look the URL up first and match the stored request against itself.
function cleanUrl(url) {
    try {
        const parsed = new URL(url, self.location.origin);
        parsed.hash = '';

        return parsed.href;
    } catch (err) {
        return String(url);
    }
}

function pathOf(url) {
    try {
        return new URL(url, self.location.origin).pathname;
    } catch (err) {
        return '';
    }
}

async function cachedIn(cacheName, url) {
    try {
        const cache = await caches.open(cacheName);
        const wanted = cleanUrl(url);
        const keys = await cache.keys();

        for (const key of keys) {
            if (cleanUrl(key.url) !== wanted) continue;
            const hit = await cache.match(key);
            if (hit) return hit;
        }
    } catch (err) {
        // Cache API unavailable — the caller falls through to the network.
    }

    return null;
}

async function cachedPage(url) {
    return await cachedIn(PAGE_CACHE, url)
        || await cachedIn(SHELL_CACHE, url)
        || await cachedIn(STATIC_CACHE, url);
}

// Where to send a navigation whose own URL is not cached. Only ever a URL
// that is provably stored, and never the one that just missed, so the
// redirect cannot bounce between two entries.
async function fallbackUrl(request) {
    try {
        const cache = await caches.open(PAGE_CACHE);
        const keys = await cache.keys();
        if (!keys.length) return null;

        const here = cleanUrl(request.url);

        for (const path of FALLBACK_PAGES) {
            const match = keys.find((key) => pathOf(key.url) === path && cleanUrl(key.url) !== here);
            if (match) return match.url;
        }

        const other = keys.find((key) => cleanUrl(key.url) !== here);

        return other ? other.url : null;
    } catch (err) {
        return null;
    }
}

function offlineResponse() {
    return new Response('You are offline.', {
        status: 503,
        statusText: 'Offline',
        headers: { 'Content-Type': 'text/plain' }
    });
}

async function pageStrategy(request, isNavigate, isPrefetchRequest) {
    // Two attempts before the app calls itself offline.
    //
    // This is the fix for "it shows the offline page even though I have
    // internet": one congested request, a radio handover or a TLS warm-up on a
    // slow link used to be enough to drop the user into the takeover screen.
    // A device with genuinely no connection rejects both attempts almost
    // immediately, so a real offline still falls through without delay.
    // A hover-prefetch gets a single attempt — nobody is waiting on it.
    const attempts = isPrefetchRequest ? 1 : 2;

    for (let attempt = 0; attempt < attempts; attempt++) {
        if (attempt > 0) await sleep(PAGE_RETRY_DELAY_MS);

        try {
            const fresh = await fetchWithTimeout(request, PAGE_TIMEOUT_MS);
            await cachePage(request, fresh);
            if (!isPrefetchRequest) notifyPages(true);
            return fresh;
        } catch (err) {
            // Try the other attempt, then fall through to the fallbacks below.
        }
    }

    // Tell the open page (if any) so the top strip flips immediately rather
    // than waiting for its next heartbeat. A hover-prefetch is not something
    // the user did, so it says nothing about the connection.
    if (!isPrefetchRequest) notifyPages(false);

    // 1. This exact page, if it has ever been opened in this browser. Works
    //    for navigations and Turbo visits alike — Turbo renders the stored
    //    copy and the user simply sees the last version of that page.
    const cached = await cachedPage(request.url);
    if (cached) return cached;

    // 2. Background prefetch: an ordinary error so Turbo forgets the request
    //    quietly instead of treating it as a failed visit.
    if (isPrefetchRequest) return offlineResponse();

    // 3. A Turbo visit or XHR already has a page on screen. Rejecting makes
    //    the browser treat this as a plain network failure, so that page
    //    stays exactly as it is — no takeover, nothing replaced — and the
    //    strip at the top of the header reports the problem.
    if (!isNavigate) throw new Error('offline');

    // 4. A browser navigation has already left the previous page, so it needs
    //    somewhere real to land. Redirect (rather than serving foreign
    //    content at this address) so the address bar and the screen agree.
    const target = await fallbackUrl(request);
    if (target) return Response.redirect(target, 302);

    // 5. Nothing from this session — the sign-in screen captured at install.
    //    Redirect to it when we are not already on that URL, so its own
    //    navigation cannot bounce; serve it directly otherwise.
    const shell = await cachedIn(SHELL_CACHE, SHELL_URL);
    if (shell) {
        if (pathOf(request.url) !== SHELL_URL) {
            return Response.redirect(new URL(SHELL_URL, self.location.origin).href, 302);
        }

        return shell;
    }

    // 6. Absolute last resort — a browser with no cache at all (storage
    //    cleared mid-session). Nothing above can reach a user who is already
    //    inside the app, because every page they can see is in the cache.
    const offline = await caches.match('/offline.html');
    if (offline) return offline;

    return offlineResponse();
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
