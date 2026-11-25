@extends('layouts.sidebar')

@section('title', 'Recommended Supplies')

@section('content')
@php
  $now = now();
  $formattedDate = $now->format('l, F j, Y');

  // The cards stay plain product tiles. The explanation and the extended
  // product preview live in this payload, read only by the temporary drawer,
  // so clicking a card never needs a page load or a URL change.
  $rsrPayload = $recommendations->mapWithKeys(function (array $item) {
      $p = $item['product'];
      $image = $p->mainImage?->card_url
          ?? ($p->images?->first()?->card_url ?? null);

      return [(int) $p->id => [
          'product' => [
              'id' => (int) $p->id,
              'name' => (string) $p->name,
              'brand' => (string) ($p->brand ?? ''),
              'sku' => (string) ($p->sku ?? ''),
              'category' => (string) $p->category_display_name,
              'product_type' => (string) ($p->product_type ?? ''),
              'season' => (string) ($p->recommended_season ?? ''),
              'price' => '₱'.number_format((float) $p->price, 2),
              'stock' => (int) $p->stock_quantity,
              'stock_unit' => (string) ($p->stock_unit ?? 'pcs'),
              'store' => (string) ($p->storeOwner?->store_name ?? $p->storeOwner?->name ?? 'Partner store'),
              'image' => $image,
              'description' => (string) ($p->description ?? ''),
              'package_size' => $p->package_size,
              'package_unit' => (string) ($p->package_unit ?? ''),
              'price_unit' => (string) ($p->price_unit ?? ''),
              'applications' => ($p->applications ?? collect())->map(fn ($app) => [
                  'crop' => (string) ($app->crop?->name ?? ''),
                  'rate' => trim(
                      (string) ($app->application_rate_min ?? '')
                      .((int) ($app->application_rate_max ?? 0) > (int) ($app->application_rate_min ?? 0)
                          ? '–'.$app->application_rate_max : '')
                      .' '.(string) ($app->application_rate_unit ?? '')
                  ),
                  'method' => (string) ($app->application_method ?? ''),
                  'timing' => (string) ($app->application_timing ?? ''),
                  'notes' => (string) ($app->notes ?? ''),
              ])->values()->all(),
              'formulas' => ($p->decisionFormulas ?? collect())->map(fn ($formula) => [
                  'crop' => (string) ($formula->crop_type ?? ''),
                  'season' => (string) ($formula->season ?? ''),
                  'stage' => (string) $formula->stageLabel(),
                  'rate' => trim((string) ($formula->rate_per_hectare ?? '').' '.(string) ($formula->unit ?? '')),
              ])->values()->all(),
          ],
          'reason' => [
              'explanation' => (string) $item['explanation'],
              'reason_codes' => $item['reason_codes'],
              'reason_data' => $item['reason_data'],
          ],
      ]];
  })->all();
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

  /* ── Rule explanation entry point ────────────────────────────
     A quiet button that opens the same temporary drawer a card opens. */
  .rq-how {
    display: flex; align-items: center; gap: 16px;
    width: 100%; text-align: left; font-family: inherit;
    background: #ffffff; border: 1px solid #E9EDE8;
    border-radius: 14px; padding: 18px 20px; margin-bottom: 24px;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
  }
  .rq-how:hover { border-color: #C8D5C5; background: #fbfcfa; }
  .rq-how__text { flex: 1; min-width: 0; }
  .rq-how__text h2 { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0 0 3px 0; }
  .rq-how__text p { font-size: 13px; color: #6b7c6e; margin: 0; line-height: 1.55; }
  .rq-how__cta {
    flex-shrink: 0; font-size: 12.5px; font-weight: 700; color: #143d23;
    display: inline-flex; align-items: center; gap: 6px;
  }

  /* ── The grid ─────────────────────────────────────────────────
     Normal product tiles. The rule explanation and extended preview
     are deliberately kept out of the cards. */
  .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 900px)  { .product-grid { grid-template-columns: repeat(2, 1fr); } }

  /* ── Temporary sub-page drawer ───────────────────────────────
     Same treatment as the crop directory reference and the report
     guide: a right-side sheet on desktop, a full sub-page on mobile.
     It never navigates, so the grid, filters and scroll stay untouched. */
  :root { --rsr-header-offset: 60px; }
  .rsr-drawer {
    position: fixed; top: var(--rsr-header-offset); right: 0; bottom: 0; z-index: 151;
    width: 100%; max-width: 520px;
    display: flex; flex-direction: column;
    background: #ffffff; border-left: 1px solid #e4e9e0;
    box-shadow: -12px 0 36px rgba(30, 41, 35, .14);
    transform: translateX(102%);
    transition: transform .25s cubic-bezier(.16, 1, .3, 1);
  }
  .rsr-drawer.open { transform: translateX(0); }
  .rsr-drawer-head {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 16px; border-bottom: 1px solid #e8ece5;
    flex-shrink: 0;
  }
  .rsr-back {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 10px; border-radius: 9px;
    border: 1px solid #d5ddd1; background: #f4f6f2;
    color: #274a30; font-size: 11.5px; font-weight: 700;
    font-family: inherit; cursor: pointer; white-space: nowrap;
  }
  .rsr-back:hover { background: #e9eee5; }
  .rsr-badge {
    font-size: 10px; font-weight: 800; letter-spacing: .04em;
    text-transform: uppercase; color: #708171;
  }
  .rsr-close {
    margin-left: auto; width: 29px; height: 29px;
    border: 1px solid transparent; border-radius: 8px;
    background: transparent; color: #708171;
    font-size: 20px; line-height: 1; cursor: pointer;
  }
  .rsr-close:hover { background: #f1f4f0; color: #2b3c30; }
  .rsr-drawer-body { flex: 1; overflow-y: auto; padding: 18px; }

  .rsr-hero { display: flex; align-items: flex-start; gap: 14px; }
  .rsr-hero img, .rsr-hero-placeholder {
    width: 88px; height: 88px; flex-shrink: 0;
    border-radius: 12px; border: 1px solid #e8e4d9;
    background: #f6f4ee; object-fit: contain; padding: 7px;
  }
  .rsr-hero-placeholder {
    display: inline-flex; align-items: center; justify-content: center;
    color: #c9c2b2;
  }
  .rsr-eyebrow {
    margin: 0 0 3px; font-size: 10px; font-weight: 800;
    letter-spacing: .05em; text-transform: uppercase; color: #6e8a72;
  }
  .rsr-drawer-title {
    margin: 0; font-size: 18px; line-height: 1.3; font-weight: 800;
    color: #1a2b1e; overflow-wrap: anywhere;
  }
  .rsr-hero-meta { margin: 5px 0 0; font-size: 12px; color: #708171; }

  .rsr-callout {
    margin-top: 18px; padding: 14px;
    border: 1px solid #d9e5d6; border-radius: 12px; background: #f1f6ef;
  }
  .rsr-callout-label {
    display: block; margin-bottom: 6px;
    font-size: 10px; font-weight: 800; letter-spacing: .05em;
    text-transform: uppercase; color: #143d23;
  }
  .rsr-callout p { margin: 0; font-size: 13px; line-height: 1.65; color: #435647; }

  .rsr-section { margin-top: 20px; }
  .rsr-section h4 {
    margin: 0 0 9px; font-size: 12px; font-weight: 800;
    letter-spacing: .03em; text-transform: uppercase; color: #4b6250;
  }
  .rsr-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
  .rsr-fact {
    padding: 10px; border: 1px solid #e8ece5;
    border-radius: 10px; background: #fbfcfa; min-width: 0;
  }
  .rsr-fact span {
    display: block; margin-bottom: 3px;
    font-size: 9.5px; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase; color: #879a8c;
  }
  .rsr-fact strong {
    display: block; font-size: 12.5px; color: #263b2b;
    overflow-wrap: anywhere;
  }
  .rsr-chip-list {
    display: flex; flex-wrap: wrap; gap: 6px;
    list-style: none; padding: 0; margin: 0;
  }
  .rsr-chip-list li {
    padding: 5px 8px; border-radius: 999px;
    background: #eef4ed; color: #3e5f46;
    font-size: 10.5px; font-weight: 700;
  }
  .rsr-desc { margin: 0; font-size: 13px; line-height: 1.65; color: #4d5c50; }
  .rsr-table { width: 100%; border-collapse: collapse; font-size: 12px; }
  .rsr-table th, .rsr-table td {
    padding: 8px; border-bottom: 1px solid #edf0ea;
    text-align: left; vertical-align: top;
  }
  .rsr-table th { width: 32%; color: #708171; font-weight: 600; }
  .rsr-empty-note { margin: 0; font-size: 12px; color: #879a8c; font-style: italic; }

  .rsr-drawer-foot {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 12px 16px; border-top: 1px solid #e8ece5;
    background: #fbfcfa; flex-shrink: 0;
  }
  .rsr-nav-btn {
    padding: 7px 11px; border-radius: 9px;
    border: 1px solid #d5ddd1; background: #ffffff;
    color: #2c4b34; font-size: 11.5px; font-weight: 700;
    font-family: inherit; cursor: pointer;
  }
  .rsr-nav-btn:disabled { opacity: .4; cursor: not-allowed; }
  .rsr-counter { font-size: 11px; color: #708171; }

  [data-theme="dark"] .rsr-drawer { background: #111827; border-left-color: #334155; }
  [data-theme="dark"] .rsr-drawer-head,
  [data-theme="dark"] .rsr-drawer-foot { border-color: #334155; background: #151f2b; }
  [data-theme="dark"] .rsr-drawer-title { color: #e6ecea; }
  [data-theme="dark"] .rsr-hero img,
  [data-theme="dark"] .rsr-hero-placeholder { background: #1b2430; border-color: #334155; }
  [data-theme="dark"] .rsr-callout { background: #16211b; border-color: #2b3b31; }
  [data-theme="dark"] .rsr-callout p,
  [data-theme="dark"] .rsr-desc { color: #b7c9bb; }
  [data-theme="dark"] .rsr-fact { background: #151f2b; border-color: #334155; }
  [data-theme="dark"] .rsr-fact strong { color: #dce9df; }
  [data-theme="dark"] .rsr-chip-list li { background: #1b2a20; color: #9cc4a3; }
  [data-theme="dark"] .rsr-nav-btn,
  [data-theme="dark"] .rsr-back { background: #1f2937; border-color: #475569; color: #d6f5e3; }

  @media (max-width: 768px) {
    .rsr-drawer {
      top: var(--rsr-header-offset);
      left: 0; right: 0; bottom: 0;
      max-width: none;
      height: calc(100vh - var(--rsr-header-offset));
      height: calc(100dvh - var(--rsr-header-offset));
      border-left: 0; border-top: 1px solid #e4e9e0;
      box-shadow: none;
      transform: translateX(100%);
    }
    .rsr-drawer.open { transform: translateX(0); }
    .rsr-hero { gap: 11px; }
    .rsr-hero img, .rsr-hero-placeholder { width: 72px; height: 72px; }
    .rsr-drawer-body { padding: 15px; }
    .rsr-facts { grid-template-columns: 1fr; }
  }

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
    .rq-how { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px; }
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
      {{ ucfirst($currentSeason) }} Season
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

  <a href="{{ route('farmer.products.browse', ['season' => $currentSeason]) }}" class="rq-way">
    <span class="rq-way__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
    </span>
    <h3>Seasonal Picks</h3>
    <p>Products ideal for the current {{ $currentSeason }} season and local climate.</p>
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

{{-- One entry point at the top; the full explanation lives in the temporary drawer. --}}
<button type="button" class="rq-how" data-rsr-open-top>
  <span class="rq-how__ic">
    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
  </span>
  <div class="rq-how__text">
    <h2>How these are chosen</h2>
    <p>Products are ranked using your crop records, quotations, planting records, harvest reports, the current season and the system’s product rules.</p>
  </div>
  <span class="rq-how__cta">
    View reasons
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </span>
</button>

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
     catalog card emits, so the bar filters them the same way. --}}
<x-catalog-filters
  uid="recommendations"
  target="#srProductGrid"
  :categories="$categories"
  :price-min="$priceMin"
  :price-max="$priceMax"
  search-placeholder="Search recommended supplies, SKU, brand..."
  noun="recommendations" />

<div class="product-grid" id="srProductGrid">
  @forelse($recommendations as $recommendation)
    <x-recommended-product-card :product="$recommendation['product']" />
  @empty
    <div class="empty-state" data-cf-no-products>
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3>No recommendations yet</h3>
      <p>Add crop, quotation, planting, or harvest records to receive rule-based recommendations.</p>
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

{{-- Product data and rule explanations live outside the cards so a card stays
     a plain product tile. The temporary drawer reads this payload on demand. --}}
<script type="application/json" id="rsrPayload">{!! json_encode($rsrPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

<aside id="rsrDrawer" class="rsr-drawer" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="rsrDrawerTitle">
  <div class="rsr-drawer-head">
    <button type="button" class="rsr-back" id="rsrBack">← Back to recommendations</button>
    <span class="rsr-badge" id="rsrBadge">Why this was recommended</span>
    <button type="button" class="rsr-close" id="rsrClose" aria-label="Close sub-page">&times;</button>
  </div>
  <div class="rsr-drawer-body" id="rsrDrawerBody">
    <div class="rsr-hero" id="rsrHero"></div>
    <section class="rsr-section" id="rsrReasonPanel"></section>
    <section class="rsr-section" id="rsrInfoPanel" hidden></section>
  </div>
  <div class="rsr-drawer-foot">
    <button type="button" class="rsr-nav-btn" id="rsrPrev">← Previous</button>
    <span class="rsr-counter" id="rsrCounter"></span>
    <button type="button" class="rsr-nav-btn" id="rsrNext">Next →</button>
  </div>
</aside>

<script>
  (function () {
    'use strict';

    var drawer = document.getElementById('rsrDrawer');
    if (!drawer || drawer.dataset.bound === '1') return;
    drawer.dataset.bound = '1';
    drawer.inert = true;

    var payloadNode = document.getElementById('rsrPayload');
    var data = {};
    try { data = JSON.parse(payloadNode ? payloadNode.textContent : '{}') || {}; } catch (e) { data = {}; }

    var cards = Array.prototype.slice.call(document.querySelectorAll('#srProductGrid [data-rsr-card]'));
    var hero = document.getElementById('rsrHero');
    var reasonPanel = document.getElementById('rsrReasonPanel');
    var infoPanel = document.getElementById('rsrInfoPanel');
    var badge = document.getElementById('rsrBadge');
    var counter = document.getElementById('rsrCounter');
    var prevBtn = document.getElementById('rsrPrev');
    var nextBtn = document.getElementById('rsrNext');
    var backBtn = document.getElementById('rsrBack');
    var closeBtn = document.getElementById('rsrClose');
    var body = drawer.querySelector('.rsr-drawer-body');

    var currentCard = null;
    var currentMode = 'reason';
    var lastTrigger = null;

    function esc(value) {
      return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function syncHeaderOffset() {
      var candidates = [
        '.aq-global-search-host',
        '.mobile-header',
        'header:not(.rsr-drawer-head)',
        '.topbar',
        '[data-topbar]'
      ];

      for (var i = 0; i < candidates.length; i++) {
        var el = document.querySelector(candidates[i]);
        if (!el) continue;
        var rect = el.getBoundingClientRect();
        if (rect.height > 0 && rect.top <= 10) {
          document.documentElement.style.setProperty('--rsr-header-offset', rect.height + 'px');
          return;
        }
      }
    }

    function visibleCards() {
      return cards.filter(function (card) { return card.style.display !== 'none'; });
    }

    function payloadFor(card) {
      return data[String(card.getAttribute('data-rsr-product-id'))] || null;
    }

    function sourceLabel(source) {
      return {
        planted: 'Your planting record',
        quotation: 'Your quotation crop',
        harvest: 'Your harvest history',
        own_quotation: 'Your own quotation',
        quotations: 'Farmer quotations'
      }[source] || 'Product rule library';
    }

    function codeLabel(code) {
      return {
        planted_crop_match: 'Your planting record',
        crop_formula_match: 'Your quotation crop',
        harvest_history_match: 'Your harvest history',
        own_quotation_match: 'Your own quotation',
        top_quoted: 'Farmer quotations',
        quotation_support: 'Quotation record',
        season_fit: 'Current season',
        application_guidance: 'Product application guide',
        stage_rule: 'Application stage',
        formula_backed: 'Product rule library'
      }[code] || null;
    }

    function renderHero(product, mode) {
      var image = product.image
        ? '<img src="' + esc(product.image) + '" alt="' + esc(product.name) + '">'
        : '<span class="rsr-hero-placeholder"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></span>';

      hero.innerHTML = image +
        '<div>' +
          '<p class="rsr-eyebrow">' + (mode === 'info' ? 'Product preview' : 'Rule-based match') + '</p>' +
          '<h2 class="rsr-drawer-title" id="rsrDrawerTitle">' + esc(product.name) + '</h2>' +
          '<p class="rsr-hero-meta">' + esc([product.brand, product.category].filter(Boolean).join(' · ')) + '</p>' +
        '</div>';
    }

    function renderReason(reason) {
      var rd = reason.reason_data || {};
      var codes = reason.reason_codes || [];
      var chips = codes.map(codeLabel).filter(Boolean).map(function (label) {
        return '<li>' + esc(label) + '</li>';
      }).join('');

      if (chips === '') chips = '<li>Rule-based match</li>';

      var crop = rd.crop_label || rd.crop || 'Not crop-specific';
      var season = rd.season_fit ? (rd.season || 'current season') : 'Not season-specific';

      reasonPanel.innerHTML =
        '<div class="rsr-callout">' +
          '<span class="rsr-callout-label">Why this was recommended</span>' +
          '<p>' + esc(reason.explanation) + '</p>' +
        '</div>' +
        '<div class="rsr-section">' +
          '<h4>What the system used</h4>' +
          '<div class="rsr-facts">' +
            '<div class="rsr-fact"><span>Matched source</span><strong>' + esc(sourceLabel(rd.source)) + '</strong></div>' +
            '<div class="rsr-fact"><span>Crop / variety</span><strong>' + esc(crop) + '</strong></div>' +
            '<div class="rsr-fact"><span>Product kind</span><strong>' + esc(rd.kind_label || 'Farm input') + '</strong></div>' +
            '<div class="rsr-fact"><span>Season fit</span><strong>' + esc(season) + '</strong></div>' +
          '</div>' +
        '</div>' +
        '<div class="rsr-section">' +
          '<h4>Rules that matched</h4>' +
          '<ul class="rsr-chip-list">' + chips + '</ul>' +
        '</div>';
    }

    function renderInfo(product) {
      var apps = product.applications || [];
      var formulas = product.formulas || [];
      var appRows = apps.length
        ? apps.map(function (app) {
            return '<tr><th>' + esc(app.crop || 'General') + '</th><td>' +
              esc([app.rate, app.method, app.timing, app.notes].filter(Boolean).join(' · ')) +
              '</td></tr>';
          }).join('')
        : '<tr><td colspan="2" class="rsr-empty-note">No application guide recorded for this product.</td></tr>';

      var formulaRows = formulas.length
        ? '<table class="rsr-table"><tbody>' + formulas.map(function (formula) {
            return '<tr><th>' + esc(formula.crop || 'General') + '</th><td>' +
              esc([formula.season, formula.stage, formula.rate].filter(Boolean).map(function (part) {
                return String(part).charAt(0).toUpperCase() + String(part).slice(1);
              }).join(' · ')) +
              '</td></tr>';
          }).join('') + '</tbody></table>'
        : '<p class="rsr-empty-note">No registered application rules for this product.</p>';

      var pack = [product.package_size, product.package_unit].filter(function (part) {
        return part !== null && part !== undefined && String(part).trim() !== '';
      }).join(' ');
      var stock = product.stock > 0
        ? product.stock + ' ' + product.stock_unit + ' in stock'
        : 'Out of stock';

      infoPanel.innerHTML =
        '<div class="rsr-section">' +
          '<h4>Product preview</h4>' +
          '<p class="rsr-desc">' + esc(product.description || 'No description provided by the store.') + '</p>' +
        '</div>' +
        '<div class="rsr-section">' +
          '<div class="rsr-facts">' +
            '<div class="rsr-fact"><span>Price</span><strong>' + esc(product.price) + '</strong></div>' +
            '<div class="rsr-fact"><span>Availability</span><strong>' + esc(stock) + '</strong></div>' +
            '<div class="rsr-fact"><span>Brand</span><strong>' + esc(product.brand || '—') + '</strong></div>' +
            '<div class="rsr-fact"><span>SKU</span><strong>' + esc(product.sku || '—') + '</strong></div>' +
            '<div class="rsr-fact"><span>Product type</span><strong>' + esc(product.product_type || product.category) + '</strong></div>' +
            '<div class="rsr-fact"><span>Season</span><strong>' + esc(product.season || 'All seasons') + '</strong></div>' +
            '<div class="rsr-fact"><span>Package</span><strong>' + esc(pack || '—') + '</strong></div>' +
            '<div class="rsr-fact"><span>Sold by</span><strong>' + esc(product.store) + '</strong></div>' +
          '</div>' +
        '</div>' +
        '<div class="rsr-section">' +
          '<h4>Application guidance</h4>' +
          '<table class="rsr-table"><tbody>' + appRows + '</tbody></table>' +
        '</div>' +
        '<div class="rsr-section">' +
          '<h4>Registered application rules</h4>' + formulaRows +
        '</div>';
    }

    function open(card, mode, trigger) {
      var payload = payloadFor(card);
      if (!payload) return;

      currentCard = card;
      currentMode = mode === 'info' ? 'info' : 'reason';
      lastTrigger = trigger || card;

      syncHeaderOffset();
      renderHero(payload.product, currentMode);

      if (currentMode === 'info') {
        renderInfo(payload.product);
        reasonPanel.hidden = true;
        infoPanel.hidden = false;
        badge.textContent = 'Product information';
      } else {
        renderReason(payload.reason);
        reasonPanel.hidden = false;
        infoPanel.hidden = true;
        badge.textContent = 'Why this was recommended';
      }

      cards.forEach(function (item) { item.classList.remove('is-selected'); });
      card.classList.add('is-selected');

      var visible = visibleCards();
      var index = visible.indexOf(card);
      counter.textContent = index >= 0 ? (index + 1) + ' of ' + visible.length : '';
      prevBtn.disabled = index <= 0;
      nextBtn.disabled = index < 0 || index >= visible.length - 1;

      body.scrollTop = 0;
      drawer.inert = false;
      drawer.classList.add('open');
      drawer.setAttribute('aria-hidden', 'false');
      if (window.innerWidth <= 768) document.body.style.overflow = 'hidden';
      closeBtn.focus();
    }

    function close() {
      if (!drawer.classList.contains('open')) return;
      drawer.classList.remove('open');
      drawer.setAttribute('aria-hidden', 'true');
      drawer.inert = true;
      cards.forEach(function (item) { item.classList.remove('is-selected'); });
      document.body.style.overflow = '';
      if (lastTrigger && lastTrigger.focus) lastTrigger.focus();
    }

    function openRelative(delta) {
      if (!currentCard) return;
      var visible = visibleCards();
      var index = visible.indexOf(currentCard);
      var next = visible[index + delta];
      if (next) open(next, currentMode, next);
    }

    document.addEventListener('click', function (e) {
      if (!e.target.closest) return;

      var action = e.target.closest('[data-rsr-action]');
      if (action) {
        var actionCard = action.closest('[data-rsr-card]');
        if (actionCard) {
          e.preventDefault();
          open(actionCard, action.getAttribute('data-rsr-action'), action);
        }
        return;
      }

      if (e.target.closest('.rec-save')) return;

      var top = e.target.closest('[data-rsr-open-top]');
      if (top) {
        e.preventDefault();
        var first = visibleCards()[0];
        if (first) open(first, 'reason', top);
        return;
      }

      if (drawer.contains(e.target)) return;

      var card = e.target.closest('[data-rsr-card]');
      if (card) {
        open(card, 'reason', card);
        return;
      }

      if (drawer.classList.contains('open')) close();
    });

    backBtn.addEventListener('click', close);
    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', function () { openRelative(-1); });
    nextBtn.addEventListener('click', function () { openRelative(1); });

    cards.forEach(function (card) {
      card.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        if (e.target !== card) return;
        e.preventDefault();
        open(card, 'reason', card);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (!drawer.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') openRelative(-1);
      if (e.key === 'ArrowRight') openRelative(1);
    });

    syncHeaderOffset();
    window.addEventListener('resize', syncHeaderOffset);
  })();
</script>
@endsection
