@extends('layouts.sidebar')

@section('title', 'My Store')

@section('content')
@php
  $user = auth()->user();
  $storeName = $user->store_name ?? $user->name;
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<style>
  .dash-page { position: relative; }
  .dash-page::before,
  .dash-page::after {
    content: "";
    position: fixed;
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
    z-index: 0;
  }
  .dash-page::before { width: 340px; height: 340px; background: rgba(22,163,74,0.18); top: -80px; right: -80px; }
  .dash-page::after { width: 280px; height: 280px; background: rgba(2,132,199,0.15); bottom: -60px; left: -60px; }
  .dash-content { position: relative; z-index: 1; }
  @media (min-width: 769px) {
    .dash-page > .dash-content { margin-top: 57px; }
  }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  /* ── Store Hero (consistent light band) ─────────────────────── */
  .hero-band {
    background: rgb(241, 250, 214);
    border-radius: 22px; padding: 30px 32px; color: #143d23; position: relative; overflow: hidden;
    box-shadow: 0 4px 16px rgba(20,61,35,0.10); margin-bottom: 26px;
  }
  .hero-band::before { content: ""; position: absolute; top: -110px; right: -60px; width: 280px; height: 280px; border-radius: 50%; background: rgba(16,185,129,0.15); }
  .hero-band::after { content: ""; position: absolute; bottom: -90px; left: 25%; width: 200px; height: 200px; border-radius: 50%; background: rgba(59,130,246,0.10); }
  .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
  .hero-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #166534; margin-bottom: 6px; }
  .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; }
  .hero-sub { font-size: 13px; color: #40632d; margin: 6px 0 0 0; }
  .hero-sub strong { color: #14532d; }
  .hero-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
  .hero-meta span {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.85); border: 1px solid rgba(20,61,35,0.10);
    padding: 6px 12px; border-radius: 9px;
    font-size: 12px; font-weight: 500; color: #40632d;
  }
  .hero-meta svg { width: 14px; height: 14px; stroke: #16a34a; flex-shrink: 0; }
  .btn-hero {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.10);
    color: #14532d; padding: 11px 20px; border-radius: 12px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(20,61,35,0.08);
  }
  .btn-hero:hover { background: #f0fdf4; transform: translateY(-1px); }
  .btn-hero svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  /* ── Quick Actions ──────────────────────────────────────────── */
  .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 28px; }
  .qa-btn {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border: 1px solid #e6ece7; border-radius: 14px;
    padding: 14px 16px; text-decoration: none; color: #1e2922;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
  }
  .qa-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(0,0,0,0.09); border-color: #cfe3d6; }
  .qa-icon {
    width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .qa-icon svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .qa-btn .qa-txt { min-width: 0; }
  .qa-btn .qa-txt b { display: block; font-size: 13px; font-weight: 700; color: #143d23; }
  .qa-btn .qa-txt span { display: block; font-size: 11px; color: #64748b; }

  /* ── Panels ─────────────────────────────────────────────────── */
  .dash-panel {
    background: rgba(255,255,255,0.82); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
  }
  .dash-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
  .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .panel-head h3 { font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
  .panel-body { padding: 18px 22px; }

  .btn-ghost {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none;
    padding: 7px 14px; border-radius: 9px; background: rgba(20,61,35,0.07);
    display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: rgba(20,61,35,0.13); }
  .btn-ghost svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    background: linear-gradient(135deg, #14532d, #16a34a); color: #fff;
    padding: 10px 18px; border-radius: 11px; font-size: 13px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer;
    box-shadow: 0 5px 16px rgba(20,83,45,0.28);
    transition: all 0.2s ease;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(20,83,45,0.35); }
  .btn-primary svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  /* ── Dashboard product cards ────────────────────────────────── */
  /* ── Product carousel ───────────────────────────────────────── */
  .prod-carousel-wrap { position: relative; }
  .prod-carousel { display: flex; gap: 18px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; padding: 2px 2px 10px; }
  .prod-carousel .product-card { flex: 0 0 230px; scroll-snap-align: start; }
  .prod-carousel .product-title { font-size: 14px; margin-top: 8px; }
  .prod-carousel::-webkit-scrollbar { height: 8px; }
  .prod-carousel::-webkit-scrollbar-track { background: transparent; }
  .prod-carousel::-webkit-scrollbar-thumb { background: #dbe5df; border-radius: 8px; }
  .prod-carousel::-webkit-scrollbar-thumb:hover { background: #c4d5ca; }
  .prod-more {
    flex: 0 0 230px; scroll-snap-align: start;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
    background: #f7faf8; border: 2px dashed #cfe0d6; border-radius: 16px;
    text-decoration: none; color: #14532d; text-align: center; padding: 22px 18px; cursor: pointer;
    transition: all 0.25s ease;
  }
  .prod-more:hover { border-color: #14532d; background: #eef5f1; }
  .prod-more svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .prod-more strong { font-size: 15px; font-weight: 800; }
  .prod-more span { font-size: 12px; color: #64748b; }
  .carousel-arrow {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
    width: 34px; height: 34px; border-radius: 50%; border: 1px solid #e2e8f0;
    background: #fff; color: #143d23; cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,0.10); transition: all 0.2s ease;
    opacity: 0.35; border-color: #dbe5df;
  }
  .carousel-arrow:hover,
  .carousel-arrow:active,
  .carousel-arrow:focus-visible { opacity: 1; background: #143d23; color: #fff; border-color: #143d23; }
  .carousel-arrow:disabled { opacity: 0; pointer-events: none; }
  .carousel-arrow svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .carousel-arrow.prev { left: -12px; }
  .carousel-arrow.next { right: -12px; }

  /* ── Top performers (Most Viewed / Most Quoted / Top Rated) ───────── */
  .top-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
  .top-grid .dash-panel { display: flex; flex-direction: column; height: 100%; }
  .top-grid .dash-panel .panel-body { flex: 1; }
  .top-panel-head { display: flex; align-items: center; gap: 10px; }
  .top-panel-head .top-ic {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .top-panel-head .top-ic svg { width: 17px; height: 17px; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
  .top-row {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0; border-bottom: 1px dashed #eef2ef;
    text-decoration: none; color: inherit; min-width: 0;
  }
  .top-row:last-child { border-bottom: none; }
  .top-row:hover .top-name { color: #15803d; }
  .top-rank {
    width: 22px; height: 22px; border-radius: 7px; flex-shrink: 0;
    background: #eef2ef; color: #475569; font-size: 10px; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .top-row:nth-child(2) .top-rank { background: #fef9ec; color: #b45309; }
  .top-row:nth-child(3) .top-rank { background: #fef2f2; color: #b91c1c; }
  .top-thumb {
    width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex-shrink: 0;
    background: #f4f7f5; display: flex; align-items: center; justify-content: center;
  }
  .top-thumb svg { width: 20px; height: 20px; stroke: #cbd5e1; stroke-width: 1.5; fill: none; stroke-linecap: round; stroke-linejoin: round; }
  .top-name {
    flex: 1; min-width: 0; font-size: 12.5px; font-weight: 700; color: #1e2922;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.35;
  }
  .top-stat { text-align: right; flex-shrink: 0; line-height: 1.15; }
  .top-stat b { display: block; font-size: 13px; font-weight: 800; color: #14532d; }
  .top-stat i { font-style: normal; font-size: 9.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; }
  .top-stat .top-stars { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }
  .top-stat .top-stars svg { width: 12px; height: 12px; fill: #f59e0b; }
  .top-empty { text-align: center; padding: 26px 12px; }
  .top-empty svg { width: 30px; height: 30px; stroke: #cbd5e1; stroke-width: 1.5; fill: none; }
  .top-empty p { font-size: 12.5px; color: #94a3b8; margin: 8px 0 0 0; line-height: 1.5; }
  @media (max-width: 960px) {
    .top-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 640px) {
    .hero-band { padding: 22px; }
    .hero-title { font-size: 21px; }
    .prod-carousel { gap: 12px; }
    .prod-carousel .product-card, .prod-carousel .prod-more { flex: 0 0 200px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <!-- 1. STORE HERO -->
    <section class="hero-band reveal in">
      <div class="hero-inner">
        <div>
          <div class="hero-eyebrow">Store Dashboard</div>
          <h1 class="hero-title"><span data-greeting>{{ $greeting }}</span>, {{ $storeName }}</h1>
          <p class="hero-sub">Here's your <strong>store overview</strong> for {{ $now->format('l, F j') }}.@if($user->store_name && $user->store_name !== $user->name) Owned by <strong>{{ $user->name }}</strong>.@endif</p>
          @if($user->address || $user->barangay || $user->city || $user->province || $user->contact_number || $user->email)
            <div class="hero-meta">
              @if($user->address || $user->barangay || $user->city || $user->province)
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ trim(implode(', ', array_filter([$user->address, $user->barangay, $user->city, $user->province]))) }}
                </span>
              @endif
              @if($user->contact_number)
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  {{ $user->contact_number }}
                </span>
              @endif
              @if($user->email)
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  {{ $user->email }}
                </span>
              @endif
            </div>
          @endif
        </div>
        <div class="hero-actions">
          <a href="{{ route('store.storefront.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24" fill="none"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 13v7h14v-7"/></svg>
            Storefront
          </a>
          <a href="{{ route('store.inventory.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            {{ $productCount }} Products
          </a>
        </div>
      </div>
    </section>

    <!-- 2. KPI CARDS -->
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;">
      <x-kpi-card title="Total Products" value="{{ $productCount }}" trendValue="{{ $productCount > 0 ? 'live listings' : 'start listing' }}" trendDirection="{{ $productCount > 0 ? 'up' : 'neutral' }}" trendLabel="In marketplace" accent="#16a34a" link="{{ route('store.storefront.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Low Stock Items" value="{{ $lowStockCount }}" trendValue="{{ $lowStockCount > 0 ? 'reorder soon' : 'all healthy' }}" trendDirection="{{ $lowStockCount > 0 ? 'down' : 'up' }}" trendLabel="Alert threshold" accent="#d97706" link="{{ route('store.inventory.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Demand Signals" value="{{ $demandQuotationCount }}" trendValue="{{ $computedQuotationCount }} estimate-ready" trendDirection="{{ $demandQuotationCount > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $viewedQuotationCount }} viewed by you" accent="#7c3aed" link="{{ route('store.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Average Rating" value="{{ $averageRating ?? '—' }}" trendValue="{{ $totalReviews }} farmer reviews" trendDirection="{{ $totalReviews > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $averageRating !== null ? 'Across ' . $ratedProducts->count() . ' products' : 'Waiting for first review' }}" accent="#0ea5e9" link="{{ route('store.storefront.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 3. QUICK ACTIONS -->
    <section class="quick-actions reveal" data-reveal>
      <a href="{{ route('store.products.create') }}" class="qa-btn">
        <span class="qa-icon" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        <span class="qa-txt"><b>Add Product</b><span>New listing</span></span>
      </a>
      <a href="{{ route('store.inventory.index') }}" class="qa-btn">
        <span class="qa-icon" style="background:#dbeafe;color:#2563eb;"><svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
        <span class="qa-txt"><b>Inventory</b><span>Manage stock</span></span>
      </a>
      <a href="{{ route('store.quotations.index') }}" class="qa-btn">
        <span class="qa-icon" style="background:#f3e8ff;color:#7c3aed;"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
        <span class="qa-txt"><b>Quotations</b><span>Cost Quotations</span></span>
      </a>
      <a href="{{ route('store.formulas.index') }}" class="qa-btn">
        <span class="qa-icon" style="background:#fef3c7;color:#d97706;"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span>
        <span class="qa-txt"><b>Formulas</b><span>Input rates</span></span>
      </a>
      <a href="{{ route('store.storefront.index') }}" class="qa-btn">
        <span class="qa-icon" style="background:#ccfbf1;color:#0f766e;"><svg viewBox="0 0 24 24"><path d="M3 9l1.5-4.5h15L21 9"/><path d="M3 9v10a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V9"/><path d="M3 9h18"/><path d="M6 14h4v4H6z"/><path d="M14 14h4v6h-4z"/></svg></span>
        <span class="qa-txt"><b>My Store</b><span>Storefront</span></span>
      </a>
    </section>

    <!-- 4. MAIN GRID -->
    <section style="display:grid;grid-template-columns:1fr;gap:22px;align-items:start;">

      <!-- 5. TOP PERFORMERS -->
      <section class="top-grid reveal" data-reveal>

        <!-- MOST VIEWED -->
        <div class="dash-panel">
          <div class="panel-head">
            <div class="top-panel-head">
              <span class="top-ic" style="background:#dbeafe;color:#2563eb;"><i data-lucide="eye" width="17" height="17"></i></span>
              <div><h3>Most Viewed</h3><p>Product pages opened by farmers</p></div>
            </div>
          </div>
          <div class="panel-body">
            @forelse($topViewed as $idx => $product)
              <a class="top-row" href="{{ route('store.products.show', $product->id) }}">
                <span class="top-rank">#{{ $idx + 1 }}</span>
                @if($product->mainImage && !empty($product->mainImage->file_path))
                  <img class="top-thumb" src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <span class="top-thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                @endif
                <span class="top-name">{{ $product->name }}</span>
                <span class="top-stat"><b>{{ $product->views_count }}</b><i>views</i></span>
              </a>
            @empty
              <div class="top-empty">
                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <p>No farmer views yet. When farmers open your product pages, they'll be ranked here.</p>
              </div>
            @endforelse
          </div>
        </div>

        <!-- MOST QUOTED -->
        <div class="dash-panel">
          <div class="panel-head">
            <div class="top-panel-head">
              <span class="top-ic" style="background:#f3e8ff;color:#7c3aed;"><i data-lucide="file-text" width="17" height="17"></i></span>
              <div><h3>Most Quoted</h3><p>Products requested in generated quotations</p></div>
            </div>
          </div>
          <div class="panel-body">
            @forelse($topQuoted as $idx => $row)
              @php $product = $row->product; @endphp
              <a class="top-row" href="{{ route('store.products.show', $product->id) }}">
                <span class="top-rank">#{{ $idx + 1 }}</span>
                @if($product->mainImage && !empty($product->mainImage->file_path))
                  <img class="top-thumb" src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <span class="top-thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                @endif
                <span class="top-name">{{ $product->name }}</span>
                <span class="top-stat"><b>{{ $row->count }}</b><i>quotes</i></span>
              </a>
            @empty
              <div class="top-empty">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                <p>No quotations generated yet. Products farmers request will be ranked here.</p>
              </div>
            @endforelse
          </div>
        </div>

        <!-- TOP RATED -->
        <div class="dash-panel">
          <div class="panel-head">
            <div class="top-panel-head">
              <span class="top-ic" style="background:#fef9ec;color:#d97706;"><i data-lucide="star" width="17" height="17"></i></span>
              <div><h3>Top Rated</h3><p>Highest rated by farmers' approved reviews</p></div>
            </div>
          </div>
          <div class="panel-body">
            @forelse($topRated as $idx => $product)
              <a class="top-row" href="{{ route('store.products.show', $product->id) }}">
                <span class="top-rank">#{{ $idx + 1 }}</span>
                @if($product->mainImage && !empty($product->mainImage->file_path))
                  <img class="top-thumb" src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <span class="top-thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                @endif
                <span class="top-name">{{ $product->name }}</span>
                <span class="top-stat">
                  <span class="top-stars">
                    @for($i = 1; $i <= 5; $i++)
                      <svg viewBox="0 0 24 24" style="{{ $i <= (int) round($product->average_rating) ? '' : 'fill:#e2e8f0;' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                  </span>
                  <i>{{ number_format($product->average_rating, 1) }} · {{ $product->review_count }} reviews</i>
                </span>
              </a>
            @empty
              <div class="top-empty">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <p>No approved reviews yet. Farmer ratings will be ranked here.</p>
              </div>
            @endforelse
          </div>
        </div>
      </section>

      <!-- 4b. MY PRODUCTS -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>My Products</h3>
            <p>Active listings shown to farmers in the marketplace.</p>
          </div>
          <a href="{{ route('store.storefront.index') }}" class="btn-ghost">Manage <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="panel-body">
          @if($products->isNotEmpty())
            <div class="prod-carousel-wrap">
              <div class="prod-carousel" id="prodCarousel">
                @foreach($products as $product)
                  @if($loop->index < 10)
                    <x-product-card :product="$product" href="{{ route('store.products.show', $product->id) }}" image-height="150px" />
                  @endif
                @endforeach
                <a class="prod-more" href="{{ route('store.storefront.index') }}">
                  <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/></svg>
                  <strong>See more</strong>
                  <span>View all {{ $products->count() }} products</span>
                </a>
              </div>
              <button type="button" class="carousel-arrow prev" id="prodPrev" aria-label="Scroll left">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <button type="button" class="carousel-arrow next" id="prodNext" aria-label="Scroll right">
                <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>
          @else
            <div style="text-align:center;padding:44px 20px;">
              <svg viewBox="0 0 24 24" width="42" height="42" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom:12px;"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              <h3 style="font-size:17px;font-weight:700;color:#1e2922;margin:0 0 6px 0;">No Products Listed Yet</h3>
              <p style="font-size:14px;color:#64748b;margin:0 0 18px 0;">Add your first product to start selling to farmers.</p>
              <a href="{{ route('store.products.create') }}" class="btn-primary">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Product
              </a>
            </div>
          @endif
        </div>
      </div>

      </section>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Render Lucide official icons
    if (window.lucide) lucide.createIcons();

    // Reveal on scroll
    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
      var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            ro.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08 });
      revealEls.forEach(function (el) { ro.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    // Product carousel arrows
    var carousel = document.getElementById('prodCarousel');
    var prevBtn = document.getElementById('prodPrev');
    var nextBtn = document.getElementById('prodNext');
    if (carousel && prevBtn && nextBtn) {
      var step = function () { return Math.round(carousel.clientWidth * 0.8); };
      var updateArrows = function () {
        var max = carousel.scrollWidth - carousel.clientWidth;
        prevBtn.disabled = carousel.scrollLeft <= 1;
        nextBtn.disabled = carousel.scrollLeft >= max - 1;
      };
      prevBtn.addEventListener('click', function () { carousel.scrollBy({ left: -step(), behavior: 'smooth' }); });
      nextBtn.addEventListener('click', function () { carousel.scrollBy({ left: step(), behavior: 'smooth' }); });
      carousel.addEventListener('scroll', updateArrows);
      window.addEventListener('resize', updateArrows);
      updateArrows();
    }
  });
</script>
@endsection
