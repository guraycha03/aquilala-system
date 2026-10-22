/*
 * ── Aquilala app lockdown — behaviour half ─────────────────────────────
 * Paired with css/app-lockdown.css (linked in BOTH layouts with ?v= —
 * bump that version when editing this file, sw.js caches .js cache-first).
 *
 * What it enforces, for everyone, on every page:
 *   · the browser's long-press / right-click menu is suppressed on the
 *     parts that DO something — buttons, links, table rows with a click
 *     handler, cards that open a page. On those, "open in a new tab" and
 *     "copy this link" are the wrong menu: a long press there means
 *     "do this", and a tap must open the page, not a sheet.
 *   · everywhere else the normal menu stays, so the farmer can select
 *     and copy their own words and numbers
 *   · no devtools / view-source / save-page / print shortcuts
 *
 * KEEP WORKING (deliberate exemptions, mirrored from css/app-lockdown.css
 * — if you add a zone here, add it there too and bump both ?v=):
 *   · form fields: typing, caret moves, select-all, copy/cut/paste, IME,
 *     autofill, the native text-editing menu (mobile paste)
 *   · all display text: selectable and copyable, everywhere
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

  // ── The parts that do something ─────────────────────────────────────
  // Mirrors the CSS list exactly (css/app-lockdown.css): a press on one
  // of these is a command, not a text selection, so the browser's
  // long-press menu must not appear there.
  var INTERACTIVE = 'a[href], button, summary, [onclick], [role="button"], [role="link"],'
    + ' [role="tab"], [role="menuitem"], [role="switch"], [role="checkbox"], [role="radio"],'
    + ' [data-toggle], [data-open], [data-close], [data-lucide], [tabindex]:not([tabindex="-1"])';

  function interactive(el) {
    if (!el || !el.closest) return false;
    if (el.closest(INTERACTIVE)) return true;
    // Anything the page itself points at as clickable counts as well:
    // the cursor is the page's own answer to "is this tappable?".
    try {
      return getComputedStyle(el).cursor === 'pointer';
    } catch (err) {
      return false;
    }
  }

  function shielded() {
    return !!(document.body && document.body.hasAttribute('data-capture-shield'));
  }

  // Mark the parts the page itself calls tappable — the ones with a
  // pointer cursor rather than a click attribute — so their text stops
  // being selectable as well. A card that opens a page should feel like
  // a button, not like a paragraph of text. Scanned over the elements
  // that can carry a click, not every node on the page.
  function markTappable() {
    var nodes = document.querySelectorAll(
      'a[href], button, summary, [onclick], [role], [tabindex], [data-toggle], [data-open],'
      + ' [data-close], [data-lucide], [class*="card"], [class*="btn"], [class*="button"],'
      + ' [class*="toggle"], [class*="tab"], [class*="link"], tr, li'
    );
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (el.closest('input, textarea, select, [contenteditable]')) continue;
      var cursor = '';
      try { cursor = getComputedStyle(el).cursor; } catch (err) { continue; }
      if (cursor === 'pointer') el.classList.add('aq-interactive');
    }
  }

  document.addEventListener('DOMContentLoaded', markTappable);
  document.addEventListener('turbo:load', markTappable);

  // ── Long-press / right-click menu ───────────────────────────────────
  // Suppressed on the parts that do something (this is what used to
  // show "Open in new tab" on a long press of a card). Allowed
  // everywhere else, so the farmer keeps the normal copy menu on their
  // own text.
  document.addEventListener('contextmenu', function (e) {
    if (!interactive(e.target)) return;
    // A part the page only marked as tappable (a pointer cursor, no
    // click attribute): mark it now so its text stops being selectable
    // too.
    if (e.target && e.target.classList && !e.target.closest(INTERACTIVE)) {
      e.target.classList.add('aq-interactive');
    }
    e.preventDefault();
  });

  // ── Keyboard shortcuts ──────────────────────────────────────────────
  document.addEventListener('keydown', function (e) {
    var key = e.key || '';
    var lower = key.toLowerCase();
    var mod = e.ctrlKey || e.metaKey;      // Cmd on macOS behaves like Ctrl here

    // F12 / devtools variants (Windows+macOS) / view-source / save / print.
    // Blocked even inside form fields: none of them are data-entry
    // keys, and printing has its own CSS-level blank page.
    if (key === 'F12' ||
        (mod && e.shiftKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (e.metaKey && e.altKey && ['i', 'j', 'c'].indexOf(lower) !== -1) ||
        (mod && !e.shiftKey && ['u', 's', 'p'].indexOf(lower) !== -1)) {
      e.preventDefault();
    }
    // Select-all, copy and cut are left alone: text is selectable and
    // copyable everywhere now, and a farmer copying a quotation number
    // must not have to fight the app for it.
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
