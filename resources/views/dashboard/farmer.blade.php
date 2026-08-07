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
    display: flex;
    flex-direction: column;
  }
  .glass-panel:hover { box-shadow: 0 14px 44px rgba(0,0,0,0.09), 0 2px 8px rgba(0,0,0,0.04); }

  /* Section grid layout fix */
  .dash-grid-2col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    align-items: start; /* Prevents height stretching between adjacent panels */
    gap: 24px;
    margin-bottom: 32px;
  }

  .glass-panel > .header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  .glass-panel > .body {
    padding: 20px 24px 24px;
  }

  /* Refactored Timeline Step Item */
  .step-item {
    position: relative;
    padding: 14px 0 14px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.25s ease;
  }
  .step-item:last-child { border-bottom: none; }
  .step-item:hover { transform: translateX(4px); }
  
  .step-dot {
    position: absolute;
    left: 0;
    top: 18px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2.5px solid;
    background: #ffffff;
    z-index: 2;
    transition: all 0.3s ease;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
  }

  /* Two-column layout for season cards */
  .season-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
  }

  .micro-hover { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
  .micro-hover:hover { transform: translateY(-2px); }

  @media (max-width: 640px) {
    .dash-grid-2col { grid-template-columns: 1fr; }
    .season-grid { grid-template-columns: 1fr; }
  }
</style>

<!-- 1. HEADER -->
<div style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
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
<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;">
  <x-kpi-card title="Crop Varieties" value="{{ $totalCrops }}" trendValue="{{ $supportedCrops->count() }} types" trendDirection="up" trendLabel="supported crops" accent="#16a34a">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Recommended Products" value="{{ $recommendedCount }}" trendValue="{{ $activeProducts->count() }} active" trendDirection="up" trendLabel="in marketplace" accent="#7c3aed">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.75V17h8v-2.25A7 7 0 0 0 12 2z"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Quotation Requests" value="{{ $quotationCount }}" trendValue="{{ $pendingQuotations }} pending" trendDirection="{{ $pendingQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $dispatchedQuotations }} dispatched" accent="#d97706">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
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
  <!-- Quotation Activity Panel -->
  <div class="glass-panel">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <div>
        <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;">Quotation Activity</h2>
        <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Step-tracker of your requests</p>
      </div>
      <a href="{{ route('farmer.quotations.index') }}" style="font-size:12px;font-weight:700;color:#143d23;text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(20,61,35,0.06);display:inline-flex;align-items:center;gap:4px;white-space:nowrap;">
        View All <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    <div class="body">
      @if($recentQuotations->isNotEmpty())
        <div style="position:relative;">
          @foreach($recentQuotations as $qt)
            <div class="step-item">
              <div class="step-dot" style="border-color:{{ $qt->status_color }};"></div>
              @if(!$loop->last)
                <div style="position:absolute;left:5px;top:28px;bottom:-14px;width:2px;background:rgba(0,0,0,0.06);"></div>
              @endif
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                  <span style="font-weight:700;font-size:13px;color:#0f172a;font-family:monospace;">{{ $qt->quotation_number }}</span>
                  <span class="status-pill" style="background:{{ $qt->status_bg }};color:{{ $qt->status_color }};border:1px solid {{ $qt->status_color }}33;">
                    <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background:{{ $qt->status_color }};"></span> {{ $qt->status_label }}
                  </span>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.4;">
                  <strong>{{ $qt->crop_type }}</strong> · {{ ucfirst($qt->season) }} Season · {{ $qt->land_area }} ha
                  @if($qt->storeOwner)
                    <br><span style="color:#475569;">📍 {{ $qt->storeOwner->store_name ?? $qt->storeOwner->name }}</span>
                  @endif
                </div>
                @if($qt->estimated_total_cost > 0)
                  <div style="font-size:14px;font-weight:800;color:#0f172a;margin-top:6px;">₱{{ number_format($qt->estimated_total_cost, 2) }}</div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div style="text-align:center;padding:28px 16px;">
          <svg viewBox="0 0 24 24" width="32" height="32" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No quotation requests yet.</p>
          <a href="{{ route('farmer.quotations.request') }}" style="display:inline-block;margin-top:8px;font-size:12px;font-weight:700;color:#143d23;text-decoration:underline;">Request a Quotation →</a>
        </div>
      @endif
    </div>
  </div>

  <!-- Supported Crops & Formulas Panel -->
  <div class="glass-panel">
    <div class="header">
      <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;">Supported Crops &amp; Formulas</h2>
      <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Decision Support System rules by season</p>
    </div>
    <div class="body">
      @if($supportedCrops->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
          @foreach($supportedCrops as $crop)
            <span style="background:rgba(22,163,74,0.08);color:#166534;font-size:12px;font-weight:600;padding:4px 12px;border-radius:16px;border:1px solid rgba(22,163,74,0.18);display:inline-flex;align-items:center;gap:6px;">
              <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;"></span> {{ $crop }}
            </span>
          @endforeach
        </div>

        <p style="margin:0 0 16px 0;font-size:13px;color:#475569;line-height:1.5;">
          The system applies <strong>Decision Formula</strong> rules specifying standard input rates per hectare based on crop selection and seasonal requirements:
        </p>

        <!-- Horizontal Season Grid -->
        <div class="season-grid">
          <div style="background:rgba(2,132,199,0.05);border-radius:12px;padding:12px 14px;border:1px solid rgba(2,132,199,0.12);">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
              <span style="font-size:14px;">🌧️</span>
              <strong style="font-size:13px;color:#0284c7;">Wet Season</strong>
            </div>
            <p style="margin:0;font-size:12px;color:#475569;line-height:1.4;">Higher pest/fungus risk, flood-tolerant seed variants.</p>
          </div>

          <div style="background:rgba(217,119,6,0.05);border-radius:12px;padding:12px 14px;border:1px solid rgba(217,119,6,0.12);">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
              <span style="font-size:14px;">☀️</span>
              <strong style="font-size:13px;color:#d97706;">Dry Season</strong>
            </div>
            <p style="margin:0;font-size:12px;color:#475569;line-height:1.4;">Drought-tolerant varieties, higher irrigation requirements.</p>
          </div>
        </div>
      @else
        <div style="text-align:center;padding:28px 16px;">
          <p style="font-size:13px;color:#64748b;margin:0;">No crop types registered in the Decision Formula rules yet.</p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection