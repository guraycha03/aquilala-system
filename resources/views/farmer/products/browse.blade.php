@extends('layouts.sidebar')

@section('title', 'Browse Supplies')

@section('content')
<style>
  *, *::before, *::after {
    outline: none !important;
  }
  /* PAGE-SPECIFIC: raise the breadcrumb + title + subtitle block higher.
     Reduces the layout's default top padding (64px) to 32px on desktop only.
     Mobile keeps 88px clearance under the fixed mobile header.
     To reuse on another page, copy this rule into that page's <style> block. */
  @media (min-width: 769px) {
    .main-content { padding-top: 32px; }
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

  /* Product Cards
     The card's shape, photo field, brand, name, divider, unit caption and
     price all come from components/product-card.blade.php, which is the
     single source of truth for every catalog surface. This page used to
     restate them with its own colours (a slate card with a teal chip),
     which is why the farmer's cards never matched the store's. Only the
     grid and the empty state below are specific to this page. */

  /* Empty State. Visibility is driven by the shared filter engine through the
     [data-cf-no-products] / [data-cf-no-results] hooks, which set an inline
     display value, so no .visible class is needed. */
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    display: none;
  }
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

  /* "Clear all filters" in the no-results state. */
  .see-more-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 16px;
    padding: 11px;
    background: #f8fafc;
    color: #143d23;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
  }
  .see-more-btn:hover { background: #f0fdf4; border-color: #bbf7d0; }

  @media (max-width: 640px) {
    .product-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
  }
</style>

<x-breadcrumb :items="[['label' => 'Browse Products']]"/>

<div class="page-header">
  <h1 class="page-title">Browse Supplies</h1>
  <p class="page-subtitle">Find high-quality seeds, fertilizers, and equipment from verified local stores.</p>
</div>

{{-- Shared catalog filter bar: search, category, season, availability, price
     range and sorting, identical on every catalog surface. Reads and writes
     ?search=/?season=/?category=/?min=/?max=/?sort= so a filtered catalog can
     be linked. --}}
<x-catalog-filters
  uid="browse"
  target="#productGrid"
  :categories="$categories"
  :price-min="$priceMin"
  :price-max="$priceMax"
  search-placeholder="Search supplies, SKU, brand..."
  noun="supplies" />

<!-- Supplies Grid -->
<div class="product-grid" id="productGrid">
  @forelse($products as $product)
    <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}" />
  @empty
    <div class="empty-state" data-cf-no-products>
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Supplies Found</h3>
      <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto;">There are no supplies in the catalog yet. Check back later.</p>
    </div>
  @endforelse
</div>

<!-- Shown when filters are applied and nothing matches. The filter engine
     toggles this via [data-cf-no-results]. -->
<div class="empty-state" id="emptyStateFiltered" data-cf-no-results style="grid-column: 1 / -1; display: none;">
  <div class="empty-icon">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </div>
  <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Matching Supplies</h3>
  <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto 18px;">No supplies match your current filters. Try widening the price range or clearing a filter.</p>
  <button type="button" class="see-more-btn" data-cf-reset style="display: inline-flex;">Clear all filters</button>
</div>
@endsection
