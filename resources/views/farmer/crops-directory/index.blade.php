@extends('layouts.sidebar')

@section('content')

@php
    $fallbackImage = 'images/crop.png';

    $tagBg = [
        'grain' => '#fef9c3', 'vegetable' => '#dcfce7', 'fruit' => '#fce7f3',
        'root' => '#ffedd5', 'herb' => '#dbeafe', 'fiber' => '#f3e8ff',
        'legume' => '#fef3c7',
    ];
    $tagFg = [
        'grain' => '#a16207', 'vegetable' => '#15803d', 'fruit' => '#be185d',
        'root' => '#c2410c', 'herb' => '#1d4ed8', 'fiber' => '#7e22ce',
        'legume' => '#b45309',
    ];
    $categoryLabels = [
        'grain' => 'Grain', 'vegetable' => 'Vegetable', 'fruit' => 'Fruit',
        'root' => 'Root Crop', 'herb' => 'Herb', 'fiber' => 'Fiber & Industrial',
        'legume' => 'Legume',
    ];

    // Shared pastel crop color map — mirrors the colors used in the farmer land
    // partition so a crop reads as the same color in the catalog and the field.
    $cropColors = [
        // Grains
        'Rice' => '#E8F5E9', 'Corn' => '#FFF59D', 'Wheat' => '#F5E6CA',
        // Fruits
        'Coconut' => '#E0D5C1', 'Pili' => '#D7CCC8', 'Banana' => '#FFF176',
        'Mango' => '#FFCC80', 'Papaya' => '#FFB74D', 'Pineapple' => '#FFE082',
        'Jackfruit' => '#E6EE9C', 'Calamansi' => '#C5E1A5', 'Watermelon' => '#FFAB91',
        'Orange' => '#FFB74D', 'Lemon' => '#FFF59D',
        // Vegetables
        'Tomato' => '#FF8A80', 'Eggplant' => '#D1C4E9', 'Okra' => '#C8E6C9',
        'String Bean' => '#A5D6A7', 'Squash' => '#FFE082', 'Ampalaya' => '#A5D6A7',
        'Pepper' => '#FF8A80', 'Cabbage' => '#C8E6C9', 'Sayote' => '#DCEDC8',
        'Luffa' => '#C8E6C9', 'Cucumber' => '#A5D6A7', 'Lettuce' => '#DCEDC8',
        'Malunggay' => '#A5D6A7', 'Kangkong' => '#C8E6C9',
        // Root Crops
        'Cassava' => '#D7CCC8', 'Sweet Potato' => '#E1BEE7', 'Taro' => '#E1BEE7',
        'Potato' => '#F5E6CA', 'Carrot' => '#FFCC80',
        // Herbs & Spices
        'Onion' => '#F8BBD0', 'Garlic' => '#F5F5F5', 'Ginger' => '#E0D5C1',
        'Turmeric' => '#FFE082', 'Lemongrass' => '#DCEDC8',
        // Legumes
        'Mung Bean' => '#C8E6C9', 'Soybean' => '#FFF59D', 'Peanut' => '#E0D5C1',
        // Fiber & Industrial
        'Abaca' => '#D7CCC8', 'Coffee' => '#D7CCC8', 'Cacao' => '#CFD8DC',
        'Rubber' => '#ECEFF1', 'Sugarcane' => '#DCEDC8', 'Tobacco' => '#D7CCC8',
        'Cotton' => '#FAFAFA',
        // Unknown / catch-all
        'default' => '#C8E6C9',
    ];

    // Case-insensitive lookup with a soft pastel green fallback.
    $getCropColor = function ($name) use ($cropColors) {
        $name = trim((string) $name);
        if ($name === '') return '#C8E6C9';
        foreach ($cropColors as $key => $hex) {
            if (strcasecmp($key, $name) === 0) return $hex;
        }
        return '#C8E6C9';
    };

    $featuredCrops = array_filter($crops, function($c) { return $c['featured'] ?? false; });
@endphp

