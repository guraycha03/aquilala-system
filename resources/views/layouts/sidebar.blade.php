<!DOCTYPE html>
<html lang="en" data-theme="{{ optional(auth()->user())->theme_mode === 'dark' ? 'dark' : 'light' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  {{-- Authenticated app shell: no dashboard or internal page may ever be
       indexed (belt to the robots.txt braces). --}}
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="Aquilala agricultural association management portal.">
  <title>@yield('title') | Aquilala</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}" width="32" height="32">
  {{-- PWA: installable from the browser ("Add to Home Screen" / install
       prompt) so Aquilala opens like a native app. The manifest describes
       the app and its home-screen icons; js/pwa-register.js registers the
       service worker (offline fallback + fast repeat launches) and clears
       cached pages on logout. --}}
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="theme-color" content="#f3f6f4" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#0b1220" media="(prefers-color-scheme: dark)">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Aquilala">
  <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}" width="180" height="180">
  <script src="{{ asset('js/pwa-register.js') }}" defer></script>
  <script src="{{ asset('js/net-status.js') }}" defer></script>
  {{-- Shared filter-collapse engine: pages with a search bar + filter choices
       hide those choices behind one funnel button (.btn-filter) until asked. --}}
  <script src="{{ asset('js/filter-collapse.js') }}" defer></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  {{-- Render-blocking only hurts here: a slow/blocked CDN would hold the whole
       page blank until it answers. Loading it as print/swap paints the page on
       the system font first, then swaps in DM Sans when it arrives. --}}
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
  <noscript><link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet"></noscript>
  {{-- Served from the app (not unpkg) so a CDN outage can never stall the UI.
       Deferred: the layout re-runs createIcons() on DOMContentLoaded. --}}
  <script src="{{ asset('js/lucide.min.js') }}" defer></script>
  {{-- App-like navigation: Turbo swaps page bodies on link clicks (no white
       screen), keeps a slim progress bar and instant back/forward, and with
       the meta below it prefetches links on hover so pages open instantly. --}}
  <meta name="turbo-prefetch" content="true">
  {{-- Bootstrap must run before any body script: Turbo re-evaluates body
       scripts on every visit, but DOMContentLoaded fires only once per
       session. Queue DOMContentLoaded registrations and drain them on the
       first load and after every Turbo visit, so page init code keeps
       working unchanged. --}}
  <script>
    if (!window.__aqBoot) {
      window.__aqBoot = true;
      window.__aqDCQueue = [];
      var aqNativeDC = document.addEventListener.bind(document);
      document.addEventListener = function (type, fn, opts) {
        if (type === 'DOMContentLoaded' && typeof fn === 'function') {
          window.__aqDCQueue.push(fn);
          return;
        }
        return aqNativeDC(type, fn, opts);
      };
      // Cached Turbo snapshots restore the original DOM nodes, so a canvas can
      // come back still bound to its previous Chart instance and Chart.js throws
      // "Canvas is already in use" when the page init re-runs. Destroy the
      // leftover instance first (same element lookup the Chart constructor does).
      window.aqDestroyChart = function (target) {
        try {
          if (window.Chart && Chart.getChart) {
            var prev = Chart.getChart(target);
            if (prev) prev.destroy();
          }
        } catch (e) {}
      };
      // Failsafe for the gate below: if a drain is waiting on script load
      // events that were already consumed (or lost across a Turbo swap /
      // tab restore), the queue could stay blocked forever and every page
      // init would die with it. Force a drain after a short grace period so
      // the app can never end up permanently unwired.
      window.__aqDrainFailsafe = function () {
        clearTimeout(window.__aqDrainTimer);
        window.__aqDrainTimer = setTimeout(function () {
          window.__aqDrainTimer = null;
          window.__aqDrainDC(true);
        }, 2500);
      };
      window.__aqDrainDC = function (force) {
        var q = window.__aqDCQueue;
        if (!q.length) return;
        // On Turbo visits body <script src> elements are activated asynchronously,
        // so a queued init can run before its sibling library finishes loading
        // (e.g. flatpickr init skipped because typeof flatpickr === 'undefined').
        // Wait for every not-yet-loaded script to settle (load/error/1.5s
        // timeout) unless this is the real DOMContentLoaded, where the parser
        // guarantees all sync/defer scripts already executed. The timeout must
        // stay short: it gates the whole queue, and a stalled script here used
        // to freeze sidebar/navigation inits for 15 seconds.
        if (!force) {
          var pending = [];
          try {
            pending = [].slice.call(document.querySelectorAll('script[src]')).filter(function (s) {
              if (s.dataset.aqReady) return false;
              var done = performance.getEntriesByType('resource').some(function (e) { return e.name === s.src; });
              if (done) { s.dataset.aqReady = '1'; return false; }
              return true;
            });
          } catch (e) { pending = []; }
          if (pending.length) {
            pending.forEach(function (s) {
              if (s.dataset.aqWait) return;
              s.dataset.aqWait = '1';
              var settle = function () { s.dataset.aqReady = '1'; window.__aqDrainDC(false); };
              s.addEventListener('load', settle);
              s.addEventListener('error', settle);
              setTimeout(settle, 1500);
            });
            // Arm the failsafe so this gate can never stay shut forever.
            window.__aqDrainFailsafe();
            return;
          }
        }
        var fns = window.__aqDCQueue.splice(0);
        for (var i = 0; i < fns.length; i++) {
          try { fns[i](); } catch (e) { console.error('[aq:init]', e); }
        }
      };
      aqNativeDC('DOMContentLoaded', function () { window.__aqDrainDC(true); });
      aqNativeDC('turbo:load', function () { window.__aqDrainDC(false); });
    }
  </script>
  {{-- ?v=2 busts the service worker's static cache — it is cache-first, so an
       old Turbo would otherwise keep booting from aquilala-v1 storage. --}}
  <script src="{{ asset('js/turbo.min.js') }}?v=2" defer></script>
  <script src="{{ asset('js/alpine.min.js') }}" defer></script>
  {{-- Display theme: every rule is scoped to [data-theme="dark"], so this file
       costs nothing while the user is in light mode. --}}
  <link id="aqDarkModeStylesheet" rel="stylesheet" href="{{ asset('css/dark-mode.css') }}?v=12"
        media="{{ optional(auth()->user())->theme_mode === 'dark' ? 'all' : 'not all' }}">
  {{-- App lockdown: kills text selection, copy/cut, long-press & right-click
       browser menus, and printing outside form fields — applies to every page
       and role, so the portal behaves like a native app and long-press is
       free for the app's own modals. The same file also raises the blank
       "capture shield" cover on routes carrying the `shield` middleware.
       ?v=1 busts the service worker's cache-first static cache — bump it on
       every edit to either file below or installed PWAs keep serving stale
       bytes (same trick as turbo.min.js?v=2). --}}
  <link rel="stylesheet" href="{{ asset('css/app-lockdown.css') }}?v=4">
  <script src="{{ asset('js/app-lockdown.js') }}?v=4" defer></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { width: 0; height: 0; display: none; }

    /* ── The one primary button the whole app shares ────────────────
       Every page styles its own .btn-primary — the size, the padding,
       the shape — but the colour is one decision for the whole system:
       a soft sage green rather than a near-black one, so the button
       sits on the page instead of shouting over it. Qualified with the
       content wrapper so it wins over the per-page rules. */
    .main-content .btn-primary {
      background-color: #3F6B4D;
      background-image: none;
      border-color: #3F6B4D;
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(63, 107, 77, 0.20);
    }
    .main-content .btn-primary:hover {
      background-color: #375F43;
      border-color: #375F43;
      box-shadow: 0 6px 16px rgba(63, 107, 77, 0.24);
    }

    /* ── System color rule (applies to every page): muted, matte, WARM SAGE
       greens — never bright or neon greens, never hard black borders or
       text. Build backgrounds, accents, focus rings, and hover states from
       these tokens so the whole portal stays soft and easy on the eyes. */
    :root {
      --aq-sage-700: #3f5a46;   /* deepest sage: emphasized accents on light */
      --aq-sage-600: #4f6a56;   /* strong sage: prices, links, small buttons */
      --aq-sage-500: #648268;   /* primary action sage: buttons, focus, avatar */
      --aq-sage-400: #7f9a85;   /* soft accents, status dots */
      --aq-sage-300: #a9bdaa;
      --aq-sage-200: #ccd7c9;
      --aq-sage-100: #e6ebe2;   /* soft sage surface (tints, table heads) */
      --aq-sage-50:  #f1f4ee;   /* sage wash: message panes, hover fills */
      --aq-bg:       #f4f5f0;   /* warm matte page background */
      --aq-line:     #e4e8de;   /* warm hairline borders */
      --aq-ink:      #2c3530;   /* primary text: warm near-black */
      --aq-ink-soft: #5c6d61;   /* secondary text */
      --aq-ink-faint:#879a8c;   /* tertiary text, placeholders */
    }

    body { font-family: 'DM Sans', ui-sans-serif, system-ui, -apple-system, sans-serif; background: var(--aq-bg, #f4f5f0); color: var(--aq-ink, #2c3530); display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

    /* Remove the browser's default focus outline (a black ring on Windows)
       on form fields; every input family paints its own focus state
       (border-color + box-shadow) and the native ring covers it.
       Same reset the auth layout already applies. Links/buttons keep
       their keyboard focus ring. */
    input:focus, input:focus-visible, input:focus-within,
    select:focus, select:focus-visible,
    textarea:focus, textarea:focus-visible {
      outline: none !important;
    }

    /* Soft matte focus border on every field (light mode) — pages used
       near-black greens (#143d23, #1e293b …) which read as a hard dark
       border while typing; the muted sage from the palette (#648268)
       keeps focus visible without the harshness. Dark mode keeps its
       green focus border from dark-mode.css. */
    html[data-theme="light"] input:focus,
    html[data-theme="light"] select:focus,
    html[data-theme="light"] textarea:focus {
      border-color: #648268 !important;
    }

    /* Field border weight — the Submit Harvest Report form's 1.5px border
       reads calm and substantial; the thinner 1px used elsewhere looked
       sharp and unsettling on focus. Applied at rest (not only :focus) so
       focused fields don't shift by a half-pixel. Native checkbox/radio/
       range widgets keep their own geometry. */
    input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]),
    select, textarea {
      border-width: 1.5px !important;
    }

    /* At-rest field border colour — a field whose CSS never declares one
       falls back to the browser's dark gray (#767676), which reads as the
       unwanted thin dark border. Element-level, so any page rule still wins. */
    input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]),
    select, textarea {
      border-color: #cbd5c8;
    }

    /* Global: consistent, readable letter-spacing on all validation / feedback text */
    .field-error, .field-feedback, .form-error, .form-error-message,
    .form-errors, .form-errors li, .form-errors ul, .alert-error, .alert-error li,
    [id$="-feedback"], [id$="-error"], [id$="Feedback"], [id$="Error"] {
      letter-spacing: 0.05em;
    }

    /* Global readability: readable letter-spacing across headings, labels and controls */
    h1, h2, h3, h4, h5, h6, button, select, th,
    label, .form-label, .menu-group-title, .user-role {
      letter-spacing: 0.03em;
    }
    .nav-links li a, .nav-dropdown-btn, .sub-nav-links li a { letter-spacing: 0.02em; }

    /* ── Shared KPI / stat strip ────────────────────────────────────────
       One responsive grid for every page's top-of-page metric cards so
       they stay uniform and never stack one-per-line on small screens.
       Desktop: cards flow across (3–5 up by count). Phones (≤640px): a
       tidy 2-up grid where each card reshapes compactly, roughly halving
       the scroll length. Scoped with `body` so it overrides the per-page
       container rules regardless of source order. */
    body .kpi-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 14px;
      margin-bottom: 22px;
    }
    @media (max-width: 640px) {
      /* The filter dropdowns in two columns on a phone: status and crop on
         the first row, the rest below. Stacked in one column they pushed
         the table itself off the screen. The width is capped a little under
         half so two always fit the row, with room for the gap. */
      .ttb-filters { gap: 8px; }
      .ttb-filters .ncb-group-inline {
        min-width: 0;
        flex: 1 1 calc(50% - 6px);
        max-width: calc(50% - 6px);
      }
      .ttb-filters .ncb-select-trigger { padding: 0 10px; }

      body .kpi-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
      }
      /* Compact card shape so two cards share a row without tall towers */
      body .kpi-strip > * { min-height: 0; padding: 13px 13px 12px; }
      body .kpi-strip .kpi-value,
      body .kpi-strip .stat-value,
      body .kpi-strip .stat-val,
      body .kpi-strip .stat-num,
      body .kpi-strip .uk-value,
      body .kpi-strip .ak-value,
      body .kpi-strip .metric-value,
      body .kpi-strip .fd-kpi-value,
      body .kpi-strip .v { font-size: 21px; }
      body .kpi-strip .kpi-label,
      body .kpi-strip .kpi-title,
      body .kpi-strip .stat-label,
      body .kpi-strip .stat-lbl,
      body .kpi-strip .uk-label,
      body .kpi-strip .ak-label,
      body .kpi-strip .metric-label,
      body .kpi-strip .fd-kpi-label,
      body .kpi-strip .fd-kpi-tag,
      body .kpi-strip .k { font-size: 10px; }
      body .kpi-strip .kpi-icon-chip,
      body .kpi-strip .stat-icon,
      body .kpi-strip .metric-icon,
      body .kpi-strip .fd-kpi-icon { width: 32px; height: 32px; }
    }

    /* Sidebar menu: kill the blue tap highlight and the focus box the browser
       draws when a menu item is tapped. Keyboard users keep a themed outline. */
    .sidebar a, .sidebar button { -webkit-tap-highlight-color: transparent; }
    .nav-links li a:focus, .nav-dropdown-btn:focus, .sub-nav-links li a:focus,
    .sidebar .nav-dropdown-btn:focus { outline: none; }
    .nav-links li a:focus-visible, .nav-dropdown-btn:focus-visible,
    .sub-nav-links li a:focus-visible { outline: 2px solid #8FB89A; outline-offset: -2px; }

    /* Global select / dropdown styling — matches the Aquilala white + green theme */
    select {
      color-scheme: light;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.5rem 2.5rem 0.5rem 0.75rem;
      background-color: #ffffff;
      outline: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23143423' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 0.5rem center;
      background-repeat: no-repeat;
      background-size: 1.5em 1.5em;
      font-family: inherit;
    }
    select:not([disabled]) { cursor: pointer; }
    select:focus {
      border-color: #4A7A54;
      box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
    }

    /* Layout Framework */
    .sidebar { width: 260px; background: #143d23; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: none; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .main-content { margin-left: 260px; flex: 1; padding: 64px 32px 32px; min-width: 0; }

    /* Shared overlay horizontal scrollbar (green draggable pill) */
    .app-scroll { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 52px; }
    .app-scroll::-webkit-scrollbar { display: none; }
    .app-scroll-wrap { position: relative; }
    /* No sideways overflow → the pill is hidden, so the space it was given
       back goes to the content instead of sitting as dead air. */
    .app-scroll-wrap.no-scroll .app-scroll { padding-bottom: 0; }
    .app-scrollbar {
      position: absolute; left: 40px; right: 40px; bottom: var(--sb-bottom, 18px); height: 10px;
      display: none; cursor: grab; touch-action: none;
    }
    .app-scrollbar.dragging { cursor: grabbing; }
    .app-scrollbar-track {
      position: absolute; left: 0; right: 0; top: 2px; bottom: 2px;
      background: rgba(169, 189, 170, 0.32); border-radius: 999px;
    }
    /* A soft, light sage pill - solid, never a gradient. The scrollbar is
       furniture, not a signal: it should be seen when you look for it and
       forgotten the rest of the time. */
    .app-scrollbar-thumb {
      position: absolute; top: 0; bottom: 0; left: 0; border-radius: 999px;
      background: var(--aq-sage-300, #a9bdaa);
      box-shadow: 0 1px 3px rgba(63, 90, 70, 0.16);
      min-width: 48px; max-width: 42%; transition: background 0.15s ease;
    }
    .app-scrollbar-thumb:hover { background: var(--aq-sage-400, #7f9a85); }
    .app-scrollbar.dragging .app-scrollbar-thumb { background: var(--aq-sage-500, #648268); }
    
    /* Brand Section */
    .brand-section { padding: 15px 20px 14px 20px; font-size: 12px; font-weight: 600; color: rgba(255, 255, 255, 0.95); letter-spacing: -0.01em; line-height: 1.35; border-bottom: 1px solid rgba(255, 255, 255, 0.08); display: flex; flex-direction: column; }
    .brand-title { font-size: 17px; font-weight: 800; letter-spacing: -0.01em; display: block; margin-bottom: 1px; color: #ffffff; line-height: 1.05; }
    .brand-accent { color: var(--sb-accent, #C8F36A); text-shadow: 0 0 14px color-mix(in srgb, var(--sb-accent, #C8F36A) 18%, transparent); }

    /* Weather Widget Icon (real condition icons from public/images/weather) */
    .season-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0; }
    .season-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .weather-unavailable img { filter: grayscale(1); opacity: 0.45; }

    /* Climate widget card — one single card; compact row by default, expands in place on hover (desktop) or click/tap */
    .climate-card {
      margin: 4px 22px 20px 8px;
      cursor: pointer; user-select: none; -webkit-tap-highlight-color: transparent;
      padding: 10px 12px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--sb-card-bg-a, rgba(255,255,255,0.16)), var(--sb-card-bg-b, rgba(255,255,255,0.04)));
      border: 1px solid var(--sb-card-border, rgba(255,255,255,0.14));
      box-shadow: 0 6px 18px rgba(20, 40, 25, 0.12), var(--sb-card-shadow, inset 0 1px 0 rgba(255,255,255,0.08));
      -webkit-backdrop-filter: blur(8px);
      backdrop-filter: blur(8px);
      display: flex; flex-direction: column;
      transition: background 0.2s ease, border-color 0.2s ease;
    }
    @media (max-width: 768px) {
      .climate-card { margin: 4px 8px 20px 8px; }
    }
    .climate-card:focus-visible { outline: 2px solid #8FB89A; outline-offset: 2px; }
    .climate-card:hover,
    .climate-card.pinned { background: linear-gradient(135deg, var(--sb-card-bg-a, rgba(255,255,255,0.22)), var(--sb-card-bg-b, rgba(255,255,255,0.06))); border-color: var(--sb-card-border-hover, rgba(255,255,255,0.26)); }
    .climate-row { display: flex; align-items: center; gap: 10px; min-height: 40px; }
    .climate-mini-info { flex: 1; min-width: 0; }
    .climate-mini-temp { font-size: 16px; font-weight: 800; color: var(--sb-fg); font-family: ui-monospace, monospace; letter-spacing: -0.02em; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .climate-mini-sub { font-size: 10px; color: var(--sb-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .climate-chevron { width: 16px; height: 16px; stroke: var(--sb-muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; transition: transform 0.25s ease; }
    .climate-card.pinned .climate-chevron { transform: rotate(180deg); }
    .climate-details {
      display: none; margin-top: 10px; padding-top: 11px;
      border-top: 1px dashed var(--sb-border-soft);
      animation: climateIn 0.2s ease;
    }
    .climate-card:hover .climate-details,
    .climate-card.pinned .climate-details { display: block; }
    .climate-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .climate-meta .climate-updated { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--sb-muted); font-weight: 700; }
    .climate-meta .climate-clock { font-size: 11px; color: var(--sb-fg); font-family: ui-monospace, monospace; font-weight: 600; }
    .climate-season { display: flex; align-items: center; gap: 10px; }
    .season-badge {
      display: flex; align-items: center; gap: 9px;
      width: 100%;
      padding: 7px 12px;
      border-radius: 10px;
      background: var(--sb-badge-bg);
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.15);
      max-width: 100%; box-sizing: border-box;
    }
    .season-badge img { width: 20px; height: 20px; object-fit: contain; display: block; flex-shrink: 0; background: rgba(255, 255, 255, 0.55); border-radius: 50%; padding: 4px; box-sizing: content-box; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
    .climate-season-info { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .climate-season-label { display: block; font-size: 13px; font-weight: 800; color: var(--sb-fg); white-space: nowrap; }
    .climate-season-sub { display: block; font-size: 10px; color: var(--sb-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-top: 2px; white-space: normal; line-height: 1.35; }
    @media (hover: none) {
      .climate-card:hover .climate-details { display: none; }
      .climate-card.pinned .climate-details, .climate-card.pinned .climate-chevron { display: block; }
    }
    @keyframes climateIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Navigation Body */
    .nav-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 20px 0 20px 14px; scrollbar-width: none; }
    .nav-body::-webkit-scrollbar { display: none; }
    .menu-group-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #87a992; font-weight: 700; margin: 16px 0 8px 8px; }
    .nav-links { list-style: none; overflow: visible; }
    .nav-links li { margin-bottom: 4px; position: relative; overflow: visible; }
    
    .nav-links li a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #b9cfc0; text-decoration: none; border-top-left-radius: 12px; border-bottom-left-radius: 12px; border-top-right-radius: 0px; border-bottom-right-radius: 0px; font-size: 14px; font-weight: 600; transition: color 0.2s ease, background 0.2s ease, margin 0.2s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-links li a svg { width: 18px; height: 18px; stroke: #b9cfc0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.2s ease; flex-shrink: 0; }
    .nav-links li a img { width: 18px; height: 18px; flex-shrink: 0; transition: content 0.2s ease; }
    .nav-links .nav-count { margin-left: auto; font-size: 10px; font-weight: 800; background: #dc2626; color: #ffffff; border-radius: 999px; padding: 1px 7px; line-height: 1.6; flex-shrink: 0; }
    .nav-links li a.active .nav-count { background: #dc2626; color: #ffffff; }
    .nav-links li a:hover:not(.active) img { content: url('{{ asset("images/store-%23ffffff.png") }}'); }
    .nav-links li a.active img { content: url('{{ asset("images/store-%23143d23.png") }}'); }
    .nav-links li a:hover:not(.active) { background: rgba(255, 255, 255, 0.05); color: #ffffff; margin-right: 14px; border-radius: 12px; }
    .nav-links li a:hover:not(.active) svg { stroke: #ffffff; }

    /* Continuous Active Hook */
    .nav-links li a.active { background: #f4f5f0; color: #1e2922; font-weight: 700; position: relative; margin-right: -1px; z-index: 10; }
    .nav-links li a.active svg { stroke: #143d23; }
    .nav-links li:not(.nav-dropdown):has(a.active) { z-index: 12; }
    .nav-links li:not(.nav-dropdown):has(a.active)::before { content: ""; position: absolute; top: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-bottom-right-radius: 20px; box-shadow: 10px 10px 0 10px #f4f5f0; pointer-events: none; z-index: 12; }
    .nav-links li:not(.nav-dropdown):has(a.active)::after { content: ""; position: absolute; bottom: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-top-right-radius: 20px; box-shadow: 10px -10px 0 10px #f4f5f0; pointer-events: none; z-index: 12; }

    /* Dropdown Sub-Navigation */
    .nav-dropdown { display: flex; flex-direction: column; }
    .nav-dropdown-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      color: #b9cfc0;
      background: transparent;
      border: none;
      border-top-left-radius: 12px;
      border-bottom-left-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: color 0.2s ease, background 0.2s ease;
      font-family: inherit;
    }
    .nav-dropdown-btn:hover { background: rgba(255, 255, 255, 0.05); color: #ffffff; }
    .nav-dropdown-title { display: flex; align-items: center; gap: 12px; }
    .nav-dropdown-title svg { width: 18px; height: 18px; stroke: #b9cfc0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .nav-dropdown-btn:hover .nav-dropdown-title svg { stroke: #ffffff; }
    .dropdown-chevron { width: 16px; height: 16px; stroke: #87a992; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.25s ease; }
    .nav-dropdown.open .dropdown-chevron { transform: rotate(180deg); }
    .sub-nav-links { list-style: none; display: none; padding-left: 32px; margin-top: 2px; }
    .nav-dropdown.open .sub-nav-links { display: block; }
    .sub-nav-links li a { padding: 10px 14px; font-size: 13px; color: #87a992; }
    .sub-nav-links li a svg { width: 16px; height: 16px; }

    /* Footer badge and logout */
    .sidebar-footer { padding: 16px; background: #0d2917; border-top: 1px solid rgba(255, 255, 255, 0.08); position: relative; z-index: 110; }
    .profile-card { margin: 0 0 12px; }
    .user-profile-badge {
      display: flex; align-items: center; gap: 12px;
      width: 100%; padding: 6px 8px 12px; margin: 0;
      background: transparent; border: none; border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px; cursor: pointer; font-family: inherit; text-align: left; color: inherit;
      text-decoration: none;
      transition: background 0.18s ease, border-color 0.18s ease;
    }
    .user-profile-badge:hover { background: rgba(255, 255, 255, 0.06); border-bottom-color: rgba(255, 255, 255, 0.12); }
    .profile-chevron { margin-left: auto; width: 15px; height: 15px; stroke: var(--sb-muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    /* One avatar for the whole system: the member's own picture when they
       have one, their initials when they do not. Soft sage, never a loud
       tile. */
    .user-avatar {
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0; overflow: hidden; border-radius: 50%;
      background: #EDF1E9; color: #4A7A54; border: 1px solid #E0E5DB;
      font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; line-height: 1;
    }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    /* The sidebar profile must look the same on every page. Page styles are
       printed after this block, so without this (higher-specificity) guard a
       page's own .user-avatar rules would repaint this placeholder — the
       stores page, for one, turned it into a bright green tile. */
    .sidebar-footer .user-avatar { background: #EDF1E9; color: #4A7A54; border: 1px solid #E0E5DB; border-radius: 50%; }
    .sidebar-footer .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    [data-theme="dark"] .user-avatar { background: #16301f; color: #a7f3d0; border-color: #334155; }
    .user-info { display: flex; flex-direction: column; min-width: 0; }
    .user-name { font-size: 14px; font-weight: 600; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role { font-size: 11px; color: #87a992; text-transform: capitalize; }
    .logout-btn { background: var(--sb-logout-bg, rgba(239, 68, 68, 0.08)); color: var(--sb-logout-fg, #fca5a5); border: 1px solid var(--sb-logout-border, rgba(239, 68, 68, 0.15)); padding: 11px 16px; width: 100%; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: flex-start; gap: 12px; transition: all 0.2s ease; }
    .logout-btn svg { width: 16px; height: 16px; stroke: var(--sb-logout-fg, #fca5a5); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .logout-btn:hover { background: var(--sb-logout-hover-bg, #b0403a); color: var(--sb-logout-hover-fg, #ffffff); border-color: var(--sb-logout-hover-bg, #b0403a); }
    .logout-btn:hover svg { stroke: var(--sb-logout-hover-fg, #ffffff); }

    /* Hide page-description sub-texts under titles; keep clear space below each title */
    .page-subtitle, .hero-sub { display: none !important; }
    .main-content h1 + p:not(.keep-sub) { display: none !important; }
    .main-content .keep-sub { display: block !important; }
    .main-content > :first-child h1:first-child { margin-bottom: 18px !important; }
    .page-header { margin-bottom: 36px !important; }

    .mobile-header { display: none; background: #143d23; color: white; padding: 14px 16px; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 90; }
    .burger-btn { background: none; border: none; color: var(--sb-fg); font-size: 24px; cursor: pointer; padding: 0; line-height: 1; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; }
    .burger-btn:active { background: rgba(255, 255, 255, 0.08); }

    .desktop-notif-wrap { position: fixed !important; top: 26px; right: 20px; z-index: 200; transition: transform 0.3s ease; display: flex; align-items: flex-start; gap: 10px; }
    .desktop-notif-wrap.hidden { transform: translateY(-80px); }
    .desktop-notif {
      width: 32px; height: 32px; border-radius: 8px;
      background: transparent; border: none;
      box-shadow: none;
      display: flex; align-items: center; justify-content: center;
      color: #6d7d46; text-decoration: none; cursor: pointer;
      transition: color 0.2s ease, background 0.2s ease;
    }
    .desktop-notif:hover { transform: none; box-shadow: none; background: rgba(109, 125, 70, 0.10); color: #4f6d4e; }
    .desktop-notif svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .desktop-notif { position: relative; }
    .notif-badge {
      position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 5px;
      border-radius: 999px; background: #dc2626; color: #ffffff;
      font-size: 10px; font-weight: 800; line-height: 18px; text-align: center;
      display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }

    /* ── The notification panel ───────────────────────────────────
       The bell opens this instead of leaving the page. It hangs under
       the bell itself, so it reads as the bell's own list rather than
       a takeover of the screen. One white card, plain rows, one sage
       dot for what is still unread - the same language the
       notifications page speaks. */
    .nmodal { position: fixed; top: 74px; right: 20px; z-index: 1200; width: min(400px, calc(100vw - 32px)); }
    .nmodal[hidden] { display: none; }
    .nmodal-card {
      display: flex; flex-direction: column; overflow: hidden;
      max-height: min(72vh, 560px);
      background: #ffffff; border: 1px solid #E0E5DB; border-radius: 16px;
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18);
    }
    .nmodal-head {
      display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
      padding: 16px 18px; border-bottom: 1px solid #F0F2F1; background: #fafdfb;
    }
    .nmodal-head h2 { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
    .nmodal-head p { font-size: 12px; color: #879a8c; margin: 3px 0 0 0; }
    .nmodal-close {
      width: 30px; height: 30px; border-radius: 9px; border: none; cursor: pointer; flex-shrink: 0;
      background: #f0f2f1; color: #6b7c6e; display: inline-flex; align-items: center; justify-content: center;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .nmodal-close:hover { background: #E0E5DB; color: #344B38; }
    .nmodal-close svg { stroke: currentColor; fill: none; }

    .nmodal-body { flex: 1; overflow-y: auto; padding: 6px 12px; overscroll-behavior: contain; }
    .nmodal-item { display: flex; align-items: stretch; gap: 6px; padding: 4px 0; }
    .nmodal-item + .nmodal-item { border-top: 1px solid #F0F2F1; }
    .nmodal-item-link {
      display: flex; align-items: flex-start; gap: 11px; flex: 1; min-width: 0;
      padding: 11px 8px; border-radius: 11px; text-decoration: none; color: inherit;
      transition: background 0.15s ease;
    }
    .nmodal-item-link:hover { background: #F7F9F6; }
    .nmodal-item-link:focus-visible { outline: 2px solid #8FB89A; outline-offset: 2px; }
    .nmodal-dot { width: 8px; height: 8px; border-radius: 50%; background: transparent; flex-shrink: 0; margin-top: 6px; }
    .nmodal-item.is-unread .nmodal-dot { background: #4A7A54; }
    .nmodal-mid { flex: 1; min-width: 0; }
    .nmodal-text { font-size: 13.5px; font-weight: 500; color: #344B38; line-height: 1.5; }
    .nmodal-item.is-unread .nmodal-text { font-weight: 600; color: #1f2d24; }
    .nmodal-meta { font-size: 11.5px; color: #94a3b8; font-weight: 500; margin-top: 4px; }
    .nmodal-state {
      display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 999px; margin-right: 7px;
      border: 1px solid #E0E5DB; background: #F3F5F0; color: #647263;
      font-size: 10.5px; font-weight: 700; line-height: 1.6; vertical-align: middle; white-space: nowrap;
    }
    .nmodal-state--ongoing { background: #EDF1E9; color: #166534; border-color: #C8D5C5; }
    .nmodal-state--done { background: #FAFBFA; color: #94a3b8; border-color: #EDF0EC; }
    .nmodal-done {
      width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; border: 1px solid #E0E5DB;
      background: #fff; color: #94a3b8; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.15s ease; align-self: center;
    }
    .nmodal-done:hover { background: #EDF1E9; border-color: #C8D5C5; color: #166534; }
    .nmodal-done svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }

    .nmodal-empty { text-align: center; padding: 40px 18px; }
    .nmodal-empty-icon {
      width: 48px; height: 48px; border-radius: 14px; margin: 0 auto 12px;
      background: #EDF1E9; color: #648268; display: flex; align-items: center; justify-content: center;
    }
    .nmodal-empty-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    .nmodal-empty h3 { font-size: 14px; font-weight: 700; color: #344B38; margin: 0 0 5px 0; }
    .nmodal-empty p { font-size: 12.5px; color: #647263; margin: 0; line-height: 1.55; }

    .nmodal-foot {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding: 12px 18px; border-top: 1px solid #F0F2F1; background: #fafdfb;
    }
    .nmodal-readall {
      display: inline-flex; align-items: center; gap: 6px; border: 1px solid #E0E5DB; cursor: pointer;
      background: #fff; color: #4b5c50; font-size: 11.5px; font-weight: 700; font-family: inherit;
      padding: 7px 12px; border-radius: 9px; transition: all 0.15s ease;
    }
    .nmodal-readall:hover { background: #EDF1E9; border-color: #C8D5C5; color: #344B38; }
    .nmodal-readall[hidden] { display: none; }
    .nmodal-readall svg { stroke: currentColor; fill: none; }
    .nmodal-all { font-size: 12.5px; font-weight: 700; color: #166534; text-decoration: none; }
    .nmodal-all:hover { text-decoration: underline; text-underline-offset: 2px; }

    [data-theme="dark"] .nmodal-card { background: rgba(15, 23, 42, 0.96); border-color: #334155; }
    [data-theme="dark"] .nmodal-head { background: rgba(15, 23, 42, 0.72); border-bottom-color: #334155; }
    [data-theme="dark"] .nmodal-head h2 { color: #d6f5e3; }
    [data-theme="dark"] .nmodal-head p { color: #9fb3a6; }
    [data-theme="dark"] .nmodal-close { background: #1f2937; color: #9fb3a6; }
    [data-theme="dark"] .nmodal-close:hover { background: #26344a; color: #d6f5e3; }
    [data-theme="dark"] .nmodal-item + .nmodal-item { border-top-color: #334155; }
    [data-theme="dark"] .nmodal-item-link:hover { background: rgba(51, 65, 85, 0.45); }
    [data-theme="dark"] .nmodal-text { color: #e2e8f0; }
    [data-theme="dark"] .nmodal-item.is-unread .nmodal-text { color: #d6f5e3; }
    [data-theme="dark"] .nmodal-meta { color: #94a3b8; }
    [data-theme="dark"] .nmodal-state { background: #1f2937; border-color: #334155; color: #9fb3a6; }
    [data-theme="dark"] .nmodal-state--ongoing { background: #16301f; border-color: #166534; color: #a7f3d0; }
    [data-theme="dark"] .nmodal-done { background: #1f2937; border-color: #334155; color: #94a3b8; }
    [data-theme="dark"] .nmodal-done:hover { background: #16301f; color: #a7f3d0; }
    [data-theme="dark"] .nmodal-empty-icon { background: #16301f; color: #6ee7a0; }
    [data-theme="dark"] .nmodal-empty h3 { color: #d6f5e3; }
    [data-theme="dark"] .nmodal-empty p { color: #9fb3a6; }
    [data-theme="dark"] .nmodal-loading { color: #9fb3a6; }
    [data-theme="dark"] .nmodal-spin { border-color: #334155; border-top-color: #6ee7a0; }
    [data-theme="dark"] .nmodal-foot { background: rgba(15, 23, 42, 0.72); border-top-color: #334155; }
    [data-theme="dark"] .nmodal-readall { background: #1f2937; border-color: #334155; color: #9fb3a6; }
    [data-theme="dark"] .nmodal-readall:hover { background: #16301f; color: #a7f3d0; }
    [data-theme="dark"] .nmodal-all { color: #6ee7a0; }

    @media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-left: 0; padding: 88px 16px 24px 16px; }
  .mobile-header { display: flex; }
  .sidebar-overlay { display: none; position: fixed; top: 0; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); z-index: 95; }
  .sidebar-overlay.open { display: block; }

  .desktop-notif { background: transparent; border: none; box-shadow: none; color: var(--sb-fg); }
  .desktop-notif { border-radius: 8px; }
  .desktop-notif:hover { transform: none; box-shadow: none; background: rgba(255, 255, 255, 0.12); }
  .desktop-notif-wrap { position: fixed !important; top: 11px; right: 16px; }

  /* On a phone the panel sits under the bell, across the width of the
     screen, where the thumb already is. */
  .nmodal { top: 60px; right: 12px; left: 12px; width: auto; }
  .nmodal-card { max-height: 72vh; border-radius: 16px; }
  .nmodal-body { padding: 4px 16px; }
  .nmodal-foot { padding: 12px 16px; }

  /* --- UPDATE HERE --- */
  /* 1. Add right padding to nav-body so space matches the left (14px on both sides) */
  .nav-body {
    padding-right: 14px;
  }

  /* 2. Standardize mobile pill buttons */
  .nav-links li a,
  .nav-links li a.active {
    border-radius: 12px;
  }
  .nav-links li a.active {
    margin-right: 0;
  }
  .nav-links li:has(a.active)::before,
  .nav-links li:has(a.active)::after {
    display: none;
  }
}

    /* -- Shared table toolbar: search + styled dropdowns (ncb-select) + sort + clear -- */
    .table-toolbar {
      position: relative; z-index: 5;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 12px 14px; margin-bottom: 22px;
      background: #ffffff;
      border: 1px solid #e2e8f0; border-radius: 14px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 6px 18px rgba(15,23,42,0.07);
    }
    .ctl-search {
      position: relative; flex: 1; min-width: 240px;
    }
    .ctl-search svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 17px; height: 17px; stroke: #94a3b8; fill: none;
    }
    .ctl-search input {
      width: 100%; height: 42px; padding: 0 16px 0 40px;
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #0f172a; outline: none; background: #f8fafc;
      font-weight: 500; font-family: inherit; box-sizing: border-box;
      transition: all .2s ease;
    }
    .ctl-search input:focus {
      border-color: #4A7A54; background: #ffffff;
      box-shadow: 0 0 0 3px rgba(74,122,84,.08);
    }
    .ctl-search input::placeholder { color: #94a3b8; }
    .ncb-group-inline { position: relative; min-width: 170px; }
    .ncb-select-trigger {
      width: 100%; height: 42px; padding: 0 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #1e293b; background-color: #ffffff; transition: all 0.2s ease;
      box-sizing: border-box; font-family: inherit; cursor: pointer;
      display: flex; justify-content: space-between; align-items: center; user-select: none;
      text-align: left; gap: 8px; white-space: nowrap;
    }
    .ncb-select-trigger.placeholder { color: #94a3b8; }
    .ncb-select-trigger:hover { border-color: #cbd5e1; }
    .ncb-select-trigger.active { border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,0.08); outline: none; }
    .ncb-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ncb-select-arrow { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
    .ncb-select-arrow.rotate { transform: rotate(180deg); }
    {{-- Scoped to the table-toolbar AND the shared filter-panel: their menus
         have no inline style and the engines show them by setting
         style.display directly, so this rule is their initial hidden state.
         A GLOBAL display:none would break Alpine x-show dropdowns
         (activity filters): x-show clears the inline style on open,
         falling back to this class rule and leaving the menu invisible. --}}
    .ttb-filters .ncb-select-menu, .filter-panel .ncb-select-menu {
      display: none;
      position: absolute; top: calc(100% + 4px); left: 0; right: 0;
      background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
      max-height: 240px; overflow-y: auto; z-index: 600; padding: 4px;
      scrollbar-width: thin;
    }
    .ncb-select-option {
      padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
      cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
    }
    .ncb-select-option:hover { background: #edf1e9; color: #648268; }
    .ncb-select-option.selected { background: #edf1e9; color: #648268; font-weight: 600; }
    .btn-clear {
      display: inline-flex; align-items: center; gap: 6px;
      height: 42px; padding: 0 14px; border: 1.5px solid #bfe3c7; border-radius: 10px;
      background: #f3faf4; color: #166534; font-size: 13px; font-weight: 600;
      cursor: pointer; font-family: inherit; transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(22,101,52,0.06); flex-shrink: 0;
    }
    .btn-clear:hover { border-color: #a3d4ad; background: #eaf6ee; box-shadow: 0 2px 8px rgba(22,101,52,0.10); }
    .btn-clear[hidden] { display: none; }
    .ttb-sort { min-width: 210px; }

    /* -- Shared data table: the ONE canonical style every list table uses -- */
    .data-table { width: 100%; border-collapse: collapse; text-align: left; }
    .data-table thead tr { background: #fafbfa; }
    .data-table th { padding: 13px 20px; color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .data-table td { padding: 14px 20px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr { transition: background 0.15s ease; }
    .data-table tbody tr:hover { background: #fafbfa; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .data-table .cell-strong { color: #1e2922; font-weight: 600; }
    .data-table .cell-muted { color: #64748b; }
    .data-table-container { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
    .table-head { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .table-head h2, .table-head h3 { font-size: 16px; color: #1e2922; font-weight: 700; margin: 0; }
    .table-head p { font-size: 13px; color: #64748b; margin: 4px 0 0; }

    /* -- Shared pagination controls: used by client toolbars AND the server
          pagination partial, so every table pages the same way (green theme) -- */
    .pagination-info { font-size: 13px; color: #64748b; font-weight: 500; }
    .pagination-controls { display: flex; align-items: center; gap: 6px; }
    .page-btn {
      min-width: 34px; height: 34px; padding: 0 10px; border: 1.5px solid #e2e8f0;
      border-radius: 8px; background: #edf1e9; color: #648268; font-size: 13px;
      font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;
      display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
    }
    .page-btn:hover:not(:disabled):not(.active):not(.disabled) { border-color: #4A7A54; color: #4A7A54; }
    .page-btn:disabled, .page-btn.disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
    .page-btn.active { background: #4A7A54; border-color: #4A7A54; color: #ffffff; font-weight: 700; }
    /* The step arrows are the first and last button. They are the way
       most people move through a table, so they are drawn at a size you
       can hit without looking twice. */
    .pagination-controls > .page-btn:first-child,
    .pagination-controls > .page-btn:last-child {
      min-width: 42px; height: 40px; padding: 0 12px;
      font-size: 22px; line-height: 1; font-weight: 700;
    }
    .page-ellipsis { color: #94a3b8; font-size: 15px; padding: 0 2px; }
    .btn-filter {
      position: relative; width: 42px; height: 42px; flex-shrink: 0;
      display: inline-flex; align-items: center; justify-content: center;
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      background: #ffffff; color: #64748b; cursor: pointer;
      font-family: inherit; transition: all 0.2s ease;
    }
    .btn-filter svg { width: 18px; height: 18px; }
    .btn-filter:hover { border-color: #cbd5e1; color: #334155; }
    .btn-filter.active { border-color: #4A7A54; background: #edf1e9; color: #648268; box-shadow: 0 0 0 3px rgba(74,122,84,0.08); }
    .btn-filter-dot {
      position: absolute; top: -3px; right: -3px; width: 9px; height: 9px;
      border-radius: 50%; background: #dc2626; border: 2px solid #ffffff;
    }
    .btn-filter-dot[hidden] { display: none; }
    .ttb-filters { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1 1 100%; }
    .ttb-filters[hidden] { display: none; }

    /* -- Shared filter-collapse panel: the choices behind EVERY .btn-filter
          toggle (audit, compare, catalog, storefront, approvals, crops
          directory). One panel style for all roles; dark-mode.css is
          regenerated from this block, so dark mode comes along for free. -- */
    .filter-panel {
      display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; flex: 1 1 100%;
    }
    .filter-panel[hidden] { display: none; }

    /* -- One native <select> look for every toolbar filter — replaces the
          per-page select CSS each of these pages used to carry. -- */
    .table-toolbar select {
      height: 42px; min-width: 140px; padding: 0 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
      background: #ffffff; color: #1e293b; font-size: 13px; font-weight: 500;
      font-family: inherit; outline: none; cursor: pointer; transition: all .2s ease;
      box-sizing: border-box; max-width: 100%;
    }
    .table-toolbar select:hover { border-color: #cbd5e1; }
    .table-toolbar select:focus { border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,.08); }
    .ttb-pagination {
      display: flex; align-items: center; gap: 10px;
      padding: 4px 0; flex-wrap: wrap; margin-left: auto;
    }

    /* -- Bottom-of-table pagination bar (client-side tables) -- */
    .ttb-bottom-bar {
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; padding: 14px 20px; border-top: 1px solid #f1f5f9;
      background: #fafdfb; flex-wrap: wrap;
    }

    /* -- Smooth page navigation: every swapped-in body fades in gently -- */
    body { animation: aqua-page-in .28s ease; transition: opacity .18s ease; }
    @keyframes aqua-page-in {
      from { opacity: 0; transform: translateY(4px); }
      to   { opacity: 1; transform: none; }
    }

    /* ------------------------------------------------------------
       Sidebar themes — switch the side menu color per user.
       The palettes below are generated from config/sidebar.php and
       applied through a data-sidebar-theme attribute on the body element.
    ------------------------------------------------------------ */
    @php
      $stThemes = config('sidebar.themes');
      $stDefault = config('sidebar.default');
    @endphp
    @foreach ($stThemes as $stKey => $stTheme)
      @php $stC = $stTheme['colors']; @endphp
      @if ($stKey === $stDefault) :root, @endif body[data-sidebar-theme="{{ $stKey }}"] {
      --sb-bg:{{ $stC['bg'] }}; --sb-deep:{{ $stC['deep'] }};
      --sb-fg:{{ $stC['fg'] }}; --sb-nav:{{ $stC['nav'] }}; --sb-muted:{{ $stC['muted'] }}; --sb-nav-hover:{{ $stC['nav_hover'] }};
      --sb-hover-bg:{{ $stC['hover_bg'] }}; --sb-hover-strong:{{ $stC['hover_strong'] }};
      --sb-active-bg:{{ $stC['active_bg'] }}; --sb-active-fg:{{ $stC['active_fg'] }};
      --sb-accent:{{ $stC['accent'] }};
      --sb-avatar-bg:{{ $stC['avatar_bg'] }}; --sb-avatar-fg:{{ $stC['avatar_fg'] }};
      --sb-border:{{ $stC['border'] }}; --sb-border-soft:{{ $stC['border_soft'] }};
      --sb-card-bg-a:{{ $stC['card_a'] }}; --sb-card-bg-b:{{ $stC['card_b'] }};
      --sb-card-border:{{ $stC['card_border'] }}; --sb-card-border-hover:{{ $stC['card_border_hover'] }};
      --sb-card-shadow:{{ $stC['card_shadow'] }};
      --sb-badge-bg:{{ $stC['badge_bg'] }};
      @if (!empty($stC['menu_ico_dark']))
      --sb-menu-ico-url:url('{{ asset("images/store-%23143d23.png") }}');
      @else
      --sb-menu-ico-url:url('{{ asset("images/store-%23ffffff.png") }}');
      @endif
      --sb-logout-bg:{{ $stC['logout_bg'] }}; --sb-logout-fg:{{ $stC['logout_fg'] }}; --sb-logout-border:{{ $stC['logout_border'] }};
      --sb-logout-hover-bg:{{ $stC['logout_hover_bg'] }}; --sb-logout-hover-fg:{{ $stC['logout_hover_fg'] }};
    }
    @endforeach

    /* Theme-aware sidebar overrides (defaults recreate the original emerald design) */
    .sidebar { background: var(--sb-bg); color: var(--sb-fg); }
    .mobile-header { background: var(--sb-bg); color: var(--sb-fg); }
    .sidebar-footer { background: var(--sb-deep); color: var(--sb-fg); border-top-color: var(--sb-border); }
    .brand-section { color: var(--sb-fg); border-bottom-color: var(--sb-border); }
    .brand-title { color: var(--sb-fg); }
    .brand-accent { color: var(--sb-accent); text-shadow: 0 0 14px color-mix(in srgb, var(--sb-accent) 18%, transparent); }
    .menu-group-title { color: var(--sb-muted); }
    .nav-links li a { color: var(--sb-nav); }
    .nav-links li a svg { stroke: var(--sb-nav); }
    .nav-links li a:hover:not(.active) { background: var(--sb-hover-bg); color: var(--sb-nav-hover); }
    .nav-links li a:hover:not(.active) svg { stroke: var(--sb-nav-hover); }
    .nav-links li a.active { background: #f4f5f0; color: #1e2922; }
    .nav-links li a.active svg { stroke: #1e2922; }
    .nav-links li:not(.nav-dropdown):has(a.active)::before { box-shadow: 10px 10px 0 10px #f4f5f0; }
    .nav-links li:not(.nav-dropdown):has(a.active)::after { box-shadow: 10px -10px 0 10px #f4f5f0; }
    .nav-dropdown-btn { color: var(--sb-nav); }
    .nav-dropdown-btn:hover { background: var(--sb-hover-bg); color: var(--sb-nav-hover); }
    .nav-dropdown-title svg { stroke: var(--sb-nav); }
    .nav-dropdown-btn:hover .nav-dropdown-title svg { stroke: var(--sb-nav-hover); }
    .dropdown-chevron { stroke: var(--sb-muted); }
    .sub-nav-links li a { color: var(--sb-muted); }
    .user-profile-badge { border-bottom-color: var(--sb-border-soft); }
    .user-name { color: var(--sb-fg); }
    .user-role { color: var(--sb-muted); }
    /* Weather card uses the active sidebar theme's translucent "glass" colors */
    .climate-card { background: linear-gradient(135deg, var(--sb-card-bg-a), var(--sb-card-bg-b)); border: 1px solid var(--sb-card-border); box-shadow: 0 6px 18px rgba(20, 40, 25, 0.12), var(--sb-card-shadow); }
    .climate-card:hover, .climate-card.pinned { background: linear-gradient(135deg, var(--sb-card-bg-a), var(--sb-card-bg-b)); border: 1px solid var(--sb-card-border-hover); }
    .climate-card:focus-visible { outline-color: var(--sb-accent); }
    .climate-mini-temp { color: var(--sb-fg); }
    .climate-mini-sub { color: var(--sb-muted); }
    .climate-chevron { stroke: var(--sb-muted); }
    .climate-details { border-top-color: var(--sb-border-soft); }
    .climate-meta .climate-updated { color: var(--sb-muted); }
    .climate-meta .climate-clock { color: var(--sb-fg); }
    .season-badge { background: var(--sb-badge-bg); }
    .climate-season-label { color: var(--sb-fg); }
    .climate-season-sub { color: var(--sb-muted); }
    .nav-links li a:hover:not(.active) img { content: var(--sb-menu-ico-url); }

/* -- Sub-page navigation inside the side menu ("Side menu" UI style):
   each main menu name becomes a dropdown and only the sub-page link
   carries the active state, styled with the same continuous active
   pill and curve hooks as the side menu's main active item. */
    .sub-nav-links { position: relative; }
    .sub-nav-links::before {
      content: "";
      position: absolute; left: 12px; top: 8px; bottom: 8px;
      width: 2px; border-radius: 2px;
      background: var(--sb-muted); opacity: 0.35;
      pointer-events: none;
    }
    .sub-nav-links li { position: relative; }
    .sub-nav-links li a { border-radius: 8px; transition: color 0.18s ease, background 0.18s ease; }
    .sub-nav-links li a:hover { color: var(--sb-nav-hover); background: var(--sb-hover-bg); }
    .sub-nav-links li a.active {
    background: #ffffff; color: #1e2922;
      font-weight: 700; position: relative;
      margin-right: -1px;
      border-top-left-radius: 12px; border-bottom-left-radius: 12px;
      border-top-right-radius: 0; border-bottom-right-radius: 0;
    }
    .sub-nav-links li:has(a.active) { z-index: 12; }
    .sub-nav-links li:has(a.active)::before {
      content: ""; position: absolute; top: -20px; right: 0;
      height: 20px; width: 20px; background-color: transparent;
      border-bottom-right-radius: 20px;
      box-shadow: 10px 10px 0 10px var(--sb-active-bg);
      pointer-events: none; z-index: 12;
    }
    .sub-nav-links li:has(a.active)::after {
      content: ""; position: absolute; bottom: -20px; right: 0;
      height: 20px; width: 20px; background-color: transparent;
      border-top-right-radius: 20px;
      box-shadow: 10px -10px 0 10px var(--sb-active-bg);
      pointer-events: none; z-index: 12;
    }
  </style>
  {{-- Per-page stylesheets (e.g. the shared AI chat skin). Loaded after the
       inline base styles so a page can restyle shared components on equal
       specificity; dark-mode.css still wins in dark mode (!important). --}}
  @yield('page-styles')
</head>
@php
  // Side menu color: light mode honors the user's saved choice; dark mode always
  // uses the single default color so it stays consistent against the dark pages.
  $stSaved = (string) optional(auth()->user())->sidebar_theme;
  $stActive = in_array($stSaved, array_keys(config('sidebar.themes')), true) ? $stSaved : config('sidebar.default');
  if ((string) optional(auth()->user())->theme_mode === 'dark') {
      $stActive = config('sidebar.default');
  }
@endphp
@php
  $adminNav = config('admin-nav') ?? [];
  $activeAdminSection = null;
  if ($adminNav) {
    foreach ($adminNav as $ai => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeAdminSection = $ai; break 2; }
      }
      // Pages intentionally left out of the menu (hidden_routes in
      // config/admin-nav.php) still open their section while on screen.
      if (!empty($section['hidden_routes']) && request()->routeIs(...$section['hidden_routes'])) { $activeAdminSection = $ai; break; }
    }
  }
  $storeNav = config('store-nav') ?? [];
  $activeStoreSection = null;
  if ($storeNav) {
    foreach ($storeNav as $si => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeStoreSection = $si; break 2; }
      }
    }
  }
  $farmerNav = config('farmer-nav') ?? [];
  $activeFarmerSection = null;
  if ($farmerNav) {
    foreach ($farmerNav as $fi => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeFarmerSection = $fi; break 2; }
      }
    }
  }
@endphp
<body data-sidebar-theme="{{ $stActive }}"@if($captureShield ?? false) data-capture-shield @endif>

  @php
    // Desktop notification bell: every role gets one. It opens a panel with
    // the same rows the notifications page shows, so nothing new costs a
    // page away from whatever the reader is doing. The rows are rendered
    // here, with the badge, so the panel opens on its content - never on a
    // spinner. The page is still one click away, at the foot of the panel.
    $notifBellUrl = null;
    $notifUnreadCount = 0;
    $notifItems = [];
    $notifReadAllUrl = null;
    if (auth()->check()) {
        $notifRole = auth()->user()->role;

        // The role decides the scope; the route prefix decides the pages.
        $notifScope = match ($notifRole) {
            'farmer' => 'farmerNotifications',
            'store_owner' => 'storeNotifications',
            'admin' => 'adminNotifications',
            default => null,
        };
        $notifPrefix = match ($notifRole) {
            'farmer' => 'farmer',
            'store_owner' => 'store',
            'admin' => 'admin',
            default => null,
        };

        if ($notifScope !== null && $notifPrefix !== null) {
            $notifFeed = app(\App\Services\NotificationFeed::class);
            $notifBellUrl = route($notifPrefix . '.notifications.index');
            $notifUnreadCount = $notifFeed->unreadCount(auth()->user());
            $notifItems = $notifFeed->items(auth()->user(), 8, $notifPrefix . '.notifications');
            $notifReadAllUrl = route($notifPrefix . '.notifications.read-all');
        }
    }
  @endphp

  <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

  <header class="mobile-header">
    <div style="display: flex; align-items: center; gap: 4px;">
      <button class="burger-btn" onclick="toggleSidebar()" aria-label="Open menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="aq-mobile-header-brand" style="font-weight: 700; font-size: 18px; line-height: 1;">
        Aqui<span class="brand-accent" style="font-weight: 800;">lala</span>
      </div>
    </div>
    <button id="aqMobileSearchToggle" class="aq-mobile-search-toggle" type="button" aria-label="Search pages" aria-expanded="false" title="Search pages">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
  </header>

  <aside class="sidebar" id="sidebar">
    <div class="brand-section">
      <span class="brand-title">Aqui<span class="brand-accent">lala</span></span>
      Irrigators Association, Inc.
    </div>

    <div class="nav-body">
      <!-- Live Season/Climate Widget -->
@php 
  $climate = $globalClimateInfo ?? (class_exists('\App\Helpers\SeasonHelper') ? \App\Helpers\SeasonHelper::getClimateInfo() : ['season' => 'dry', 'label' => 'Dry']); 
  $isWet = ($climate['season'] ?? 'dry') === 'wet';
  
  // Cache read only — never the live API. The sidebar is rendered on every
  // page, so a blocking OpenWeather call here would add seconds to each
  // navigation whenever the network or API is slow. When the cache is cold the
  // widget shows a placeholder and fills itself in from /weather/current.
  $weather = class_exists('\App\Services\WeatherService') ? \App\Services\WeatherService::cachedWeather() : null;

  $weatherIconMap = [
    'sun'             => 'sunny',
    'cloud-sun'       => 'cloud-sun',
    'cloud'           => 'cloudy',
    'cloud-rain'      => 'rain',
    'cloud-lightning' => 'cloud-lightning',
    'cloud-drizzle'   => 'drizzle',
  ];
  $weatherAvailable = is_array($weather) && !empty($weather['is_available']) && !empty($weather['icon']);
  $weatherIconFile = $weatherAvailable ? ($weatherIconMap[$weather['icon']] ?? 'cloudy') : 'cloudy';
  $seasonIconFile = $isWet ? 'wet-season' : 'dry-season';
@endphp

<div class="climate-card" id="climate-card" tabindex="0" role="button" aria-expanded="false" aria-label="Weather details"
     data-weather-loaded="{{ $weatherAvailable ? '1' : '0' }}"
     data-weather-src="{{ route('weather.current') }}"
     data-weather-icon-base="{{ asset('images/weather') }}"
     data-weather-icons="{{ json_encode($weatherIconMap) }}">
  <div class="climate-row">
    <span class="season-icon {{ $weatherAvailable ? 'weather-available' : 'weather-unavailable' }}" id="climate-weather-icon" style="width: 34px; height: 34px;">
      <img src="{{ asset('images/weather/' . $weatherIconFile . '.png') }}" id="climate-weather-img" alt="{{ $weatherAvailable ? ($weather['condition'] ?? 'Weather') : 'Weather unavailable' }}">
    </span>
    <div class="climate-mini-info">
      <div class="climate-mini-temp" id="climate-weather-temp">{{ $weatherAvailable && $weather['temperature'] !== null ? $weather['temperature'] . '°C' : '—' }}</div>
      <div class="climate-mini-sub" id="climate-weather-condition">{{ $weatherAvailable ? ($weather['condition'] ?? 'Weather unavailable') : 'Loading weather…' }}</div>
    </div>
    <svg class="climate-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
  </div>
  <div class="climate-details">
    <div class="climate-meta">
      <span id="today-date" class="climate-updated"></span>
      <span id="live-time" class="climate-clock"></span>
    </div>
    <div class="climate-season">
      <span class="season-badge">
        <img src="{{ asset('images/weather/' . $seasonIconFile . '.png') }}" alt="{{ $climate['label'] ?? ($isWet ? 'Wet Season' : 'Dry Season') }}">
        <span class="climate-season-info">
          <span class="climate-season-label">{{ $climate['label'] ?? 'Dry Season' }}</span>
          <span class="climate-season-sub">{{ $climate['months'] ?? ($isWet ? 'June – January' : 'February – May') }}</span>
        </span>
      </span>
    </div>
  </div>
</div>


      <div class="menu-group-title">
        @if(auth()->user()->role === 'farmer') Farmer Dashboard @elseif(auth()->user()->role === 'store_owner') My Store @else Admin Control @endif
      </div>
      <ul class="nav-links">
        @if(auth()->user()->role === 'farmer')
          @if($farmerNav)
            @foreach($farmerNav as $fi => $section)
              @php $isFarmerOpen = ($activeFarmerSection === $fi); @endphp
              @php $isFarmerStandalone = !empty($section['standalone']) && count($section['items']) === 1; @endphp
              @php $farmerStandaloneItem = $section['items'][0] ?? null; @endphp
              @if($isFarmerStandalone && $farmerStandaloneItem)
                <li>
                  <a href="{{ route($farmerStandaloneItem['routes'][0]) }}" class="{{ request()->routeIs(...$farmerStandaloneItem['routes']) ? 'active' : '' }}">
                    {!! $section['icon'] !!}
                    {{ $section['label'] }}
                  </a>
                </li>
              @else
                <li class="nav-dropdown {{ $isFarmerOpen ? 'open' : '' }}">
                  <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isFarmerOpen ? 'true' : 'false' }}">
                    <span class="nav-dropdown-title">
                      {!! $section['icon'] !!}
                      <span>{{ $section['label'] }}</span>
                    </span>
                    <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                  </button>
                  <ul class="sub-nav-links">
                    @foreach($section['items'] as $item)
                      <li>
                        <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                          {{ $item['label'] }}
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </li>
              @endif
            @endforeach
          @else
          <li>
            <a href="{{ route('dashboard.farmer') }}" class="{{ request()->routeIs('dashboard.farmer') || request()->routeIs('farmer.dashboard') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.crops.index') }}" class="{{ request()->routeIs('farmer.crops.index', 'farmer.crops.show') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              My Crops
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.harvests.index') }}" class="{{ request()->routeIs('farmer.harvests.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
              Harvest Reports
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.harvests.compare') }}" class="{{ request()->routeIs('farmer.harvests.compare') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
              Estimate vs Harvest
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.calendar.index') }}" class="{{ request()->routeIs('farmer.calendar.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Calendar
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.recommendations.index') }}" class="{{ request()->routeIs('farmer.recommendations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Recommendations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.crops-directory.index') }}" class="{{ request()->routeIs('farmer.crops-directory.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              Crop Directory
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.ai-chat.index') }}" class="{{ request()->routeIs('farmer.ai-chat.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7l.9 2.4L15.3 10l-2.4.9L12 13.3l-.9-2.4L8.7 10l2.4-.6z"/></svg>
              AI Assistant
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.products.browse') }}" class="{{ request()->routeIs('farmer.products.browse', 'farmer.products.show') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Browse Products
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.top-quoted.index') }}" class="{{ request()->routeIs('farmer.top-quoted.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Top Quoted Products
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.stores.index') }}" class="{{ request()->routeIs('farmer.stores.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Stores Directory
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.quotations.index') }}" class="{{ request()->routeIs('farmer.quotations.index', 'farmer.quotations.show', 'farmer.quotations.breakdown') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              My Cost Quotations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.settings.index') }}" class="{{ request()->routeIs('farmer.settings.index', 'farmer.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @endif

        @elseif(auth()->user()->role === 'store_owner')
          @if($storeNav)
            @foreach($storeNav as $si => $section)
              @php $isStoreOpen = ($activeStoreSection === $si); @endphp
              @php $isStoreStandalone = !empty($section['standalone']) && count($section['items']) === 1; @endphp
              @php $storeStandaloneItem = $section['items'][0] ?? null; @endphp
              @if($isStoreStandalone && $storeStandaloneItem)
                <li>
                  <a href="{{ route($storeStandaloneItem['routes'][0]) }}" class="{{ request()->routeIs(...$storeStandaloneItem['routes']) ? 'active' : '' }}">
                    {!! $section['icon'] !!}
                    {{ $section['label'] }}
                  </a>
                </li>
              @else
                <li class="nav-dropdown {{ $isStoreOpen ? 'open' : '' }}">
                  <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isStoreOpen ? 'true' : 'false' }}">
                    <span class="nav-dropdown-title">
                      {!! $section['icon'] !!}
                      <span>{{ $section['label'] }}</span>
                    </span>
                    <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                  </button>
                  <ul class="sub-nav-links">
                    @foreach($section['items'] as $item)
                      <li>
                        <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                          {{ $item['label'] }}
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </li>
              @endif
            @endforeach
          @else
            <li>
              <a href="{{ route('store.dashboard') }}" class="{{ request()->routeIs('store.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Store Dashboard
              </a>
            </li>
          <li>
            <a href="{{ route('store.dashboard.performance') }}" class="{{ request()->routeIs('store.dashboard.performance') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Top Products
            </a>
          </li>
          <li>
            <a href="{{ route('store.inventory.index') }}" class="{{ request()->routeIs('store.inventory.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              Inventory Management
            </a>
          </li>
          <li>
            <a href="{{ route('store.quotations.index') }}" class="{{ request()->routeIs('store.quotations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              Quotation Demand Trends
            </a>
          </li>
          <li>
            <a href="{{ route('store.ai-chat.index') }}" class="{{ request()->routeIs('store.ai-chat.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7l.9 2.4L15.3 10l-2.4.9L12 13.3l-.9-2.4L8.7 10l2.4-.6z"/></svg>
              AI Assistant
            </a>
          </li>
          <li>
            <a href="{{ route('store.storefront.index') }}" class="{{ request()->routeIs('store.storefront.index') ? 'active' : '' }}">
              <img src="{{ asset('images/store-%23b9cfc0.png') }}" alt="" />
              My Store
            </a>
          </li>
          <li>
            <a href="{{ route('store.settings.index') }}" class="{{ request()->routeIs('store.settings.index', 'store.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @endif

        @elseif(auth()->user()->role === 'admin')
          @if($adminNav)
            @foreach($adminNav as $ai => $section)
              @php
                $isOpen = ($activeAdminSection === $ai);
                $isStandalone = !empty($section['standalone']);
                $standaloneItem = $section['items'][0] ?? null;
              @endphp
              @if($isStandalone && $standaloneItem)
                <li>
                  <a href="{{ route($standaloneItem['routes'][0]) }}" class="{{ request()->routeIs(...$standaloneItem['routes']) ? 'active' : '' }}">
                    {!! $section['icon'] !!}
                    {{ $section['label'] }}
                  </a>
                </li>
              @else
                <li class="nav-dropdown {{ $isOpen ? 'open' : '' }}">
                  <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                    <span class="nav-dropdown-title">
                      {!! $section['icon'] !!}
                      <span>{{ $section['label'] }}</span>
                    </span>
                    <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                  </button>
                  <ul class="sub-nav-links">
                    @foreach($section['items'] as $item)
                      <li>
                        <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                          {{ $item['label'] }}
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </li>
              @endif
            @endforeach
          @else
          <li>
            <a href="{{ route('dashboard.admin') }}" class="{{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('admin.members.index') }}" class="{{ request()->is('admin/members*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
              Members
            </a>
          </li>
          <li>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->is('admin/users*') || request()->is('admin/approvals*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Users
            </a>
          </li>
          <li>
            <a href="{{ route('admin.stores.index') }}" class="{{ request()->is('admin/stores*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/><path d="M4 11h16v10H4z"/></svg>
              Stores
            </a>
          </li>
          <li>
            <a href="{{ route('admin.operations.catalog') }}" class="{{ request()->is('admin/operations*') || request()->routeIs('admin.plantings.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              Crops &amp; Inputs
            </a>
          </li>
          <li>
            <a href="{{ route('admin.harvests.index') }}" class="{{ request()->routeIs('admin.harvests.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M12 20V10"/><path d="M12 10C9 10 6 9 6 5c3 0 6 2 6 5"/><path d="M12 14c3 0 6-1 6-5-3 0-6 2-6 5"/><path d="M3 20h18"/><path d="M3 12l4-4 4 4 5-5 5 5"/></svg>
              Harvest
            </a>
          </li>
          <li>
            <a href="{{ route('admin.calendar.index') }}" class="{{ request()->routeIs('admin.calendar.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Calendar
            </a>
          </li>
          <li>
            <a href="{{ route('admin.quotations.index') }}" class="{{ request()->is('admin/analytics*') || request()->is('admin/quotations*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              Analytics
            </a>
          </li>
          <li>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->is('admin/settings*') || request()->routeIs('admin.archive', 'admin.audit.index', 'admin.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @endif
        @endif

        @yield('nav-links')
      </ul>
    </div>

    <div class="sidebar-footer">
      <div class="profile-card">
        <a href="{{ route('profile.index') }}" class="user-profile-badge" title="View my profile">
          <x-user-avatar :user="auth()->user()" :size="36" />
          <div class="user-info">
            <span class="user-name">{{ auth()->user()->name }}</span>
            <span class="user-role">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
          </div>
          <svg class="profile-chevron" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
        </a>
      </div>

      {{-- Logout lands on the auth layout (different <head>), so bypass Turbo
           and do a full page load — same for any jump out of the app shell. --}}
      <form method="POST" action="{{ route('logout') }}" data-turbo="false">
        @csrf
        <button type="submit" class="logout-btn">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Log Out
        </button>
      </form>
    </div>
  </aside>

  <main class="main-content">
    @yield('content')
  </main>

  @if($notifBellUrl)
  <div class="desktop-notif-wrap">
    <a class="desktop-notif" id="desktopNotifBtn" href="{{ $notifBellUrl }}" aria-label="Notifications" title="Notifications" aria-haspopup="dialog">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      @if($notifUnreadCount > 0)
        <span class="notif-badge" id="desktopNotifBadge">{{ $notifUnreadCount }}</span>
      @endif
    </a>
  </div>

  {{-- The bell opens this panel instead of leaving the page. The rows are
       rendered with the page, so the panel opens on its content; it reads
       the same rows as the notifications page, through the same service, so
       the two can never disagree. --}}
  <div class="nmodal" id="notifModal" role="dialog" aria-labelledby="notifModalTitle" aria-hidden="true" hidden>
    <div class="nmodal-card" role="document">
      <div class="nmodal-head">
        <div>
          <h2 id="notifModalTitle">Notifications</h2>
          <p id="notifModalCount">{{ $notifUnreadCount > 0 ? $notifUnreadCount . ' unread' : 'You are all caught up' }}</p>
        </div>
        <button type="button" class="nmodal-close" data-notif-close aria-label="Close notifications" title="Close">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="nmodal-body" id="notifModalBody">
        @forelse($notifItems as $item)
          <div class="nmodal-item{{ $item['unread'] ? ' is-unread' : '' }}">
              @if(!empty($item['url']))
                <a class="nmodal-item-link" href="{{ $item['url'] }}"
                   @if($item['unread'] && $item['read_url']) data-read-url="{{ $item['read_url'] }}" @endif>
              @else
                <div class="nmodal-item-link">
              @endif
                  <span class="nmodal-dot"></span>
                  <div class="nmodal-mid">
                    <div class="nmodal-text">
                      @if($item['state_label'])
                        <span class="nmodal-state nmodal-state--{{ $item['state'] }}">{{ $item['state_label'] }}</span>
                      @endif
                      {{ $item['text'] }}
                    </div>
                    <div class="nmodal-meta">
                      {{ $item['meta'] }}@if($item['actor']) &middot; {{ $item['actor'] }}@endif
                    </div>
                  </div>
              @if(!empty($item['url']))
                </a>
              @else
                </div>
              @endif
            @if($item['unread'] && $item['read_url'])
              <button type="button" class="nmodal-done" data-read-url="{{ $item['read_url'] }}" aria-label="Mark as read" title="Mark as read">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            @endif
          </div>
        @empty
          <div class="nmodal-empty">
            <div class="nmodal-empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <h3>Nothing new</h3>
            <p>When something reaches you, it will show up here.</p>
          </div>
        @endforelse
      </div>

      <div class="nmodal-foot">
        <button type="button" class="nmodal-readall" id="notifReadAll" data-url="{{ $notifReadAllUrl }}" @if($notifUnreadCount === 0) hidden @endif>
          <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Mark all as read
        </button>
        <a class="nmodal-all" id="notifAllLink" href="{{ $notifBellUrl }}">View all notifications</a>
      </div>
    </div>
  </div>
  @endif

  <script>
    // Render every <i data-lucide="..."> into its official Lucide SVG.
    // This has to live in the layout, not in a single page: components such
    // as the product card are shared by the storefront, the farmer marketplace
    // and the dashboards, and an icon with no init pass stays an empty <i>.
    // Re-running is safe — Lucide only swaps the placeholder for the glyph.
    function renderLucideIcons() {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }
    }
    renderLucideIcons();
    document.addEventListener('DOMContentLoaded', renderLucideIcons);

    // Apply dynamic styles from data-inline-style (keeps Blade interpolation
    // out of style="" attributes). setAttribute, NOT style.cssText: assigning
    // cssText makes the browser SERIALIZE the style attribute with normalized
    // colors ("background: #ffffff" comes back as "background: rgb(255, 255,
    // 255)"), which breaks the generated [style*="#ffffff"] dark-mode rules
    // that match on the literal source string. setAttribute keeps the literal.
    function applyDataInlineStyles() {
      document.querySelectorAll('[data-inline-style]').forEach(function (el) {
        el.setAttribute('style', el.getAttribute('data-inline-style'));
      });
      // For elements whose width is animated by JS (bars), only set the --bg custom property
      document.querySelectorAll('[data-bg]').forEach(function (el) {
        el.style.setProperty('--bg', el.getAttribute('data-bg'));
      });
    }
    applyDataInlineStyles();
    document.addEventListener('DOMContentLoaded', applyDataInlineStyles);

    // Shared overlay scrollbar pill (green) — wires every .app-scrollbar to its sibling .app-scroll
    function initAppScrollbars() {
      document.querySelectorAll('.app-scrollbar').forEach(function (bar) {
        if (bar.dataset.ready) return;
        bar.dataset.ready = '1';
        var wrap = bar.parentElement;
        var scroller = wrap ? wrap.querySelector('.app-scroll') : null;
        if (!scroller) return;
        var thumb = bar.querySelector('.app-scrollbar-thumb');
        if (!thumb) return;

        function update() {
          var sw = scroller.scrollWidth;
          var cw = scroller.clientWidth;
          var canScroll = sw > cw + 1;
          bar.style.display = canScroll ? 'block' : 'none';
          // When there is nothing to scroll, the room reserved for the pill
          // is dead space under the table — drop it.
          wrap.classList.toggle('no-scroll', !canScroll);
          if (!canScroll) return;
          var bw = bar.clientWidth;
          var tw = Math.max(48, Math.min(bw * 0.42, (cw / sw) * bw * 0.72));
          thumb.style.width = tw + 'px';
          var maxScroll = sw - cw;
          var maxLeft = bw - tw;
          thumb.style.left = maxScroll > 0 ? ((scroller.scrollLeft / maxScroll) * maxLeft) + 'px' : '0px';
        }

        scroller.addEventListener('scroll', update);
        window.addEventListener('resize', update);

        // The table toolbar re-filters and re-sorts rows in place, which
        // changes how wide the table is — watch the content so the pill (and
        // the room reserved for it) stays in step.
        if (typeof ResizeObserver !== 'undefined') {
          new ResizeObserver(update).observe(scroller);
        }

        var dragging = false;
        var startX = 0;
        var startLeft = 0;
        thumb.addEventListener('mousedown', function (e) {
          dragging = true;
          startX = e.clientX;
          startLeft = parseFloat(thumb.style.left) || 0;
          bar.classList.add('dragging');
          e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
          if (!dragging) return;
          var bw = bar.clientWidth;
          var tw = thumb.offsetWidth;
          var maxLeft = bw - tw;
          var left = Math.max(0, Math.min(maxLeft, startLeft + (e.clientX - startX)));
          var maxScroll = scroller.scrollWidth - scroller.clientWidth;
          scroller.scrollLeft = maxLeft > 0 ? (left / maxLeft) * maxScroll : 0;
        });
        document.addEventListener('mouseup', function () {
          dragging = false;
          bar.classList.remove('dragging');
        });

        update();
      });
    }
    initAppScrollbars();
    document.addEventListener('DOMContentLoaded', initAppScrollbars);

    // Live date + time clock — updates every second
    function updateLiveTime() {
      const now = new Date();
      const dateEl = document.getElementById('today-date');
      const timeEl = document.getElementById('live-time');
      if (dateEl) {
        dateEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric' }).format(now);
      }
      if (timeEl) {
        timeEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now);
      }
    }
    updateLiveTime();
    // Started once for the whole session — the interval's own closure re-
    // queries the clock elements on every tick, so it survives body swaps.
    if (!window.__aqClock) window.__aqClock = setInterval(updateLiveTime, 1000);

    // Auto-scroll the active nav link into the center of the scrollable sidebar
    function centerActiveNav() {
      const navBody = document.querySelector('.nav-body');
      const activeLink = document.querySelector('.nav-links li a.active');
      if (!navBody || !activeLink) return;

      const navRect = navBody.getBoundingClientRect();
      const linkRect = activeLink.getBoundingClientRect();

      // If the active link is already fully visible, do nothing
      const isVisible =
        linkRect.top >= navRect.top &&
        linkRect.bottom <= navRect.bottom;
      if (isVisible) return;

      // Center the active link within the scrollable area
      const linkCenter = linkRect.top - navRect.top + activeLink.offsetHeight / 2;
      const scrollTarget = navBody.scrollTop + linkCenter - navRect.height / 2;
      navBody.scrollTo({ top: Math.max(0, scrollTarget), behavior: 'smooth' });
    }

    // Run on page load (after full render) and after the sidebar opens on mobile
    window.addEventListener('load', centerActiveNav);
    document.addEventListener('DOMContentLoaded', centerActiveNav);
    setTimeout(centerActiveNav, 100);
    setTimeout(centerActiveNav, 350);

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('overlay').classList.toggle('open');
      // Re-center when the mobile sidebar is opened
      setTimeout(centerActiveNav, 250);
    }

    function toggleNavDropdown(btn) {
      var li = btn.closest('.nav-dropdown');
      if (!li) return;
      var open = li.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    // Sidebar dropdowns: ONE delegated listener on <document>, bound once for
    // the whole session. The old approach attached a listener to every button
    // through the DOMContentLoaded queue — but those listeners live on the
    // sidebar's DOM nodes, and Turbo replaces the whole body (and restores
    // cloned snapshots) whenever the page is swapped or the tab/app is
    // resumed after being left. When the re-init didn't run, a CLOSED
    // dropdown could never be opened again, while plain links kept working
    // because native navigation needs no listener. Delegation resolves the
    // button at click time, so it works on whatever markup is in the DOM.
    if (!window.__aqDdDelegate) {
      window.__aqDdDelegate = true;
      document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('.nav-dropdown-btn') : null;
        if (!btn) return;
        toggleNavDropdown(btn);
      });
    }

    // Climate widget: background fill when the server had no fresh weather to
    // render. The page never waits on the weather API — this runs after paint
    // and only when the card was rendered without cached data.
    (function () {
      var card = document.getElementById('climate-card');
      if (!card || card.getAttribute('data-weather-loaded') === '1') return;
      if (card.getAttribute('data-weather-src') === null || typeof window.fetch !== 'function') return;

      var tempEl = document.getElementById('climate-weather-temp');
      var condEl = document.getElementById('climate-weather-condition');
      var iconEl = document.getElementById('climate-weather-icon');
      var imgEl = document.getElementById('climate-weather-img');
      var icons = {};
      try { icons = JSON.parse(card.getAttribute('data-weather-icons') || '{}'); } catch (e) { icons = {}; }
      var iconBase = (card.getAttribute('data-weather-icon-base') || '').replace(/\/+$/, '');

      function markUnavailable() {
        if (condEl) condEl.textContent = 'Weather unavailable';
        card.setAttribute('data-weather-loaded', '1');
      }

      fetch(card.getAttribute('data-weather-src'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (res) { if (!res.ok) throw new Error('weather ' + res.status); return res.json(); })
        .then(function (w) {
          if (!w || !w.is_available) { markUnavailable(); return; }
          if (tempEl && w.temperature !== null && w.temperature !== undefined) tempEl.textContent = w.temperature + '°C';
          if (condEl && w.condition) condEl.textContent = w.condition;
          if (imgEl && w.icon && icons[w.icon] && iconBase) {
            imgEl.src = iconBase + '/' + icons[w.icon] + '.png';
            if (w.condition) imgEl.alt = w.condition;
          }
          if (iconEl) { iconEl.classList.remove('weather-unavailable'); iconEl.classList.add('weather-available'); }
          card.setAttribute('data-weather-loaded', '1');
        })
        .catch(markUnavailable);
    })();

    // Climate widget: click/tap toggles open/closed (pins it; hover also expands on desktop)
    function toggleClimate() {
      const card = document.getElementById('climate-card');
      if (!card) return;
      const pinned = card.classList.contains('pinned');
      card.classList.toggle('pinned', !pinned);
      card.setAttribute('aria-expanded', String(!pinned));
    }
    // Climate widget: same once-only delegation as the dropdowns (see above)
    // so a replaced/restored sidebar can never end up with dead handlers.
    if (!window.__aqClimateDelegate) {
      window.__aqClimateDelegate = true;
      document.addEventListener('click', function (e) {
        var card = e.target && e.target.closest ? e.target.closest('#climate-card') : null;
        if (!card) return;
        e.stopPropagation();
        toggleClimate();
      });
      document.addEventListener('keydown', function (e) {
        var card = document.getElementById('climate-card');
        if (!card) return;
        if ((e.key === 'Enter' || e.key === ' ') && document.activeElement === card) {
          e.preventDefault();
          toggleClimate();
        }
        // Close the pinned card when pressing Escape
        if (e.key === 'Escape' && card.classList.contains('pinned')) {
          card.classList.remove('pinned');
          card.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Desktop notification bell — hide on scroll down, show on scroll up (desktop only)
    document.addEventListener('DOMContentLoaded', function () {
      var wrap = document.querySelector('.desktop-notif-wrap');
      if (!wrap) return;

      window.addEventListener('scroll', function () {
        if (wrap.classList.contains('hidden')) {
          wrap.classList.remove('hidden');
        }
      }, { passive: true });
    });

    // ── The notification bell opens a panel ─────────────────────────────
    // The bell used to be a link to the notifications page, which meant
    // reading one notice cost the page you were on. It now opens a panel
    // with the same rows, and the page is still one click away at the
    // foot of it.
    //
    // The rows arrive with the page, so the panel opens on its content —
    // there is no spinner between the click and the list. Reading in the
    // panel posts to the same route the notifications page uses, and the
    // row and the badge are updated in place.
    (function () {
      var bell = document.getElementById('desktopNotifBtn');
      var modal = document.getElementById('notifModal');
      if (!bell || !modal) return;

      var countLabel = document.getElementById('notifModalCount');
      var readAllBtn = document.getElementById('notifReadAll');
      var allLink = document.getElementById('notifAllLink');

      function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
      }

      // The badge is only rendered when there is something unread, so it
      // may have to be made once the count turns up.
      function setBadge(count) {
        var badge = document.getElementById('desktopNotifBadge');

        if (!badge) {
          if (!count) return;
          badge = document.createElement('span');
          badge.className = 'notif-badge';
          badge.id = 'desktopNotifBadge';
          bell.appendChild(badge);
        }

        if (count > 0) {
          badge.textContent = count > 99 ? '99+' : count;
          badge.hidden = false;
        } else {
          badge.hidden = true;
        }
      }

      function setCount(count) {
        countLabel.textContent = count > 0 ? count + ' unread' : 'You are all caught up';
        readAllBtn.hidden = !(count > 0);
        setBadge(count);
      }

      function open() {
        window.dispatchEvent(new CustomEvent('aq-notification-open'));
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
      }

      function close() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
      }
      window.aqCloseNotifications = close;
      window.addEventListener('aq-search-open', close);

      function post(url) {
        return fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        }).then(function (r) { return r.json(); });
      }

      bell.addEventListener('click', function (e) {
        e.preventDefault();
        // The bell is a switch: the same icon that opened the panel closes
        // it again, so the reader never has to hunt for a way out.
        if (modal.hidden) {
          open();
        } else {
          close();
        }
      });

      modal.addEventListener('click', function (e) {
        if (e.target.closest('[data-notif-close]')) {
          close();
          return;
        }

        // Clicking a notice opens the page it is about, and the notice
        // counts as read on the way: it has been seen.
        var link = e.target.closest('.nmodal-item-link[data-read-url]');
        if (link) {
          var readUrl = link.getAttribute('data-read-url');
          if (readUrl) {
            post(readUrl).then(function (data) {
              var row = link.closest('.nmodal-item');
              if (row) row.classList.remove('is-unread');
              var done = link.closest('.nmodal-item').querySelector('.nmodal-done');
              if (done) done.remove();
              setCount(parseInt(data.unread, 10) || 0);
            }).catch(function () { /* the page opens either way */ });
          }
          return;
        }

        var done = e.target.closest('.nmodal-done');
        if (done) {
          var row = done.closest('.nmodal-item');
          done.disabled = true;
          post(done.getAttribute('data-read-url')).then(function (data) {
            row.classList.remove('is-unread');
            done.remove();
            setCount(parseInt(data.unread, 10) || 0);
          }).catch(function () { done.disabled = false; });
          return;
        }

        if (e.target.closest('#notifReadAll')) {
          readAllBtn.disabled = true;
          post(readAllBtn.dataset.url).then(function () {
            modal.querySelectorAll('.nmodal-item.is-unread').forEach(function (row) {
              row.classList.remove('is-unread');
            });
            modal.querySelectorAll('.nmodal-done').forEach(function (b) { b.remove(); });
            setCount(0);
          }).catch(function () { readAllBtn.disabled = false; });
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) close();
      });

      // A panel under the bell closes when the reader touches anything
      // outside it. The bell itself is excluded, or clicking it again
      // would close and immediately reopen.
      document.addEventListener('click', function (e) {
        if (modal.hidden) return;
        if (modal.contains(e.target) || bell.contains(e.target)) return;
        close();
      });
    })();

    // ── Resilience guards (registered ONCE per session) ──────────────────
    // 1) A Turbo visit whose request never settles (server asleep, connection
    //    dropped while the app was in the background) leaves the shell stuck:
    //    clicks look dead until a manual refresh. Watch every visit and do
    //    the refresh automatically if it does not finish in time.
    // 2) When the session expires while idle, Turbo would render the login
    //    body INSIDE this shell; do a clean full-page redirect instead.
    // 3) Returning from another app/tab can restore a stale modal lock
    //    (overlay hidden but body still scroll-locked) — clear it.
    if (!window.__aqNavGuard) {
      window.__aqNavGuard = true;
      var aqNavTimer = null;
      var aqArmNavTimer = function () {
        clearTimeout(aqNavTimer);
        aqNavTimer = setTimeout(function () {
          aqNavTimer = null;
          window.location.reload();
        }, 20000);
      };
      var aqClearNavTimer = function () { clearTimeout(aqNavTimer); aqNavTimer = null; };
      document.addEventListener('turbo:click', aqArmNavTimer);
      document.addEventListener('turbo:before-fetch-request', aqArmNavTimer);
      document.addEventListener('turbo:visit', aqClearNavTimer);
      document.addEventListener('turbo:render', aqClearNavTimer);
      document.addEventListener('turbo:load', aqClearNavTimer);
      document.addEventListener('turbo:fetch-request-error', function () {
        aqClearNavTimer();
        window.location.reload();
      });
      // Session expired mid-idle: send the user to the real login page.
      document.addEventListener('turbo:before-fetch-response', function (e) {
        try {
          var resp = e.detail && e.detail.fetchResponse && e.detail.fetchResponse.response;
          if (!resp || !resp.redirected) return;
          var loc = new URL(resp.url, location.href);
          if (loc.pathname === '/login') {
            aqClearNavTimer();
            window.location.href = loc.pathname + loc.search;
          }
        } catch (err) { /* ignore */ }
      });
      // Stale locks when the app is resumed.
      window.addEventListener('pageshow', function () {
        try {
          var ov = document.getElementById('aqnOverlay');
          if (!ov || !ov.classList.contains('open')) document.body.classList.remove('aqn-lock');
        } catch (err) { /* ignore */ }
      });
    }
  </script>

  <style>
    /* -- Global notice modal (replaces native alert()/confirm()) -- */
    body.aqn-lock { overflow: hidden; }
    .aqn-overlay {
      position: fixed; inset: 0; z-index: 12000;
      display: flex; align-items: center; justify-content: center; padding: 20px;
      background: rgba(243, 246, 244, 0.30);
      opacity: 0; visibility: hidden; transition: opacity 0.18s ease;
    }
    .aqn-overlay.open { opacity: 1; visibility: visible; }
    .aqn-card {
      width: 100%; max-width: 400px; background: #ffffff; border: 1px solid #e5ebe7;
      border-radius: 18px; padding: 28px 26px 22px; text-align: center;
      box-shadow: 0 18px 48px rgba(20, 61, 35, 0.16), 0 2px 8px rgba(20, 61, 35, 0.06);
      transform: translateY(8px) scale(0.97); opacity: 0;
      transition: transform 0.18s ease, opacity 0.18s ease;
    }
    .aqn-overlay.open .aqn-card { transform: translateY(0) scale(1); opacity: 1; }
    .aqn-icon {
      width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .aqn-icon svg { width: 27px; height: 27px; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .aqn-icon.help   { background: #eef7ef; color: #15803d; }
    .aqn-icon.info   { background: #eef4ff; color: #2563eb; }
    .aqn-icon.warn   { background: #fdf3e7; color: #c2620b; }
    .aqn-icon.danger { background: #fdeeee; color: #c0392b; }
    .aqn-icon.template { background: transparent; color: #4b5c50; }
    .aqn-title { font-size: 17px; font-weight: 700; color: #1f2a24; margin-bottom: 6px; }
    .aqn-message { font-size: 14px; line-height: 1.55; color: #55625b; word-break: break-word; }
    .aqn-actions { display: flex; gap: 10px; margin-top: 22px; }
    .aqn-btn {
      flex: 1; border: none; border-radius: 999px; padding: 10px 16px;
      font-size: 14px; font-weight: 600; font-family: inherit; cursor: pointer;
      transition: background 0.15s ease, transform 0.05s ease;
    }
    .aqn-btn:active { transform: scale(0.98); }
    .aqn-btn.primary { background: #4A7A54; color: #ffffff; }
    .aqn-btn.primary:hover { background: #15803d; }
    .aqn-btn.danger { background: #c0392b; color: #ffffff; }
    .aqn-btn.danger:hover { background: #a93226; }
    .aqn-btn.ghost { background: #eef2ef; color: #33413a; }
    .aqn-btn.ghost:hover { background: #e2e8e4; }
  </style>

  <div class="aqn-overlay" id="aqnOverlay" aria-hidden="true">
    <div class="aqn-card" role="dialog" aria-modal="true" aria-labelledby="aqnTitle" aria-describedby="aqnMsg">
      <div class="aqn-icon" id="aqnIcon"></div>
      <div class="aqn-title" id="aqnTitle"></div>
      <p class="aqn-message" id="aqnMsg"></p>
      <div class="aqn-actions" id="aqnActions"></div>
    </div>
  </div>

  <script>
    (function () {
      'use strict';
      // Turbo replaces the body on every visit, so element references are
      // resolved lazily instead of being captured once. The first instance
      // owns the document-level listeners (registered once) and stays the
      // public AquiNotice API for the whole session.
      if (window.__aqAqui) { window.AquiNotice = window.__aqAqui; return; }

      function overlay() { return document.getElementById('aqnOverlay'); }
      function icon() { return document.getElementById('aqnIcon'); }
      function titleEl() { return document.getElementById('aqnTitle'); }
      function msgEl() { return document.getElementById('aqnMsg'); }
      function actionsEl() { return document.getElementById('aqnActions'); }
      var resolver = null;
      var hideTimer = null;

      var ICONS = {
        help: '<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        warn: '<svg viewBox="0 0 24 24" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        danger: '<svg viewBox="0 0 24 24" stroke="currentColor"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        success: '<svg viewBox="0 0 24 24" stroke="currentColor"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        template: '<svg viewBox="0 0 24 24" stroke="currentColor"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path></svg>'
      };

      function settle(value) {
        if (resolver) { var r = resolver; resolver = null; r(value); }
      }

      function dismiss() {
        var ov = overlay();
        if (ov) ov.classList.remove('open');
        document.body.classList.remove('aqn-lock');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
          var o = overlay();
          if (o) o.setAttribute('aria-hidden', 'true');
        }, 200);
      }

      function open(options) {
        var ov = overlay(), ic = icon(), ti = titleEl(), me = msgEl(), ae = actionsEl();
        if (!ov || !ic || !ti || !me || !ae) return;
        var tone = options.tone || (options.danger ? 'danger' : 'help');
        ic.className = 'aqn-icon ' + tone;
        ic.innerHTML = ICONS[tone] || ICONS.help;
        ti.textContent = options.title || '';
        ti.style.display = options.title ? '' : 'none';
        me.textContent = options.message || '';
        ae.innerHTML = '';

        var addBtn = function (label, cls, value) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'aqn-btn ' + cls;
          b.textContent = label;
          b.addEventListener('click', function () { settle(value); dismiss(); });
          ae.appendChild(b);
          return b;
        };

        if (options.showCancel !== false) {
          addBtn(options.cancelLabel || 'Cancel', 'ghost', false);
        }
        var ok = addBtn(
          options.confirmLabel || (options.danger ? 'Delete' : 'Confirm'),
          options.danger ? 'danger' : 'primary',
          true
        );
        ok.focus();

        ov.classList.add('open');
        ov.setAttribute('aria-hidden', 'false');
        document.body.classList.add('aqn-lock');
      }

      function confirm(options) {
        return new Promise(function (resolve) {
          resolver = resolve;
          open(options);
        });
      }

      function notice(options) {
        return new Promise(function (resolve) {
          resolver = resolve;
          open(Object.assign({}, options, {
            showCancel: false,
            confirmLabel: options.confirmLabel || 'OK',
            tone: options.tone || 'success'
          }));
        });
      }

      window.__aqAqui = { confirm: confirm, notice: notice };
      window.AquiNotice = window.__aqAqui;

      // Document-level listeners are registered exactly once for the whole
      // session (lazy element lookups keep them valid across Turbo visits).
      document.addEventListener('click', function (e) {
        if (e.target === overlay()) { settle(false); dismiss(); return; }
        var el = e.target.closest ? e.target.closest('[data-aqui-confirm]') : null;
        if (!el || e.defaultPrevented || e.button !== 0) return;
        if (el.tagName === 'FORM') return; // forms are handled on submit
        e.preventDefault();
        var tone = el.getAttribute('data-aqui-tone');
        var danger = el.hasAttribute('data-aqui-danger') || tone === 'danger';
        confirm({
          message: el.getAttribute('data-aqui-confirm'),
          title: el.getAttribute('data-aqui-title'),
          tone: tone,
          danger: danger,
          confirmLabel: el.getAttribute('data-aqui-confirm-label') || (danger ? 'Delete' : 'Continue')
        }).then(function (ok) {
          if (!ok) return;
          if (el.tagName === 'A') {
            window.location.href = el.href;
          } else if (el.form) {
            el.form.setAttribute('data-aqui-confirm-ok', '1');
            if (el.form.requestSubmit) el.form.requestSubmit(el); else el.form.submit();
          }
        });
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { settle(false); dismiss(); }
      });

      // Forms whose submitter (or the form itself) carries data-aqui-confirm.
      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches('form')) return;
        if (form.hasAttribute('data-aqui-confirm-ok')) {
          form.removeAttribute('data-aqui-confirm-ok');
          return;
        }
        var sub = e.submitter || null;
        var el = sub && sub.hasAttribute('data-aqui-confirm') ? sub : (form.hasAttribute('data-aqui-confirm') ? form : null);
        if (!el) return;
        e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
        if (sub) sub.disabled = true;
        var tone = el.getAttribute('data-aqui-tone');
        var danger = el.hasAttribute('data-aqui-danger') || tone === 'danger';
        confirm({
          message: el.getAttribute('data-aqui-confirm'),
          title: el.getAttribute('data-aqui-title'),
          tone: tone,
          danger: danger,
          confirmLabel: el.getAttribute('data-aqui-confirm-label') || (danger ? 'Delete' : 'Continue')
        }).then(function (ok) {
          if (!ok) { if (sub) sub.disabled = false; return; }
          form.setAttribute('data-aqui-confirm-ok', '1');
          if (form.requestSubmit && sub) form.requestSubmit(sub);
          else form.submit();
        });
      });
    })();
  </script>
  <script>
  (function () {
    var button = document.getElementById('aqMobileSearchToggle');

    function closeSearch() {
      var results = document.getElementById('aqGlobalSearchResults');
      document.documentElement.classList.remove('aq-mobile-search-open');
      if (results) {
        results.classList.remove('aq-show');
        results.classList.remove('aq-expanded');
      }
      if (button) button.setAttribute('aria-expanded', 'false');
    }

    function toggle() {
      var isOpen = document.documentElement.classList.toggle('aq-mobile-search-open');
      if (!isOpen) {
        closeSearch();
      } else {
        window.dispatchEvent(new CustomEvent('aq-search-open'));
        if (button) button.setAttribute('aria-expanded', 'true');
        var input = document.getElementById('aqGlobalSearch');
        if (input) window.setTimeout(function () { input.focus(); }, 80);
      }
    }
    if (button) button.addEventListener('click', function (event) {
      event.stopPropagation();
      toggle();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeSearch();
      }
    });
    document.addEventListener('click', function (event) {
      if (
        document.documentElement.classList.contains('aq-mobile-search-open') &&
        (!event.target.closest || !event.target.closest('#aqGlobalSearchHost'))
      ) {
        closeSearch();
      }
    });
    window.addEventListener('aq-notification-open', closeSearch);
  })();
  </script>

  @stack('scripts')


@php
  $aqSearchUser = Auth::user();
  $aqSearchRole = $aqSearchUser?->role ?? '';
  $aqSearchPages = [];
  $aqSearchDescriptions = [
      'admin.settings.index' => 'Manage system preferences and open the settings subpages.',
      'admin.settings.interface' => 'Choose display, harvest units, number formatting, and interface preferences.',
      'admin.settings.seasons' => 'Configure planting seasons used by harvest reports and quotations.',
      'admin.settings.weather' => 'Record weather events such as typhoons, floods, droughts, and other field conditions.',
      'farmer.settings.index' => 'Manage your account, appearance, saved items, and personal preferences.',
      'farmer.quotations.index' => 'Review your cost quotations and open quotation details or breakdowns.',
      'farmer.quotations.request' => 'Create a cost quotation by choosing your crop, area, season, and farming inputs.',
      'farmer.quotations.archive' => 'View archived cost quotations that are no longer in your active list.',
      'farmer.products.browse' => 'Browse farm supplies from available stores and open product details.',
      'farmer.recommendations.index' => 'Find products recommended for your selected crop and farming needs.',
      'farmer.top-quoted.index' => 'See the products most often included in farmer cost quotations.',
      'store.settings.index' => 'Manage store account, appearance, saved settings, and profile preferences.',
      'store.storefront.index' => 'Manage product listings, inventory visibility, and store products.',
      'store.products.create' => 'Add a new product with pricing, stock, crop types, and application rules.',
      'store.products.import' => 'Import products from a CSV or spreadsheet and review the uploaded rows.',
      'store.formulas.index' => 'Review application rules that connect products to crops, seasons, rates, and stages.',
      'store.formulas.create' => 'Create an application rule for a product, crop type, season, and usage rate.',
      'admin.operations.catalog' => 'Browse the product catalog and review farm inputs available to the association.',
      'admin.settings.general' => 'Review general association settings and administrative information.',
  ];
  $aqAddSearchPage = function (string $label, string $routeName, string $aliases = '', string $description = '', string $parent = '', string $fragment = '') use (&$aqSearchPages, $aqSearchDescriptions) {
      if (\Illuminate\Support\Facades\Route::has($routeName)) {
          $url = route($routeName);
          if ($fragment !== '') {
              $url .= '#' . ltrim($fragment, '#');
          }
          $aqSearchPages[] = [
              'label' => $label,
              'url' => $url,
              'aliases' => trim($aliases . ' ' . $parent),
              'heading' => $label,
              'description' => $description !== '' ? $description : ($aqSearchDescriptions[$routeName] ?? ''),
              'parent' => $parent,
          ];
      }
  };

  if ($aqSearchRole === 'admin') {
      foreach ([
          ['Dashboard', 'dashboard.admin', 'home overview admin'],
          ['AI Assistant', 'admin.ai-chat.index', 'ai chat assistant general'],
          ['Members', 'admin.members.index', 'member list view members'],
          ['Import Members', 'admin.members.import', 'import member csv excel'],
          ['Import Uploads', 'admin.members.uploads', 'uploads imported files'],
          ['All Users', 'admin.users.index', 'users list account management'],
          ['Create Account', 'admin.users.create', 'add account register user'],
          ['Harvest Performance', 'admin.harvests.index', 'harvest reports performance'],
          ['All Harvest Reports', 'admin.harvests.reports', 'report list records'],
          ['Estimate vs Actual Harvest', 'admin.harvests.compare', 'compare estimate actual'],
          ['Farming Cost Reports', 'admin.analytics.expenses', 'expense cost budget'],
          ['Planting Requests', 'admin.plantings.index', 'planting request confirm reject'],
          ['Calendar', 'admin.calendar.index', 'calendar schedule'],
          ['Events', 'admin.calendar.events', 'association events meetings'],
          ['Planting Seasons', 'admin.operations.seasons', 'season wet dry'],
          ['Product Catalog', 'admin.operations.catalog', 'products catalog inputs'],
          ['Stores', 'admin.stores.index', 'stores owners paperwork'],
          ['Quotation Demand Trends', 'admin.quotations.index', 'demand quotation products'],
          ['General Settings', 'admin.settings.index', 'settings general subpages'],
          ['Appearance', 'admin.settings.interface', 'theme color sidebar dark light'],
          ['Season Settings', 'admin.settings.seasons', 'season settings climate'],
          ['Weather Log', 'admin.settings.weather', 'weather log flood typhoon drought'],
          ['Archive', 'admin.archive', 'archive deleted records'],
          ['Audit Log', 'admin.audit.index', 'audit activity logs'],
          ['Activity History', 'admin.activity.index', 'activity history timeline'],
      ] as [$label, $route, $aliases]) {
          $aqAddSearchPage($label, $route, $aliases);
      }
      foreach ([
          ['General Settings', 'admin.settings.general', 'association profile general information', 'Organization identity, regional formatting, and location.'],
          ['Appearance Settings', 'admin.settings.interface', 'theme colors sidebar interface display', 'Sidebar color theme and dark/light mode.'],
          ['Season Settings', 'admin.settings.seasons', 'planting season wet dry configuration', 'Wet and dry season references and climate information.'],
          ['Weather Log', 'admin.settings.weather', 'weather typhoon flood drought field conditions', 'Log typhoons, floods, and weather events.'],
      ] as [$label, $route, $aliases, $description]) {
          $aqAddSearchPage($label, $route, $aliases, $description, 'Settings');
      }
  } elseif ($aqSearchRole === 'farmer') {
      foreach ([
          ['Dashboard', 'farmer.dashboard', 'home overview'],
          ['Submit Harvest Report', 'farmer.harvests.create', 'submit add report'],
          ['My Crops', 'farmer.crops.index', 'crops batches'],
          ['Harvest Reports', 'farmer.harvests.index', 'harvest list reports'],
          ['Estimate vs Actual Harvest', 'farmer.harvests.compare', 'compare estimate actual'],
          ['My Cost Quotations', 'farmer.quotations.index', 'quotation request cost'],
          ['Browse Supplies', 'farmer.products.browse', 'products supplies inputs'],
          ['Recommended Supplies', 'farmer.recommendations.index', 'recommendation supplies'],
          ['Top Quoted Products', 'farmer.top-quoted.index', 'quoted products'],
          ['Available Stores', 'farmer.stores.index', 'stores partner'],
          ['Crop Guide', 'farmer.crops-directory.index', 'crop guide'],
          ['AI Assistant', 'farmer.ai-chat.index', 'ai chat assistant'],
          ['Calendar', 'farmer.calendar.index', 'calendar schedule'],
          ['Events', 'farmer.calendar.events', 'association events'],
          ['Account Settings', 'farmer.settings.index', 'settings account theme color appearance dark light ui sidebar profile'],
          ['Activity History', 'farmer.activity.index', 'activity timeline'],
      ] as [$label, $route, $aliases]) {
          $aqAddSearchPage($label, $route, $aliases);
      }
      foreach ([
          ['Request Cost Quotation', 'farmer.quotations.request', 'create quotation crop area season products inputs', '', '', ''],
          ['Quotation Archive', 'farmer.quotations.archive', 'archived old quotations history', '', '', ''],
          ['Account Profile', 'farmer.settings.index', 'account profile name contact email', 'Name, contact, and account details.', 'Settings', 'account-profile'],
          ['Appearance', 'farmer.settings.index', 'appearance theme color dark light sidebar', 'Sidebar color theme and dark/light mode.', 'Settings', 'appearance'],
          ['Saved Items', 'farmer.settings.index', 'saved products bookmarks supplies', 'Products you saved from product cards.', 'Settings', 'saved-items'],
          ['Notification Preferences', 'farmer.settings.index', 'notifications alerts planting events reminders', 'Choose which planting and association alerts appear in your notifications.', 'Settings', 'notifications'],
          ['Security', 'farmer.settings.index', 'security password account', 'Change password and account security.', 'Settings', 'security'],
          ['Language', 'farmer.settings.index', 'language display', 'Display language preferences.', 'Settings', 'language'],
      ] as [$label, $route, $aliases, $description, $parent, $fragment]) {
          $aqAddSearchPage($label, $route, $aliases, $description ?? '', $parent ?? '', $fragment ?? '');
      }
  } elseif ($aqSearchRole === 'store_owner') {
      foreach ([
          ['Dashboard', 'store.dashboard', 'home overview'],
          ['Top Products', 'store.dashboard.performance', 'performance top products'],
          ['Product Listings', 'store.storefront.index', 'products listings catalog'],
          ['Inventory', 'store.inventory.index', 'inventory stock'],
          ['Quotation Demand Trends', 'store.quotations.index', 'quotation demand trends'],
          ['AI Assistant', 'store.ai-chat.index', 'ai chat assistant'],
          ['Settings', 'store.settings.index', 'settings account theme color appearance dark light ui sidebar profile'],
          ['Activity History', 'store.activity.index', 'activity timeline'],
      ] as [$label, $route, $aliases]) {
          $aqAddSearchPage($label, $route, $aliases);
      }
      foreach ([
          ['Store Profile', 'store.settings.index', 'store profile name contact address', 'Store name, address, and contact details.', 'Settings', 'store-profile'],
          ['Appearance', 'store.settings.index', 'appearance theme color dark light sidebar', 'Sidebar color theme and dark/light mode.', 'Settings', 'appearance'],
          ['Notification Preferences', 'store.settings.index', 'notifications quotation demand alerts', 'Quotation demand alerts.', 'Settings', 'notifications'],
          ['Security', 'store.settings.index', 'security password account', 'Change password and account security.', 'Settings', 'security'],
          ['Language', 'store.settings.index', 'language display', 'Display language preferences.', 'Settings', 'language'],
      ] as [$label, $route, $aliases, $description, $parent, $fragment]) {
          $aqAddSearchPage($label, $route, $aliases, $description, $parent, $fragment);
      }
      foreach ([
          ['Create Product', 'store.products.create', 'add product price stock crop types application rules'],
          ['Import Products', 'store.products.import', 'upload csv excel spreadsheet products'],
          ['Application Rules', 'store.formulas.index', 'product crop type season rate stage formula'],
          ['Create Application Rule', 'store.formulas.create', 'add product rule crop season usage rate'],
      ] as [$label, $route, $aliases]) {
          $aqAddSearchPage($label, $route, $aliases);
      }
  }
@endphp

<style>
  .aq-global-search-host {
    position: fixed;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    width: 320px;
    max-width: calc(100vw - 32px);
    z-index: 190;
    display: none;
    opacity: 0;
    transition: opacity .16s ease, transform .16s ease, width .16s ease;
  }
  .aq-global-search-host.aq-search-open { opacity: 1; }
  @media (min-width: 769px) {
    body { padding-top: 60px; }
    .aq-global-search-host {
      display: flex;
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      margin-left: 0;
      transform: none;
      width: auto;
      max-width: none;
      height: 60px;
      padding: 10px 88px 10px 24px;
      justify-content: center;
      align-items: center;
      background: var(--sb-bg, #14532d);
      border-bottom: 1px solid rgba(255,255,255,0.12);
      box-shadow: 0 1px 0 rgba(15,23,42,0.06), 0 2px 14px rgba(15,23,42,0.04);
      z-index: 185;
      opacity: 1;
      pointer-events: auto;
      overflow: visible;
    }
    .aq-global-search-host .aq-global-search-wrap { position: relative; width: min(620px, 46vw); }
    .desktop-notif-wrap {
      position: fixed;
      top: 14px;
      right: 24px;
      z-index: 195;
    }
  }
  @media (max-width: 768px) {
    .aq-mobile-search-toggle {
      position: absolute;
      right: 64px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border: 0;
      background: transparent;
      color: var(--sb-fg);
      cursor: pointer;
      padding: 0;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      z-index: 195;
    }
    .aq-mobile-search-toggle:hover { background: var(--sb-hover-bg); }
    .aq-mobile-search-open .aq-mobile-header-brand { display: none; }
    .aq-global-search-host {
      position: fixed;
      top: 14px;
      left: 56px;
      right: 112px;
      width: auto;
      max-width: none;
      overflow: visible;
      opacity: 0;
      display: block;
      pointer-events: none;
      transform: none;
      z-index: 192;
      padding: 0;
      transition: left .22s ease, right .22s ease, opacity .18s ease, padding .22s ease, transform .18s ease;
    }
    .aq-mobile-search-open .aq-global-search-host {
      display: block;
      left: 56px;
      right: 112px;
      opacity: 1;
      padding: 0;
      pointer-events: auto;
      transform: none;
      overflow: visible;
    }
    .aq-mobile-search-toggle {
      position: absolute;
      right: 64px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border: 0;
      background: transparent;
      color: var(--sb-fg);
      cursor: pointer;
      padding: 0;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      z-index: 195;
    }
    .aq-mobile-search-toggle:hover { background: var(--sb-hover-bg); }
    .aq-mobile-search-open .aq-mobile-header-brand { display: none; }
  }
  .aq-global-search-wrap {
    position: relative;
  }
  .aq-global-search-input {
    width: 100%;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #0f172a;
    border-radius: 8px;
    padding: 7px 34px 7px 34px;
    font-size: 12px;
    line-height: 18px;
    outline: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
  }
  .aq-global-search-input::-webkit-search-cancel-button,
  .aq-global-search-input::-webkit-search-decoration {
    -webkit-appearance: none;
    appearance: none;
    display: none;
  }
  .aq-global-search-input::-ms-clear {
    display: none;
  }
  .aq-global-search-input::placeholder { color: #8a94a6; }
  .aq-global-search-input:focus {
    background: #ffffff;
    border-color: #d1d5db;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  }
  .aq-global-search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
  .aq-global-search-clear {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    width: 22px; height: 22px; border: 0; border-radius: 8px; background: #eef2f0; color: #526257;
    cursor: pointer; font-size: 12px; line-height: 1; display: none; align-items: center; justify-content: center;
  }
  .aq-global-search-clear.aq-show { display: flex; }
  .aq-global-search-results {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e0e5e1;
    box-shadow: 0 16px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    display: none;
  }
  .aq-global-search-results.aq-show { display: block; }
  .aq-global-search-results a {
    display: flex; flex-direction: column; align-items: flex-start; gap: 3px;
    padding: 11px 14px; text-decoration: none; color: #26352b;
    border-bottom: 1px solid #edf1ee; font-size: 13px;
  }
  .aq-global-search-result-label { font-weight: 700; }
  .aq-global-search-result-parent {
    color: #68756c; font-size: 11px; font-weight: 600; margin-right: 4px;
  }
  .aq-global-search-result-snippet {
    color: #68756c; font-size: 12px; line-height: 1.4; font-weight: 400;
  }
  .aq-global-search-results a:last-child { border-bottom: 0; }
  .aq-global-search-results a:hover { background: #f3f6f3; }
  .aq-global-search-empty { color: #68756c; padding: 12px 14px; font-size: 13px; }
  .aq-global-search-more {
    display: block; padding: 11px 14px; border-top: 1px solid #edf1ee;
    color: #4f6a56; font-size: 12px; font-weight: 700; text-decoration: none;
    cursor: pointer;
  }
  .aq-global-search-more:hover { background: #f3f6f3; }
  .aq-global-search-results.aq-expanded {
    position: fixed;
    top: 60px;
    left: 260px;
    right: 0;
    bottom: 0;
    max-height: none;
    overflow-y: auto;
    border-radius: 0;
    padding: 24px max(24px, 8vw) 40px;
    box-shadow: 0 10px 30px rgba(15,23,42,0.12);
  }
  .aq-global-search-results.aq-expanded::before {
    content: 'Search results';
    display: block;
    margin-bottom: 14px;
    color: #26352b;
    font-size: 20px;
    font-weight: 700;
  }
  [data-theme="dark"] .aq-global-search-results {
    background: #0f172a;
    border-color: #334155;
  }
  [data-theme="dark"] .aq-global-search-results a {
    color: #d6f5e3;
    border-bottom-color: #334155;
  }
  [data-theme="dark"] .aq-global-search-result-snippet { color: #9fb3a6; }
  [data-theme="dark"] .aq-global-search-result-parent { color: #9fb3a6; }
  [data-theme="dark"] .aq-global-search-results a:hover,
  [data-theme="dark"] .aq-global-search-more:hover { background: #1f2937; }
  [data-theme="dark"] .aq-global-search-empty { color: #9fb3a6; }
  [data-theme="dark"] .aq-global-search-more { color: #a7f3d0; border-top-color: #334155; }
  [data-theme="dark"] .aq-global-search-results.aq-expanded::before { color: #d6f5e3; }
  @media (max-width: 768px) {
    .aq-global-search-results.aq-expanded {
      top: 88px;
      left: 0;
      right: 0;
      padding: 20px 16px 32px;
    }
  }
</style>

<div id="aqGlobalSearchHost" class="aq-global-search-host">
  <div class="aq-global-search-wrap">
    <svg class="aq-global-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input id="aqGlobalSearch" class="aq-global-search-input" type="search" placeholder="Search pages..." autocomplete="off" spellcheck="false">
    <button id="aqGlobalSearchClear" class="aq-global-search-clear" type="button" aria-label="Clear search">×</button>
    <div id="aqGlobalSearchResults" class="aq-global-search-results"></div>
  </div>
</div>

<script type="application/json" id="aqSearchCatalog">
{!! json_encode($aqSearchPages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var host = document.getElementById('aqGlobalSearchHost');
  var input = document.getElementById('aqGlobalSearch');
  var clear = document.getElementById('aqGlobalSearchClear');
  var results = document.getElementById('aqGlobalSearchResults');
  if (!host || !input || !results) return;

  var pages = JSON.parse((document.getElementById('aqSearchCatalog') || {}).textContent || '[]');
  var stop = new Set(['a','an','the','to','of','for','page','pages','show','me','where','find','open','how','do','i','can','system','and','my','the']);
  var activeIndex = -1;
  var expanded = false;

  function normalize(value) {
    return String(value || '').toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
  }

  function tokens(value) {
    return normalize(value).split(' ').filter(function (word) { return word && !stop.has(word); });
  }

  function levenshtein(a, b) {
    if (a === b) return 0;
    if (!a.length) return b.length;
    if (!b.length) return a.length;

    var prev = [];
    for (var j = 0; j <= b.length; j++) prev[j] = j;
    for (var i = 1; i <= a.length; i++) {
      var curr = [i];
      for (var j = 1; j <= b.length; j++) {
        curr[j] = (a.charAt(i - 1) === b.charAt(j - 1))
          ? prev[j - 1]
          : 1 + Math.min(prev[j], curr[j - 1], prev[j - 1]);
      }
      prev = curr;
    }
    return prev[b.length];
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (character) {
      return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[character];
    });
  }

  function snippet(item, query) {
    var text = String(item.description || item.aliases || '').replace(/\s+/g, ' ').trim();
    if (!text) return '';
    var lowerText = text.toLowerCase();
    var terms = tokens(query);
    var matchAt = -1;
    for (var i = 0; i < terms.length; i++) {
      matchAt = lowerText.indexOf(terms[i]);
      if (matchAt >= 0) break;
    }
    if (matchAt < 0) return text.length > 110 ? text.slice(0, 107) + '...' : text;
    var start = Math.max(0, matchAt - 42);
    var end = Math.min(text.length, matchAt + 68);
    return (start > 0 ? '... ' : '') + text.slice(start, end).trim() + (end < text.length ? ' ...' : '');
  }

  function score(item, query) {
    var label = normalize(item.label || '');
    var aliases = normalize(item.aliases || '');
    var heading = normalize(item.heading || '');
    var description = normalize(item.description || '');
    var q = normalize(query);
    var qTokens = tokens(q);
    var words = tokens(label + ' ' + aliases + ' ' + heading + ' ' + description);

    if (!q) return 0;
    if (q === label) return 1000;
    if (label.indexOf(q) >= 0) return 920;
    if (aliases.indexOf(q) >= 0) return 900;
    if (heading.indexOf(q) >= 0) return 880;
    if (description.indexOf(q) >= 0) return 820;

    var total = 0;
    for (var i = 0; i < qTokens.length; i++) {
      var token = qTokens[i];
      if (words.indexOf(token) >= 0) total += 120;
      else if (token.length >= 4 && words.some(function (word) { return levenshtein(word, token) <= 2; })) total += 70;
      else if (aliases.indexOf(token) >= 0) total += 40;
      else if (label.indexOf(token) >= 0) total += 35;
      else if (heading.indexOf(token) >= 0) total += 30;
      else if (description.indexOf(token) >= 0) total += 25;
    }

    if (q.length >= 3 && label.indexOf(q) < 0 && qTokens.length === 1) total += Math.max(0, 20 - levenshtein(label, q));
    return total;
  }

  function getMatches(query) {
    return pages
      .map(function (item) { return { item: item, score: score(item, query) }; })
      .filter(function (entry) { return entry.score >= 45; })
      .sort(function (left, right) {
        if (right.score !== left.score) return right.score - left.score;
        return left.item.label.localeCompare(right.item.label);
      })
      .map(function (entry) { return entry.item; });
  }

  function renderResults(query) {
    var matches = getMatches(query);
    var visible = expanded ? matches : matches.slice(0, 5);

    results.innerHTML = '';

    if (!query.trim()) {
      results.classList.remove('aq-show');
      results.classList.remove('aq-expanded');
      expanded = false;
      return;
    }

    if (!matches.length) {
      var empty = document.createElement('div');
      empty.className = 'aq-global-search-empty';
      empty.textContent = 'No matching pages. Try terms like members, reports, products, settings, events, or crops.';
      results.appendChild(empty);
      results.classList.add('aq-show');
      results.classList.toggle('aq-expanded', expanded);
      activeIndex = -1;
      return;
    }

    visible.forEach(function (item) {
      var anchor = document.createElement('a');
      anchor.href = item.url;
      var detail = snippet(item, query);
      var parent = item.parent ? '<span class="aq-global-search-result-parent">' + escapeHtml(item.parent) + ' &gt;</span>' : '';
      anchor.innerHTML = '<span class="aq-global-search-result-label">' + parent + escapeHtml(item.label) + '</span>' +
        (detail ? '<span class="aq-global-search-result-snippet">' + escapeHtml(detail) + '</span>' : '');
      anchor.addEventListener('click', function () {
        results.classList.remove('aq-show');
        results.classList.remove('aq-expanded');
        expanded = false;
      });
      results.appendChild(anchor);
    });

    if (!expanded && matches.length > 5) {
      var more = document.createElement('a');
      more.href = '#';
      more.className = 'aq-global-search-more';
      more.textContent = 'Show more results (' + matches.length + ')';
      more.addEventListener('click', function (event) {
        event.preventDefault();
        expanded = true;
        renderResults(input.value);
      });
      results.appendChild(more);
    }

    results.classList.add('aq-show');
    results.classList.toggle('aq-expanded', expanded);
    activeIndex = -1;
  }

  input.addEventListener('input', function () {
    window.dispatchEvent(new CustomEvent('aq-search-open'));
    var query = input.value || '';
    if (clear) clear.classList.toggle('aq-show', query.trim() !== '');
    renderResults(query);
  });
  input.addEventListener('focus', function () {
    window.dispatchEvent(new CustomEvent('aq-search-open'));
  });

  input.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter') return;
    var query = input.value.trim();
    if (!query) return;
    if (activeIndex >= 0) return;
    event.preventDefault();
    expanded = true;
    renderResults(query);
  });

  if (clear) {
    clear.addEventListener('click', function () {
      input.value = '';
      clear.classList.remove('aq-show');
      results.classList.remove('aq-show');
      results.classList.remove('aq-expanded');
      expanded = false;
      input.focus();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (!results.classList.contains('aq-show')) return;
    var links = results.querySelectorAll('a');
    if (!links.length) return;

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      activeIndex = Math.min(activeIndex + 1, links.length - 1);
      links.forEach(function (link, index) { link.style.background = index === activeIndex ? 'rgba(255,255,255,0.08)' : ''; });
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      activeIndex = Math.max(activeIndex - 1, 0);
      links.forEach(function (link, index) { link.style.background = index === activeIndex ? 'rgba(255,255,255,0.08)' : ''; });
    } else if (event.key === 'Enter' && activeIndex >= 0 && links[activeIndex]) {
      event.preventDefault();
      links[activeIndex].click();
    } else if (event.key === 'Escape') {
      results.classList.remove('aq-show');
      results.classList.remove('aq-expanded');
      expanded = false;
    }
  });

  document.addEventListener('click', function (event) {
    if (!host.contains(event.target)) results.classList.remove('aq-show');
  });
});
</script>


</body>
</html>
