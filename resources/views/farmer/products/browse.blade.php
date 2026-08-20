@extends('layouts.sidebar')

@section('title', 'Browse Farm Supplies')

@section('content')
<style>
  *, *::before, *::after {
    outline: none !important;
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

  /* Product Grid */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
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
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
  .meta-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }
  .meta-tag {
    font-size: 11px;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 500;
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
  .stock-status {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
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
      <button type="button" class="filter-chip" data-season="wet">🌧️ Wet Season</button>
      <button type="button" class="filter-chip" data-season="dry">☀️ Dry Season</button>
    </div>
  </div>
</div>

<!-- Results Info -->
<div id="resultsInfo" class="results-info"></div>

<!-- Supplies Grid -->
<div class="product-grid" id="productGrid">
  @forelse($products as $product)
    <a href="{{ route('farmer.products.show', $product->id) }}" class="product-card"
       data-name="{{ strtolower($product->name) }}"
       data-description="{{ strtolower($product->description ?? '') }}"
       data-brand="{{ strtolower($product->brand ?? '') }}"
       data-sku="{{ strtolower($product->sku ?? '') }}"
       data-category="{{ strtolower($product->category?->name ?? $product->getOriginal('category') ?? '') }}"
       data-season="{{ $product->recommended_season ?? '' }}">
      <div style="height: 180px; background: #f8fafc; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f1f5f9; overflow: hidden; flex-shrink: 0;">
        @if($product->mainImage && !empty($product->mainImage->file_path))
          <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
        @elseif($product->images && $product->images->first())
          <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
        @else
          <svg viewBox="0 0 24 24" width="48" height="48" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        @endif
      </div>
      <div class="card-body">
        <div class="badge-container">
          <span class="category-pill">{{ $product->category?->name ?? $product->getOriginal('category') ?? 'General Supply' }}</span>

          @if($product->recommended_season)
            <span class="season-pill {{ $product->recommended_season === 'wet' ? 'season-wet' : ($product->recommended_season === 'dry' ? 'season-dry' : 'season-all') }}">
              @if($product->recommended_season === 'wet') 🌧️ Wet
              @elseif($product->recommended_season === 'dry') ☀️ Dry
              @else 🔄 Year-round
              @endif
            </span>
          @endif
        </div>

        <h3 class="product-title">{{ $product->name }}</h3>
        <p class="product-description">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 90) : 'No additional details provided.' }}</p>

        <div class="meta-tags">
          @if($product->brand)
            <span class="meta-tag">Brand: {{ $product->brand }}</span>
          @endif
          @if($product->sku)
            <span class="meta-tag">Code: {{ $product->sku }}</span>
          @endif
        </div>
      </div>

      <div class="card-footer">
        <span class="price-tag">₱{{ number_format($product->price, 2) }}</span>
        <span class="stock-status {{ $product->is_low_stock ? 'stock-low' : 'stock-in' }}">
          <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
          {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pieces' }} left
        </span>
      </div>
    </a>
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