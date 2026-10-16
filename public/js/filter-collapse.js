/*
 * Shared filter-collapse engine.
 *
 * One behaviour for every page with a search bar + filter choices: the
 * choices sit behind a single funnel button (.btn-filter) until the user
 * asks for them. Opt in with:
 *
 *   <div data-fc data-fc-open-when="param1,param2">
 *     …search…
 *     <button class="btn-filter" data-fc-toggle aria-expanded="false" …>
 *       <svg …funnel…></svg>
 *       <span class="btn-filter-dot" data-fc-dot hidden></span>
 *     </button>
 *     <div class="filter-panel" data-fc-panel hidden>…choices…</div>
 *   </div>
 *
 * data-fc-open-when lists URL query keys that mean "filters are applied";
 * when any is present the panel starts open and the dot shows, so an
 * applied filter is never hidden from the user. Works with native selects,
 * hand-rolled dropdowns and chip buttons alike — it only toggles visibility.
 * Pages whose own JS owns the toggle (activity drawers, the table-toolbar
 * engine) simply do not use these hooks.
 */
(function () {
    'use strict';

    function setPanel(panel, toggle, open) {
        if (open) panel.removeAttribute('hidden');
        else panel.setAttribute('hidden', '');
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('active', open);
        }
    }

    function init() {
        var bars = document.querySelectorAll('[data-fc]');
        Array.prototype.forEach.call(bars, function (bar) {
            if (bar.getAttribute('data-fc-ready')) return;

            var toggle = bar.querySelector('[data-fc-toggle]');
            var panel = bar.querySelector('[data-fc-panel]');
            if (!toggle || !panel) return;
            bar.setAttribute('data-fc-ready', '1');

            toggle.addEventListener('click', function () {
                setPanel(panel, toggle, panel.hasAttribute('hidden'));
            });

            var when = (bar.getAttribute('data-fc-open-when') || '')
                .split(',')
                .map(function (s) { return s.trim(); })
                .filter(Boolean);

            if (when.length) {
                var query = new URLSearchParams(window.location.search);
                var applied = when.some(function (key) { return query.has(key); });
                if (applied) {
                    setPanel(panel, toggle, true);
                    var dot = toggle.querySelector('[data-fc-dot]');
                    if (dot) dot.removeAttribute('hidden');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    // Turbo swaps the body on every visit; re-wire the new markup.
    document.addEventListener('turbo:load', init);
})();
