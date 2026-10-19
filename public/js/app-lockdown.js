/*
 * ── Aquilala app lockdown — behaviour half ─────────────────────────────
 * Paired with css/app-lockdown.css (linked in BOTH layouts with ?v= —
 * bump that version when editing this file, sw.js caches .js cache-first).
 *
 * What it enforces, for everyone, on every page:
 *   · no long-press / right-click browser menu on display content (the
 *     iOS callout sheet is already suppressed by the CSS half; this
 *     covers Android + desktop, where the event is `contextmenu`) — this
 *     is what previously showed "Open in new tab" on long-press of the
 *     KPI cards
 *   · no copy / cut / drag-out of display content (event layer +
 *     shortcut layer)
 *   · no devtools / view-source / save-page / print shortcuts
 *
 * KEEP WORKING (deliberate exemptions, mirrored from css/app-lockdown.css
 * — if you add a zone here, add it there too and bump both ?v=):
 *   · form fields: typing, caret moves, select-all, copy/cut/paste, IME,
 *     autofill, the native text-editing menu (mobile paste)
 *   · TABLES: selecting and copying the user's own rows — harvest
 *     numbers, member lists. Per feedback, blocking that is a hassle
 *     rather than a protection; page titles, subtext and cards remain
 *     locked everywhere.
 *
 * Per-page capture shield (opt-in via `shield` route middleware →
 * data-capture-shield on <body>): a blank branded cover is shown while
 * the page is hidden or the window is unfocused, so OS task-switcher /
 * alt-tab snapshots reveal the logo instead of member data.
 *
 * HONEST LIMITS — no script can prevent an OS-level screenshot taken
 * WHILE the page is visible (PrtScn, Win+Shift+S, iOS side+volume,
 * a second phone). The shield only cleans up app-switcher previews, and
 * the PrtScn handling below merely overwrites the clipboard afterwards
 * (best effort: Windows puts the capture there, JS can blank it; macOS
 * Cmd+Shift+3/4 never reaches the page at all). Real capture blocking
 * needs a native Android wrapper (FLAG_SECURE) and is impossible on iOS.
 *
 * Turbo-safe: listeners bind ONCE per session (window.__aqLockdown
 * guard, same convention as the layout's other document-level handlers)
 * and every decision reads document.body / event target at event time,
 * because Turbo replaces the whole body between visits.
 */
