@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('content')
<style>
.glass-panel {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 18px;
  }
  .glass-panel:hover { box-shadow: 0 14px 44px rgba(0,0,0,0.09), 0 2px 8px rgba(0,0,0,0.04); }

  /* Breathing room for dashboards on wide screens */
  .dash-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    margin-bottom: 32px;
  }
  @media (max-width: 1024px) {
    .dash-grid-2col { grid-template-columns: 1fr; gap: 24px; }
  }
  .dash-grid-2col > .glass-panel { min-height: 100%; }
  
  .store-card-glass {
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 14px; 
    padding: 16px;
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  }
  .store-card-glass:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); background: rgba(255,255,255,0.8); border-color: rgba(255,255,255,0.4); }
  
  .step-item { position: relative; padding: 14px 0 14px 24px; border-bottom: 1px solid rgba(0,0,0,0.04); transition: all 0.3s ease; }
  .step-item:last-child { border-bottom: none; }
  .step-item:hover { padding-left: 28px; }
  .step-dot { position: absolute; left: -20px; top: 18px; width: 14px; height: 14px; border-radius: 50%; border: 3px solid; background: white; z-index: 2; transition: all 0.3s ease; animation: glow-pulse 2s ease-in-out infinite; }
  .step-item:hover .step-dot { transform: scale(1.25); }
  @keyframes glow-pulse { 0%,100% { box-shadow: 0 0 4px rgba(0,0,0,0.1); } 50% { box-shadow: 0 0 12px currentColor; } }
  
  .status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; transition: all 0.3s ease; }
  .status-pill:hover { transform: scale(1.05); }
  
  .micro-hover { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
  .micro-hover:hover { transform: scale(1.03); }
</style>

<!-- 1. HEADER -->
<div style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:26px;font-weight:800;color:#0f172a;margin:0 0 4px 0;letter-spacing:-0.02em;">Producer Hub</h1>
      <p style="color:#64748b;font-size:14px;margin:0;">Welcome back, <strong>{{ auth()->user()->name }}</strong>. Here's your farming overview.</p>
    </div>
    <a href="{{ route('farmer.quotations.request') }}" class="micro-hover" style="background:linear-gradient(135deg,#143d23,#1b5e3a);color:white;padding:11px 22px;border-radius:12px;text-decoration:none;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(20,61,35,0.25);">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New Quotation Request
    </a>
  </div>
</div>

<!-- 2. TOP: KPI METRICS -->
<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">
  <x-kpi-card title="Crop Varieties" value="{{ $totalCrops }}" trendValue="{{ $supportedCrops->count() }} types" trendDirection="up" trendLabel="supported crops" accent="#16a34a">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Recommended Products" value="{{ $recommendedCount }}" trendValue="{{ $activeProducts->count() }} active" trendDirection="up" trendLabel="in marketplace" accent="#7c3aed">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.75V17h8v-2.25A7 7 0 0 0 12 2z"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Quotation Requests" value="{{ $quotationCount }}" trendValue="{{ $pendingQuotations }} pending" trendDirection="{{ $pendingQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $dispatchedQuotations }} dispatched" accent="#d97706">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Partner Stores" value="{{ $storeCount }}" trendValue="{{ $stores->count() }} with inventory" trendDirection="up" trendLabel="available now" accent="#0284c7">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg></x-slot:icon>
  </x-kpi-card>
</section>

<!-- 3. SECOND ROW: CLIMATE & AGRONOMY WIDGETS -->
<div class="dash-grid-2col">
  <div style="display:flex;flex-direction:column;gap:20px;">
    <x-weather-widget :weatherData="$weatherData" :hourlyForecast="$hourlyForecast" />
    <x-climate-recommendation :weatherContext="$weatherContext" :supportedCrops="$supportedCrops" />
  </div>
  <div style="display:flex;flex-direction:column;gap:20px;">
    <x-soil-gauges :soilIndicators="$soilIndicators" />
    <x-climate-chart chartId="farmerClimateChart" />
  </div>
</div>

<!-- 4. THIRD ROW: QUOTATIONS & SUPPORTED CROPS -->
<div class="dash-grid-2col">
  <!-- Quotation Activity -->
  <div class="glass-panel" style="overflow:hidden;">
    <div style="padding:18px 20px;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h2 style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">Quotation Activity</h2>
        <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Step-tracker of your requests</p>
      </div>
      <a href="{{ route('farmer.quotations.index') }}" style="font-size:12px;font-weight:700;color:#143d23;text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(20,61,35,0.06);display:inline-flex;align-items:center;gap:4px;">View All <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div style="padding:4px 20px 4px 40px;">
      @if($recentQuotations->isNotEmpty())
        @foreach($recentQuotations as $qt)
          <div class="step-item">
            <div class="step-dot" style="border-color:{{ $qt->status_color }};color:{{ $qt->status_color }};"></div>
            @if(!$loop->last)<div style="position:absolute;left:-20px;top:32px;bottom:0;width:2px;background:{{ $qt->status_color }}22;"></div>@endif
            <div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-weight:700;font-size:13px;color:#0f172a;font-family:monospace;">{{ $qt->quotation_number }}</span>
                <span class="status-pill" style="background:{{ $qt->status_bg }};color:{{ $qt->status_color }};border:1px solid {{ $qt->status_color }}33;"><span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:{{ $qt->status_color }};"></span> {{ $qt->status_label }}</span>
              </div>
              <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $qt->crop_type }} · {{ ucfirst($qt->season) }} Season · {{ $qt->land_area }} ha{{ $qt->storeOwner ? ' · ' . ($qt->storeOwner->store_name ?? $qt->storeOwner->name) : '' }}</div>
              @if($qt->estimated_total_cost > 0)<div style="font-size:15px;font-weight:800;color:#0f172a;margin-top:4px;">₱{{ number_format($qt->estimated_total_cost, 2) }}</div>@endif
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align:center;padding:32px 16px;">
          <svg viewBox="0 0 24 24" width="32" height="32" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No quotation requests yet.</p>
          <a href="{{ route('farmer.quotations.request') }}" style="display:inline-block;margin-top:10px;font-size:13px;font-weight:700;color:#143d23;text-decoration:underline;">Request a Quotation →</a>
        </div>
      @endif
    </div>
  </div>

  <!-- Supported Crops & Formulas -->
  <div class="glass-panel" style="overflow:hidden;">
    <div style="padding:18px 20px;border-bottom:1px solid rgba(0,0,0,0.04);">
      <h2 style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">Supported Crops &amp; Formulas</h2>
      <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Decision Support System rules by season</p>
    </div>
    <div style="padding:18px 20px;">
      @if($supportedCrops->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
          @foreach($supportedCrops as $crop)
            <span class="micro-hover" style="background:rgba(22,163,74,0.08);color:#166534;font-size:13px;font-weight:600;padding:5px 14px;border-radius:20px;border:1px solid rgba(22,163,74,0.15);display:inline-flex;align-items:center;gap:4px;cursor:default;"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#16a34a;"></span> {{ $crop }}</span>
          @endforeach
        </div>
        <div style="font-size:13px;color:#475569;line-height:1.7;">
          <p style="margin:0 0 10px 0;">The system uses <strong>Decision Formula</strong> rules that define standard input rates per hectare for each crop and season combination.</p>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="background:rgba(2,132,199,0.06);border-radius:10px;padding:10px 14px;border:1px solid rgba(2,132,199,0.1);">
              <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;"><span style="font-size:14px;">🌧️</span> <strong style="font-size:13px;color:#0284c7;">Wet Season</strong></div>
              <p style="margin:0;font-size:12px;color:#475569;">Higher pest/fungus risk, flood-tolerant seed variants</p>
            </div>
            <div style="background:rgba(217,119,6,0.06);border-radius:10px;padding:10px 14px;border:1px solid rgba(217,119,6,0.1);">
              <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;"><span style="font-size:14px;">☀️</span> <strong style="font-size:13px;color:#d97706;">Dry Season</strong></div>
              <p style="margin:0;font-size:12px;color:#475569;">Drought-tolerant varieties, higher irrigation requirements</p>
            </div>
          </div>
        </div>
      @else
        <div style="text-align:center;padding:32px 16px;">
          <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No crop types registered in the Decision Formula rules yet.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- 5. FOURTH ROW: PARTNER STORES (FULL WIDTH) -->
<div class="glass-panel" style="overflow:hidden;margin-bottom:28px;">
  <div style="padding:18px 20px;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h2 style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">Partner Stores</h2>
      <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Registered supply nodes in your area</p>
    </div>
    <a href="{{ route('farmer.stores.index') }}" style="font-size:12px;font-weight:700;color:#143d23;text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(20,61,35,0.06);display:inline-flex;align-items:center;gap:4px;">All Stores <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
  </div>
  <div style="padding:16px;">
    @if($stores->isNotEmpty())
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:12px;">
        @foreach($stores->take(4) as $store)
          <div class="store-card-glass">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                  <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#143d23,#1b5e3a);display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:700;flex-shrink:0;">{{ substr($store->store_name, 0, 1) }}</div>
                  <div><h3 style="font-size:14px;font-weight:700;color:#0f172a;margin:0;">{{ $store->store_name }}</h3></div>
                </div>
                @if($store->address)
                  <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;"><svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none" style="display:inline;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ $store->address }}{{ $store->barangay ? ', ' . $store->barangay : '' }}{{ $store->city ? ', ' . $store->city : '' }}</p>
                @endif
              </div>
              <span class="status-pill" style="background:rgba(22,163,74,0.1);color:#16a34a;white-space:nowrap;flex-shrink:0;">{{ $store->products->count() }} items</span>
            </div>
            @if($store->contact_number)
              <div style="font-size:12px;color:#475569;margin-top:8px;"><svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none" style="display:inline;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> {{ $store->contact_number }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @else
      <div style="text-align:center;padding:32px 16px;background:rgba(255,255,255,0.5);border:1px dashed rgba(0,0,0,0.06);border-radius:12px;">
        <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/></svg>
        <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No registered stores with inventory yet.</p>
      </div>
    @endif
  </div>
</div>

@endsection