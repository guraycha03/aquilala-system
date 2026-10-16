@props([
    // Unique per page. Namespaces every id so two bars can never collide.
    'uid' => 'catalog',
    // Selector for the container holding the cards to filter.
    'target' => '.product-grid',
    // Flat list of category names. Plain strings; the value is derived by
    // lower-casing so pages do not have to build [value, label] pairs.
    'categories' => [],
    'showSeason' => true,
    'showStock' => true,
    'showSort' => true,
    'searchPlaceholder' => 'Search products, SKU, brand...',
    'priceMin' => null,
    'priceMax' => null,
    // Mirror the active filters into the query string so a filtered catalog
    // can be linked and the back button works.
    'syncUrl' => true,
    'noun' => 'products',
])

@php
    $cid = 'cf-' . $uid;
    $catOptions = collect($categories)
        ->map(fn ($c) => is_array($c) ? ($c['label'] ?? $c['value'] ?? '') : (string) $c)
        ->map(fn ($c) => trim((string) $c))
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<style>
  /* ── Shared catalog filter bar ────────────────────────────────────────
     One look for every catalog surface (farmer browse, storefront, farmer
     top-quoted, farmer recommendations) so filtering behaves and reads the same
     everywhere. Native <select> and <input type="number"> are used on
     purpose: they inherit the platform picker on phones and keyboard and
     screen-reader behaviour for free, which the hand-rolled dropdowns this
     replaces did not have. */
  .cf-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px;
    padding: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 16px;
  }

  .cf-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
  .cf-field > label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding-left: 2px;
  }
  .cf-grow { flex: 1 1 220px; }

  .cf-input, .cf-select {
    appearance: none;
    -webkit-appearance: none;
    font-family: inherit;
    font-size: 13.5px;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    padding: 9px 11px;
    min-height: 38px;
    width: 100%;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
  }
  .cf-input::placeholder { color: #94a3b8; }
  .cf-input:hover, .cf-select:hover { border-color: #94a3b8; }
  .cf-input:focus, .cf-select:focus {
    outline: none;
    background: #ffffff;
    border-color: #166534;
    box-shadow: 0 0 0 3px rgba(22, 101, 52, .12);
  }
  /* The dropdown arrow is drawn as a background so the native one can be
     hidden without losing the tap target the browser gives on phones. */
  .cf-select {
    padding-right: 30px;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    cursor: pointer;
  }

  .cf-search { position: relative; display: flex; align-items: center; }
  .cf-search > svg {
    position: absolute; left: 11px;
    width: 16px; height: 16px;
    stroke: #94a3b8; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    pointer-events: none;
  }
  .cf-search .cf-input { padding-left: 34px; padding-right: 32px; }
  .cf-search-clear {
    position: absolute; right: 6px;
    width: 24px; height: 24px;
    display: none; align-items: center; justify-content: center;
    border: none; border-radius: 6px;
    background: none; cursor: pointer;
    color: #64748b; font-size: 17px; line-height: 1;
  }
  .cf-search-clear:hover { background: #f1f5f9; color: #0f172a; }
  .cf-search-clear.is-visible { display: inline-flex; }

  .cf-price { display: flex; align-items: center; gap: 6px; }
  .cf-price .cf-input { width: 88px; text-align: center; }
  .cf-price-sep { color: #94a3b8; font-size: 13px; }

  .cf-reset {
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    padding: 9px 13px;
    min-height: 38px;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
    white-space: nowrap;
  }
  .cf-reset:hover { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
  .cf-reset[disabled] { opacity: .5; cursor: default; background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; }
  .cf-reset[disabled]:hover { background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; }

  /* Result count + removable chips for whatever is currently applied. */
  .cf-summary {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    margin-bottom: 14px;
  }
  .cf-summary[hidden] { display: none; }
  .cf-count { font-size: 13px; color: #64748b; margin-right: 2px; }
  .cf-count b { color: #0f172a; font-weight: 700; }
  .cf-chips { display: flex; flex-wrap: wrap; gap: 6px; }
  .cf-chip {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600;
    color: #14532d;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 999px;
    padding: 4px 8px 4px 10px;
    cursor: pointer;
    font-family: inherit;
  }
  .cf-chip:hover { background: #dcfce7; border-color: #86efac; }
  .cf-chip-x { font-size: 14px; line-height: 1; color: #166534; opacity: .7; }
  .cf-chip:hover .cf-chip-x { opacity: 1; }
  .cf-chip-clear-all { background: #ffffff; border-color: #cbd5e1; color: #475569; }
  .cf-chip-clear-all:hover { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }

  /* The engine hides cards with this class. !important so it beats the
     display rules each page sets on its own card type (grid item, flex). */
  .cf-hidden { display: none !important; }

  @media (max-width: 900px) {
    .cf-field { flex: 1 1 150px; }
    .cf-grow { flex: 1 1 100%; }
  }
  @media (max-width: 640px) {
    .cf-bar { padding: 12px; gap: 8px; }
    .cf-field { flex: 1 1 calc(50% - 4px); }
    .cf-grow, .cf-reset { flex: 1 1 100%; }
    .cf-price .cf-input { width: 100%; }
    .cf-price { width: 100%; }
  }
</style>

<div class="cf-bar" data-cf-root data-cf-uid="{{ $uid }}" data-cf-target="{{ $target }}"
     data-cf-sync="{{ $syncUrl ? '1' : '0' }}" data-cf-noun="{{ $noun }}"
     data-fc data-fc-open-when="category,season,stock,min,max,sort">

  <div class="cf-field cf-grow">
    <label for="{{ $cid }}-search">Search</label>
    <div class="cf-search">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" id="{{ $cid }}-search" class="cf-input" data-cf-search-input
             placeholder="{{ $searchPlaceholder }}" autocomplete="off">
      <button type="button" class="cf-search-clear" data-cf-search-clear aria-label="Clear search">&times;</button>
    </div>
  </div>

  {{-- One funnel button (shared .btn-filter) hides every filter choice until
       the user asks for them — same pattern on all list pages. --}}
  <button type="button" class="btn-filter" data-fc-toggle aria-expanded="false"
          aria-label="Show filter options" title="Filters &amp; sort">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
    <span class="btn-filter-dot" data-fc-dot hidden></span>
  </button>

  <div class="filter-panel" data-fc-panel hidden>

  @if($catOptions->isNotEmpty())
    <div class="cf-field">
      <label for="{{ $cid }}-category">Category</label>
      <select id="{{ $cid }}-category" class="cf-select" data-cf-filter="category">
        <option value="">All categories</option>
        @foreach($catOptions as $cat)
          <option value="{{ mb_strtolower($cat) }}">{{ $cat }}</option>
        @endforeach
      </select>
    </div>
  @endif

  @if($showSeason)
    <div class="cf-field">
      <label for="{{ $cid }}-season">Season</label>
      <select id="{{ $cid }}-season" class="cf-select" data-cf-filter="season">
        <option value="">Any season</option>
        <option value="wet">Wet season</option>
        <option value="dry">Dry season</option>
        <option value="both">Year-round</option>
      </select>
    </div>
  @endif

  @if($showStock)
    <div class="cf-field">
      <label for="{{ $cid }}-stock">Availability</label>
      <select id="{{ $cid }}-stock" class="cf-select" data-cf-filter="stock">
        <option value="">Any availability</option>
        <option value="in">In stock</option>
        <option value="low">Low stock</option>
        <option value="out">Out of stock</option>
      </select>
    </div>
  @endif

  <div class="cf-field">
    <label for="{{ $cid }}-min">Price range (&#8369;)</label>
    <div class="cf-price">
      <input type="number" id="{{ $cid }}-min" class="cf-input" data-cf-price="min"
             placeholder="Min" min="0" step="1" inputmode="numeric"
             @if($priceMin !== null) title="Catalog minimum &#8369;{{ number_format($priceMin, 2) }}" @endif>
      <span class="cf-price-sep" aria-hidden="true">&ndash;</span>
      <input type="number" id="{{ $cid }}-max" class="cf-input" data-cf-price="max"
             placeholder="Max" min="0" step="1" inputmode="numeric"
             @if($priceMax !== null) title="Catalog maximum &#8369;{{ number_format($priceMax, 2) }}" @endif>
    </div>
  </div>

  @if($showSort)
    <div class="cf-field">
      <label for="{{ $cid }}-sort">Sort by</label>
      <select id="{{ $cid }}-sort" class="cf-select" data-cf-sort>
        <option value="default">Featured</option>
        <option value="newest">Newest first</option>
        <option value="price-asc">Price: low to high</option>
        <option value="price-desc">Price: high to low</option>
        <option value="name">Name (A&ndash;Z)</option>
      </select>
    </div>
  @endif

  <button type="button" class="cf-reset" data-cf-reset disabled>Clear all</button>
  </div>
</div>

<div class="cf-summary" data-cf-summary hidden>
  <span class="cf-count" data-cf-count></span>
  <span class="cf-chips" data-cf-chips></span>
</div>

@once
<script>
  /* Shared catalog filter engine.
     Filtering is client-side: the page renders every card once and the engine
     shows/hides and reorders them, which is what the rest of this app does.
     Card facets come from the data-cf-* attributes the product-card
     component emits, so every catalog surface filters on identical data. */
  (function () {
    'use strict';

    /* Sorts return a comparable key; the caller uses compareKeys so string
       keys (name) and numeric keys (price) both sort correctly. */
    var SORTS = {
      'default': function () { return 0; },
      'newest': function (c) { return num(c, 'date'); },
      'price-asc': function (c) { return num(c, 'price'); },
      'price-desc': function (c) { return -num(c, 'price'); },
      'name': function (c) { return str(c, 'name'); },
    };

    function compareKeys(a, b) {
      if (typeof a === 'string' || typeof b === 'string') {
        return String(a).localeCompare(String(b));
      }
      return a - b;
    }

    function num(card, key) {
      var v = parseFloat(card.getAttribute('data-cf-' + key));
      return isNaN(v) ? 0 : v;
    }
    function str(card, key) {
      return (card.getAttribute('data-cf-' + key) || '').toLowerCase();
    }

    function boot(root) {
      if (root.getAttribute('data-cf-ready') === '1') return;
      root.setAttribute('data-cf-ready', '1');

      var targetSel = root.getAttribute('data-cf-target') || '.product-grid';
      // A page can hold more than one grid (the storefront renders the same
      // catalog in a manage tab and a preview tab), so every container is
      // collected and one card list is built across all of them.
      var containers = Array.prototype.slice.call(document.querySelectorAll(targetSel));
      if (!containers.length) return;

      var cards = [];
      containers.forEach(function (c) {
        cards = cards.concat(Array.prototype.slice.call(c.querySelectorAll('[data-cf-search],[data-cf-price]')));
      });
      // No cards is a legitimate state (an empty catalog), so the empty-state
      // hooks still have to be wired rather than bailing out here.

      var searchInput = root.querySelector('[data-cf-search-input]');
      var searchClear = root.querySelector('[data-cf-search-clear]');
      var priceInputs = root.querySelectorAll('[data-cf-price]');
      var selects = root.querySelectorAll('[data-cf-filter]');
      var sortSelect = root.querySelector('[data-cf-sort]');
      // "Clear all" appears in the bar and again in the no-results empty state,
      // so every instance on the page is wired and kept in sync.
      var resetBtns = Array.prototype.slice.call(document.querySelectorAll('[data-cf-reset]'));
      var summary = document.querySelector('[data-cf-summary]');
      var countEl = document.querySelector('[data-cf-count]');
      var chipsEl = document.querySelector('[data-cf-chips]');

      // The empty states belong to the page (they carry its own copy and
      // position); the engine only decides which one is showing.
      var emptyNoProducts = document.querySelectorAll('[data-cf-no-products]');
      var emptyNoResults = document.querySelectorAll('[data-cf-no-results]');

      var state = { search: '', category: '', season: '', stock: '', min: null, max: null, sort: 'default' };
      var debounce = null;

      function setEmpty(nodes, show) {
        Array.prototype.forEach.call(nodes, function (el) {
          el.style.display = show ? (el.getAttribute('data-cf-display') || 'block') : 'none';
        });
      }

      /* Every token must appear somewhere. A token that is not a substring of
         the haystack still matches when it prefixes the product name, which
         covers the "fertizer"/"fertiliser" and truncated-typeahead cases the
         old fuzzy matcher was there for, without a per-card Levenshtein. */
      function matchesSearch(tokens, haystack, name) {
        for (var i = 0; i < tokens.length; i++) {
          var t = tokens[i];
          if (haystack.indexOf(t) !== -1) continue;
          if (t.length >= 2 && name.indexOf(t) === 0) continue;
          return false;
        }
        return true;
      }

      function isVisible(card) {
        var haystack = card.getAttribute('data-cf-search') || '';
        var name = card.getAttribute('data-cf-name') || '';

        if (state.search) {
          var tokens = state.search.split(/\s+/);
          if (!matchesSearch(tokens, haystack, name)) return false;
        }

        // A category matches the card's own category or its top-level group,
        // so picking "Fertilizers" still returns items filed under a sub-group.
        if (state.category) {
          if (str(card, 'category') !== state.category && str(card, 'group') !== state.category) return false;
        }
        if (state.season && (card.getAttribute('data-cf-season') || 'both') !== state.season) return false;
        if (state.stock && (card.getAttribute('data-cf-stock') || '') !== state.stock) return false;

        var price = num(card, 'price');
        if (state.min !== null && price < state.min) return false;
        if (state.max !== null && price > state.max) return false;

        return true;
      }

      function apply() {
        state.search = (searchInput.value || '').toLowerCase().trim();

        Array.prototype.forEach.call(priceInputs, function (input) {
          var which = input.getAttribute('data-cf-price');
          var raw = (input.value || '').trim();
          var val = raw === '' ? null : parseFloat(raw);
          state[which] = isNaN(val) ? null : val;
        });

        Array.prototype.forEach.call(selects, function (sel) {
          state[sel.getAttribute('data-cf-filter')] = sel.value;
        });

        state.sort = sortSelect ? sortSelect.value : 'default';

        var visible = [];
        cards.forEach(function (card) {
          var show = isVisible(card);
          card.classList.toggle('cf-hidden', !show);
          if (show) visible.push(card);
        });

        // Reordering is what makes "Price: low to high" mean anything.
        // Array#sort is stable, so equal keys keep the server's ordering, and
        // appending each card back to its own container sorts every grid
        // independently while walking the shared order.
        var cmp = SORTS[state.sort] || SORTS.default;
        if (state.sort !== 'default') {
          visible.sort(function (a, b) { return compareKeys(cmp(a), cmp(b)); });
          visible.forEach(function (card) {
            if (card.parentNode) card.parentNode.appendChild(card);
          });
        }

        if (searchClear) searchClear.classList.toggle('is-visible', (searchInput.value || '').length > 0);

        var activeCount = countActive();
        resetBtns.forEach(function (b) { b.disabled = activeCount === 0; });

        if (countEl) {
          countEl.innerHTML = 'Showing <b>' + visible.length + '</b> of ' + cards.length + ' ' + noun();
        }
        if (summary) summary.hidden = activeCount === 0;
        renderChips();

        setEmpty(emptyNoProducts, cards.length === 0);
        setEmpty(emptyNoResults, cards.length > 0 && visible.length === 0);

        writeUrl();
      }

      function noun() {
        return (root.getAttribute('data-cf-noun') || 'products').toLowerCase();
      }

      function countActive() {
        return (state.search ? 1 : 0) + (state.category ? 1 : 0) + (state.season ? 1 : 0)
          + (state.stock ? 1 : 0) + (state.min !== null ? 1 : 0) + (state.max !== null ? 1 : 0);
      }

      function labels() {
        var out = [];
        if (state.search) out.push({ key: 'search', label: '“' + (searchInput.value || '').trim() + '”' });
        if (state.category) out.push({ key: 'category', label: titleCase(state.category) });
        if (state.season) out.push({ key: 'season', label: state.season === 'both' ? 'Year-round' : titleCase(state.season) + ' season' });
        if (state.stock) out.push({ key: 'stock', label: titleCase(state.stock === 'in' ? 'in stock' : state.stock) });
        if (state.min !== null || state.max !== null) {
          var peso = '₱';
          out.push({
            key: 'price',
            label: state.min !== null && state.max !== null
              ? peso + fmt(state.min) + ' – ' + peso + fmt(state.max)
              : (state.min !== null ? peso + fmt(state.min) + '+' : 'up to ' + peso + fmt(state.max))
          });
        }
        return out;
      }

      function fmt(n) {
        return n % 1 === 0 ? String(n) : n.toFixed(2);
      }

      function titleCase(s) {
        return String(s).replace(/\b\w/g, function (c) { return c.toUpperCase(); });
      }

      function renderChips() {
        if (!chipsEl) return;
        chipsEl.innerHTML = '';
        labels().forEach(function (item) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'cf-chip';
          btn.innerHTML = '<span>' + escapeHtml(item.label) + '</span><span class="cf-chip-x" aria-hidden="true">&times;</span>';
          btn.setAttribute('aria-label', 'Remove filter ' + item.label);
          btn.addEventListener('click', function () {
            clearOne(item.key);
            apply();
          });
          chipsEl.appendChild(btn);
        });

        if (countActive() > 1) {
          var all = document.createElement('button');
          all.type = 'button';
          all.className = 'cf-chip cf-chip-clear-all';
          all.textContent = 'Clear all';
          all.addEventListener('click', reset);
          chipsEl.appendChild(all);
        }
      }

      function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }

      function clearOne(key) {
        if (key === 'search') searchInput.value = '';
        else if (key === 'price') {
          Array.prototype.forEach.call(priceInputs, function (i) { i.value = ''; });
        } else {
          var sel = root.querySelector('[data-cf-filter="' + key + '"]');
          if (sel) sel.value = '';
        }
      }

      function reset() {
        searchInput.value = '';
        Array.prototype.forEach.call(priceInputs, function (i) { i.value = ''; });
        Array.prototype.forEach.call(selects, function (s) { s.value = ''; });
        if (sortSelect) sortSelect.value = 'default';
        apply();
      }

      /* ── URL sync ─────────────────────────────────────────────────────
         Read on load so ?season=wet from the recommendations page and any
         shared link still work, and write with replaceState so the back
         button steps through the previous page rather than through every
         keystroke. Unknown params in the URL are preserved. */
      function readUrl() {
        if (root.getAttribute('data-cf-sync') !== '1') return;
        var p = new URLSearchParams(window.location.search);

        if (p.get('search')) searchInput.value = p.get('search');
        var cat = p.get('category');
        var season = p.get('season');
        var stock = p.get('stock');
        var min = p.get('min');
        var max = p.get('max');
        var sort = p.get('sort');

        var setSel = function (key, val) {
          if (!val) return;
          var sel = root.querySelector('[data-cf-filter="' + key + '"]');
          if (sel) sel.value = val;
        };
        setSel('category', cat);
        setSel('season', season);
        setSel('stock', stock);

        Array.prototype.forEach.call(priceInputs, function (input) {
          var v = input.getAttribute('data-cf-price') === 'min' ? min : max;
          if (v) input.value = v;
        });
        if (sortSelect && sort && SORTS[sort]) sortSelect.value = sort;
      }

      function writeUrl() {
        if (root.getAttribute('data-cf-sync') !== '1') return;
        var p = new URLSearchParams(window.location.search);

        var setOrDrop = function (key, val) {
          if (val === '' || val === null) p.delete(key);
          else p.set(key, val);
        };
        setOrDrop('search', (searchInput.value || '').trim());
        setOrDrop('category', state.category);
        setOrDrop('season', state.season);
        setOrDrop('stock', state.stock);
        setOrDrop('min', state.min);
        setOrDrop('max', state.max);

        // "Featured" is the server's own ordering, so it is not worth a param.
        if (!sortSelect || sortSelect.value === 'default') p.delete('sort');
        else p.set('sort', sortSelect.value);

        var qs = p.toString();
        var url = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
        window.history.replaceState(null, '', url);
      }

      /* ── Wiring ──────────────────────────────────────────────────────── */
      searchInput.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(apply, 150);
      });
      if (searchClear) {
        searchClear.addEventListener('click', function () {
          searchInput.value = '';
          apply();
          searchInput.focus();
        });
      }
      Array.prototype.forEach.call(priceInputs, function (input) {
        input.addEventListener('input', function () {
          clearTimeout(debounce);
          debounce = setTimeout(apply, 200);
        });
      });
      Array.prototype.forEach.call(selects, function (sel) {
        sel.addEventListener('change', apply);
      });
      if (sortSelect) sortSelect.addEventListener('change', apply);
      resetBtns.forEach(function (b) { b.addEventListener('click', reset); });

      readUrl();
      apply();
    }

    function start() {
      Array.prototype.forEach.call(document.querySelectorAll('[data-cf-root]'), boot);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start);
    } else {
      start();
    }
  })();
</script>
@endonce
