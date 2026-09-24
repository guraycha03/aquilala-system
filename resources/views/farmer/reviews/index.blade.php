@extends('layouts.sidebar')

@section('title', 'Top Rated Products')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'browse',  'label' => 'Browse Products',    'url' => route('farmer.products.browse')],
      ['key' => 'reviews', 'label' => 'Top Rated Products', 'url' => route('farmer.reviews.index')],
      ['key' => 'stores',  'label' => 'Stores Directory',   'url' => route('farmer.stores.index')],
    ],
    'activeTab' => 'reviews',
  ])
<style>
  @media (min-width: 769px) {
    .main-content { padding-top: {{ ((string) optional(auth()->user())->sub_pages_style === 'top') ? '64px' : '32px' }}; }
  }
  .page-header { margin-bottom: 20px; }
  .page-title {
    font-size: 26px;
    font-weight: 800;
    color: #143d23;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
  }
  .page-subtitle { color: #526356; font-size: 15px; line-height: 1.5; }

  /* Stat strip */
  .stats-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
  .stat-chip {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }
  .stat-chip .stat-num { font-size: 20px; font-weight: 800; color: #143d23; }
  .stat-chip .stat-label { font-size: 12px; color: #64748b; font-weight: 600; }
  .stat-chip svg { width: 20px; height: 20px; stroke: #16a34a; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* Category filter chips */
  .filter-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
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
  .filter-chip.selected { background: #143d23; border-color: #143d23; color: #ffffff; }

  .results-info { font-size: 13px; color: #94a3b8; margin-bottom: 16px; }
  .results-info strong { color: #475569; font-weight: 700; }

  /* Star rating renderer */
  .stars { display: inline-flex; align-items: center; gap: 2px; }
  .stars svg { width: 16px; height: 16px; fill: currentColor; stroke: none; }
  .stars .star-empty { fill: #e2e8f0; }
  .stars .star-filled { fill: #f59e0b; }
  .stars.sm svg { width: 13px; height: 13px; }

  /* Product grid */
  .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 900px)  { .product-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 640px)  { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }

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
  .product-card.hidden { display: none; }
  .card-body { padding: 16px; }
  .card-rank-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
  .rank-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10.5px; font-weight: 700;
    background: #143d23; color: #fff;
    padding: 3px 8px; border-radius: 8px;
    white-space: nowrap;
  }
  .rank-badge svg { width: 12px; height: 12px; fill: #f59e0b; stroke: none; }
  .category-pill {
    font-size: 10px; font-weight: 700;
    background: #f1f5f9; color: #475569;
    padding: 3px 8px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: 0.03em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 55%;
    margin-left: 6px;
  }
  .rating-block { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
  .rating-num { font-size: 18px; font-weight: 800; color: #143d23; }
  .rating-count { font-size: 11px; color: #64748b; white-space: nowrap; }
  .product-title { font-size: 13.5px; font-weight: 700; color: #143d23; margin-bottom: 4px; line-height: 1.35; }
  .store-name { font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 5px; }
  .store-name svg { width: 12px; height: 12px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

  .card-footer {
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .price-tag { font-size: 15px; font-weight: 800; color: #143d23; }
  .review-link { font-size: 12px; font-weight: 700; color: #16a34a; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
  .review-link:hover { text-decoration: underline; }

  /* Empty state */
  .empty-state { text-align: center; padding: 60px 20px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 16px; }
  .empty-icon {
    width: 56px; height: 56px;
    background: #f1f5f9; color: #94a3b8;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
  }
  .empty-state h3 { font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px; }
  .empty-state p { font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto 18px; }
  .btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: #143d23; color: #fff;
    padding: 10px 20px; border-radius: 10px;
    font-size: 14px; font-weight: 700; text-decoration: none;
    transition: background 0.2s;
  }
  .btn-primary:hover { background: #1a5a30; }

  @media (max-width: 640px) {
    .card-body { padding: 14px; }
    .card-footer { padding: 12px 14px; }
    .product-title { font-size: 13px; }
    .rating-num { font-size: 18px; }
    .rating-count { font-size: 11px; }
  }
</style>

<x-breadcrumb :items="[['label' => 'Top Rated Products']]"/>

<div class="page-header">
  <h1 class="page-title">Top Rated Products</h1>
  <p class="page-subtitle">See which farm supplies other farmers rate highest, based on real farmer reviews.</p>
</div>

<div class="stats-row">
  <div class="stat-chip">
    <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    <div>
      <div class="stat-num">{{ $products->count() }}</div>
      <div class="stat-label">Rated Supplies</div>
    </div>
  </div>
  <div class="stat-chip">
    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <div>
      <div class="stat-num">{{ $totalReviews }}</div>
      <div class="stat-label">Farmer Reviews</div>
    </div>
  </div>
</div>

<div class="filter-row" id="categoryFilters">
  <button type="button" class="filter-chip selected" data-category="">All Categories</button>
  @foreach($categoryNames as $catName)
    <button type="button" class="filter-chip" data-category="{{ $catName }}">{{ $catName }}</button>
  @endforeach
</div>

<div id="resultsInfo" class="results-info"></div>

@if($products->count() === 0)
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <h3>No Reviews Yet</h3>
    <p>No farmer reviews have been submitted yet. Browse the supplies catalog and be the first to share your experience with a product.</p>
    <a href="{{ route('farmer.products.browse') }}" class="btn-primary">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Browse Supplies
    </a>
  </div>
@else
  <div class="product-grid" id="productGrid">
    @foreach($products as $index => $product)
      @php
        $cat = $product->getRelation('category');
        $group = $cat ? ($cat->parent_id ? ($cat->parent->name ?? $cat->name) : $cat->name) : ($product->getOriginal('category') ?? 'General Supply');
      @endphp
      <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}"
         data-category="{{ $group }}"
         data-name="{{ strtolower($product->name) }}">
        <x-slot name="body">
          <div class="card-rank-row">
            @if($index < 3)
              <span class="rank-badge">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Top {{ $index + 1 }}
              </span>
            @else
              <span class="rank-badge" style="background: #475569;">#{{ $index + 1 }} Most Rated</span>
            @endif
          </div>

          <div class="rating-block">
            <span class="rating-num">{{ number_format($product->avg_rating, 1) }}</span>
            <span class="stars">
              @for($i = 1; $i <= 5; $i++)
                <svg class="{{ $i <= (int) round($product->avg_rating) ? 'star-filled' : 'star-empty' }}" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              @endfor
            </span>
            <span class="rating-count">{{ number_format($product->avg_rating, 1) }} ({{ $product->reviews_count }} {{ $product->reviews_count === 1 ? 'review' : 'reviews' }})</span>
          </div>

          <div class="store-name">
            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ $product->storeOwner?->name ?? 'Unknown Merchant' }}
          </div>
        </x-slot>

        <x-slot name="footer">
          <span class="review-link">
            See Reviews
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </span>
        </x-slot>
      </x-product-card>
    @endforeach
  </div>

  <div id="filterEmpty" class="empty-state" style="display: none;">
    <div class="empty-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <h3>No Rated Supplies Yet</h3>
    <p>No rated supplies in the <strong id="filterEmptyCategory" style="color: #143d23;">category</strong> yet. Browse the supplies catalog and be the first to share your experience.</p>
    <a href="{{ route('farmer.products.browse') }}" class="btn-primary">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Browse Supplies
    </a>
  </div>
@endif

<script>
(function() {
  const chips = document.querySelectorAll('#categoryFilters .filter-chip');
  const cards = document.querySelectorAll('#productGrid .product-card');
  const resultsInfo = document.getElementById('resultsInfo');
  const filterEmpty = document.getElementById('filterEmpty');
  const filterEmptyCategory = document.getElementById('filterEmptyCategory');
  let activeCategory = '';

  // Render star row for a rating out of 5
  function starRow(rating) {
    let out = '<span class="stars sm">';
    for (let i = 1; i <= 5; i++) {
      const cls = i <= Math.round(rating) ? 'star-filled' : 'star-empty';
      out += '<svg class="' + cls + '" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    }
    return out + '</span>';
  }

  function applyFilters() {
    let visible = 0;
    cards.forEach(function(card) {
      const matches = !activeCategory || card.dataset.category === activeCategory;
      card.classList.toggle('hidden', !matches);
      if (matches) visible++;
    });

    const showEmpty = activeCategory && visible === 0;

    if (filterEmpty) {
      filterEmpty.style.display = showEmpty ? 'block' : 'none';
      if (showEmpty && filterEmptyCategory) {
        filterEmptyCategory.textContent = '\u201C' + activeCategory + '\u201D';
      }
    }

    if (activeCategory && cards.length > 0 && visible > 0) {
      resultsInfo.style.display = 'block';
      resultsInfo.innerHTML = 'Showing <strong>' + visible + '</strong> of ' + cards.length + ' rated supplies';
    } else {
      resultsInfo.style.display = 'none';
    }
  }

  chips.forEach(function(chip) {
    chip.addEventListener('click', function() {
      chips.forEach(function(c) { c.classList.remove('selected'); });
      chip.classList.add('selected');
      activeCategory = chip.dataset.category;
      applyFilters();
    });
  });

  applyFilters();
})();
</script>
@endsection