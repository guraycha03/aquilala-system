@props([
    'uid' => 'ttb' . mt_rand(10000, 99999),
    'workTable' => '',
    'searchPlaceholder' => 'Search…',
    'filters' => collect(),
    'sorts' => collect(),
    'count' => '',
    'perPage' => 0,
    'noun' => 'items',
    'empty' => '',
    'collapseFilters' => true,
    'search' => true,
])

@php
    $filters = is_array($filters) ? collect($filters) : $filters;
    $sorts = is_array($sorts) ? collect($sorts) : $sorts;
    // No visible controls at all → hide the toolbar shell but keep it in the DOM
    // so the engine still drives the shared bottom pagination bar for the table.
    $hideShell = !$search && !count($filters) && !count($sorts);
@endphp

<div class="ctl-toolbar table-toolbar"
     @if($hideShell) style="display:none;" @endif
     data-ttb="{{ $uid }}"
     data-ttb-works="{{ $workTable }}"
     data-ttb-count="{{ $count }}"
     data-ttb-perpage="{{ (int) $perPage }}"
     data-ttb-noun="{{ $noun }}"
     data-ttb-empty="{{ $empty }}">
    @if($search)
    <div class="ctl-search">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="{{ $uid }}Search" placeholder="{{ $searchPlaceholder }}" autocomplete="off">
    </div>
    @endif

    @if(count($filters) || count($sorts))
    <button type="button" class="btn-filter" data-ttb-filter-toggle aria-expanded="false" aria-label="Show filter options" title="Filters &amp; sort">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span class="btn-filter-dot" data-ttb-filter-dot hidden></span>
    </button>
    <div class="ttb-filters" data-ttb-filter-panel {{ $collapseFilters ? 'hidden' : '' }}>

    @foreach($filters as $f)
    <div class="ncb-group-inline" data-ttb-filter="{{ $f['key'] }}">
        <button type="button" class="ncb-select-trigger placeholder" data-default-label="{{ $f['label'] }}" tabindex="0" aria-haspopup="listbox">
            <span class="ncb-select-label">{{ $f['label'] }}</span>
            <svg class="ncb-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ncb-select-menu" role="listbox">
            <div class="ncb-select-option selected" data-value="">{{ $f['label'] }}</div>
            @foreach($f['options'] as $o)
            <div class="ncb-select-option" data-value="{{ $o[0] }}" data-label="{{ $o[1] }}">{{ $o[1] }}</div>
            @endforeach
        </div>
    </div>
    @endforeach

    @if(count($sorts))
    <div class="ncb-group-inline ttb-sort" data-ttb-sort>
        <button type="button" class="ncb-select-trigger placeholder" data-default-label="Sort: {{ $sorts->first()['label'] }}" tabindex="0" aria-haspopup="listbox">
            <span class="ncb-select-label">Sort: {{ $sorts->first()['label'] }}</span>
            <svg class="ncb-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ncb-select-menu" role="listbox">
            @foreach($sorts as $s)
            <div class="ncb-select-option {{ $loop->first ? 'selected' : '' }}"
                 data-value="{{ $s['key'] }}"
                 data-label="{{ $s['label'] }}"
                 data-dir="{{ $s['dir'] }}"
                 data-type="{{ $s['type'] }}">{{ $s['label'] }}</div>
            @endforeach
        </div>
    </div>
    @endif

    <button type="button" class="btn-clear" data-ttb-clear hidden>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear
    </button>
    {{-- The filter panel's own close tag must stay INSIDE the @if: on a
         toolbar without filters an extra </div> here would close the
         caller's panel and foster-parent its table out into the grid. --}}
    </div>
    @endif
</div>

@once
<script>
(function () {
    // The engine (and its document-level listeners) is defined once per
    // session, but init must re-run on every Turbo visit: a body swap brings
    // brand-new toolbar nodes with no listeners. Per-toolbar binding is
    // guarded by a JS property, not a data-* attribute, because Turbo's
    // snapshot cache restores keep attributes but lose both listeners and
    // JS properties — so clones must re-bind, while same-node re-runs skip.
    if (window.__TTBEngineDefined) {
        if (window.TableToolbarRun) window.TableToolbarRun();
        return;
    }
    window.__TTBEngineDefined = true;
    window.TableToolbarEngine = true;

    function debounce(fn, ms) {
        var t = null;
        return function () { clearTimeout(t); t = setTimeout(fn, ms); };
    }

    document.addEventListener('click', function (e) {
        document.querySelectorAll('.ncb-select-menu').forEach(function (m) {
            var trigger = m.previousElementSibling;
            if (m.contains(e.target) || (trigger && trigger.contains(e.target))) return;
            m.style.display = 'none';
            if (trigger) trigger.classList.remove('active');
            var ar = trigger && trigger.querySelector('.ncb-select-arrow');
            if (ar) ar.classList.remove('rotate');
        });
    });

    function initToolbar(tb) {
        // Same-node guard: run() fires on DOMContentLoaded, on turbo:load and
        // on every script re-evaluation — bind each toolbar instance once.
        if (tb.__ttbInited) return;
        tb.__ttbInited = true;

        var uid = tb.dataset.ttb;
        var tableId = tb.dataset.ttbWorks;
        var countEl = document.getElementById(tb.dataset.ttbCount);
        var perPage = parseInt(tb.dataset.ttbPerpage || '0', 10);
        var noun = tb.dataset.ttbNoun || 'items';
        var emptyId = tb.dataset.ttbEmpty || '';
        var emptyEl = emptyId ? document.getElementById(emptyId) : null;

        var filterToggle = tb.querySelector('[data-ttb-filter-toggle]');
        var filterPanel = tb.querySelector('[data-ttb-filter-panel]');
        var filterDot = tb.querySelector('[data-ttb-filter-dot]');

        var bottomBar = null, bottomInfo = null, bottomControls = null;

        function ensureBottomBar() {
            if (bottomBar || perPage <= 0) return;
            var tgtTable = document.getElementById(tableId);
            if (!tgtTable) return;
            var card = null;
            var scrollWrap = tgtTable.closest('.app-scroll-wrap');
            if (scrollWrap) {
                card = scrollWrap.parentElement;
            } else {
                var tableScroll = tgtTable.closest('.table-scroll');
                card = tableScroll ? tableScroll.parentElement : tgtTable.parentElement;
            }
            if (!card) return;
            // Turbo's snapshot cache restores the DOM as it was when leaving
            // the page — including a bottom bar injected by a previous visit.
            // Reuse it instead of stacking a duplicate under the table.
            bottomBar = card.querySelector(':scope > .ttb-bottom-bar');
            if (!bottomBar) {
                bottomBar = document.createElement('div');
                bottomBar.className = 'ttb-bottom-bar';
                bottomBar.innerHTML = '<span class="pagination-info ttb-info"></span>' +
                    '<span class="pagination-controls ttb-controls"></span>';
                card.appendChild(bottomBar);
            }
            bottomInfo = bottomBar.querySelector('.ttb-info');
            bottomControls = bottomBar.querySelector('.ttb-controls');
            if (!bottomInfo || !bottomControls) {
                bottomBar.innerHTML = '<span class="pagination-info ttb-info"></span>' +
                    '<span class="pagination-controls ttb-controls"></span>';
                bottomInfo = bottomBar.querySelector('.ttb-info');
                bottomControls = bottomBar.querySelector('.ttb-controls');
            }
        }

        var state = { sortKey: '', sortDir: 1, sortType: 'str', page: 1, search: '', filters: {} };
        var lastOrderSig = null;
        var searchEl = document.getElementById(uid + 'Search');

        var firstSort = tb.querySelector('[data-ttb-sort] .ncb-select-option');
        if (firstSort) {
            state.sortKey = firstSort.dataset.value;
            state.sortDir = parseInt(firstSort.dataset.dir || '1', 10);
            state.sortType = firstSort.dataset.type || 'str';
        }

        tb.querySelectorAll('[data-ttb-filter]').forEach(function (g) {
            state.filters[g.dataset.ttbFilter] = '';
        });

        function resolveTable() {
            var t = document.getElementById(tableId);
            if (!t) return null;
            return t.querySelector('tbody') || t;
        }
        function getRows() {
            var t = resolveTable();
            if (!t) return [];
            var rows = Array.prototype.slice.call(t.querySelectorAll('tr'));
            return rows.filter(function (r) { return !r.closest('thead'); });
        }

        function matches(row) {
            if (state.search) {
                var hay = (row.dataset.search || row.textContent || '').toLowerCase();
                if (hay.indexOf(state.search) === -1) return false;
            }
            for (var k in state.filters) {
                if (!state.filters.hasOwnProperty(k) || state.filters[k] === '') continue;
                var fv = state.filters[k];
                var rv = String(row.dataset[k] || '');
                if (fv.charAt(0) === '<' || fv.charAt(0) === '>') {
                    var m = fv.match(/^([<>]=?)(.+)$/);
                    if (!m) continue;
                    var val = parseFloat(rv) || 0;
                    var num = parseFloat(m[2]);
                    if (m[1] === '<=' && !(val <= num)) return false;
                    if (m[1] === '>=' && !(val >= num)) return false;
                    if (m[1] === '<' && !(val < num)) return false;
                    if (m[1] === '>' && !(val > num)) return false;
                } else {
                    if (rv.toLowerCase() !== fv.toLowerCase()) return false;
                }
            }
            return true;
        }

        function cmpRow(a, b) {
            var av = a.dataset[state.sortKey] || '';
            var bv = b.dataset[state.sortKey] || '';
            var r = 0;
            if (state.sortType === 'num') {
                r = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
            } else if (state.sortType === 'date') {
                var da = av ? Date.parse(av) : Infinity;
                var db = bv ? Date.parse(bv) : Infinity;
                r = da - db;
            } else {
                r = String(av).localeCompare(String(bv));
            }
            return r * state.sortDir;
        }

        function updateClear() {
            var btn = tb.querySelector('[data-ttb-clear]');
            var active = state.search !== '' || Object.keys(state.filters).some(function (k) { return state.filters[k] !== ''; });
            if (btn) btn.hidden = !active;
            if (filterDot) filterDot.hidden = !active;
        }

        function renderPagination(total) {
            if (bottomBar) bottomBar.style.display = total === 0 ? 'none' : '';
            if (!bottomInfo || !bottomControls) return;
            var pages = Math.max(1, Math.ceil(total / perPage));
            if (state.page > pages) state.page = pages;
            var start = (state.page - 1) * perPage;
            var to = Math.min(start + perPage, total);
            bottomInfo.textContent = 'Showing ' + (total === 0 ? 0 : start + 1) + '\u2013' + (total === 0 ? 0 : to) + ' of ' + total + ' ' + noun;

            bottomControls.innerHTML = '';
            var mk = function (html, disabled, active, fn) {
                var b = document.createElement('button');
                b.className = 'page-btn' + (active ? ' active' : '');
                b.innerHTML = html;
                b.disabled = !!disabled;
                b.onclick = fn;
                bottomControls.appendChild(b);
            };
            mk('&lsaquo;', state.page === 1, false, function () { state.page--; render(); });
            var set = [];
            for (var i = 1; i <= pages; i++) {
                if (i === 1 || i === pages || Math.abs(i - state.page) <= 1) set.push(i);
            }
            var last = 0;
            set.forEach(function (p) {
                if (p - last > 1) {
                    var s = document.createElement('span');
                    s.className = 'page-ellipsis';
                    s.textContent = '\u2026';
                    bottomControls.appendChild(s);
                }
                mk(String(p), false, p === state.page, (function (p) { return function () { state.page = p; render(); }; })(p));
                last = p;
            });
            mk('&rsaquo;', state.page === pages, false, function () { state.page++; render(); });
        }

        function render(forceOrder) {
            var all = getRows();
            var filtered = all.filter(matches);
            filtered.sort(cmpRow);
            var visible = filtered;
            if (perPage > 0) {
                ensureBottomBar();
                var pages = Math.max(1, Math.ceil(filtered.length / perPage));
                if (state.page > pages) state.page = pages;
                var start = (state.page - 1) * perPage;
                visible = filtered.slice(start, start + perPage);
                renderPagination(filtered.length);
            }

            // Reordering moves every row node in the table. Only do it when
            // the sort actually changed (or a caller forces it, e.g. after
            // rows were added) — search/filter/page flips just toggle
            // visibility and must stay cheap on large tables.
            var container = resolveTable();
            if (container) {
                var sig = state.sortKey + '\u0000' + state.sortDir + '\u0000' + state.sortType;
                if (forceOrder || sig !== lastOrderSig) {
                    var filteredSet = new Set(filtered);
                    var frag = document.createDocumentFragment();
                    filtered.forEach(function (r) { frag.appendChild(r); });
                    all.forEach(function (r) { if (!filteredSet.has(r)) frag.appendChild(r); });
                    container.appendChild(frag);
                    lastOrderSig = sig;
                }
            }

            var visibleSet = new Set(visible);
            all.forEach(function (r) {
                var show = visibleSet.has(r);
                if (show) {
                    if (r.style.display === 'none') r.style.display = '';
                } else if (r.style.display !== 'none') {
                    r.style.display = 'none';
                }
            });

            if (countEl) countEl.textContent = filtered.length;
            var table = document.getElementById(tableId);
            if (emptyEl && table) {
                table.style.display = filtered.length ? '' : 'none';
                emptyEl.classList.toggle('show', filtered.length === 0);
            }
            // Tell listeners which rows currently match search/filters —
            // bulk actions (select-all across client-side pages) act on this
            // set, not on the single page the user happens to be viewing.
            // The set is also parked on the table node: on Turbo visits this
            // render can run before a page's script registers its listener,
            // and a property survives that ordering while an event does not.
            if (table) table.__ttbMatched = filtered;
            try {
                document.dispatchEvent(new CustomEvent('ttb:render', {
                    detail: { uid: uid, table: tableId, matched: filtered }
                }));
            } catch (err) { /* listeners are optional */ }
            updateClear();
        }

        function closeMenus() {
            tb.querySelectorAll('.ncb-select-menu').forEach(function (m) { m.style.display = 'none'; });
            tb.querySelectorAll('.ncb-select-trigger.active').forEach(function (t) { t.classList.remove('active'); });
            tb.querySelectorAll('.ncb-select-arrow.rotate').forEach(function (a) { a.classList.remove('rotate'); });
        }

        tb.addEventListener('click', function (e) {
            var trigger = e.target.closest('.ncb-select-trigger');
            if (trigger) {
                e.stopPropagation();
                var menu = trigger.parentElement.querySelector('.ncb-select-menu');
                var wasOpen = menu.style.display === 'block';
                closeMenus();
                if (!wasOpen) {
                    menu.style.display = 'block';
                    trigger.classList.add('active');
                    var ar = trigger.querySelector('.ncb-select-arrow');
                    if (ar) ar.classList.add('rotate');
                }
                return;
            }
            var option = e.target.closest('.ncb-select-option');
            if (option) {
                e.stopPropagation();
                var group = option.closest('.ncb-group-inline');
                var menu2 = option.closest('.ncb-select-menu');
                menu2.querySelectorAll('.ncb-select-option').forEach(function (o) { o.classList.remove('selected'); });
                option.classList.add('selected');
                var trig = group.querySelector('.ncb-select-trigger');
                var value = option.dataset.value || '';
                var label = option.dataset.label || trig.querySelector('.ncb-select-label').textContent;
                trig.querySelector('.ncb-select-label').textContent = group.hasAttribute('data-ttb-sort') ? ('Sort: ' + label) : label;
                trig.classList.toggle('placeholder', value === '');
                menu2.style.display = 'none';
                trig.classList.remove('active');
                var ar2 = trig.querySelector('.ncb-select-arrow');
                if (ar2) ar2.classList.remove('rotate');

                if (group.hasAttribute('data-ttb-sort')) {
                    state.sortKey = value;
                    state.sortDir = parseInt(option.dataset.dir || '1', 10);
                    state.sortType = option.dataset.type || 'str';
                } else {
                    state.filters[group.dataset.ttbFilter] = value;
                }
                state.page = 1;
                render();
            }
        });

        if (searchEl) {
            searchEl.addEventListener('input', debounce(function () {
                state.search = searchEl.value.trim().toLowerCase();
                state.page = 1;
                render();
            }, 120));
        }

        var clearBtn = tb.querySelector('[data-ttb-clear]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchEl) searchEl.value = '';
                state.search = '';
                for (var k in state.filters) state.filters[k] = '';
                state.page = 1;
                closeMenus();
                tb.querySelectorAll('[data-ttb-filter]').forEach(function (g) {
                    var trig = g.querySelector('.ncb-select-trigger');
                    trig.querySelector('.ncb-select-label').textContent = trig.dataset.defaultLabel;
                    trig.classList.add('placeholder');
                    g.querySelectorAll('.ncb-select-option').forEach(function (o) { o.classList.remove('selected'); });
                    var first = g.querySelector('.ncb-select-option');
                    if (first) first.classList.add('selected');
                });
                render();
            });
        }

        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', function () {
                var opening = filterPanel.hidden;
                filterPanel.hidden = !opening;
                filterToggle.classList.toggle('active', opening);
                filterToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
                if (opening) closeMenus();
            });
        }

        var tries = 0;
        (function poll() {
            if (document.getElementById(tableId)) { render(); return; }
            if (tries < 60) { tries++; setTimeout(poll, 50); }
        })();

        // Pages that add/remove rows at runtime (import editors) call
        // TableToolbarRefresh(tableId) so counts and pages stay truthful.
        // Public render forces a DOM reorder: new rows arrive in server order.
        window.TableToolbarEngines = window.TableToolbarEngines || {};
        window.TableToolbarEngines[tableId] = {
            render: function () { render(true); },
            goToLastPage: function () {
                var t = resolveTable();
                if (!t || perPage <= 0) return;
                var pages = Math.max(1, Math.ceil(t.querySelectorAll('tr').length / perPage));
                state.page = pages;
                render(true);
            }
        };
    }

    function run() {
        document.querySelectorAll('.ctl-toolbar.table-toolbar').forEach(initToolbar);
        if (window.TableToolbarPending) window.TableToolbarPending();
    }
    // Exported before any early return so re-evaluated copies of this script
    // (every Turbo visit re-runs body scripts) can re-wire the fresh DOM.
    window.TableToolbarRun = run;

    // Re-render a work table's toolbar after rows were added/removed in the DOM.
    // opts.lastPage jumps to the final page first (new rows land at the end).
    window.TableToolbarRefresh = function (tableId, opts) {
        var eng = window.TableToolbarEngines && window.TableToolbarEngines[tableId];
        if (!eng) return;
        if (opts && opts.lastPage) eng.goToLastPage();
        else eng.render();
    };

    // Registered once (this branch only runs on the first evaluation): fires
    // after every visit — including restorations where inits that registered
    // through the layout's DOMContentLoaded shim get drained — so pending
    // page hooks like TableToolbarPending always see the final DOM.
    document.addEventListener('turbo:load', run);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
@endonce