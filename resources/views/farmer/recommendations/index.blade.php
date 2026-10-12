@extends('layouts.sidebar')

@section('title', 'Smart Recommendations')

@section('content')
@php
  $now = now();
  $formattedDate = $now->format('l, F j, Y');
@endphp

<style>
  .sr-page { position: relative; }
  .sr-page::before,
  .sr-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .sr-page::before { width: 340px; height: 340px; background: rgba(22,163,74,0.14); top: -80px; right: -80px; }
  .sr-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.10); bottom: -60px; left: -60px; }
  .sr-content { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 28px 32px 60px; }

  /* ── Breadcrumb ── */
  .sr-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 24px;
  }
  .sr-breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
  }
  .sr-breadcrumb a:hover { color: #16a34a; }
  .sr-breadcrumb-sep {
    color: #cbd5e1;
    font-weight: 400;
    font-size: 11px;
    user-select: none;
  }
  .sr-breadcrumb-current {
    color: #0f172a;
    font-weight: 700;
  }

  /* ── Hero Card ── */
  .sr-hero {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
  }
  .sr-hero h1 {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
    letter-spacing: -0.01em;
  }
  .sr-hero p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
    line-height: 1.6;
    max-width: 600px;
  }
  .sr-info-bar {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
  }
  .sr-info-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 10px;
    background: #f8faf9;
    border: 1px solid #e8eaed;
    color: #475569;
  }
  .sr-info-chip svg { flex-shrink: 0; }
  .sr-info-chip.season-chip {
    background: #e0f2fe;
    border-color: #bae6fd;
    color: #0369a1;
  }

  /* ── AI Banner ── */
  .sr-ai-banner {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdfa 100%);
    border: 1px solid #a7f3d0;
    border-radius: 16px;
    padding: 26px 28px;
    margin-bottom: 28px;
    display: flex;
    gap: 18px;
    align-items: flex-start;
  }
  .sr-ai-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(34,197,94,0.3);
    overflow: hidden;
  }
  .sr-ai-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
  }
  .sr-ai-input {
    background: white;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 13px 16px;
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 12px;
    cursor: pointer;
    text-decoration: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .sr-ai-input:hover {
    border-color: #22c55e;
    box-shadow: 0 4px 14px rgba(34,197,94,0.18);
  }
  .sr-ai-input span {
    flex: 1;
    color: #86a892;
    font-size: 13.5px;
    font-style: italic;
  }
  .sr-ai-input .sr-ai-go {
    flex: none;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: white;
    font-style: normal;
    padding: 9px 18px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    white-space: nowrap;
  }

  /* ── Recommendation Categories ── */
  .sr-categories {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
  }
  @media (max-width: 1024px) {
    .sr-categories { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 640px) {
    .sr-categories { grid-template-columns: 1fr; }
  }
  #recommended-products { scroll-margin-top: 24px; }
  .sr-cat-card {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 14px;
    padding: 22px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .sr-cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.08);
    border-color: #c8e6c9;
  }
  .sr-cat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .sr-cat-card h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
  }
  .sr-cat-card p {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
  }
  .sr-cat-count {
    font-size: 12px;
    font-weight: 700;
    color: #16a34a;
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* ── Section Headers ── */
  .sr-section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 10px;
  }
  .sr-section-head h2 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
  }
  .sr-section-head p {
    font-size: 13px;
    color: #64748b;
    margin: 4px 0 0 0;
  }

  /* ── Product Grid ── */
  .sr-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 20px;
  }
  @media (max-width: 640px) {
    .sr-product-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
  }

  /* ── Product Card ── */
  .sr-product-card {
    background: #ffffff;
    border: 1px solid #e8eaed;
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    text-decoration: none;
    color: inherit;
    height: 100%;
  }
  .sr-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 36px rgba(0,0,0,0.09);
    border-color: #c8e6c9;
  }
  .sr-product-img {
    height: 180px;
    background: #f8faf9;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #f1f5f9;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
  }
  .sr-product-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  .sr-product-img svg {
    width: 48px;
    height: 48px;
    stroke: #cbd5e1;
    fill: none;
    stroke-width: 1.5;
  }
  .sr-category-tag {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #334155;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border: 1px solid rgba(0,0,0,0.06);
  }
  .sr-season-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 10.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1;
    padding: 5px 10px;
    border-radius: 6px;
    white-space: nowrap;
  }
  .sr-season-wet { background: #e0f2fe; color: #0369a1; }
  .sr-season-dry { background: #ffedd5; color: #c2410c; }
  .sr-season-both { background: #f0fdf4; color: #166534; }

  .sr-product-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; }
  .sr-product-body h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
    line-height: 1.35;
  }
  .sr-product-body .sr-desc {
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.5;
    margin: 0 0 10px 0;
  }
  .sr-product-store {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 6px;
  }
  .sr-product-brand {
    font-size: 11px;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 10px;
  }

  .sr-product-footer {
    padding: 14px 20px;
    background: #fafbfc;
    border-top: 1px solid #e8eaed;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .sr-product-price {
    font-size: 18px;
    font-weight: 800;
    color: #14532d;
  }
  .sr-stock-in { font-size: 12px; font-weight: 600; color: #16a34a; }
  .sr-stock-low { font-size: 12px; font-weight: 600; color: #b91c1c; }

  /* ── Why This Product ── */
  .sr-why-toggle {
    padding: 10px 20px 14px;
    border-top: 1px dashed #e8eaed;
    cursor: pointer;
    user-select: none;
  }
  .sr-why-toggle summary {
    font-size: 11.5px;
    font-weight: 600;
    color: #16a34a;
    cursor: pointer;
    list-style: none;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .sr-why-toggle summary::-webkit-details-marker { display: none; }
  .sr-why-toggle summary svg { transition: transform 0.2s; }
  .sr-why-toggle[open] summary svg { transform: rotate(90deg); }
  .sr-why-toggle .sr-why-body {
    margin-top: 8px;
    font-size: 12px;
    color: #64748b;
    line-height: 1.55;
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    border-radius: 8px;
    padding: 10px 12px;
    display: flex;
    gap: 8px;
    align-items: flex-start;
  }
  .sr-why-toggle .sr-why-body svg { flex-shrink: 0; margin-top: 1px; }

  /* ── Empty State ── */
  .sr-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 64px 24px;
    color: #94a3b8;
    background: #fafbfc;
    border: 1px dashed #d1d5db;
    border-radius: 14px;
  }
  .sr-empty h3 {
    font-size: 18px;
    font-weight: 700;
    color: #475569;
    margin: 0 0 8px 0;
  }
  .sr-empty p {
    font-size: 14px;
    color: #94a3b8;
    line-height: 1.6;
    margin: 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
  }

  /* ── Floating AI Chat Button ── */
  .sr-ai-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 24px rgba(22,163,74,0.35);
    z-index: 999;
    text-decoration: none;
    transition: all 0.25s ease;
    animation: sr-fab-pulse 3s ease-in-out infinite;
  }
  .sr-ai-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 8px 30px rgba(22,163,74,0.45);
  }
  .sr-ai-fab::after {
    content: "Chat with AI";
    position: absolute;
    right: 64px;
    background: #0f172a;
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
  }
  .sr-ai-fab:hover::after { opacity: 1; }
  @keyframes sr-fab-pulse {
    0%, 100% { box-shadow: 0 6px 24px rgba(22,163,74,0.35); }
    50% { box-shadow: 0 6px 24px rgba(22,163,74,0.35), 0 0 0 8px rgba(22,163,74,0.12); }
  }

  @media (max-width: 640px) {
    .sr-content { padding: 20px 16px 60px; }
    .sr-hero h1 { font-size: 21px; }
    .sr-hero { padding: 22px 20px; }
    .sr-ai-banner { padding: 20px; flex-direction: column; }
    .sr-ai-fab { bottom: 20px; right: 20px; width: 50px; height: 50px; }
  }
</style>

<div class="sr-page">
  <div class="sr-content">

    {{-- ═══ 1. Breadcrumb ═══ --}}
    <nav class="sr-breadcrumb">
      <a href="{{ route('dashboard.farmer') }}">Dashboard</a>
      <span class="sr-breadcrumb-sep">
        <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </span>
      <span class="sr-breadcrumb-current">Recommendations</span>
    </nav>

    {{-- ═══ 2. Hero Section ═══ --}}
    <div class="sr-hero">
      <h1>Smart Recommendations</h1>
      <p>Personalized product suggestions based on your crops, season, and farming needs in Bulan, Sorsogon</p>
      <div class="sr-info-bar">
        <div class="sr-info-chip season-chip">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
          Wet Season
        </div>
        <div class="sr-info-chip">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Bulan, Sorsogon
        </div>
        <div class="sr-info-chip">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ $formattedDate }}
        </div>
      </div>
    </div>

    {{-- ═══ 3. AI Assistant Banner ═══ --}}
    <div class="sr-ai-banner">
      <div class="sr-ai-icon">
        <img src="{{ asset('images/ai-avatar.png') }}" alt="AI Assistant">
      </div>
      <div style="flex: 1; min-width: 0;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
          <h2 style="font-size: 17px; font-weight: 700; color: #14532d; margin: 0;">AI Crop &amp; Product Assistant</h2>
        </div>
        <p style="font-size: 14px; color: #15803d; line-height: 1.6; margin: 0 0 4px 0;">
          Our intelligent assistant analyzes your crop type, land area, and local weather conditions to recommend the most effective inputs — from seeds to fertilizers and pest control.
        </p>
        <a class="sr-ai-input" href="{{ route('farmer.ai-chat.index') }}">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="#22c55e" stroke-width="2" fill="none" style="flex-shrink: 0;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span>e.g. "What fertilizer do I need for 2 hectares of rice this wet season?"</span>
          <span class="sr-ai-go">Ask Assistant</span>
        </a>
      </div>
    </div>

    {{-- ═══ 4. Recommendation Categories ═══ --}}
    <div class="sr-categories">
      @php
        $forYourCrops = $products->count();
        $seasonalPicks = $products->where('recommended_season', 'wet')->count() + $products->where('recommended_season', 'both')->count();
        $topQuoted = $products->count();
      @endphp

      <a href="#recommended-products" class="sr-cat-card">
        <div class="sr-cat-icon" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 20h10"/>
            <path d="M12 20v-8"/>
            <path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/>
            <path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/>
          </svg>
        </div>
        <div>
          <h3>For Your Crops</h3>
          <p>Products matched to your registered crop types and farming profile.</p>
        </div>
        <div class="sr-cat-count">
          {{ $forYourCrops }} {{ Str::plural('product', $forYourCrops) }}
          <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </a>

      <a href="{{ route('farmer.products.browse', ['season' => 'wet']) }}" class="sr-cat-card">
        <div class="sr-cat-icon" style="background: linear-gradient(135deg, #e0f2fe, #dbeafe);">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="#0369a1" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
          </svg>
        </div>
        <div>
          <h3>Seasonal Picks</h3>
          <p>Products ideal for the current wet season and local climate conditions.</p>
        </div>
        <div class="sr-cat-count">
          {{ $seasonalPicks }} {{ Str::plural('product', $seasonalPicks) }}
          <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </a>

      <a href="{{ route('farmer.top-quoted.index') }}" class="sr-cat-card">
        <div class="sr-cat-icon" style="background: linear-gradient(135deg, #fef3c7, #fef9c3);">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="#d97706" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <div>
          <h3>Top Quoted</h3>
          <p>Products other farmers request most in cost quotations.</p>
        </div>
        <div class="sr-cat-count">
          {{ $topQuoted }} {{ Str::plural('product', $topQuoted) }}
          <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </a>

      <a href="{{ route('farmer.stores.index') }}" class="sr-cat-card">
        <div class="sr-cat-icon" style="background: linear-gradient(135deg, #f3e8ff, #ede9fe);">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l1-5h16l1 5"/>
            <path d="M5 9v11h14V9"/>
            <path d="M9 20v-6h6v6"/>
          </svg>
        </div>
        <div>
          <h3>Partner Stores</h3>
          <p>Trusted local farm supply stores in your area offering quality products.</p>
        </div>
        <div class="sr-cat-count">
          {{ $storeCount }} {{ Str::plural('store', $storeCount) }}
          <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </a>
    </div>

    {{-- ═══ 5. Product Grid ═══ --}}
    <div class="sr-section-head" id="recommended-products">
      <div>
        <h2>Recommended Products</h2>
        <p>Curated selections based on your crops, season, and farming needs in Bulan.</p>
      </div>
      <a href="{{ route('farmer.products.browse') }}" style="font-size: 13px; font-weight: 700; color: #143d23; text-decoration: none; padding: 8px 16px; border-radius: 10px; background: rgba(20,61,35,0.06); display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;">
        Browse All
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

    <div class="sr-product-grid" id="srProductGrid">
      @forelse($products as $product)
        @php
          $season = $product->recommended_season ?? 'both';
          $seasonClass = 'sr-season-' . $season;
          $seasonLabel = $season === 'wet' ? 'Wet' : ($season === 'dry' ? 'Dry' : 'All');
          $stockClass = $product->is_low_stock ? 'sr-stock-low' : 'sr-stock-in';
          $facets = new Illuminate\View\ComponentAttributeBag($product->catalogFacets());
        @endphp
        <a href="{{ route('farmer.products.show', $product->id) }}" class="sr-product-card" {{ $facets }}>
          <div class="sr-product-img">
            @if($product->mainImage && !empty($product->mainImage->file_path))
              <img src="{{ $product->mainImage->thumb_url }}" alt="{{ $product->display_name }}" loading="lazy">
            @elseif($product->images && $product->images->first())
              <img src="{{ $product->images->first()->thumb_url }}" alt="{{ $product->display_name }}" loading="lazy">
            @else
              <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
            @endif
            <span class="sr-category-tag">{{ $product->category_display_name }}</span>
            <span class="sr-season-badge {{ $seasonClass }}">
              @if($season === 'wet')
                <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
              @elseif($season === 'dry')
                <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
              @else
                <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              @endif
              {{ $seasonLabel }}
            </span>
          </div>

          <div class="sr-product-body">
            <h3>{{ $product->display_name }}</h3>
            @if($product->storeOwner)
              <div class="sr-product-store">
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg>
                {{ $product->storeOwner->store_name ?? $product->storeOwner->name }}
              </div>
            @endif
            @if($product->brand)
              <span class="sr-product-brand">{{ $product->brand }}</span>
            @endif
          </div>

          <div class="sr-product-footer">
            <span class="sr-product-price">&#8369;{{ number_format($product->price, 2) }}</span>
            <span class="{{ $stockClass }}">
              {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pcs' }}
            </span>
          </div>

          <details class="sr-why-toggle">
            <summary>
              <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              Why this product?
            </summary>
            <div class="sr-why-body">
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Recommended based on your crop type and current season.
            </div>
          </details>
        </a>
      @empty
        <div class="sr-empty" data-cf-no-products>
          <svg viewBox="0 0 24 24" width="64" height="64" stroke="#d1d5db" fill="none" stroke-width="1.5" style="display: block; margin: 0 auto 16px;">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <h3>No Products Available</h3>
          <p>There are currently no active product listings from network stores. Check back soon as new products are added regularly.</p>
        </div>
      @endforelse
    </div>

    {{-- Shown when filters are applied and nothing matches. The filter engine
         toggles this via [data-cf-no-results]. --}}
    <div class="sr-empty" data-cf-no-results style="display: none;">
      <svg viewBox="0 0 24 24" width="64" height="64" stroke="#d1d5db" fill="none" stroke-width="1.5" style="display: block; margin: 0 auto 16px;">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <h3>No Matches</h3>
      <p>No recommendations match your current filters. Try widening the price range or clearing a filter.</p>
    </div>

  </div>
</div>

{{-- ═══ Floating AI Chat Button ═══ --}}
<a href="{{ route('farmer.ai-chat.index') }}" class="sr-ai-fab" title="Chat with AI">
  <img src="{{ asset('images/ai-avatar.png') }}" alt="AI Assistant" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
</a>

@endsection
