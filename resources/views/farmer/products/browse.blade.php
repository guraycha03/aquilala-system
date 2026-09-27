@extends('layouts.sidebar')

@section('title', 'Browse Farm Supplies')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'browse',  'label' => 'Browse Products',    'url' => route('farmer.products.browse')],
      ['key' => 'reviews', 'label' => 'Top Rated Products', 'url' => route('farmer.reviews.index')],
      ['key' => 'stores',  'label' => 'Stores Directory',   'url' => route('farmer.stores.index')],
    ],
    'activeTab' => 'browse',
  ])
<style>
  *, *::before, *::after {
    outline: none !important;
  }
  /* PAGE-SPECIFIC: raise the breadcrumb + title + subtitle block higher.
     Reduces the layout's default top padding (64px) to 32px on desktop only.
     Mobile keeps 88px clearance under the fixed mobile header.
     To reuse on another page, copy this rule into that page's <style> block. */
  @media (min-width: 769px) {
    .main-content { padding-top: {{ ((string) optional(auth()->user())->sub_pages_style === 'top') ? '64px' : '32px' }}; }
  }
  .page-header {
    margin-bottom: 20px;
  }
  .page-title {
    font-size: 26px;
    font-weight: 800;
    color: #143d23;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
  }
  .page-subtitle {
    color: #526356;
    font-size: 15px;
    line-height: 1.5;
  }

  /* Search & Filter Bar */
  .search-bar {
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }
  .search-input-wrapper {
    position: relative;
    flex: 1;
  }
  .search-input-wrapper svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    stroke: #94a3b8;
    pointer-events: none;
  }
  .search-input {
    width: 100%;
    padding: 10px 36px 10px 38px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    color: #1e2922;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #f8fafc;
  }
  .search-input:focus {
    border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.1);
    background: #ffffff;
  }
  .search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border: none;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 50%;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    line-height: 1;
    transition: background 0.15s;
  }
  .search-clear:hover { background: #cbd5e1; }
  .search-clear.visible { display: flex; }

  /* Filter Icon Button */
  .filter-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    position: relative;
  }
  .filter-btn:hover { border-color: #143d23; color: #143d23; }
  .filter-btn.active {
    background: #143d23;
    border-color: #143d23;
    color: #ffffff;
  }
  .filter-btn .filter-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 16px;
    height: 16px;
    background: #dc2626;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    line-height: 1;
  }
  .filter-btn .filter-badge.visible { display: flex; }

  /* Filter Dropdown Panel */
  .filter-panel {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, opacity 0.2s ease, margin 0.3s ease;
    opacity: 0;
    margin-bottom: 0;
  }
  .filter-panel.open {
    max-height: 200px;
    opacity: 1;
    margin-bottom: 20px;
  }
  .filter-panel-inner {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .filter-label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 10px;
  }
  .filter-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .filter-chip {
    padding: 8px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    user-select: none;
  }
  .filter-chip:hover { border-color: #143d23; color: #143d23; }
  .filter-chip.selected {
    background: #143d23;
    border-color: #143d23;
    color: #ffffff;
  }

  /* Results Count */
  .results-info {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 16px;
    display: none;
  }
  .results-info.visible { display: block; }
  .results-info strong { color: #475569; font-weight: 700; }

  /* Product Grid: 4 across on desktops (accounts for the fixed sidebar),
     3 on smaller desktops/large tablets, 2 below that */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
  @media (max-width: 1200px) {
    .product-grid { grid-template-columns: repeat(3, 1fr); }
  }
  @media (max-width: 900px) {
    .product-grid { grid-template-columns: repeat(2, 1fr); }
  }

  /* Product Cards */
  .product-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    overflow: hidden;
  }
  .product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(20, 61, 35, 0.08);
    border-color: #cbd5e1;
  }
  .product-card.hidden {
    display: none;
  }
  .card-body {
    padding: 20px;
  }
  .badge-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }
  .category-pill {
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  .season-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    padding: 5px 10px;
    border-radius: 6px;
  }
  .season-wet { background: #e0f2fe; color: #0369a1; }
  .season-dry { background: #ffedd5; color: #c2410c; }
  .season-all { background: #f0fdf4; color: #166534; }

  .product-title {
    font-size: 16px;
    font-weight: 700;
    color: #143d23;
    margin-bottom: 8px;
    line-height: 1.35;
  }
  .product-description {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 14px;
  }
  .card-footer {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .price-tag {
    font-size: 18px;
    font-weight: 800;
    color: #143d23;
  }
  .stock-in { color: #16a34a; }
  .stock-low { color: #dc2626; }

  /* Empty State */
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    display: none;
  }
  .empty-state.visible { display: block; }
  .empty-icon {
    width: 56px;
    height: 56px;
    background: #f1f5f9;
    color: #94a3b8;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }

  @media (max-width: 640px) {
    .search-bar {
      padding: 8px 10px;
      gap: 6px;
    }
    .filter-btn { width: 36px; height: 36px; }
    .product-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    .card-body { padding: 14px; }
    .card-footer { padding: 12px 14px; }
    .product-title { font-size: 14px; }
    .product-description { font-size: 12px; }
    .price-tag { font-size: 15px; }
    .filter-chips { gap: 6px; }
    .filter-chip { padding: 6px 12px; font-size: 12px; }
  }
</style>

<x-breadcrumb :items="[['label' => 'Browse Products']]"/>

<div class="page-header">
  <h1 class="page-title">Farm Supplies Catalog</h1>
  <p class="page-subtitle">Find high-quality seeds, fertilizers, and equipment from verified local stores.</p>
</div>

<!-- Search Bar (no form submit — client-side filtering) -->
<div class="search-bar">
  <div class="search-input-wrapper">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="searchInput" placeholder="Search supplies..." class="search-input" autocomplete="off">
    <button type="button" id="searchClear" class="search-clear" title="Clear search">&times;</button>
  </div>
  <button type="button" id="filterBtn" class="filter-btn" title="Filter by season" aria-label="Toggle season filter">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
    </svg>
    <span id="filterBadge" class="filter-badge">1</span>
  </button>
</div>

<!-- Filter Panel (hidden by default, opens on filter icon tap) -->
<div id="filterPanel" class="filter-panel">
  <div class="filter-panel-inner">
    <div class="filter-label">Planting Season</div>
    <div class="filter-chips">
      <button type="button" class="filter-chip selected" data-season="">All Seasons</button>
      <button type="button" class="filter-chip" data-season="wet">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>
        Wet Season
      </button>
      <button type="button" class="filter-chip" data-season="dry">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        Dry Season
      </button>
    </div>
  </div>
</div>

<!-- Results Info -->
<div id="resultsInfo" class="results-info"></div>

<!-- Supplies Grid -->
<div class="product-grid" id="productGrid">
  @forelse($products as $product)
    <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}"
       data-name="{{ strtolower($product->name . ' ' . ($product->size_label ?? '')) }}"
       data-description="{{ strtolower($product->description ?? '') }}"
       data-brand="{{ strtolower($product->brand ?? '') }}"
       data-sku="{{ strtolower($product->sku ?? '') }}"
       data-category="{{ strtolower($product->category_display_name) }}"
       data-season="{{ $product->recommended_season ?? '' }}" />
  @empty
    <div class="empty-state" id="emptyStateServer">
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Supplies Found</h3>
      <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto;">There are no supplies matching your search. Try searching for broader terms or check back later.</p>
    </div>
  @endforelse
</div>

<!-- Empty state for filtered results (shown via JS) -->
<div class="empty-state" id="emptyStateFiltered" style="grid-column: 1 / -1;">
  <div class="empty-icon">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </div>
  <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Matching Supplies</h3>
  <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto;">No supplies match your current search. Try different keywords or clear your filters.</p>
</div>

<script>
(function() {
  const searchInput = document.getElementById('searchInput');
  const searchClear = document.getElementById('searchClear');
  const filterBtn = document.getElementById('filterBtn');
  const filterPanel = document.getElementById('filterPanel');
  const filterBadge = document.getElementById('filterBadge');
  const filterChips = document.querySelectorAll('.filter-chip');
  const productGrid = document.getElementById('productGrid');
  const resultsInfo = document.getElementById('resultsInfo');
  const emptyStateFiltered = document.getElementById('emptyStateFiltered');

  let activeSeason = '';
  let debounceTimer = null;

  // --- Levenshtein distance for fuzzy matching ---
  function levenshtein(a, b) {
    if (a.length === 0) return b.length;
    if (b.length === 0) return a.length;
    const matrix = [];
    for (let i = 0; i <= b.length; i++) matrix[i] = [i];
    for (let j = 0; j <= a.length; j++) matrix[0][j] = j;
    for (let i = 1; i <= b.length; i++) {
      for (let j = 1; j <= a.length; j++) {
        if (b[i - 1] === a[j - 1]) {
          matrix[i][j] = matrix[i - 1][j - 1];
        } else {
          matrix[i][j] = Math.min(
            matrix[i - 1][j - 1] + 1,
            matrix[i][j - 1] + 1,
            matrix[i - 1][j] + 1
          );
        }
      }
    }
    return matrix[b.length][a.length];
  }

  // --- Fuzzy match: does a search word match a target word? ---
  function fuzzyMatchWord(word, target) {
    if (target.includes(word)) return true; // substring match
    if (word.length >= 3 && levenshtein(word, target) <= 2) return true; // typo tolerance
    // Also try prefix match for short inputs
    if (word.length >= 2 && target.startsWith(word)) return true;
    return false;
  }

  // --- Check if search query matches a product ---
  function matchesSearch(card, query) {
    if (!query) return true;
    const words = query.toLowerCase().trim().split(/\s+/);
    const name = card.dataset.name || '';
    const desc = card.dataset.description || '';
    const brand = card.dataset.brand || '';
    const sku = card.dataset.sku || '';
    const category = card.dataset.category || '';

    // Each search word must match at least one field
    return words.every(function(word) {
      if (fuzzyMatchWord(word, name)) return true;
      if (fuzzyMatchWord(word, category)) return true;
      if (fuzzyMatchWord(word, brand)) return true;
      if (fuzzyMatchWord(word, sku)) return true;
      // For descriptions, only check substrings (too long for fuzzy)
      if (desc.includes(word)) return true;
      return false;
    });
  }

  // --- Check if product matches season filter ---
  function matchesSeason(card) {
    if (!activeSeason) return true;
    const season = card.dataset.season;
    return season === activeSeason;
  }

  // --- Filter and re-render visibility ---
  function applyFilters() {
    const query = searchInput.value.trim();
    const cards = productGrid.querySelectorAll('.product-card');
    let visibleCount = 0;

    cards.forEach(function(card) {
      const visible = matchesSearch(card, query) && matchesSeason(card);
      card.classList.toggle('hidden', !visible);
      if (visible) visibleCount++;
    });

    // Results info
    const totalCards = cards.length;
    const isFiltering = query || activeSeason;
    if (isFiltering && totalCards > 0) {
      resultsInfo.innerHTML = 'Showing <strong>' + visibleCount + '</strong> of ' + totalCards + ' supplies';
      resultsInfo.classList.add('visible');
    } else {
      resultsInfo.classList.remove('visible');
    }

    // Empty states
    const serverEmpty = document.getElementById('emptyStateServer');
    if (serverEmpty) serverEmpty.style.display = 'none';
    emptyStateFiltered.classList.toggle('visible', visibleCount === 0 && isFiltering);
  }

  // --- Search input events ---
  searchInput.addEventListener('input', function() {
    searchClear.classList.toggle('visible', searchInput.value.length > 0);
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 150);
  });

  searchClear.addEventListener('click', function() {
    searchInput.value = '';
    searchClear.classList.remove('visible');
    applyFilters();
    searchInput.focus();
  });

  // --- Filter button toggle ---
  filterBtn.addEventListener('click', function() {
    filterPanel.classList.toggle('open');
  });

  // Close filter panel on outside click
  document.addEventListener('click', function(e) {
    if (!filterPanel.contains(e.target) && !filterBtn.contains(e.target)) {
      filterPanel.classList.remove('open');
    }
  });

  // --- Filter chip selection ---
  filterChips.forEach(function(chip) {
    chip.addEventListener('click', function() {
      filterChips.forEach(function(c) { c.classList.remove('selected'); });
      chip.classList.add('selected');
      activeSeason = chip.dataset.season;

      // Update badge
      filterBadge.classList.toggle('visible', activeSeason !== '');
      filterBtn.classList.toggle('active', activeSeason !== '');

      applyFilters();
    });
  });

  // --- Init: check URL params for pre-filled state ---
  (function() {
    const params = new URLSearchParams(window.location.search);
    const urlSearch = params.get('search');
    const urlSeason = params.get('season');
    if (urlSearch) {
      searchInput.value = urlSearch;
      searchClear.classList.add('visible');
    }
    if (urlSeason) {
      activeSeason = urlSeason;
      filterChips.forEach(function(c) {
        c.classList.toggle('selected', c.dataset.season === urlSeason);
      });
      filterBadge.classList.toggle('visible', true);
      filterBtn.classList.toggle('active', true);
      // Auto-open the filter panel if a filter was pre-selected
      filterPanel.classList.add('open');
    }
    if (urlSearch || urlSeason) applyFilters();
  })();
})();
</script>
@endsection