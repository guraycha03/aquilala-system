@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('content')
@php
  $user = auth()->user();
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
  $climateInfo = class_exists('\App\Helpers\SeasonHelper') ? \App\Helpers\SeasonHelper::getClimateInfo() : ['season' => 'dry', 'label' => 'Dry Season'];
  $season = $climateInfo['season'] ?? 'dry';
  $seasonPillStyle = $season === 'wet'
      ? 'display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:20px;background:#e0f2fe;color:#0284c7;'
      : 'display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:20px;background:#fef3c7;color:#d97706;';
@endphp

<style>
  .dash-page { position: relative; }
  .dash-page::before,
  .dash-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .dash-page::before { width: 360px; height: 360px; background: rgba(22,163,74,0.18); top: -90px; right: -90px; }
  .dash-page::after { width: 300px; height: 300px; background: rgba(217,119,6,0.15); bottom: -70px; left: -70px; }
  .dash-content { position: relative; z-index: 1; }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  .hero-band {
    background: linear-gradient(135deg, #0d2917 0%, #14532d 48%, #0f766e 100%);
    border-radius: 22px; padding: 30px 32px; color: #fff; position: relative; overflow: hidden;
    box-shadow: 0 18px 48px rgba(13,41,23,0.30); margin-bottom: 26px;
  }
  .hero-band::before { content: ""; position: absolute; top: -110px; right: -60px; width: 280px; height: 280px; border-radius: 50%; background: rgba(255,255,255,0.06); }
  .hero-band::after { content: ""; position: absolute; bottom: -90px; left: 25%; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.05); }
  .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
  .hero-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #7ee0a6; margin-bottom: 6px; }
  .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.01em; }
  .hero-sub { font-size: 13px; color: rgba(255,255,255,0.78); margin: 6px 0 0 0; }
  .hero-sub strong { color: #fff; }
  .hero-season { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 700; margin-top: 10px; padding: 5px 12px; border-radius: 20px; }
  .hero-season-wet { background: rgba(56,189,248,0.18); color: #bae6fd; }
  .hero-season-dry { background: rgba(251,191,36,0.18); color: #fde68a; }

  .btn-hero {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22);
    color: #fff; padding: 11px 20px; border-radius: 12px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease; backdrop-filter: blur(6px);
  }
  .btn-hero:hover { background: rgba(255,255,255,0.22); transform: translateY(-1px); }
  .btn-hero svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 22px; margin-bottom: 22px; align-items: start; }

  .dash-panel {
    background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
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

  /* Marketplace product cards */
  .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
  .mp-card { background: #fff; border: 1px solid #e6ece7; border-radius: 14px; overflow: hidden; text-decoration: none; color: inherit; transition: all 0.25s cubic-bezier(0.4,0,0.2,1); display: block; }
  .mp-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,0.10); border-color: #cfe3d6; }
  .mp-img { height: 96px; background: #f4f7f5; display: flex; align-items: center; justify-content: center; overflow: hidden; }
  .mp-img img { width: 100%; height: 100%; object-fit: cover; }
  .mp-img svg { width: 26px; height: 26px; stroke: #94a3b8; fill: none; stroke-width: 1.5; }
  .mp-body { padding: 11px 12px; }
  .mp-body h4 { font-size: 12.5px; font-weight: 700; color: #1e2922; margin: 0 0 3px 0; line-height: 1.25; }
  .mp-body .mp-store { font-size: 10.5px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .mp-body .mp-price { font-size: 14px; font-weight: 800; color: #14532d; margin-top: 6px; }

  /* Store directory cards */
  .store-card { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .store-card:last-child { border-bottom: none; }
  .store-avatar { width: 40px; height: 40px; border-radius: 12px; background: #143d23; color: #c8e6c9; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; text-transform: uppercase; flex-shrink: 0; }
  .store-card b { font-size: 13px; color: #1e2922; display: block; }
  .store-card .store-meta { font-size: 11px; color: #94a3b8; }

  /* Timeline */
  .qt-item { position: relative; padding: 13px 0 13px 24px; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .qt-item:last-child { border-bottom: none; }
  .qt-item:hover { transform: translateX(4px); }
  .qt-dot { position: absolute; left: 0; top: 18px; width: 12px; height: 12px; border-radius: 50%; border: 2.5px solid; background: #fff; }
  .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

  .crop-chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(22,163,74,0.08); color: #166534; font-size: 12px; font-weight: 600; padding: 5px 13px; border-radius: 18px; border: 1px solid rgba(22,163,74,0.18); }
  .crop-chip span { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }

  @media (max-width: 768px) {
    .hero-band { padding: 22px; }
    .hero-title { font-size: 21px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <!-- 1. HERO -->
    <section class="hero-band reveal in">
      <div class="hero-inner">
        <div>
          <div class="hero-eyebrow">Producer Hub</div>
          <h1 class="hero-title"><span data-greeting>{{ $greeting }}</span>, {{ $user->name }}</h1>
          <p class="hero-sub">Here's your <strong>farming overview</strong> for {{ $now->format('l, F j') }}.</p>
          <span class="hero-season {{ $season === 'wet' ? 'hero-season-wet' : 'hero-season-dry' }}">
            @if($season === 'wet')
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            @else
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
            @endif
            {{ $climateInfo['label'] ?? ($season === 'wet' ? 'Wet Season' : 'Dry Season') }} · {{ $climateInfo['months'] ?? '' }}
          </span>
        </div>
        <div class="hero-actions">
          <a href="{{ route('farmer.quotations.request') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Request Quotation
          </a>
          <a href="{{ route('farmer.products.browse') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Browse Products
          </a>
        </div>
      </div>
    </section>

    <!-- 2. KPI CARDS -->
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;">
      <x-kpi-card title="Crop Varieties" value="{{ $totalCrops }}" trendValue="{{ $supportedCrops->count() }} types" trendDirection="up" trendLabel="supported crops" accent="#eab308" link="{{ route('farmer.crops.index') }}">
        <x-slot:icon><img src="{{ asset('images/crop.png') }}" alt="Crop" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Recommended Products" value="{{ $recommendedCount }}" trendValue="{{ $activeProductCount }} active" trendDirection="up" trendLabel="in marketplace" accent="#16a34a" link="{{ route('farmer.products.browse') }}">
        <x-slot:icon><img src="{{ asset('images/product.png') }}" alt="Product" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Quotation Requests" value="{{ $quotationCount }}" trendValue="{{ $pendingQuotations }} pending" trendDirection="{{ $pendingQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $dispatchedQuotations }} dispatched" accent="#7c3aed" link="{{ route('farmer.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Stores Directory" value="{{ $storeCount }}" trendValue="registered" trendDirection="neutral" trendLabel="store owners" accent="#d97706" link="{{ route('farmer.stores.index') }}">
        <x-slot:icon><img src="{{ asset('images/store.png') }}" alt="Store" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 3. SEASON & DECISION SUPPORT -->
    <section class="dash-grid">

      <!-- Current Season -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Current Season</h3>
            <p>{{ $climateInfo['months'] ?? ($season === 'wet' ? 'June – October' : 'November – May') }} &middot; local farming calendar</p>
          </div>
          <span data-inline-style="{{ $seasonPillStyle }}">
            @if($season === 'wet')
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            @else
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
            @endif
            {{ $climateInfo['label'] ?? ($season === 'wet' ? 'Wet Season' : 'Dry Season') }}
          </span>
        </div>
        <div class="panel-body">
          @if(!empty($climateInfo['typical_weather']))
            <p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.6;">{{ $climateInfo['typical_weather'] }}</p>
          @endif

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px;">
            <div style="background:rgba(22,163,74,0.06);border:1px solid rgba(22,163,74,0.14);border-radius:12px;padding:10px 12px;">
              <div style="font-size:10.5px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Season Months</div>
              <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">{{ $climateInfo['months'] ?? ($season === 'wet' ? 'June – October' : 'November – May') }}</div>
            </div>
            <div style="background:rgba(2,132,199,0.06);border:1px solid rgba(2,132,199,0.14);border-radius:12px;padding:10px 12px;">
              <div style="font-size:10.5px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Typical Temp</div>
              <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">{{ $climateInfo['temperature'] ?? '—' }}</div>
            </div>
            <div style="background:rgba(217,119,6,0.06);border:1px solid rgba(217,119,6,0.14);border-radius:12px;padding:10px 12px;">
              <div style="font-size:10.5px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Humidity</div>
              <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">{{ $climateInfo['humidity'] ?? '—' }}</div>
            </div>
          </div>

          @if(!empty($climateInfo['recommendation']))
            <div style="background:rgba(20,61,35,0.05);border-radius:12px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:1px;"><path d="M12 2l9 4v6c0 5-3.5 8-9 10-5.5-2-9-5-9-10V6l9-4z"/><line x1="9" y1="12" x2="11" y2="14"/><line x1="11" y1="14" x2="15" y2="9"/></svg>
              <p style="margin:0;font-size:12.5px;color:#1e2922;line-height:1.5;"><strong>Seasonal guidance:</strong> {{ $climateInfo['recommendation'] }}</p>
            </div>
          @endif

          <a href="{{ route('farmer.quotations.request') }}" class="btn-primary" style="margin-top:14px;">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Plan & Request a Quotation
          </a>
        </div>
      </div>

      <!-- Farm Input Decision Support -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Farm Input Decision Support</h3>
            <p>Rule-based recommendations by crop, season &amp; land area</p>
          </div>
        </div>
        <div class="panel-body">
          @if($supportedCrops->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
              @foreach($supportedCrops as $crop)
                <span class="crop-chip"><span></span>{{ $crop }}</span>
              @endforeach
            </div>

            <p style="margin:0 0 14px 0;font-size:13px;color:#475569;line-height:1.6;">
              The system recommends seed, fertilizer, and supply quantities using <strong>Decision Formula</strong> rules — standard per-hectare rates adjusted for your crop, land area, and the season you select.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px;">
              <div style="background:rgba(2,132,199,0.05);border-radius:12px;padding:12px 14px;border:1px solid rgba(2,132,199,0.12);">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                  <svg viewBox="0 0 24 24" width="13" height="13" stroke="#0284c7" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                  <strong style="font-size:13px;color:#0284c7;">Wet Season</strong>
                </div>
                <p style="margin:0;font-size:12px;color:#475569;line-height:1.4;">Higher pest/fungus risk, flood-tolerant seed variants.</p>
              </div>
              <div style="background:rgba(217,119,6,0.05);border-radius:12px;padding:12px 14px;border:1px solid rgba(217,119,6,0.12);">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                  <svg viewBox="0 0 24 24" width="13" height="13" stroke="#d97706" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
                  <strong style="font-size:13px;color:#d97706;">Dry Season</strong>
                </div>
                <p style="margin:0;font-size:12px;color:#475569;line-height:1.4;">Drought-tolerant varieties, higher irrigation requirements.</p>
              </div>
            </div>

            <a href="{{ route('farmer.recommendations.index') }}" class="btn-ghost">View Recommendations <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
          @else
            <div style="text-align:center;padding:34px 16px;">
              <p style="font-size:13px;color:#64748b;margin:0;">No crop types registered in the Decision Formula rules yet.</p>
            </div>
          @endif
        </div>
      </div>
    </section>

    <!-- 4. STORES + WORKFLOW -->
    <section class="dash-grid">
      <!-- Stores directory -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Nearby Stores</h3>
            <p>Registered input suppliers</p>
          </div>
          <a href="{{ route('farmer.stores.index') }}" class="btn-ghost">View Directory <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="panel-body">
          @if($stores->isNotEmpty())
            @foreach($stores->take(5) as $store)
              <div class="store-card">
                <div class="store-avatar">{{ Str::upper(mb_substr($store->store_name ?? $store->name, 0, 1)) }}</div>
                <div style="flex:1;min-width:0;">
                  <b>{{ $store->store_name ?? $store->name }}</b>
                  <span class="store-meta">
                    {{ $store->products_count ?? $store->products->count() }} products
                    @if($store->city || $store->province) · {{ trim(implode(', ', array_filter([$store->city, $store->province]))) }}@endif
                  </span>
                </div>
                <span style="font-size:11px;font-weight:700;color:#16a34a;background:#f0fdf4;padding:4px 10px;border-radius:8px;">Open</span>
              </div>
            @endforeach
          @else
            <div style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No registered stores yet.</p>
            </div>
          @endif
        </div>
      </div>

      <!-- From Planning to Harvest -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>From Planning to Harvest</h3>
            <p>How the system supports your season</p>
          </div>
        </div>
        <div class="panel-body">
          <ol style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px;">
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Choose your crop &amp; season</strong><br>Select what you're planting and the season (Wet or Dry).</div>
            </li>
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Get recommended inputs</strong><br>The system applies Decision Formula rates for your land area.</div>
            </li>
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Request a cost quotation</strong><br>Itemized prices from the store, valid for 3–7 days.</div>
            </li>
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">4</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Store prepares your supplies</strong><br>The store owner computes prices and dispatches your order.</div>
            </li>
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">5</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Report your harvest</strong><br>Submit your yield so the association can compare and improve guidance.</div>
            </li>
          </ol>
          <a href="{{ route('farmer.quotations.request') }}" class="btn-primary" style="margin-top:16px;">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Start Planning Today
          </a>
        </div>
      </div>
    </section>

    <!-- 5. QUOTATION ACTIVITY -->
    <section class="dash-grid">
      <!-- Quotation activity -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Quotation Activity</h3>
            <p>Step-tracker of your requests</p>
          </div>
          <a href="{{ route('farmer.quotations.index') }}" class="btn-ghost">View All <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="panel-body">
          @if($recentQuotations->isNotEmpty())
            <div style="position:relative;">
              @foreach($recentQuotations as $qt)
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
                    @if($qt->storeOwner)
                      <br><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align:-1px;margin-right:2px;"><path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg> {{ $qt->storeOwner->store_name ?? $qt->storeOwner->name }}
                    @endif
                  </div>
                  @if($qt->estimated_total_cost > 0)
                    <div style="font-size:14px;font-weight:800;color:#0f172a;margin-top:5px;">₱{{ number_format($qt->estimated_total_cost, 2) }}</div>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <div style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No quotation requests yet.</p>
              <a href="{{ route('farmer.quotations.request') }}" class="btn-primary" style="margin-top:14px;">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Request a Quotation
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
    function setGreeting() {
      var h = new Date().getHours();
      var el = document.querySelector('[data-greeting]');
      if (!el) return;
      el.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
    }
    setGreeting();
    setInterval(setGreeting, 60000);

    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
      var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            ro.unobserve(entry.target);
          }
        });
      }, { threshold: 0.06 });
      revealEls.forEach(function (el) { ro.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }
  });
</script>
@endsection