<style>
  .cd-page {
    position: relative;
    min-height: 100vh;
    background: #f3f6f4;
  }
  .cd-page::before,
  .cd-page::after {
    content: "";
    position: fixed;
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
    z-index: 0;
  }
  .cd-page::before {
    width: 340px; height: 340px;
    background: rgba(22,163,74,0.14);
    top: -80px; right: -80px;
  }
  .cd-page::after {
    width: 280px; height: 280px;
    background: rgba(217,119,6,0.10);
    bottom: -60px; left: -60px;
  }
  .cd-content {
    position: relative;
    z-index: 1;
    padding: 28px 32px;
  }

  /* ── Breadcrumb ── */
  .cd-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 24px;
  }
  .cd-breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
  }
  .cd-breadcrumb a:hover { color: #16a34a; }
  .cd-breadcrumb-sep {
    color: #cbd5e1;
    font-weight: 400;
    font-size: 11px;
    user-select: none;
  }
  .cd-breadcrumb-current {
    color: #0f172a;
    font-weight: 700;
  }

  /* ── Hero card ── */
  .cd-hero {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.04);
    padding: 32px;
    margin-bottom: 24px;
  }
  .cd-hero-inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
  }
  .cd-hero h1 {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px 0;
    letter-spacing: -0.01em;
  }
  .cd-hero p {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
    line-height: 1.6;
    max-width: 520px;
  }
  .cd-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #f0fdf4;
    color: #16a34a;
    font-size: 13px;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 24px;
    border: 1px solid #dcfce7;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .cd-badge svg {
    width: 16px; height: 16px;
    stroke: currentColor; fill: none; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
  }

  /* ── Featured crops section ── */
  .cd-featured-section {
    margin-bottom: 28px;
  }
  .cd-featured-header {
    margin-bottom: 16px;
  }
  .cd-featured-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px 0;
  }
  .cd-featured-header p {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
    line-height: 1.5;
  }
  .cd-featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
  }

  .cd-featured-card {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    cursor: default;
  }
  .cd-featured-card:hover {
    border-color: #c8e6c9;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transform: translateY(-3px);
  }
  .cd-featured-top {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 18px 0;
  }
  .cd-featured-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid rgba(22,163,74,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }
  .cd-featured-icon img {
    width: 40px;
    height: 40px;
    object-fit: contain;
  }
  .cd-featured-meta {
    flex: 1;
    min-width: 0;
  }
  .cd-featured-name {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 5px;
    line-height: 1.25;
  }
  .cd-featured-tag {
    display: inline-block;
    font-size: 10.5px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1px solid;
  }
  .cd-featured-body {
    padding: 14px 18px 18px;
  }
  .cd-featured-snippets {
    display: flex;
    flex-direction: column;
    gap: 7px;
  }
  .cd-featured-snippet {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    padding: 6px 10px;
    background: #f8faf9;
    border-radius: 8px;
  }
  .cd-featured-snippet-label {
    color: #94a3b8;
    font-weight: 500;
  }
  .cd-featured-snippet-value {
    color: #cbd5e1;
    font-weight: 600;
  }

  /* ── Search + Filter controls ── */
  .cd-controls {
    display: flex;
    gap: 14px;
    margin-bottom: 12px;
    flex-wrap: wrap;
    align-items: center;
  }
  .cd-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
  }
  .cd-search-wrap svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 17px; height: 17px;
    stroke: #94a3b8; fill: none; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
    pointer-events: none;
  }
  .cd-search {
    width: 100%;
    padding: 12px 16px 12px 42px;
    background: #ffffff;
    border: 1px solid #e2e5e3;
    border-radius: 12px;
    font-size: 13.5px;
    color: #0f172a;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .cd-search::placeholder { color: #94a3b8; }
  .cd-search:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.10);
  }

  .cd-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 24px;
  }
  .cd-filter-btn {
    padding: 8px 18px;
    border-radius: 24px;
    font-size: 12.5px;
    font-weight: 600;
    background: #ffffff;
    border: 1px solid #e2e5e3;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
  }
  .cd-filter-btn:hover {
    border-color: #cbd5e1;
    color: #334155;
  }
  .cd-filter-btn.active {
    background: #16a34a;
    border-color: #16a34a;
    color: #ffffff;
  }

  /* ── Full crop grid ── */
  .cd-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
  }

  .cd-card {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
  }
  .cd-card:hover {
    border-color: #d4d8d6;
    box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  }
  .cd-card.expanded {
    border-color: #16a34a;
    box-shadow: 0 4px 20px rgba(22,163,74,0.10);
  }

  .cd-card-top {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
  }
  .cd-card-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid rgba(22,163,74,0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }
  .cd-card-icon img { width: 32px; height: 32px; object-fit: contain; }
  .cd-card-meta {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .cd-card-left {
    min-width: 0;
  }
  .cd-card-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .cd-card-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    border: 1px solid;
    margin-top: 4px;
  }
  .cd-card-chevron {
    width: 20px; height: 20px;
    stroke: #cbd5e1; fill: none; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
    transition: transform 0.25s ease, stroke 0.2s;
    flex-shrink: 0;
  }
  .cd-card:hover .cd-card-chevron { stroke: #94a3b8; }
  .cd-card.expanded .cd-card-chevron {
    transform: rotate(180deg);
    stroke: #16a34a;
  }

  /* ── Info panel (expand/collapse) ── */
  .cd-card-info {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s cubic-bezier(0.4,0,0.2,1);
  }
  .cd-card.expanded .cd-card-info {
    max-height: 320px;
  }
  .cd-card-info-inner {
    padding: 0 16px 16px;
    border-top: 1px solid #f1f3f2;
    padding-top: 14px;
  }
  .cd-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }
  .cd-info-item {
    padding: 10px 12px;
    background: #f8faf9;
    border-radius: 8px;
  }
  .cd-info-label {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 3px;
  }
  .cd-info-value {
    font-size: 12.5px;
    color: #cbd5e1;
    font-weight: 500;
  }
  .cd-info-desc {
    margin-top: 8px;
    padding: 10px 12px;
    background: #f8faf9;
    border-radius: 8px;
  }
  .cd-info-desc .cd-info-label {
    margin-bottom: 4px;
  }
  .cd-info-desc .cd-info-value {
    font-size: 12px;
    line-height: 1.5;
  }

  /* ── Empty state ── */
  .cd-empty {
    display: none;
    padding: 60px 20px;
    text-align: center;
  }
  .cd-empty svg {
    width: 56px; height: 56px;
    stroke: #cbd5e1; fill: none; stroke-width: 1.5;
    margin: 0 auto 16px;
    display: block;
  }
  .cd-empty h3 {
    font-size: 16px;
    font-weight: 700;
    color: #64748b;
    margin: 0 0 6px 0;
  }
  .cd-empty p {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .cd-content { padding: 20px 16px; }
    .cd-hero { padding: 22px; }
    .cd-hero h1 { font-size: 22px; }
    .cd-grid { grid-template-columns: 1fr; gap: 10px; }
    .cd-featured-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
  }
