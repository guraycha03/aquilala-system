/*
 * Aquilala connection status — the thin strip at the very top of the screen.
 *
 * Why this exists: when the connection drops, the page the user is already
 * looking at is what should stay on screen — the service worker serves a
 * cached copy instead of taking the display over, and this strip is how the
 * user is told. It sits above the app chrome as a viewport overlay, so
 * appearing or disappearing never changes the page layout or pushes the
 * header down.
 *
 * Detection uses two signals, because they mean different things:
 *   - navigator.onLine === false  -> the device has no network at all
 *   - a failed GET /up            -> the device has network but cannot reach
 *                                    Aquilala (server restarting, VPN, DNS)
 * The strip is re-created after every Turbo visit, since Turbo swaps <body>.
 */
(function () {
    'use strict';

    var BAR_ID = 'aq-net-bar';
    var STYLE_ID = 'aq-net-style';

    var HEALTHY_MS = 20000;   // quiet poll while everything is fine
    var RETRY_MS = 3000;      // quick poll while it is not
    var BACK_ONLINE_MS = 2500; // how long the green "back online" stays up

    var mode = 'ok';          // 'ok' | 'offline' | 'down' | 'back'
    var attempts = 0;
    var pollTimer = null;
    var backTimer = null;
    var started = false;

    // ── Markup ─────────────────────────────────────────────────────

    function ensureStyle() {
        if (document.getElementById(STYLE_ID)) return;

        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = [
        /* A thin fixed bar at the very top of the viewport. It must be a
           direct child of <body> (appended last) and sized/positioned
           explicitly so nothing in the page flow can push it down or
           leave a gap above it. */
        '#' + BAR_ID + ' {',
        '  position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: auto !important; z-index: 2147483647 !important;',
        '  contain: layout style paint; isolation: isolate;',
        '  box-sizing: border-box; width: 100%; max-width: 100%; height: 34px; min-height: 34px; margin: 0 !important; flex: 0 0 auto;',
        '  display: none; align-items: center; justify-content: center; gap: 9px;',
        '  padding: 7px 14px; text-align: center; flex-wrap: nowrap;',
        '  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;',
        '  font-size: 12.5px; font-weight: 600; line-height: 1.4; letter-spacing: 0.01em;',
        '  color: #052e16; background: rgba(236, 253, 243, 0.96);',
        '  border-bottom: 1px solid rgba(22, 101, 52, 0.18);',
        '  box-shadow: 0 4px 16px rgba(0,0,0,0.12);',
        '  pointer-events: none;',
        '}',
        '#' + BAR_ID + '.aq-net-show { display: flex; }',
        '#' + BAR_ID + '.aq-net-back { background: #14532d; color: #ffffff; }',
        '[data-theme="dark"] #' + BAR_ID + ':not(.aq-net-back) { background: rgba(5, 46, 22, 0.96); color: #d1fae5; border-bottom-color: rgba(167, 243, 208, 0.18); }',
        '.aq-net-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor;',
        '  flex: 0 0 8px; animation: aq-net-pulse 1.4s ease-in-out infinite; }',
        '#' + BAR_ID + '.aq-net-back .aq-net-dot { animation: none; }',
        '.aq-net-text { flex: 0 1 auto; white-space: nowrap; }',
        '.aq-net-hint { font-weight: 500; opacity: 0.85; white-space: nowrap; }',
        '@keyframes aq-net-pulse {',
        '  0%, 100% { opacity: 0.3; transform: scale(0.75); }',
        '  50%      { opacity: 1;   transform: scale(1.2);  }',
        '}',
        '',
        '@media (prefers-reduced-motion: reduce) { .aq-net-dot { animation: none !important; } }',
        '@media (max-width: 520px) { .aq-net-hint { display: none; } #' + BAR_ID + ' { font-size: 12px; gap: 7px; } }'
        ].join('\n');

        (document.head || document.documentElement).appendChild(style);
    }

    function ensureBar() {
        var bar = document.getElementById(BAR_ID);

        // If a previous render or Turbo snapshot left the bar somewhere in
        // the document flow, move it to the end of <body> so this fixed
        // overlay never affects layout or gets pushed by other nodes.
        if (bar) {
            if (document.body && bar.parentElement !== document.body) {
                document.body.appendChild(bar);
            }
            return bar;
        }

        bar = document.createElement('div');
        bar.id = BAR_ID;
        bar.setAttribute('role', 'status');
        bar.setAttribute('aria-live', 'polite');
        bar.setAttribute('aria-hidden', 'true');
        bar.innerHTML =
            '<span class="aq-net-dot"></span>' +
            '<span class="aq-net-text"></span>' +
            '<span class="aq-net-hint"></span>';

        document.body.appendChild(bar);

        return bar;
    }

    // ── Painting ───────────────────────────────────────────────────

    function render() {
        if (!document.body) return;

        ensureStyle();

        var bar = ensureBar();
        var text = bar.querySelector('.aq-net-text');
        var hint = bar.querySelector('.aq-net-hint');

        var visible = mode === 'down' || mode === 'offline' || mode === 'back';
        bar.classList.toggle('aq-net-show', visible);
        bar.classList.toggle('aq-net-unreachable', mode === 'down');
        bar.classList.toggle('aq-net-back', mode === 'back');
        bar.setAttribute('aria-hidden', visible ? 'false' : 'true');

        document.body.classList.toggle('aq-netbar-visible', mode === 'down' || mode === 'offline');

        if (mode === 'offline') {
            text.textContent = 'No internet connection';
            hint.textContent = 'Reconnecting as soon as you are back â€” everything already open is still here.';
        } else if (mode === 'down') {
            text.textContent = 'Connection lost â€” reconnectingâ€¦';
            hint.textContent = 'Attempt ' + attempts;
        } else if (mode === 'back') {
            text.textContent = 'Back online';
            hint.textContent = '';
        } else {
            text.textContent = '';
            hint.textContent = '';
        }
    }
    }

    function setMode(next) {
        if (next === mode) return;

        if (next === 'back') {
            if (backTimer) clearTimeout(backTimer);
            backTimer = setTimeout(function () {
                backTimer = null;
                mode = 'ok';
                render();
            }, BACK_ONLINE_MS);
        }

        mode = next;
        render();
    }

    // ── Probing ────────────────────────────────────────────────────

    function schedule(ms) {
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = setTimeout(probe, ms);
    }

    function markOk() {
        attempts = 0;
        // Only announce a recovery if something was actually wrong; the quiet
        // 20-second heartbeat must never flash a green strip on its own.
        if (mode === 'offline' || mode === 'down') setMode('back');
        else setMode('ok');
        schedule(HEALTHY_MS);
    }

    function markDown() {
        if (navigator.onLine === false) {
            attempts += 1;
            setMode('offline');
            schedule(RETRY_MS);
            return;
        }

        attempts += 1;
        setMode('down');
        schedule(RETRY_MS);
    }

    function probe() {
        if (navigator.onLine === false) {
            markDown();
            return;
        }

        var settled = false;
        var guard = setTimeout(function () {
            if (!settled) { settled = true; markDown(); }
        }, 8000);

        fetch('/up', {
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (res) {
            if (settled) return;
            settled = true;
            clearTimeout(guard);
            res && res.ok ? markOk() : markDown();
        }).catch(function () {
            if (settled) return;
            settled = true;
            clearTimeout(guard);
            markDown();
        });
    }

    // ── Events ─────────────────────────────────────────────────────

    function start() {
        if (started) return;
        started = true;

        render();
        probe();
        schedule(HEALTHY_MS);

        window.addEventListener('online', function () {
            attempts = 0;
            probe();
        });

        window.addEventListener('offline', function () {
            markDown();
        });

        window.addEventListener('focus', function () {
            if (mode !== 'ok') probe();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && mode !== 'ok') probe();
        });

        // A Turbo visit that could not be fetched is the moment a user first
        // notices something is wrong — show it there rather than on navigate.
        document.addEventListener('turbo:fetch-request-error', function () {
            markDown();
        });

        document.addEventListener('turbo:load', function () {
            render();
        });

        document.addEventListener('turbo:render', function () {
            render();
        });

        // The service worker knows before we do: its network-first page
        // fetch is what just failed (or finally succeeded).
        if ('serviceWorker' in navigator && navigator.serviceWorker.addEventListener) {
            navigator.serviceWorker.addEventListener('message', function (event) {
                var data = event.data;
                if (!data || data.type !== 'AQ_NET') return;

                data.ok ? markOk() : markDown();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
