/*
 * ── Aquilala app lockdown — behaviour half ─────────────────────────────
 * Paired with css/app-lockdown.css (linked in BOTH layouts with ?v= —
 * bump that version when editing this file, sw.js caches .js cache-first).
 *
 * What it enforces, for everyone, on every page:
 *   · no long-press / right-click browser menu (the iOS callout sheet is
 *     already suppressed by the CSS half; this covers Android + desktop,
 *     where the event is `contextmenu`) — this is what previously showed
 *     "Open in new tab" on long-press of the KPI cards
 *   · no copy / cut of page content (event layer + shortcut layer)
 *   · no dragging text/images out of the page
 *   · no devtools / view-source / save-page / print shortcuts
 *
 * KEEP WORKING (deliberate exemptions — these are the app's own inputs,
 * losing them would break data entry):
 *   · every input, textarea, select, contenteditable: typing, caret
 *     moves, select-all, copy/cut/paste, IME, autofill
 *   · the native text-editing menu inside those fields (mobile paste)
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
  function isEditable(el) {
    return !!(el && el.closest && el.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])'));
  }
  function shielded() {
    return !!(document.body && document.body.hasAttribute('data-capture-shield'));
  }

  // ── Long-press / right-click menu ───────────────────────────────────
  // Blocked everywhere EXCEPT inside form fields, where mobile users need
  // the native Paste / Select-all menu to enter data. Cards, tables and
  // links get no "Open in new tab" sheet — freeing the gesture for the
  // long-press modals planned for the KPI cards (those will use their own
  // touchstart timer, which this does not interfere with).
  document.addEventListener('contextmenu', function (e) {
    if (isEditable(e.target)) return;
    e.preventDefault();
  });

  // ── Copy / cut / drag-out ───────────────────────────────────────────
  // The event layer is the real enforcement: it catches Ctrl+C, the
  // browser Edit menu, drag-to-another-app, and any path the keydown
  // layer below misses. Allowed inside form fields (copying within a
  // form is data entry, not data theft).
  ['copy', 'cut'].forEach(function (type) {
    document.addEventListener(type, function (e) {
      if (isEditable(e.target)) return;
      e.preventDefault();
    });
  });
  document.addEventListener('dragstart', function (e) {
    if (isEditable(e.target)) return;
    e.preventDefault();
  });

  // ── Keyboard shortcuts ──────────────────────────────────────────────
  document.addEventListener('keydown', function (e) {
    var key = e.key || '';
    var lower = key.toLowerCase();
    var mod = e.ctrlKey || e.metaKey;      // Cmd on macOS behaves like Ctrl here
    var editable = isEditable(e.target);

    // F12 / devtools variants (Windows+macOS) / view-source / save / print.
    // Blocked even inside form fields: none of them are data-entry keys.
    if (key === 'F12' ||
        (mod && e.shiftKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (e.metaKey && e.altKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (mod && !e.shiftKey && ['u', 's', 'p'].indexOf(lower) !== -1)) {
      e.preventDefault();
      return;
    }

    // Select-all / copy / cut: allowed inside form fields, blocked on
    // page content (where selection is already off — this closes the
    // keyboard path to it).
    if (mod && !e.shiftKey && !e.altKey && ['a', 'c', 'x'].indexOf(lower) !== -1) {
      if (!editable) e.preventDefault();
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