</style>

<div class="cd-page">
  <div class="cd-content">

    {{-- ═══ 1. Breadcrumb ═══ --}}
    <nav class="cd-breadcrumb">
      <a href="{{ route('dashboard.farmer') }}">Dashboard</a>
      <span class="cd-breadcrumb-sep">
        <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </span>
      <span class="cd-breadcrumb-current">Crops Directory</span>
    </nav>

    {{-- ═══ 2. Hero card ═══ --}}
    <div class="cd-hero">
      <div class="cd-hero-inner">
        <div>
          <h1>Supported Crops</h1>
          <p>Comprehensive list of crops suitable for cultivation in Bulan, Sorsogon and across the Bicol region of the Philippines, backed by the Decision Formula system.</p>
        </div>
        <span class="cd-badge">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-5"/></svg>
          {{ count($crops) }} Crops
        </span>
      </div>
    </div>

    {{-- ═══ 3. Featured crops section ═══ --}}
    @if(!empty($featuredCrops))
    <div class="cd-featured-section">
      <div class="cd-featured-header">
        <h2>Most Planted &amp; Quoted</h2>
        <p>Crops most frequently used in cost quotations and harvest tracking in Bulan, Sorsogon</p>
      </div>
      <div class="cd-featured-grid">
        @foreach($crops as $crop)
          @if(!($crop['featured'] ?? false))
            @continue
          @endif
          @php
              $category = $crop['category'] ?? 'vegetable';
              $imgSrc = $crop['image'] ? "images/{$crop['image']}" : $fallbackImage;
              $label = $categoryLabels[$category] ?? ucfirst($category);
              $bg = $tagBg[$category] ?? '#f1f5f9';
              $fg = $tagFg[$category] ?? '#475569';
              $borderColor = $fg . '30';
              $cropColor = $getCropColor($crop['name']);
          @endphp
          <div class="cd-featured-card">
            <div class="cd-featured-top">
              <div class="cd-featured-icon" style="background:{{ $cropColor }}; border-color: rgba(0,0,0,0.12);">
                <img src="{{ asset($imgSrc) }}" alt="{{ $crop['name'] }}">
              </div>
              <div class="cd-featured-meta">
                <div class="cd-featured-name">{{ $crop['name'] }}</div>
                <span class="cd-featured-tag" style="background:{{ $bg }};color:{{ $fg }};border-color:{{ $borderColor }};">{{ $label }}</span>
              </div>
            </div>
            <div class="cd-featured-body">
              <div class="cd-featured-snippets">
                <div class="cd-featured-snippet">
                  <span class="cd-featured-snippet-label">Days to Harvest</span>
                  <span class="cd-featured-snippet-value" style="color:#94a3b8;">—</span>
                </div>
                <div class="cd-featured-snippet">
                  <span class="cd-featured-snippet-label">Best Season</span>
                  <span class="cd-featured-snippet-value" style="color:#94a3b8;">—</span>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- ═══ 4. Search + Filter controls ═══ --}}
    <div class="cd-controls">
      <div class="cd-search-wrap">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input id="cdSearchInput" type="text" placeholder="Search crops..." class="cd-search" />
      </div>
    </div>

    <div id="cdCategoryFilters" class="cd-filters">
      <button class="cd-filter-btn active" data-category="all">All Crops</button>
      <button class="cd-filter-btn" data-category="grain">Grains</button>
      <button class="cd-filter-btn" data-category="vegetable">Vegetables</button>
      <button class="cd-filter-btn" data-category="fruit">Fruits</button>
      <button class="cd-filter-btn" data-category="root">Root Crops</button>
      <button class="cd-filter-btn" data-category="herb">Herbs &amp; Spices</button>
      <button class="cd-filter-btn" data-category="legume">Legumes</button>
      <button class="cd-filter-btn" data-category="fiber">Fiber &amp; Industrial</button>
    </div>

    {{-- ═══ 5. Full Crop Grid ═══ --}}
    <div id="cdCropGrid" class="cd-grid">
      @foreach($crops as $crop)
        @php
            $category = $crop['category'] ?? 'vegetable';
            $imgSrc = $crop['image'] ? "images/{$crop['image']}" : $fallbackImage;
            $label = $categoryLabels[$category] ?? ucfirst($category);
            $bg = $tagBg[$category] ?? '#f1f5f9';
            $fg = $tagFg[$category] ?? '#475569';
            $borderColor = $fg . '30';
            $cropColor = $getCropColor($crop['name']);
        @endphp
        <div class="cd-card"
             data-category="{{ $category }}"
             data-name="{{ strtolower($crop['name']) }}">
          <div class="cd-card-top">
            <div class="cd-card-icon" style="background:{{ $cropColor }}; border-color: rgba(0,0,0,0.12);">
              <img src="{{ asset($imgSrc) }}" alt="{{ $crop['name'] }}">
            </div>
            <div class="cd-card-meta">
              <div class="cd-card-left">
                <div class="cd-card-name">{{ $crop['name'] }}</div>
                <span class="cd-card-tag" style="background:{{ $bg }};color:{{ $fg }};border-color:{{ $borderColor }};">{{ $label }}</span>
              </div>
              <svg class="cd-card-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
          </div>
          <div class="cd-card-info">
            <div class="cd-card-info-inner">
              <div class="cd-info-grid">
                <div class="cd-info-item">
                  <div class="cd-info-label">Planting Method</div>
                  <div class="cd-info-value">—</div>
                </div>
                <div class="cd-info-item">
                  <div class="cd-info-label">Days to Harvest</div>
                  <div class="cd-info-value">—</div>
                </div>
                <div class="cd-info-item">
                  <div class="cd-info-label">Best Season</div>
                  <div class="cd-info-value">—</div>
                </div>
                <div class="cd-info-item">
                  <div class="cd-info-label">Soil Type</div>
                  <div class="cd-info-value">—</div>
                </div>
                <div class="cd-info-item">
                  <div class="cd-info-label">Water Needs</div>
                  <div class="cd-info-value">—</div>
                </div>
              </div>
              <div class="cd-info-desc">
                <div class="cd-info-label">Description</div>
                <div class="cd-info-value">—</div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- ═══ 6. Empty state ═══ --}}
    <div id="cdEmptyState" class="cd-empty">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/>
      </svg>
      <h3>No crops found</h3>
      <p>Try adjusting your search or filter criteria.</p>
    </div>

  </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('cdSearchInput');
    var categoryBtns = document.querySelectorAll('#cdCategoryFilters .cd-filter-btn');
    var cropCards = document.querySelectorAll('#cdCropGrid .cd-card');
    var emptyState = document.getElementById('cdEmptyState');
    var activeCategory = 'all';

    function applyFilters() {
        var query = searchInput.value.toLowerCase().trim();
        var visible = 0;

        cropCards.forEach(function(card) {
            var name = card.getAttribute('data-name');
            var category = card.getAttribute('data-category');
            var matchesSearch = !query || name.indexOf(query) !== -1;
            var matchesCategory = activeCategory === 'all' || category === activeCategory;
            var show = matchesSearch && matchesCategory;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        emptyState.style.display = visible > 0 ? 'none' : 'block';
    }

    searchInput.addEventListener('input', applyFilters);

    categoryBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            activeCategory = this.getAttribute('data-category');
            applyFilters();
        });
    });

    cropCards.forEach(function(card) {
        card.addEventListener('click', function() {
            var wasExpanded = this.classList.contains('expanded');
            cropCards.forEach(function(c) { c.classList.remove('expanded'); });
            if (!wasExpanded) {
                this.classList.add('expanded');
            }
        });
    });
})();
</script>

@endsection
