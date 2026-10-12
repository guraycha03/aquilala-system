@extends('layouts.sidebar')

@section('title', 'Cost Quotation ' . $quotation->quotation_number)

@section('content')
@php
  $total      = (float) $quotation->estimated_total_cost;
  $landArea   = (float) $quotation->land_area;
  $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;
  $daysLeft   = $quotation->valid_until ? (int) max(0, now()->diffInDays($quotation->valid_until, false)) : null;
  $effStatus  = $quotation->effective_status;
  $storeOwners = $quotation->items->pluck('product.storeOwner')->filter()->unique('id');
@endphp
<style>
  .page-header-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #e0f7ea 100%);
    border: 1px solid #bbf7d0;
    border-radius: 18px;
    padding: 26px 30px;
    margin-bottom: 22px;
  }

  .status-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 16px; border-radius: 24px;
    font-size: 13px; font-weight: 700; letter-spacing: 0.01em;
  }
  .status-pill.computed { background: #eff6ff; color: #1d4ed8; }
  .status-pill.viewed   { background: #f5f3ff; color: #6d28d9; }
  .status-pill.expired  { background: #f5f5f5; color: #9ca3af; }
  .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

  .btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    background: linear-gradient(135deg, #143d23, #1a5230);
    color: #fff; text-decoration: none; cursor: pointer;
    padding: 11px 20px; border-radius: 12px;
    font-size: 13.5px; font-weight: 700; font-family: inherit; border: none;
    box-shadow: 0 3px 10px rgba(20,61,35,0.28);
    transition: all 0.2s ease;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(20,61,35,0.35); }
  .btn-primary:active { transform: scale(0.98); }
  .btn-primary svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    background: #ffffff; color: #166534; text-decoration: none; cursor: pointer;
    padding: 10px 16px; border-radius: 12px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    border: 1px solid #bbf7d0;
    transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: #f0fdf4; }
  .btn-ghost svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }

  .delete-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; background: #faf2f1; border: 1px solid #e4c6c3;
    border-radius: 12px; color: #b0403a; font-size: 13px; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: all 0.15s;
  }
  .delete-btn:hover { background: #f5e5e3; border-color: #d8a49f; transform: translateY(-1px); }
  .delete-btn:active { transform: scale(0.98); }
  .delete-btn svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2; fill: none; }

  .panel-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 18px rgba(0,0,0,0.03);
    border: 1px solid #eef1ef;
    overflow: hidden;
    margin-bottom: 20px;
  }
  .panel-head {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f2f1;
    background: #fafdfb;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
  }
  .panel-body { padding: 22px 24px; }

  .info-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em; color: #879a8c; margin-bottom: 4px;
  }
  .info-value { font-weight: 700; font-size: 15px; color: #1a2b1e; }

  /* Total spotlight — merged into header card */
  .header-total {
    margin-top: 22px;
    background: linear-gradient(135deg, #14532d 0%, #166534 55%, #1a7a42 100%);
    border-radius: 14px;
    padding: 22px 26px;
    color: #fff;
    position: relative; overflow: hidden;
    box-shadow: 0 10px 30px rgba(20,83,45,0.18);
  }
  .header-total::before {
    content: ""; position: absolute; top: -80px; right: -60px; width: 240px; height: 240px;
    border-radius: 50%; background: rgba(255,255,255,0.08);
  }
  .header-total::after {
    content: ""; position: absolute; bottom: -90px; left: 30%; width: 200px; height: 200px;
    border-radius: 50%; background: rgba(110,235,47,0.12);
  }
  .spot-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 22px; flex-wrap: wrap; }
  .spot-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.72); margin-bottom: 6px; }
  .spot-value { font-size: 40px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.05; }
  .spot-sub { font-size: 13px; color: rgba(255,255,255,0.85); margin-top: 6px; }
  .spot-stats { display: flex; gap: 10px; flex-wrap: wrap; }
  .spot-stat {
    background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);
    border-radius: 14px; padding: 13px 18px; min-width: 118px;
  }
  .spot-stat b { display: block; font-size: 18px; font-weight: 800; }
  .spot-stat span { font-size: 11px; color: rgba(255,255,255,0.78); text-transform: uppercase; letter-spacing: 0.05em; }

  /* How-it-works */
  .how-step { display: flex; gap: 14px; align-items: flex-start; padding: 10px 0; }
  .how-step + .how-step { border-top: 1px dashed #eef1ef; }
  .how-badge {
    width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
  }
  .how-badge.green { background: #dcfce7; color: #166534; }
  .how-badge.blue  { background: #dbeafe; color: #1d4ed8; }
  .how-badge.amber { background: #fef3c7; color: #b45309; }
  .how-badge.violet{ background: #ede9fe; color: #6d28d9; }
  .how-title { font-size: 13.5px; font-weight: 700; color: #1a2b1e; }
  .how-desc { font-size: 13px; color: #51605a; line-height: 1.6; margin-top: 3px; }

  .example-box {
    background: #f8faf9; border: 1px solid #e6ece7; border-radius: 14px;
    padding: 16px 18px; font-family: 'SF Mono', ui-monospace, monospace;
    font-size: 13px; line-height: 1.9; color: #33463a;
  }
  .example-box strong { color: #14532d; }

  .store-chip {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px;
    border: 1px solid #eef1ef; transition: all 0.15s;
  }
  .store-chip:hover { background: #f8faf9; border-color: #dbe4de; }

  .info-banner {
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex; align-items: flex-start; gap: 14px;
  }

  /* AI Overview */
  .ai-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: #ffffff; color: #6d28d9; border: 1px solid #ddd6fe; cursor: pointer;
    padding: 9px 14px; border-radius: 10px;
    font-size: 12.5px; font-weight: 700; font-family: inherit;
    transition: all 0.2s ease;
  }
  .ai-btn:hover:not(:disabled) { background: #faf5ff; }
  .ai-btn:disabled { opacity: 0.6; cursor: wait; }
  .ai-spin { width: 13px; height: 13px; border: 2px solid rgba(109,40,217,0.25); border-top-color: #6d28d9; border-radius: 50%; animation: aiSpin 0.7s linear infinite; }
  @keyframes aiSpin { to { transform: rotate(360deg); } }

  .ai-source-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px;
  }
  .ai-source-badge.ai { background: #ede9fe; color: #6d28d9; }
  .ai-source-badge.fallback { background: #f0fdf4; color: #15803d; }

  .ai-narrative {
    font-size: 14px; color: #3b3b40; line-height: 1.75;
  }
  .ai-narrative p { margin: 8px 0 0 0; }
  .ai-narrative ul, .ai-narrative ol { margin: 10px 0 0 18px; padding: 0; }
  .ai-narrative li { margin-bottom: 7px; line-height: 1.6; }
  .ai-narrative strong { color: #5b21b6; font-weight: 700; }
  .ai-narrative h1, .ai-narrative h2, .ai-narrative h3, .ai-narrative h4 {
    margin: 18px 0 6px; font-weight: 700; color: #4c1d95; line-height: 1.35;
  }
  .ai-narrative h1 { font-size: 16px; }
  .ai-narrative h2 { font-size: 15.5px; }
  .ai-narrative h3 { font-size: 15px; }
  .ai-narrative h4 { font-size: 14.5px; }
  .ai-narrative > *:first-child { margin-top: 0; }
  .ai-narrative table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
  .ai-narrative th { background: #f3e8ff; text-align: left; font-weight: 700; color: #4c1d95; }
  .ai-narrative th, .ai-narrative td { border: 1px solid #e9d5ff; padding: 7px 9px; }

  .ai-loading { display: flex; gap: 14px; padding: 6px 2px; }
  .ai-loading .shimmer-lines { flex: 1; }
  .shimmer-line {
    height: 12px; border-radius: 6px; margin-bottom: 10px;
    background: linear-gradient(90deg, #f1f0f5 25%, #e5e2f0 50%, #f1f0f5 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
  }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

  .ai-error {
    background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
    border-radius: 10px; padding: 12px 14px; font-size: 12.5px; line-height: 1.5;
    display: flex; gap: 8px; align-items: flex-start;
  }

  .two-col { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 20px; align-items: start; }
  @media (max-width: 1000px) { .two-col { grid-template-columns: 1fr; } }

  .fact-row { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; padding: 11px 0; }
  .fact-row + .fact-row { border-top: 1px dashed #f0f2f1; }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

<!-- Header -->
<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px;">
        <span class="status-pill {{ $effStatus }}">
          <span class="dot"></span>
          {{ $quotation->status_label }}
        </span>
        <span style="font-size: 12px; color: #879a8c;">
          @if($quotation->valid_until)
            @if($effStatus === 'expired')
              Expired {{ $quotation->valid_until->format('M d, Y') }}
            @elseif(is_numeric($daysLeft))
              Valid until {{ $quotation->valid_until->format('M d, Y') }} &middot; {{ $daysLeft }} day(s) left
            @else
              Valid until {{ $quotation->valid_until->format('M d, Y') }}
            @endif
          @else
            No expiration set
          @endif
        </span>
      </div>
      <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em; font-family: 'SF Mono', ui-monospace, monospace;">
        {{ $quotation->quotation_number }}
      </h1>
      <p style="color: #51605a; font-size: 14px; margin: 0; line-height: 1.5;">
        <strong style="text-transform: capitalize; color: #143d23;">{{ $quotation->crop_type }}</strong>
        &middot; {{ ucfirst($quotation->season) }} Season &middot; {{ number_format($landArea, 2) }} ha
        &middot; generated {{ $quotation->created_at->format('M d, Y \a\t g:i A') }}
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <a href="{{ route('farmer.quotations.breakdown', $quotation) }}" class="btn-primary">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Full Itemized Breakdown
      </a>
      <form action="{{ route('farmer.quotations.destroy', $quotation) }}" method="POST"
            data-aqui-confirm="Are you sure you want to delete this cost quotation? This cannot be undone."
            data-aqui-danger data-aqui-confirm-label="Delete" style="margin: 0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn">
          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
  <div class="header-total">
    <div class="spot-inner">
      <div>
        <div class="spot-label">Estimated Total Cost</div>
        <div class="spot-value">₱{{ number_format($total, 2) }}</div>
        <div class="spot-sub">≈ ₱{{ number_format($perHectare, 2) }} per hectare &middot; based on current store prices</div>
      </div>
      <div class="spot-stats">
        <div class="spot-stat"><b>{{ number_format($landArea, 2) }} ha</b><span>Land Area</span></div>
        <div class="spot-stat"><b>{{ $quotation->items->count() }}</b><span>Line Items</span></div>
        <div class="spot-stat"><b>{{ max(1, $storeOwners->count()) }}</b><span>Store(s)</span></div>
      </div>
    </div>
  </div>
</div>

<!-- AI Overview (auto-loaded) -->
<div class="panel-card">
  <div class="panel-head">
    <div>
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">AI Farm Advisor</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">A plain-language explanation of what your quotation means</p>
    </div>
    <button type="button" class="ai-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
      <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      <span>Regenerate</span>
    </button>
  </div>
  <div class="panel-body" id="aiOverviewPanel">
    <div id="aiNarrative" class="ai-loading">
      <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #f5f3ff, #ede9fe); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #ddd6fe;">
        <svg viewBox="0 0 24 24" width="17" height="17" stroke="#6d28d9" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      </div>
      <div class="shimmer-lines">
        <div class="shimmer-line" style="width: 42%;"></div>
        <div class="shimmer-line" style="width: 96%;"></div>
        <div class="shimmer-line" style="width: 88%;"></div>
        <div class="shimmer-line" style="width: 74%;"></div>
      </div>
    </div>
  </div>
</div>

<div class="two-col">
  <!-- Plan at a glance -->
  <div class="panel-card" style="margin-bottom: 0;">
    <div class="panel-head">
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Your Plan at a Glance</h2>
    </div>
    <div class="panel-body" style="padding-top: 8px; padding-bottom: 8px;">
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Crop Type</span>
        <span style="font-weight: 700; color: #1a2b1e; text-transform: capitalize;">{{ $quotation->crop_type }}</span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Season</span>
        <span style="font-weight: 700; color: #1a2b1e; display: flex; align-items: center; gap: 7px;">
          @if($quotation->season === 'wet')
            <span style="width: 9px; height: 9px; background: linear-gradient(135deg, #38bdf8, #0284c7); border-radius: 50%;"></span> Wet Season
          @else
            <span style="width: 9px; height: 9px; background: linear-gradient(135deg, #fbbf24, #d97706); border-radius: 50%;"></span> Dry Season
          @endif
        </span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Land Area</span>
        <span style="font-weight: 700; color: #1a2b1e;">{{ number_format($landArea, 2) }} hectares</span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Cost per Hectare</span>
        <span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($perHectare, 2) }}</span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Selected Inputs</span>
        <span style="font-weight: 700; color: #1a2b1e;">{{ $quotation->items->count() }} item(s)</span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Generated On</span>
        <span style="font-weight: 700; color: #1a2b1e;">{{ $quotation->created_at->format('M d, Y') }}</span>
      </div>
      <div class="fact-row">
        <span style="font-size: 13px; color: #51605a;">Valid Until</span>
        <span style="font-weight: 700; color: {{ $effStatus === 'expired' ? '#dc2626' : '#166534' }};">
          @if($quotation->valid_until)
            {{ $quotation->valid_until->format('M d, Y') }}
            @if($effStatus === 'expired')
              (expired)
            @elseif(is_numeric($daysLeft))
              ({{ $daysLeft }} day(s) left)
            @endif
          @else
            No expiration
          @endif
        </span>
      </div>
      @if($quotation->notes)
        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #f0f2f1;">
          <div class="info-label">Your Notes</div>
          <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #374138; line-height: 1.5;">{{ $quotation->notes }}</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Supply stores -->
  <div>
    <div class="panel-card" style="margin-bottom: 0;">
      <div class="panel-head">
        <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Supply Stores</h2>
        <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Where your inputs come from</p>
      </div>
      <div class="panel-body" style="padding-top: 16px;">
        @if($storeOwners->count() > 0)
          @foreach($storeOwners as $store)
            <div class="store-chip" style="{{ !$loop->last ? 'margin-bottom: 10px;' : '' }}">
              <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; border: 1px solid #bfdbfe;">
                @php $w = explode(' ', trim($store->name)); echo count($w) >= 2 ? mb_substr($w[0],0,1).mb_substr(end($w),0,1) : mb_substr($store->name,0,2); @endphp
              </div>
              <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; color: #1a2b1e; font-size: 13.5px;">{{ $store->name }}</div>
                <div style="font-size: 12px; color: #879a8c;">{{ $quotation->items->where('product.user_id', $store->id)->count() }} product(s) &middot; ₱{{ number_format($quotation->items->where('product.user_id', $store->id)->sum('subtotal'), 2) }}</div>
              </div>
            </div>
          @endforeach
        @else
          <p style="color: #94a3b8; font-size: 13.5px; margin: 0;">Products from the general catalog were used.</p>
        @endif
      </div>
    </div>

    <div class="panel-card" style="margin-bottom: 0; margin-top: 20px;">
      <div class="panel-head">
        <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Full Itemized Breakdown</h2>
        <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Every line of your quotation</p>
      </div>
      <div class="panel-body">
        <p style="font-size: 13.5px; color: #51605a; margin: 0 0 16px 0; line-height: 1.6;">
          See the exact quantity, unit price, and subtotal for each of your selected inputs.
        </p>
        <a href="{{ route('farmer.quotations.breakdown', $quotation) }}" class="btn-ghost" style="width: 100%; justify-content: center;">
          View the full line-by-line breakdown
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Info banner -->
<div class="info-banner">
  <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
  </div>
  <div>
    <div style="font-weight: 700; font-size: 14px; color: #166534; margin-bottom: 4px;">This is a cost quotation, not a purchase order</div>
    <p style="margin: 0; font-size: 13px; color: #15803d; line-height: 1.6;">
      Prices are the current store prices at the time of generation and may change. Store owners can see this as demand intelligence.
      Contact the store directly to finalize any purchase.
    </p>
  </div>
</div>

<script>
  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return t
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
      .replace(/\u2014/g, '&mdash;');
  }
  function renderAiMarkdown(raw) {
    raw = String(raw || '').replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
    var lines = escapeAiHtml(raw).replace(/\r/g, '').split('\n');
    var html = '', inUl = false, inOl = false;
    function closeUl() { if (inUl) { html += '</ul>'; inUl = false; } }
    function closeOl() { if (inOl) { html += '</ol>'; inOl = false; } }
    function renderTable(buf) {
      var rows = buf.map(function (r) { return r.slice(1, -1).split('|').map(function (c) { return c.trim(); }); })
        .filter(function (cells) { return !cells.every(function (c) { return /^:?-+:?$/.test(c); }); });
      if (!rows.length) return;
      html += '<div class="table-wrap" style="overflow-x:auto;"><table>';
      var header = rows.shift();
      html += '<thead><tr>' + header.map(function (c) { return '<th>' + inlineAi(c) + '</th>'; }).join('') + '</tr></thead><tbody>';
      rows.forEach(function (cells) {
        html += '<tr>' + cells.map(function (c) { return '<td>' + inlineAi(c) + '</td>'; }).join('') + '</tr>';
      });
      html += '</tbody></table></div>';
    }
    for (var i = 0; i < lines.length; i++) {
      var trimmed = lines[i].trim();
      if (!trimmed) { closeUl(); closeOl(); continue; }
      if (trimmed.charAt(0) === '|' && trimmed.charAt(trimmed.length - 1) === '|') {
        closeUl(); closeOl();
        var buf = [];
        while (i < lines.length) {
          var t = lines[i].trim();
          if (t.charAt(0) === '|' && t.charAt(t.length - 1) === '|') { buf.push(t); i++; } else break;
        }
        i--;
        renderTable(buf);
        continue;
      }
      var hMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
      if (hMatch) {
        closeUl(); closeOl();
        var lvl = hMatch[1].length;
        html += '<h' + lvl + '>' + inlineAi(hMatch[2]) + '</h' + lvl + '>';
        continue;
      }
      if (/^[-*]\s+/.test(trimmed)) {
        if (!inUl) { closeOl(); html += '<ul>'; inUl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^[-*]\s+/, '')) + '</li>';
        continue;
      } else { closeUl(); }
      if (/^\d+[.)]\s+/.test(trimmed)) {
        if (!inOl) { closeUl(); html += '<ol>'; inOl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^\d+[.)]\s+/, '')) + '</li>';
        continue;
      } else { closeOl(); }
      html += '<p>' + inlineAi(trimmed) + '</p>';
    }
    closeUl(); closeOl();
    return html;
  }

  var aiLoading = false;

  function renderAiOverview(data) {
    var container = document.getElementById('aiNarrative');
    if (data && typeof data === 'object' && data.error) {
      container.className = 'ai-error';
      container.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>' + escapeAiHtml(data.error) + ' <button onclick="generateAiOverview(document.getElementById(\'aiOverviewBtn\'))" style="background:none;border:none;padding:0;color:#b91c1c;font-weight:700;cursor:pointer;text-decoration:underline;">Try again</button></span>';
      return;
    }
    var source = (data && data.source === 'ai')
      ? '<span class="ai-source-badge ai"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>AI assisted</span>'
      : '<span class="ai-source-badge fallback"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>Smart summary (AI offline)</span>';
    container.className = 'ai-narrative';
    container.innerHTML =
      '<div style="margin-bottom: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">' + source +
      '<span style="font-size: 11.5px; color: #879a8c;">Generated from your quotation data &mdash; it never invents numbers.</span></div>' +
      '<div>' + renderAiMarkdown(data.overview || '') + '</div>';
  }

  function setAiBusy(busy) {
    aiLoading = busy;
    var btn = document.getElementById('aiOverviewBtn');
    if (btn) {
      btn.disabled = busy;
      var label = btn.querySelector('span');
      if (label) label.textContent = busy ? 'Preparing\u2026' : 'Regenerate';
    }
  }

  function showAiLoading() {
    var container = document.getElementById('aiNarrative');
    container.className = 'ai-loading';
    container.innerHTML =
      '<div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #f5f3ff, #ede9fe); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #ddd6fe;">' +
      '<svg viewBox="0 0 24 24" width="17" height="17" stroke="#6d28d9" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg></div>' +
      '<div class="shimmer-lines">' +
      '<div class="shimmer-line" style="width: 42%;"></div>' +
      '<div class="shimmer-line" style="width: 96%;"></div>' +
      '<div class="shimmer-line" style="width: 88%;"></div>' +
      '<div class="shimmer-line" style="width: 74%;"></div>' +
      '</div>';
  }

  function generateAiOverview(btn) {
    if (aiLoading) return;
    setAiBusy(true);
    showAiLoading();

    fetch('{{ route('farmer.quotations.ai-overview', $quotation) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: '{}'
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      setAiBusy(false);
      if (res.ok && res.data && res.data.overview) {
        renderAiOverview(res.data);
      } else if (res.data && res.data.error) {
        renderAiOverview({ error: res.data.error });
      } else {
        renderAiOverview({ error: 'The AI overview could not be generated right now. Please try again.' });
      }
    })
    .catch(function () {
      setAiBusy(false);
      renderAiOverview({ error: 'Connection failed. Please try again.' });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    generateAiOverview(document.getElementById('aiOverviewBtn'));
  });
</script>
@endsection