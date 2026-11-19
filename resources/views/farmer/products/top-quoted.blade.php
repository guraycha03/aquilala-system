@extends('layouts.sidebar')

@section('title', 'Top Quoted Products')

@section('content')
<style>
  @media (min-width: 769px) {
    .main-content { padding-top: 32px; }
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
  .stat-chip svg { width: 20px; height: 20px; stroke: #4A7A54; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* Product grid */
  .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 900px)  { .product-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 640px)  { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }

  /* The card's shape, photo field, brand, name, divider and price come
     from components/product-card.blade.php. Only this page's own content
     — the rank badge and the store name it injects into the card body —
     is styled here. */
  /* The shared filter bar hides filtered-out cards itself, via .cf-hidden. */
  .card-rank-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
  .rank-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10.5px; font-weight: 700;
    background: #143d23; color: #fff;
    padding: 3px 8px; border-radius: 8px;
    white-space: nowrap;
  }
  .rank-badge svg { width: 12px; height: 12px; fill: #f59e0b; stroke: none; }
  .quote-count {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700;
    background: #edf1e9; color: #648268;
    border: 1px solid #bbf7d0;
    padding: 3px 8px; border-radius: 8px;
    white-space: nowrap;
  }
  .quote-count svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .store-name { font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 5px; }
  .store-name svg { width: 12px; height: 12px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

  .quote-link { font-size: 12px; font-weight: 700; color: #4A7A54; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
  .quote-link:hover { text-decoration: underline; }

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
</style>

<x-breadcrumb :items="[['label' => 'Top Quoted Products']]"/>

<div class="page-header">
  <h1 class="page-title">Top Quoted Products</h1>
  <p class="page-subtitle">Farm supplies other farmers request most in cost quotations across the whole system — ranked by real purchase intent, not ratings.</p>
</div>

{{-- Shared catalog filter bar: search, category, season, availability, price
     range and sorting, identical on every catalog surface. Reads and writes
     ?search=/?season=/?category=/?min=/?max=/?sort= so a filtered list can be
     linked. --}}
<x-catalog-filters
  uid="top-quoted"
  target="#productGrid"
  :categories="$categories"
  :price-min="$priceMin"
  :price-max="$priceMax"
  search-placeholder="Search in-demand supplies, SKU, brand..."
  noun="in-demand supplies" />

<div class="product-grid" id="productGrid">
  @forelse($products as $index => $product)
    <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}">
      <x-slot name="body">
        <div class="card-rank-row">
          @if($index < 3)
            <span class="rank-badge">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Top {{ $index + 1 }}
            </span>
          @else
            <span class="rank-badge" style="background: #475569;">#{{ $index + 1 }} Most Quoted</span>
          @endif
          <span class="quote-count">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            {{ $product->quotationItems_count ?? 0 }} {{ Str::plural('quote', $product->quotationItems_count ?? 0) }}
          </span>
        </div>

        <div class="store-name">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          {{ $product->storeOwner?->name ?? 'Unknown Merchant' }}
        </div>
      </x-slot>

      <x-slot name="footer">
        <span class="quote-link">
          Generate Quotation
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
      </x-slot>
    </x-product-card>
  @empty
    <div class="empty-state" data-cf-no-products>
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <h3>No Quotations Yet</h3>
      <p>Products appear here once farmers include them in cost quotations. Generate a quotation from the supplies catalog to get started.</p>
      <a href="{{ route('farmer.products.browse') }}" class="btn-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Browse Supplies
      </a>
    </div>
  @endforelse
</div>

{{-- Shown when filters are applied and nothing matches. The filter engine
     toggles this via [data-cf-no-results]. --}}
<div class="empty-state" id="filterEmpty" data-cf-no-results style="display: none;">
  <div class="empty-icon">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
  </div>
  <h3>No Matching Supplies</h3>
  <p>No in-demand supplies match your current filters. Try widening the price range or clearing a filter.</p>
  <a href="{{ route('farmer.products.browse') }}" class="btn-primary">
    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    Browse Supplies
  </a>
</div>
@endsection
