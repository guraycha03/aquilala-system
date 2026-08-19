@extends('layouts.sidebar')

@section('title', 'My Store')

@section('content')
@php
  $user = auth()->user();
  $storeName = $user->store_name ?? $user->name;
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

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  /* ── Store Hero ─────────────────────────────────────────────── */
  .store-hero {
    background: linear-gradient(135deg, #0d2917 0%, #14532d 45%, #16a34a 100%);
    border-radius: 22px;
    padding: 30px 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(13,41,23,0.30), 0 2px 8px rgba(13,41,23,0.12);
    margin-bottom: 26px;
  }
  .store-hero::before { content: ""; position: absolute; top: -120px; right: -80px; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.07); }
  .store-hero::after { content: ""; position: absolute; bottom: -100px; left: 30%; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,0.05); }
  .hero-inner { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; position: relative; z-index: 2; }
  .hero-avatar {
    width: 84px; height: 84px; border-radius: 22px;
    background: rgba(255,255,255,0.16); border: 2px solid rgba(255,255,255,0.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; font-weight: 800; color: #fff; text-transform: uppercase;
    box-shadow: inset 0 0 24px rgba(255,255,255,0.08);
    flex-shrink: 0;
  }
  .hero-info { flex: 1; min-width: 230px; }
  .hero-name { font-size: 28px; font-weight: 800; margin: 0 0 4px 0; letter-spacing: -0.01em; line-height: 1.15; }
  .hero-sub { font-size: 13px; color: rgba(255,255,255,0.82); margin: 0 0 12px 0; }
  .hero-sub strong { color: #fff; font-weight: 700; }
  .hero-meta { display: flex; flex-wrap: wrap; gap: 8px; }
  .hero-meta span {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.12); padding: 6px 12px; border-radius: 9px;
    font-size: 12px; font-weight: 500; color: #fff;
  }
  .hero-meta svg { width: 14px; height: 14px; stroke: rgba(255,255,255,0.9); flex-shrink: 0; }
  .hero-stats { display: flex; gap: 12px; position: relative; z-index: 2; }
  .hero-stat {
    text-align: center; padding: 12px 20px; border-radius: 14px;
    background: rgba(255,255,255,0.13); border: 1px solid rgba(255,255,255,0.14);
    min-width: 92px; backdrop-filter: blur(8px);
  }
  .hero-stat .num { font-size: 24px; font-weight: 800; line-height: 1; }
  .hero-stat .lbl { font-size: 11px; color: rgba(255,255,255,0.82); margin-top: 5px; }

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
  .prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 18px; }
  .prod-card {
    background: #fff; border: 1px solid #e6ece7; border-radius: 16px; overflow: hidden;
    display: flex; flex-direction: column; cursor: pointer; text-decoration: none; color: inherit;
    transition: all 0.28s cubic-bezier(0.4,0,0.2,1);
    position: relative;
  }
  .prod-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(0,0,0,0.10); border-color: #cfe3d6; }
  .prod-img { height: 150px; background: #f4f7f5; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f1f5f9; overflow: hidden; }
  .prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
  .prod-card:hover .prod-img img { transform: scale(1.05); }
  .prod-cat { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #0f766e; background: #ccfbf1; padding: 3px 8px; border-radius: 6px; }
  .stock-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 7px; }
  .stock-ok { background: #f0fdf4; color: #16a34a; }
  .stock-low { background: #fef2f2; color: #dc2626; }
  .stock-out { background: #f1f5f9; color: #64748b; }
  .stock-bar { height: 6px; border-radius: 6px; background: #eef2ef; overflow: hidden; }
  .stock-bar > div { height: 100%; border-radius: 6px; background: var(--bg, #22c55e); transition: width 1s cubic-bezier(0.4,0,0.2,1); }
  .prod-price { font-size: 16px; font-weight: 800; color: #0f172a; }
  .prod-edit {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f0f7f2; color: #14532d; padding: 7px 12px; border-radius: 9px;
    font-size: 12px; font-weight: 700; text-decoration: none;
    transition: all 0.2s ease;
  }
  .prod-edit:hover { background: #14532d; color: #fff; }
  .prod-edit svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* ── Tabs (right rail) ──────────────────────────────────────── */
  .dash-tabs { display: flex; gap: 6px; background: #eef2ef; padding: 5px; border-radius: 12px; }
  .dash-tab {
    flex: 1; border: none; background: transparent; cursor: pointer;
    padding: 8px 10px; border-radius: 9px; font-size: 12px; font-weight: 700;
    color: #64748b; transition: all 0.2s ease; white-space: nowrap;
  }
  .dash-tab.active { background: #fff; color: #143d23; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .tab-pane { display: none; animation: fadeUp 0.35s ease; }
  .tab-pane.active { display: block; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  /* ── Alert rows ─────────────────────────────────────────────── */
  .alert-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.045); transition: all 0.2s ease; }
  .alert-row:last-child { border-bottom: none; }
  .alert-row:hover { padding-left: 6px; }
  .alert-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

  /* ── Quotation timeline ─────────────────────────────────────── */
  .qt-item { position: relative; padding: 13px 0 13px 24px; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .qt-item:last-child { border-bottom: none; }
  .qt-item:hover { transform: translateX(4px); }
  .qt-dot { position: absolute; left: 0; top: 18px; width: 12px; height: 12px; border-radius: 50%; border: 2.5px solid; background: #fff; }
  .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

  @media (max-width: 640px) {
    .store-hero { padding: 22px; }
    .hero-name { font-size: 21px; }
    .hero-stats { width: 100%; }
    .prod-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .prod-img { height: 105px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <!-- 1. STORE HERO -->
    <section class="store-hero reveal in">
      <div class="hero-inner">
        <div class="hero-avatar">{{ Str::upper(mb_substr($storeName, 0, 1)) }}</div>
        <div class="hero-info">
          <h1 class="hero-name">{{ $storeName }}</h1>
          @if($user->store_name && $user->store_name !== $user->name)
            <p class="hero-sub">Owned by <strong>{{ $user->name }}</strong> · Store Owner</p>
          @else
            <p class="hero-sub">Store Owner · Aquilala Market</p>
          @endif
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
        <div class="hero-stats">
          <div class="hero-stat">
            <div class="num">{{ $productCount }}</div>
            <div class="lbl">Products</div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. KPI CARDS -->
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;">
      <x-kpi-card title="Total Products" value="{{ $productCount }}" trendValue="{{ $productCount > 0 ? 'live listings' : 'start listing' }}" trendDirection="{{ $productCount > 0 ? 'up' : 'neutral' }}" trendLabel="in marketplace" accent="#16a34a" link="{{ route('store.storefront.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Low Stock Items" value="{{ $lowStockCount }}" trendValue="{{ $lowStockCount > 0 ? 'reorder soon' : 'all healthy' }}" trendDirection="{{ $lowStockCount > 0 ? 'down' : 'up' }}" trendLabel="alert threshold" accent="#d97706" link="{{ route('store.inventory.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Pending Quotations" value="{{ $pendingQuotationsCount }}" trendValue="{{ $computedQuotationsCount }} computed" trendDirection="{{ $pendingQuotationsCount > 0 ? 'up' : 'neutral' }}" trendLabel="awaiting response" accent="#7c3aed" link="{{ route('store.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></x-slot:icon>
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

      <!-- PRODUCTS -->
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
            <div class="prod-grid">
              @foreach($products as $product)
                @php
                  $qty = (int) ($product->stock_quantity ?? 0);
                  $threshold = (int) ($product->reorder_threshold ?? 5);
                  $stockClass = $qty <= 0 ? 'stock-out' : ($qty <= $threshold ? 'stock-low' : 'stock-ok');
                  $stockLabel = $qty <= 0 ? 'Out of stock' : ($qty <= $threshold ? 'Low stock' : 'In stock');
                  $barColor = $qty <= 0 ? '#94a3b8' : ($qty <= $threshold ? '#dc2626' : '#22c55e');
                  $cap = max($threshold * 3, 10);
                  $pct = max(4, min(100, round($qty / $cap * 100)));
                @endphp
                <a class="prod-card" href="{{ route('store.products.show', $product->id) }}">
                  <div class="prod-img">
                    @if($product->mainImage && !empty($product->mainImage->file_path))
                      <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                    @elseif($product->images && $product->images->first())
                      <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                      <svg viewBox="0 0 24 24" width="38" height="38" stroke="#94a3b8" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                      </svg>
                    @endif
                  </div>
                  <div style="padding:14px;display:flex;flex-direction:column;gap:8px;flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                      <span class="prod-cat">{{ $product->category->name ?? $product->category ?? 'General' }}</span>
                      <span class="stock-pill {{ $stockClass }}">{{ $qty }} · {{ $stockLabel }}</span>
                    </div>
                    <h3 style="font-size:14px;font-weight:700;color:#1e2922;margin:0;line-height:1.3;">{{ $product->name }}</h3>
                    <div style="margin-top:auto;">
                      <div class="stock-bar"><div data-pct="{{ $pct }}" data-bar data-bg="{{ $barColor }}" style="width:0%"></div></div>
                      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:12px;">
                        <div class="prod-price">₱{{ number_format($product->price, 2) }}</div>
                        <a href="{{ route('store.products.edit', $product->id) }}" class="prod-edit" onclick="event.stopPropagation();">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                          Edit
                        </a>
                      </div>
                    </div>
                  </div>
                </a>
              @endforeach
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

      <!-- RIGHT RAIL -->
      <div style="display:flex;flex-direction:column;gap:22px;">

        <!-- STOCK HEALTH / QUOTATION ACTIVITY -->
        <div class="dash-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Store Activity</h3>
              <p>Alerts and recent quotation flow</p>
            </div>
          </div>
          <div style="padding:12px 16px;">
            <div class="dash-tabs">
              <button class="dash-tab active" data-tab="alerts"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Stock Alerts</button>
              <button class="dash-tab" data-tab="quotes"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Quotations</button>
            </div>
          </div>

          <!-- Alerts tab -->
          <div class="tab-pane active" id="tab-alerts">
            <div class="panel-body" style="padding-top:6px;">
              @if($lowStockProducts->isNotEmpty())
                @foreach($lowStockProducts as $p)
                  @php
                    $qty = (int)($p->stock_quantity ?? 0);
                    $th = (int)($p->reorder_threshold ?? 5);
                    $pct = min(100, round($qty / max($th,1) * 100));
                    $outColor = $qty <= 0 ? '#64748b' : '#dc2626';
                    $alertDotStyle = 'background:' . $outColor . ';';
                    $alertQtyStyle = 'font-size:11px;font-weight:700;color:' . $outColor . ';';
                  @endphp
                  <div class="alert-row">
                    <span class="alert-dot" data-inline-style="{{ $alertDotStyle }}"></span>
                    <div style="flex:1;min-width:0;">
                      <div style="display:flex;justify-content:space-between;gap:8px;">
                        <b style="font-size:13px;color:#1e2922;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $p->name }}</b>
                        <span data-inline-style="{{ $alertQtyStyle }}">{{ $qty <= 0 ? '0 left' : $qty . ' left' }}</span>
                      </div>
                      <div class="stock-bar" style="margin-top:7px;"><div data-pct="{{ $pct }}" data-bar data-bg="{{ $qty <= 0 ? '#94a3b8' : '#dc2626' }}" style="width:0%"></div></div>
                      <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Reorder at {{ $th }} {{ $p->stock_unit ?? 'pieces' }} · <a href="{{ route('store.products.edit', $p->id) }}" style="color:#dc2626;font-weight:700;text-decoration:none;">Restock →</a></div>
                    </div>
                  </div>
                @endforeach
              @else
                <div style="text-align:center;padding:32px 16px;">
                  <div style="width:54px;height:54px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <svg viewBox="0 0 24 24" width="26" height="26" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  </div>
                  <p style="font-size:13px;color:#64748b;margin:0;">All products are sufficiently stocked.</p>
                </div>
              @endif
            </div>
          </div>

          <!-- Quotations tab -->
          <div class="tab-pane" id="tab-quotes">
            <div class="panel-body" style="padding-top:6px;">
              @if($recentStoreQuotations->isNotEmpty())
                <div style="position:relative;">
                  @foreach($recentStoreQuotations as $qt)
                    @php
                      $qtDotStyle = 'border-color:' . $qt->status_color . ';';
                      $qtPillStyle = 'background:' . $qt->status_bg . ';color:' . $qt->status_color . ';border:1px solid ' . $qt->status_color . '33;';
                    @endphp
                    <div class="qt-item">
                      <div class="qt-dot" data-inline-style="{{ $qtDotStyle }}"></div>
                      @if(!$loop->last)
                        <div style="position:absolute;left:5px;top:30px;bottom:-15px;width:2px;background:rgba(0,0,0,0.06);"></div>
                      @endif
                      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:12px;color:#0f172a;font-family:monospace;">{{ $qt->quotation_number }}</span>
                        <span class="status-pill" data-inline-style="{{ $qtPillStyle }}">
                          <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:currentColor;"></span> {{ $qt->status_label }}
                        </span>
                      </div>
                      <div style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.4;">
                        <strong>{{ $qt->crop_type }}</strong> · {{ ucfirst($qt->season) }} Season · {{ $qt->land_area }} ha
                        @if($qt->farmer)
                          <svg viewBox="0 0 24 24" width="12" height="12" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> {{ $qt->farmer->name }}
                        @endif
                      </div>
                      @if($qt->estimated_total_cost > 0)
                        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-top:5px;">₱{{ number_format($qt->estimated_total_cost, 2) }}</div>
                      @endif
                    </div>
                  @endforeach
                </div>
              @else
                <div style="text-align:center;padding:32px 16px;">
                  <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                  <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No quotation requests yet.</p>
                  <p style="font-size:12px;color:#94a3b8;margin:4px 0 0 0;">New farmer requests will appear here.</p>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
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

    // Animate stock bars
    document.querySelectorAll('[data-bar]').forEach(function (bar) {
      var pct = bar.dataset.pct || '50';
      setTimeout(function () { bar.style.width = pct + '%'; }, 120);
    });

    // Tabs
    document.querySelectorAll('.dash-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        document.querySelectorAll('.dash-tab').forEach(function (t) { t.classList.remove('active'); });
        document.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('active'); });
        tab.classList.add('active');
        var pane = document.getElementById('tab-' + tab.dataset.tab);
        if (pane) pane.classList.add('active');
      });
    });
  });
</script>
@endsection