(function () {
  'use strict';
  if (window.__aqLockdown) return;
  window.__aqLockdown = true;

  // Resolved fresh on every event: Turbo swaps the <body>, so nothing
  // about the current page may be captured up front.
  var FORM_ZONE = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';
  // Selectable/copyable zones — MUST mirror the exempted selectors in
  // css/app-lockdown.css (form controls + labels + every table cell).
  var COPY_ZONES = FORM_ZONE + ', label, table';

  function inZone(el, zones) {
    return !!(el && el.closest && el.closest(zones));
  }
  // Copy/cut decisions must look at the SELECTION, not only the event
  // target: table cells aren't focusable, so after clicking one e.target
  // is usually <body> while the user's selection sits inside the table.
  function selectionAllowed() {
    var sel = document.getSelection();
    if (!sel || sel.isCollapsed || sel.rangeCount === 0) return false;
    var node = sel.anchorNode;
    var el = node && (node.nodeType === 1 ? node : node.parentNode);
    return inZone(el, COPY_ZONES);
  }
  function canCopy(target) {
    return inZone(target, COPY_ZONES) || selectionAllowed();
  }
  function shielded() {
    return !!(document.body && document.body.hasAttribute('data-capture-shield'));
  }

  // ── Long-press / right-click menu ───────────────────────────────────
  // Blocked on display content — cards, titles, static text (this is what
  // previously showed "Open in new tab" on long-press of the KPI cards,
  // freeing the gesture for the long-press modals planned there; those use
  // their own touchstart timer, which this does not interfere with).
  // Allowed inside form fields (mobile Paste / Select-all) and inside
  // tables (Copy of the user's own rows).
  document.addEventListener('contextmenu', function (e) {
    if (inZone(e.target, COPY_ZONES)) return;
    e.preventDefault();
  });

  // ── Copy / cut / drag-out ───────────────────────────────────────────
  // The event layer is the real enforcement: it catches Ctrl+C, the
  // browser Edit menu, drag-to-another-app, and any path the keydown
  // layer below misses. Allowed in copy zones (form fields, table cells)
  // — canCopy() also consults the selection, see above.
  ['copy', 'cut'].forEach(function (type) {
    document.addEventListener(type, function (e) {
      if (canCopy(e.target)) return;
      e.preventDefault();
    });
  });
  document.addEventListener('dragstart', function (e) {
    if (canCopy(e.target)) return;
    e.preventDefault();
  });

  // ── Keyboard shortcuts ──────────────────────────────────────────────
  document.addEventListener('keydown', function (e) {
    var key = e.key || '';
    var lower = key.toLowerCase();
    var mod = e.ctrlKey || e.metaKey;      // Cmd on macOS behaves like Ctrl here

    // F12 / devtools variants (Windows+macOS) / view-source / save / print.
    // Blocked even inside form fields and tables: none of them are
    // data-entry keys, and printing has its own CSS-level blank page.
    if (key === 'F12' ||
        (mod && e.shiftKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (e.metaKey && e.altKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (mod && !e.shiftKey && ['u', 's', 'p'].indexOf(lower) !== -1)) {
      e.preventDefault();
      return;
    }

    // Select-all / copy / cut: allowed in copy zones (form fields, table
    // cells) or when the selection already sits in one; blocked on display
    // content (where selection is already off — this closes the keyboard
    // path to it).
    if (mod && !e.shiftKey && !e.altKey && ['a', 'c', 'x'].indexOf(lower) !== -1) {
      if (!canCopy(e.target)) e.preventDefault();
    }
  });

  // ── PrintScreen (best effort, shielded pages only) ──────────────────
  // Windows mirrors the capture into the clipboard: blank the clipboard
  // right after so nothing lingers to be pasted elsewhere. Per feedback,
  // this only engages on pages that opted into the shield.
  document.addEventListener('keyup', function (e) {
    if (e.key !== 'PrintScreen' || !shielded()) return;
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText('').catch(function () { /* no gesture/permission — fine */ });
      }
    } catch (err) { /* clipboard unavailable (http origin) — fine */ }
  });

  // ── Capture shield cover (shielded pages only) ──────────────────────
  var cover = null;
  function ensureCover() {
    if (cover && cover.isConnected) return cover;
    cover = document.createElement('div');
    cover.className = 'aq-shield';
    cover.setAttribute('aria-hidden', 'true');
    // <body> (not documentElement): Turbo replaces the body wholesale, so
    // a disconnected cover is simply rebuilt on the next event below.
    document.body.appendChild(cover);
    return cover;
  }
  function showShield() {
    if (!shielded()) return;
    ensureCover().classList.add('on');
  }
  function hideShield() {
    if (cover && cover.isConnected) cover.classList.remove('on');
  }

  // Hidden = app switched away, tab switched, screen locked: the OS
  // snapshots the page around here, so the cover must be up first.
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') showShield();
    else hideShield();
  });
  // Window unfocused (alt-tab on desktop, tapping another app's chrome):
  // covers snapshot paths that don't flip visibilityState.
  window.addEventListener('blur', showShield);
  window.addEventListener('focus', hideShield);

  // Belt-and-braces after every Turbo visit and bfcache restore: if the
  // page is actually visible and focused, never leave a stuck cover.
  document.addEventListener('turbo:load', function () {
    if (document.visibilityState === 'visible' && document.hasFocus()) hideShield();
  });
  window.addEventListener('pageshow', function () {
    if (document.visibilityState === 'visible' && document.hasFocus()) hideShield();
  });
})();
