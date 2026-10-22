@extends('layouts.sidebar')

@section('title', 'Recommended Supplies')

@section('content')
@php
  $now = now();
  $formattedDate = $now->format('l, F j, Y');

  $forYourCrops = $products->count();
  $seasonalPicks = $products->where('recommended_season', 'wet')->count()
    + $products->where('recommended_season', 'both')->count();
  $topQuoted = $products->count();
@endphp

<style>
  /* ── Page shell ───────────────────────────────────────────────
     The same quiet ground as the rest of the farmer's pages: a
     title, a line of context, then the things themselves. Every
     colour on this page is one of three — the surface, the line,
     the ink — so the page reads as a list of supplies and not as
     a poster. */
  .page-header { margin-bottom: 22px; }
  .page-title {
    font-size: 26px; font-weight: 800; color: #143d23;
    letter-spacing: -0.02em; margin: 0 0 5px 0;
  }
  .page-subtitle { color: #526356; font-size: 15px; line-height: 1.55; margin: 0; max-width: 62ch; }

  /* Context line: season, place, date. One quiet row. */
  .rq-facts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
  .rq-fact {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12.5px; font-weight: 600; color: #5F6F62;
    background: #F3F5F0; border: 1px solid #E4E9E0;
    padding: 6px 12px; border-radius: 10px;
  }
  .rq-fact svg { width: 14px; height: 14px; flex-shrink: 0; }

  /* ── Ways in ─────────────────────────────────────────────────
     Four doors to the same catalog. They share one tile and one
     ink: the words tell them apart, not the colour. */
  .rq-ways {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; margin-bottom: 24px;
  }
  .rq-way {
    display: flex; flex-direction: column; gap: 10px;
    background: #ffffff; border: 1px solid #E9EDE8;
    border-radius: 14px; padding: 20px;
    text-decoration: none; color: inherit;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .rq-way:hover {
    border-color: #C8D5C5;
    box-shadow: 0 6px 18px rgba(20, 61, 35, .06);
    transform: translateY(-2px);
  }
  .rq-way__ic {
    width: 38px; height: 38px; border-radius: 11px;
    background: #EDF1E9; color: #648268;
    display: flex; align-items: center; justify-content: center;
  }
  .rq-way__ic svg { width: 19px; height: 19px; }
  .rq-way h3 { font-size: 14.5px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .rq-way p { font-size: 12.5px; color: #6b7c6e; margin: 0; line-height: 1.55; }
  .rq-way__count {
    margin-top: auto; padding-top: 6px;
    font-size: 12px; font-weight: 700; color: #4b5c50;
    display: flex; align-items: center; gap: 4px;
  }
  .rq-way__count svg { width: 13px; height: 13px; }

  /* ── The assistant ───────────────────────────────────────────
     A card, not a banner: the avatar, one sentence, one button. */
  .rq-ai {
    display: flex; align-items: center; gap: 16px;
    background: #ffffff; border: 1px solid #E9EDE8;
    border-radius: 14px; padding: 18px 20px; margin-bottom: 24px;
  }
  .rq-ai__avatar {
    width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
    overflow: hidden; background: #EDF1E9;
  }
  .rq-ai__avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .rq-ai__text { flex: 1; min-width: 0; }
  .rq-ai__text h2 { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0 0 3px 0; }
  .rq-ai__text p { font-size: 13px; color: #6b7c6e; margin: 0; line-height: 1.55; }
  .rq-ai__btn {
    display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0;
    background: #F3F5F0; color: #143d23; border: 1px solid #D5DDD1;
    border-radius: 11px; padding: 10px 16px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    text-decoration: none; white-space: nowrap;
    transition: background .15s ease, border-color .15s ease;
  }
  .rq-ai__btn:hover { background: #E9EEE5; border-color: #C4D0C0; }
  .rq-ai__btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* ── The grid ─────────────────────────────────────────────────
     The cards themselves come from the shared product card; only
     the columns are this page's business. */
  .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 900px)  { .product-grid { grid-template-columns: repeat(2, 1fr); } }

  /* The store a product comes from, and what is left in stock. */
  .rq-store {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: #6b7c6e;
  }
  .rq-store svg { width: 13px; height: 13px; flex-shrink: 0; }
  .rq-stock { font-size: 12px; font-weight: 600; color: #4b5c50; }
  .rq-stock.is-low { color: #8A7C3E; }
  .rq-stock.is-out { color: #a34a4a; }

  /* ── Empty states ───────────────────────────────────────────── */
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border: 1px dashed #E0E5DB;
    border-radius: 16px;
  }
  .empty-icon {
    width: 56px; height: 56px;
    background: #F1F4F0; color: #879a8c; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
  }
  .empty-state h3 { font-size: 17px; font-weight: 700; color: #1e2922; margin: 0 0 6px 0; }
  .empty-state p { font-size: 14px; color: #647263; max-width: 420px; margin: 0 auto; line-height: 1.6; }

  /* ── Phone ──────────────────────────────────────────────────── */
  @media (max-width: 900px) {
    .rq-ways { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  }
  @media (max-width: 640px) {
    .page-title { font-size: 21px; }
    .rq-ways { grid-template-columns: 1fr; }
    .rq-way { padding: 16px; gap: 8px; }
    .rq-way p { font-size: 12px; }
    .rq-ai { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px; }
    .rq-ai__btn { width: 100%; justify-content: center; }
    .product-grid { gap: 12px; }
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'Farm Supplies', 'url' => route('farmer.products.browse')],
  ['label' => 'Recommended Supplies'],
]"/>

<div class="page-header">
  <h1 class="page-title">Recommended Supplies</h1>
  <p class="page-subtitle">
    Inputs picked for your crops and the current season — from the stores around Bulan.
  </p>
  <div class="rq-facts">
    <span class="rq-fact">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
      Wet Season
    </span>
    <span class="rq-fact">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Bulan, Sorsogon
    </span>
    <span class="rq-fact">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      {{ $formattedDate }}
    </span>
  </div>
</div>

{{-- Four doors into the same catalog. One tile, one ink: the words
     tell them apart. --}}
<div class="rq-ways">
  <a href="#recommended-products" class="rq-way">
    <span class="rq-way__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
    </span>
    <h3>For Your Crops</h3>
    <p>Products matched to your registered crop types and farming profile.</p>
    <span class="rq-way__count">
      {{ $forYourCrops }} {{ Str::plural('product', $forYourCrops) }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </a>

  <a href="{{ route('farmer.products.browse', ['season' => 'wet']) }}" class="rq-way">
    <span class="rq-way__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
    </span>
    <h3>Seasonal Picks</h3>
    <p>Products ideal for the current wet season and local climate.</p>
    <span class="rq-way__count">
      {{ $seasonalPicks }} {{ Str::plural('product', $seasonalPicks) }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </a>

  <a href="{{ route('farmer.top-quoted.index') }}" class="rq-way">
    <span class="rq-way__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </span>
    <h3>Top Quoted</h3>
    <p>Products other farmers request most in their cost quotations.</p>
    <span class="rq-way__count">
      {{ $topQuoted }} {{ Str::plural('product', $topQuoted) }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </a>

  <a href="{{ route('farmer.stores.index') }}" class="rq-way">
    <span class="rq-way__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/></svg>
    </span>
    <h3>Partner Stores</h3>
    <p>Local farm supply stores in your area offering quality products.</p>
    <span class="rq-way__count">
      {{ $storeCount }} {{ Str::plural('store', $storeCount) }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </a>
</div>

{{-- The assistant: one card, one sentence, one button. --}}
<div class="rq-ai">
  <span class="rq-ai__avatar">
    <img src="{{ asset('images/ai-avatar.png') }}" alt="AI Assistant">
  </span>
  <div class="rq-ai__text">
    <h2>Ask the farm assistant</h2>
    <p>Tell it your crop, your land area and the weather you expect — it will work out which inputs you need and what they cost.</p>
  </div>
  <a href="{{ route('farmer.ai-chat.index') }}" class="rq-ai__btn">
    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
    Ask the assistant
  </a>
</div>

<div class="sr-section-head" id="recommended-products" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
  <div>
    <h2 style="font-size: 18px; font-weight: 700; color: #1a2b1e; margin: 0;">Recommended products</h2>
    <p style="font-size: 13px; color: #879a8c; margin: 4px 0 0 0;">Curated for your crops, your season and your land.</p>
  </div>
  <a href="{{ route('farmer.products.browse') }}" style="font-size: 13px; font-weight: 700; color: #143d23; text-decoration: none; padding: 9px 16px; border-radius: 10px; background: #F3F5F0; border: 1px solid #E0E5DB; display: inline-flex; align-items: center; gap: 6px;">
    Browse all supplies
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
  </a>
</div>

{{-- Shared catalog filter bar: search, category, season, availability, price
     range and sorting, identical on every catalog surface. Reads and writes
     ?search=/?season=/?category=/?min=/?max=/?sort= so a filtered list can be
     linked. The cards below carry the same data-cf-* facets the shared
     <x-product-card> emits, so the bar filters them the same way. --}}
<x-catalog-filters
  uid="recommendations"
  target="#srProductGrid"
  :categories="$categories"
  :price-min="$priceMin"
  :price-max="$priceMax"
  search-placeholder="Search recommended supplies, SKU, brand..."
  noun="recommendations" />

<div class="product-grid" id="srProductGrid">
  @forelse($products as $product)
    <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}">
      <x-slot name="body">
        @if($product->storeOwner)
          <div class="rq-store">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/></svg>
            {{ $product->storeOwner->store_name ?? $product->storeOwner->name }}
          </div>
        @endif
        <span class="rq-stock {{ (int) $product->stock_quantity <= 0 ? 'is-out' : ($product->is_low_stock ? 'is-low' : '') }}">
          {{ (int) $product->stock_quantity }} {{ $product->stock_unit ?? 'pcs' }} in stock
        </span>
      </x-slot>
    </x-product-card>
  @empty
    <div class="empty-state" data-cf-no-products>
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3>No supplies yet</h3>
      <p>There are no active product listings from the network stores. Check back soon as new products are added regularly.</p>
    </div>
  @endforelse
</div>

{{-- Shown when filters are applied and nothing matches. The filter engine
     toggles this via [data-cf-no-results]. --}}
<div class="empty-state" data-cf-no-results style="display: none;">
  <div class="empty-icon">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </div>
  <h3>No matches</h3>
  <p>No recommendations match your current filters. Try widening the price range or clearing a filter.</p>
</div>
@endsection
