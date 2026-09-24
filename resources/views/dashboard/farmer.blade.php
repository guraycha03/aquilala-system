@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'overview', 'label' => 'Overview',             'url' => route('dashboard.farmer')],
      ['key' => 'submit',   'label' => 'Submit Harvest Report', 'url' => route('farmer.harvests.create')],
    ],
    'activeTab' => 'overview',
  ])
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
  @media (min-width: 769px) {
    .dash-page > .dash-content { margin-top: 57px; }
  }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

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
  .hero-season { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 700; margin-top: 10px; padding: 5px 12px; border-radius: 20px; }
  .hero-season-wet { background: #dbeafe; color: #1d4ed8; }
  .hero-season-dry { background: #fef3c7; color: #b45309; }

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

  .crop-carousel { display:flex; gap:10px; overflow-x:auto; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; padding:2px 0 6px 0; cursor:grab; scrollbar-width:none; }
  .crop-carousel::-webkit-scrollbar { display:none; }
  .crop-carousel:active { cursor:grabbing; }
  .crop-carousel-card {
    scroll-snap-align:start; flex:0 0 110px; display:flex; flex-direction:column; align-items:center; gap:8px;
    padding:16px 10px 14px; background:#fafbfc; border:1px solid #e8eaed; border-radius:14px;
    transition: all 0.2s ease; user-select:none; -webkit-user-select:none;
  }
  .crop-carousel-card:hover { border-color:#c8e6c9; background:#f0fdf4; transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.06); }
  .crop-carousel-card:active { transform:scale(0.97); }
  .crop-carousel-icon {
    width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#f0fdf4,#dcfce7);
  }
  .crop-carousel-icon svg { width:18px; height:18px; stroke:#16a34a; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
  .crop-carousel-label { font-size:12px; font-weight:600; color:#1e2922; text-align:center; line-height:1.2; }
  .crop-scroll-hint { display:none; }

  @media (max-width: 768px) {
    .hero-band { padding: 22px; }
    .hero-title { font-size: 21px; }
    .crop-scroll-hint { display:flex; align-items:center; gap:4px; font-size:10px; color:#94a3b8; margin-bottom:8px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <!-- 1. HERO -->
    <section class="hero-band reveal in">
      <div class="hero-inner">
        <div>
          <div class="hero-eyebrow">Farmer Dashboard</div>
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
            Generate Quotation
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
      <x-kpi-card title="Crop Varieties" value="{{ $totalCrops }}" trendValue="{{ $supportedCrops->count() }} types" trendDirection="up" trendLabel="Supported Crops" accent="#eab308" link="{{ route('farmer.crops.index') }}">
        <x-slot:icon><img src="{{ asset('images/crop.png') }}" alt="Crop" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Recommended Products" value="{{ $recommendedCount }}" trendValue="{{ $activeProductCount }} active" trendDirection="up" trendLabel="In marketplace" accent="#16a34a" link="{{ route('farmer.products.browse') }}">
        <x-slot:icon><img src="{{ asset('images/product.png') }}" alt="Product" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Cost Quotations" value="{{ $quotationCount }}" trendValue="{{ $viewedQuotations }} seen by stores" trendDirection="{{ $viewedQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="{{ number_format($quotationValue, 2) }} total" accent="#7c3aed" link="{{ route('farmer.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Stores Directory" value="{{ $storeCount }}" trendValue="registered" trendDirection="neutral" trendLabel="Store Owners" accent="#d97706" link="{{ route('farmer.stores.index') }}">
        <x-slot:icon><img src="{{ asset('images/store.png') }}" alt="Store" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 3. SEASON & DECISION SUPPORT (hidden) -->
    <section class="dash-grid" style="display:none;">

      <!-- Current Season -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head" style="border-bottom:none;padding-bottom:0;">
          <div>
            <h3 style="font-size:16px;">Current Season</h3>
            <p style="margin-top:3px;">{{ $climateInfo['months'] ?? ($season === 'wet' ? 'June – January' : 'February – May') }}</p>
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
        <div class="panel-body" style="padding-top:8px;">
          @if(!empty($climateInfo['typical_weather']))
            <p style="margin:0 0 18px 0;font-size:13px;color:#64748b;line-height:1.6;">{{ $climateInfo['typical_weather'] }}</p>
          @endif

          <div style="display:flex;gap:0;margin-bottom:18px;border:1px solid #e8eaed;border-radius:14px;overflow:hidden;background:#fafbfc;">
            <div style="flex:1;padding:16px 18px;text-align:center;border-right:1px solid #e8eaed;">
              <div style="display:flex;align-items:center;justify-content:center;gap:5px;margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Months</span>
              </div>
              <div style="font-size:14px;font-weight:700;color:#1e293b;line-height:1.3;">{{ $climateInfo['months'] ?? ($season === 'wet' ? 'June – January' : 'February – May') }}</div>
            </div>
            <div style="flex:1;padding:16px 18px;text-align:center;border-right:1px solid #e8eaed;">
              <div style="display:flex;align-items:center;justify-content:center;gap:5px;margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#f59e0b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                <span style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Temp</span>
              </div>
              <div style="font-size:14px;font-weight:700;color:#1e293b;line-height:1.3;">{{ $climateInfo['temperature'] ?? '—' }}</div>
            </div>
            <div style="flex:1;padding:16px 18px;text-align:center;">
              <div style="display:flex;align-items:center;justify-content:center;gap:5px;margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#0ea5e9" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                <span style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Humidity</span>
              </div>
              <div style="font-size:14px;font-weight:700;color:#1e293b;line-height:1.3;">{{ $climateInfo['humidity'] ?? '—' }}</div>
            </div>
          </div>

          @if(!empty($climateInfo['recommendation']))
            <div style="background:#f0fdf4;border:1px solid #dcfce7;border-radius:12px;padding:14px 16px;display:flex;gap:11px;align-items:flex-start;">
              <div style="width:28px;height:28px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <div>
                <div style="font-size:11px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">Seasonal Guidance</div>
                <p style="margin:0;font-size:12.5px;color:#3f5144;line-height:1.55;">{{ $climateInfo['recommendation'] }}</p>
              </div>
            </div>
          @endif

        </div>
      </div>

      <!-- Farm Input Decision Support -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head" style="border-bottom:none;padding-bottom:0;">
          <div>
            <h3 style="font-size:16px;">Decision Support</h3>
            <p style="margin-top:3px;">Per-hectare input rates by crop &amp; season</p>
          </div>
        </div>
        <div class="panel-body" style="padding-top:8px;">
          @if($supportedCrops->isNotEmpty())
            <!-- Supported Crops -->
            <div style="margin-bottom:18px;">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Supported Crops</div>
                <div class="crop-scroll-hint">
                  <svg viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                  Swipe to browse
                  <svg viewBox="0 0 24 24" width="10" height="10" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </div>
              </div>
              <div class="crop-carousel" id="cropCarousel">
                @php
                  $cropImages = [
                    'rice' => 'crops/rice.png',
                    'palay' => 'crops/rice.png',
                    'corn' => 'crops/corn.png',
                    'maize' => 'crops/corn.png',
                    'mango' => 'crops/mango.png',
                    'banana' => 'crops/banana.png',
                    'eggplant' => 'crops/eggplant.png',
                    'talong' => 'crops/eggplant.png',
                    'chili' => 'crops/chili.png',
                    'pepper' => 'crops/capsicum.png',
                    'siling labuyo' => 'crops/chili.png',
                    'cacao' => 'crops/cacao.png',
                    'pineapple' => 'crops/pineapple.png',
                    'watermelon' => 'crops/watermelon.png',
                    'sugarcane' => 'crops/sugar-cane.png',
                    'calamansi' => 'crops/lemon.png',
                    'lemon' => 'crops/lemon.png',
                    'wheat' => 'crops/wheat.png',
                    'orange' => 'crops/orange.png',
                  ];
                  $defaultCropImage = 'images/crop.png';
                @endphp
                @php $shownCrops = $supportedCrops->take(10); @endphp
                @foreach($shownCrops as $crop)
                  @php
                    $key = strtolower($crop);
                    $imgPath = $cropImages[$key] ?? null;
                    $imgSrc = $imgPath ? "images/{$imgPath}" : $defaultCropImage;
                  @endphp
                  <div class="crop-carousel-card">
                    <div class="crop-carousel-icon" style="background:transparent;display:flex;align-items:center;justify-content:center;">
                      <img src="{{ asset($imgSrc) }}" alt="{{ $crop }}" style="width:36px;height:36px;object-fit:contain;">
                    </div>
                    <span class="crop-carousel-label">{{ $crop }}</span>
                  </div>
                @endforeach
              </div>
              <a href="{{ route('farmer.crops-directory.index') }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:6px 14px;border-radius:10px;background:rgba(20,61,35,0.06);font-size:12px;font-weight:700;color:#14532d;text-decoration:none;transition:all 0.2s;">
                View All {{ $supportedCrops->count() }} Crops
                <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </a>
            </div>

            <!-- How it works -->
            <div style="background:#f8faf9;border:1px solid #e8eaed;border-radius:12px;padding:14px 16px;margin-bottom:18px;">
              <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">How It Works</div>
              <div style="display:flex;gap:6px;align-items:center;">
                <div style="display:flex;align-items:center;gap:6px;flex:1;">
                  <div style="width:20px;height:20px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#0284c7;flex-shrink:0;">1</div>
                  <span style="font-size:11.5px;color:#475569;white-space:nowrap;">Pick crop</span>
                </div>
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="#cbd5e1" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                <div style="display:flex;align-items:center;gap:6px;flex:1;">
                  <div style="width:20px;height:20px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#d97706;flex-shrink:0;">2</div>
                  <span style="font-size:11.5px;color:#475569;white-space:nowrap;">Set area</span>
                </div>
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="#cbd5e1" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                <div style="display:flex;align-items:center;gap:6px;flex:1;">
                  <div style="width:20px;height:20px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#16a34a;flex-shrink:0;">3</div>
                  <span style="font-size:11.5px;color:#475569;white-space:nowrap;">Get rates</span>
                </div>
              </div>
            </div>

            <!-- Season Comparison -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;">
              <div style="padding:14px 14px 12px;border-radius:12px;background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:1px solid #bfdbfe;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="#3b82f6" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                  <span style="font-size:12px;font-weight:700;color:#1e40af;">Wet Season</span>
                </div>
                <p style="margin:0;font-size:11.5px;color:#475569;line-height:1.5;">Pest &amp; fungus risk. Flood-tolerant seed variants recommended.</p>
              </div>
              <div style="padding:14px 14px 12px;border-radius:12px;background:linear-gradient(135deg,#fffbeb,#fefce8);border:1px solid #fde68a;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="#d97706" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
                  <span style="font-size:12px;font-weight:700;color:#92400e;">Dry Season</span>
                </div>
                <p style="margin:0;font-size:11.5px;color:#475569;line-height:1.5;">Drought-tolerant varieties. Higher irrigation requirements.</p>
              </div>
            </div>

            <a href="{{ route('farmer.recommendations.index') }}" class="btn-ghost" style="width:100%;justify-content:center;">View Full Recommendations <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
          @else
            <div style="text-align:center;padding:34px 16px;">
              <p style="font-size:13px;color:#64748b;margin:0;">No crop types registered in the Decision Formula rules yet.</p>
            </div>
          @endif
        </div>
      </div>
    </section>

    <!-- 4. WORKFLOW (hidden) -->
    <section class="dash-grid" style="display:none;">
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
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Generate a cost quotation</strong><br>Get instant itemized costs for your land area, valid for 14 days.</div>
            </li>
            <li style="display:flex;gap:12px;align-items:flex-start;">
              <span style="width:26px;height:26px;border-radius:50%;background:rgba(22,163,74,0.1);color:#16a34a;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">4</span>
              <div style="font-size:13px;color:#475569;line-height:1.5;"><strong style="color:#0f172a;">Stores see your demand</strong><br>Store owners view your quotation as demand intel — no approval needed.</div>
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
            <p>Instantly generated cost estimates</p>
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
                    @if($qt->items->count() > 0)
                      <br><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align:-1px;margin-right:2px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg> {{ $qt->items->count() }} input lines
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
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No cost quotations yet.</p>
              <a href="{{ route('farmer.quotations.request') }}" class="btn-primary" style="margin-top:14px;">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Generate a Quotation
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

    /* Crop carousel — drag to scroll (mouse + touch) */
    var carousel = document.getElementById('cropCarousel');
    if (carousel) {
      var isDrag = false, startX, scrollLeft;
      carousel.addEventListener('mousedown', function (e) {
        isDrag = true;
        carousel.style.cursor = 'grabbing';
        startX = e.pageX - carousel.offsetLeft;
        scrollLeft = carousel.scrollLeft;
      });
      carousel.addEventListener('mouseleave', function () { isDrag = false; carousel.style.cursor = 'grab'; });
      carousel.addEventListener('mouseup', function () { isDrag = false; carousel.style.cursor = 'grab'; });
      carousel.addEventListener('mousemove', function (e) {
        if (!isDrag) return;
        e.preventDefault();
        var x = e.pageX - carousel.offsetLeft;
        carousel.scrollLeft = scrollLeft - (x - startX) * 1.5;
      });
    }
  });
</script>
@endsection
