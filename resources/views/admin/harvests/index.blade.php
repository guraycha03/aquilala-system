@extends('layouts.sidebar')

@section('title', 'Harvest Performance')

@section('content')
@php
  $periods = $context['periods'] ?? [];
  $recent  = $context['recent_vs_previous'] ?? null;
  $leaders = array_slice($context['top_farmers_by_reports'] ?? [], 0, 5);

  $seasonByLabel = collect($periods)->mapWithKeys(fn (array $p) => [$p['label'] => $p['season']]);

  // Harvest graph periods (same data as admin dashboard)
  $harvestGraphYear  = $context['harvestGraphYear'] ?? [];
  $harvestGraphMonth = $context['harvestGraphMonth'] ?? [];
  $harvestGraphWeek  = $context['harvestGraphWeek'] ?? [];
  $harvestGraphDays  = $context['harvestGraphDays'] ?? [];

  // Prepare chart data (mirroring visual-report.blade.php)
  $byYear = [];
  foreach ($periods as $p) {
    $y = (int) $p['year'];
    if (!isset($byYear[$y])) {
      $byYear[$y] = ['year' => $y, 'wet_yield_mt' => 0, 'wet_area_ha' => 0, 'wet_expenses_php' => 0, 'dry_yield_mt' => 0, 'dry_area_ha' => 0, 'dry_expenses_php' => 0, 'total_yield_mt' => 0, 'total_area_ha' => 0, 'total_expenses_php' => 0];
    }
    $byYear[$y]['total_yield_mt'] += (float) $p['yield_tons'];
    $byYear[$y]['total_area_ha'] += (float) $p['area_ha'];
    $byYear[$y]['total_expenses_php'] += (float) $p['expenses_php'];
    $season = strtolower($p['season'] ?? '');
    if ($season === 'wet') {
      $byYear[$y]['wet_yield_mt'] += (float) $p['yield_tons'];
      $byYear[$y]['wet_area_ha'] += (float) $p['area_ha'];
      $byYear[$y]['wet_expenses_php'] += (float) $p['expenses_php'];
    } elseif ($season === 'dry') {
      $byYear[$y]['dry_yield_mt'] += (float) $p['yield_tons'];
      $byYear[$y]['dry_area_ha'] += (float) $p['area_ha'];
      $byYear[$y]['dry_expenses_php'] += (float) $p['expenses_php'];
    }
  }
  ksort($byYear);
  $years = array_values($byYear);

  $byCrop = collect($context['by_crop'] ?? [])->values()->all();

  $wetTotalYield = collect($years)->sum('wet_yield_mt');
  $dryTotalYield = collect($years)->sum('dry_yield_mt');
  $totalYieldAll = $wetTotalYield + $dryTotalYield;
  $wetShare = $totalYieldAll > 0 ? round(($wetTotalYield / $totalYieldAll) * 100) : 0;
  $dryShare = 100 - $wetShare;

  $wetArea = collect($years)->sum('wet_area_ha');
  $dryArea = collect($years)->sum('dry_area_ha');
  $wetRecords = collect($periods)->where('season', 'wet')->sum('reports');
  $dryRecords = collect($periods)->where('season', 'dry')->sum('reports');
  $wetYieldPerHa = $wetArea > 0 ? round($wetTotalYield / $wetArea, 2) : null;
  $dryYieldPerHa = $dryArea > 0 ? round($dryTotalYield / $dryArea, 2) : null;
  $wetExpenses = collect($years)->sum('wet_expenses_php');
  $dryExpenses = collect($years)->sum('dry_expenses_php');
  $wetCostPerMt = $wetTotalYield > 0 ? round($wetExpenses / $wetTotalYield, 2) : null;
  $dryCostPerMt = $dryTotalYield > 0 ? round($dryExpenses / $dryTotalYield, 2) : null;
@endphp
<style>
  .stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
  }
  .stat-value { font-size: 26px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

  .btn-nav {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px;
    background: #4A7A54; color: #ffffff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .btn-nav:hover { background: #15803d; transform: translateY(-1px); }

  .btn-nav.ghost {
    background: #edf1e9; color: #648268; box-shadow: none;
    border: 1.5px solid #ddd6fe;
  }
  .btn-nav.ghost:hover { background: #ede9fe; border-color: #c4b5fd; }

  .hp-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  @media (max-width: 700px) {
    .hp-header-actions { width: 100%; justify-content: space-between; }
    .hp-header-actions .btn-nav { flex: 1 1 0; min-width: 0; justify-content: center; padding-left: 10px; padding-right: 10px; }
  }
  @media (max-width: 360px) {
    .hp-header-actions { justify-content: stretch; }
    .hp-header-actions .btn-nav { flex-basis: 100%; }
  }

  .hp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; align-items: start; }
  .hp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
  .hp-card-head { padding: 18px 20px; border-bottom: 1px solid #eef1f4; }
  .hp-card-title { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .hp-card-sub { font-size: 12.5px; color: #879a8c; margin: 3px 0 0 0; }
  .hp-card-body { padding: 16px 20px 18px; }

  .hp-card-head-split { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }

  .hp-fullview {
    appearance: none; -webkit-appearance: none; flex-shrink: 0;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 12px; border-radius: 9px; cursor: pointer;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    color: #334155; font-size: 12.5px; font-weight: 700;
    transition: all .15s ease;
  }
  .hp-fullview:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }

  .hp-chart { position: relative; height: 190px; margin-bottom: 8px; }
  .hp-empty { font-size: 13px; color: #94a3b8; line-height: 1.65; padding: 34px 4px; text-align: center; }

  .hp-rank { list-style: none; margin: 0; padding: 0; }
  .hp-rank-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 0; border-bottom: 1px dashed #eef1f4;
  }
  .hp-rank-item:last-child { border-bottom: none; }
  .hp-medal {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .hp-medal.gold   { background: #fef3c7; color: #b45309; box-shadow: inset 0 0 0 1.5px #fcd34d; }
  .hp-medal.silver { background: #edf1e9; color: #648268; box-shadow: inset 0 0 0 1.5px #cbd5e1; }
  .hp-medal.bronze { background: #ffedd5; color: #9a3412; box-shadow: inset 0 0 0 1.5px #fdba74; }
  .hp-medal.other  { background: #f8fafc; color: #94a3b8; box-shadow: inset 0 0 0 1.5px #e2e8f0; }
  .hp-rank-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 700; color: #1e293b; }
  .hp-rank-stats {
    font-size: 12.5px; color: #64748b; font-weight: 600;
    font-variant-numeric: tabular-nums; white-space: nowrap;
  }

  .hp-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed #eef1f4; font-size: 13px; }
  .hp-row:last-child { border-bottom: none; }
  .hp-key { color: #64748b; font-weight: 500; }
  .hp-val { color: #143d23; font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; }

  .hp-delta { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12.5px; font-weight: 700; }
  .hp-delta.up { background: #edf1e9; color: #648268; }
  .hp-delta.down { background: #fef2f2; color: #b91c1c; }
  .hp-delta.flat { background: #edf1e9; color: #648268; }

  .hp-section-title { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em; margin: 26px 0 12px; }
</style>

<div class="header-panel" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0;">Harvest Performance</h1>
    <p style="font-size: 13px; color: #64748b; margin: 5px 0 0 0;">Headline figures for the whole association, with the detail one click away.</p>
  </div>
  <div class="hp-header-actions">
    <a href="{{ route('admin.harvests.reports') }}" class="btn-nav ghost" title="Browse, filter and export every harvest report">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      All Harvest Reports
    </a>
    <a href="{{ route('admin.harvests.compare') }}" class="btn-nav ghost" title="Compare planting estimates against reported yield">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Estimate vs Harvest
    </a>
  </div>
</div>

<!-- System-wide metrics — value and label only, no icons -->
<div class="kpi-strip">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['farmers'] }}</div>
      <div class="stat-label">Farmers Reporting</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['reports'] }}</div>
      <div class="stat-label">Harvest Reports</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['yield'] }}</div>
      <div class="stat-label">Total Yield</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($stats['expenses'], 0) }}</div>
      <div class="stat-label">Total Expenses</div>
    </div>
  </div>
</div>

<!-- Analytics cards -->
<div class="hp-grid" style="grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));">

  {{-- ── Harvest Reports (full-association chart with period filter) ── --}}
  <div class="hp-card" style="grid-column:1 / -1;">
    <div class="hp-card-head hp-card-head-split">
      <div>
        <p class="hp-card-title">Harvest Reports</p>
        <p class="hp-card-sub">Farmer harvest submissions across the platform</p>
      </div>
      <div class="filter-dropdown" id="harvestFilter" style="flex-shrink:0;">
        <button type="button" class="filter-btn" id="harvestFilterBtn" aria-haspopup="true" aria-expanded="false" aria-label="Filter harvest period">
          <svg class="funnel" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          <span id="harvestFilterLabel">This Year</span>
          <svg class="chev" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <ul class="filter-menu" id="harvestFilterMenu" role="menu" aria-label="Harvest period">
          <li role="none"><button type="button" class="filter-option is-active" data-period="year" data-label="This Year">This Year</button></li>
          <li role="none"><button type="button" class="filter-option" data-period="month" data-label="This Month">This Month</button></li>
          <li role="none"><button type="button" class="filter-option" data-period="week" data-label="This Week">This Week</button></li>
          <li role="none"><button type="button" class="filter-option" data-period="days" data-label="Last 30 Days">Last 30 Days</button></li>
        </ul>
      </div>
    </div>
    <div class="hp-card-body" style="padding:0;">
      {{-- Exactly the same chart as the admin dashboard, from one shared copy. --}}
      @include('partials.harvest-report-chart')
    </div>
  </div>

  {{-- ── Year-by-Year Seasonal Performance ─────────────────────────── --}}
  @if(!empty($years))
  <div class="hp-card" style="grid-column:1 / -1;">
    <div class="hp-card-head hp-card-head-split">
      <div>
        <p class="hp-card-title">Year-by-Year Seasonal Performance</p>
        <p class="hp-card-sub">Wet monsoon vs. dry irrigated harvest cycles in the selected scope</p>
      </div>
      <div class="ahr-metric-switch no-print" role="group" aria-label="Chart metric" style="display:inline-flex; gap:6px; padding:4px; background:#f8faf9; border:1px solid #eef1f4; border-radius:8px;">
        <button type="button" class="ahr-metric-btn active" data-metric-btn="yield" style="padding:6px 12px; border-radius:6px; border:none; background:transparent; color:#14532d; font-weight:700; font-size:12px; cursor:pointer;">Yield (MT)</button>
        <button type="button" class="ahr-metric-btn" data-metric-btn="rate" style="padding:6px 12px; border-radius:6px; border:none; background:transparent; color:#64748b; font-weight:600; font-size:12px; cursor:pointer;">MT / ha</button>
        <button type="button" class="ahr-metric-btn" data-metric-btn="expenses" style="padding:6px 12px; border-radius:6px; border:none; background:transparent; color:#64748b; font-weight:600; font-size:12px; cursor:pointer;">Expenses</button>
      </div>
    </div>
    <div class="hp-card-body" style="padding:0;">
      <div class="ahr-chart" style="height:280px; padding:18px 20px;">
        <div class="ahr-readout" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 0 10px; flex-wrap:wrap;">
          <span><strong>{{ $years[count($years)-1]['year'] }}</strong> · {{ (int) $years[count($years)-1]['total_yield_mt'] > 0 ? number_format($years[count($years)-1]['total_yield_mt'], 1) . ' MT' : 'No data' }}</span>
          <div class="ahr-readout-values" style="display:flex; gap:16px; align-items:center; font-size:13px; font-weight:600;">
            @if(true)
              <span style="color:#1d8a3e">Wet: <strong>{{ number_format(collect($years)->sum('wet_yield_mt'), 1) }} MT</strong></span>
            @endif
            @if(true)
              <span style="color:#b45309">Dry: <strong>{{ number_format(collect($years)->sum('dry_yield_mt'), 1) }} MT</strong></span>
            @endif
            <span>Total: <strong>{{ number_format(collect($years)->sum('total_yield_mt'), 1) }} MT</strong></span>
          </div>
        </div>
        <div class="ahr-chart-scroll" style="height:200px; overflow-x:auto; padding-bottom:8px;">
          <svg class="ahr-chart-svg" viewBox="0 0 640 240" role="img" aria-label="Year-by-Year Seasonal Performance" style="width:100%; height:100%; min-width:max(640px, 100%);">
            @php
              $showWet = true;
              $showDry = true;
              $maxYield = collect($years)->max('total_yield_mt');
              $maxWet = collect($years)->max('wet_yield_mt');
              $maxDry = collect($years)->max('dry_yield_mt');
              $chartMax = max($maxYield, $maxWet, $maxDry, 10);
              $chartMax = $chartMax * 1.15;
            @endphp
            @foreach([0, .25, .5, .75, 1] as $step)
              @php
                $yPos = 198 - $step * 168;
                $tick = $chartMax * $step;
              @endphp
              <line class="ahr-gridline" x1="48" y1="{{ $yPos }}" x2="624" y2="{{ $yPos }}" style="stroke:rgba(0,0,0,0.06); stroke-width:1;"></line>
              <text class="ahr-axis-text" x="40" y="{{ $yPos + 4 }}" text-anchor="end" style="font-size:10px; fill:#64748b; font-weight:500;">{{ number_format($tick, 0) }}</text>
            @endforeach
            @foreach($years as $index => $year)
              @php
                $slot = 576 / max(count($years), 1);
                $center = 48 + $slot * $index + $slot / 2;
                $vals = [
                  'wet' => $year['wet_yield_mt'],
                  'dry' => $year['dry_yield_mt'],
                ];
                $barWidth = min(34, $slot * .28);
                $gap = 6;
                $wetX = $center - $barWidth - $gap / 2;
                $dryX = $center + $gap / 2;
                $wetH = max(2, ($vals['wet'] / $chartMax) * 168);
                $dryH = max(2, ($vals['dry'] / $chartMax) * 168);
              @endphp
              <g>
                <rect x="{{ $wetX }}" y="{{ 198 - $wetH }}" width="{{ $barWidth }}" height="{{ $wetH }}" rx="4" fill="#1d8a3e" opacity=".9"></rect>
                <text class="ahr-bar-label" x="{{ $wetX + $barWidth / 2 }}" y="{{ 198 - $wetH - 5 }}" text-anchor="middle" fill="#1d8a3e" style="font-size:9px; font-weight:700;">{{ number_format($vals['wet'], 0) }}</text>
                <rect x="{{ $dryX }}" y="{{ 198 - $dryH }}" width="{{ $barWidth }}" height="{{ $dryH }}" rx="4" fill="#b45309" opacity=".9"></rect>
                <text class="ahr-bar-label" x="{{ $dryX + $barWidth / 2 }}" y="{{ 198 - $dryH - 5 }}" text-anchor="middle" fill="#b45309" style="font-size:9px; font-weight:700;">{{ number_format($vals['dry'], 0) }}</text>
                <text class="ahr-axis-text" x="{{ $center }}" y="220" text-anchor="middle" style="font-weight:700; font-size:10px; fill:#334155;">{{ $year['year'] }}</text>
              </g>
            @endforeach
          </svg>
        </div>
        <div class="ahr-legend" style="display:flex; align-items:center; gap:16px; padding:10px 0 0; font-size:12px; font-weight:600;">
          <span><span class="ahr-legend-dot" style="width:10px; height:10px; border-radius:50%; background:#1d8a3e; display:inline-block; margin-right:6px; vertical-align:middle;"></span>Wet Season (Aug–Nov)</span>
          <span><span class="ahr-legend-dot" style="width:10px; height:10px; border-radius:50%; background:#b45309; display:inline-block; margin-right:6px; vertical-align:middle;"></span>Dry Season (Feb–May)</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Crop Output & Unit Cost ──────────────────────────────────── --}}
  @if(!empty($byCrop))
  <div class="hp-card" style="grid-column:1 / -1;">
    <div class="hp-card-head">
      <p class="hp-card-title">Crop Output & Unit Cost</p>
      <p class="hp-card-sub">Each crop's share of total yield, efficiency, and recorded cost per metric ton</p>
    </div>
    <div class="hp-card-body" style="padding:0;">
      <div class="ahr-crop-bars" style="padding:0 20px 18px;">
        @foreach($byCrop as $crop)
          <div class="ahr-crop-row" style="display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px dashed #eef1f4;">
            <div class="ahr-crop-row-head" style="display:flex; align-items:center; gap:8px; min-width:180px;">
              <span class="ahr-crop-row-name" style="font-weight:700; color:#1e293b; font-size:13.5px;">{{ $crop['crop_type'] }}</span>
              <span class="ahr-crop-row-yield" style="color:#64748b; font-size:12.5px; font-weight:600; white-space:nowrap;">{{ number_format((float) ($crop['total_yield_mt'] ?? 0), 1) }} MT ({{ (int) ($crop['share_pct'] ?? 0) }}%)</span>
            </div>
            <div class="ahr-share-track" style="flex:1; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden;">
              <div class="ahr-share-fill" style="width:{{ max(4, (int) ($crop['share_pct'] ?? 0)) }}%; height:100%; background:#1d8a3e; border-radius:3px;"></div>
            </div>
            <div class="ahr-crop-row-meta" style="display:flex; flex-direction:column; gap:2px; min-width:160px; text-align:right; font-size:12px; color:#64748b;">
              <span>{{ number_format((float) ($crop['yield_per_ha'] ?? 0), 2) }} MT/ha · {{ number_format((float) ($crop['total_area_ha'] ?? 0), 1) }} ha</span>
              <span>₱{{ number_format((float) ($crop['cost_per_mt'] ?? 0), 0) }}/MT</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- ── Wet vs. Dry Season Balance ────────────────────────────────── --}}
  <div class="hp-card" style="grid-column:1 / -1;">
    <div class="hp-card-head">
      <p class="hp-card-title">Wet vs. Dry Season Balance</p>
      <p class="hp-card-sub">Share of total harvest output and unit efficiency across seasons in scope</p>
    </div>
    <div class="hp-card-body" style="padding:0 20px 18px;">
      <div class="ahr-balance-head" style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding-bottom:10px; border-bottom:1px solid #eef1f4;">
        <div>
          <span style="color:#1d8a3e; font-weight:800; font-size:18px;">Wet {{ $wetShare }}%</span>
          <span style="color:#b45309; font-weight:800; font-size:18px; margin-left:12px;">Dry {{ $dryShare }}%</span>
        </div>
      </div>
      <div class="ahr-balance-bar" style="height:16px; background:#f1f5f9; border-radius:8px; overflow:hidden; margin:10px 0;">
        <div class="ahr-balance-fill wet" style="width:{{ $wetShare }}%; height:100%; background:#1d8a3e; border-radius:8px 0 0 8px; transition:width .3s ease;"></div>
        <div class="ahr-balance-fill dry" style="width:{{ $dryShare }}%; height:100%; background:#b45309; border-radius:0 8px 8px 0; transition:width .3s ease;"></div>
      </div>
      <div class="ahr-balance-grid" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:16px;">
        <div class="ahr-balance-col" style="padding:12px; background:#f8fdf9; border:1px solid #edf1e9; border-radius:10px;">
          <span class="ahr-balance-name" style="color:#1d8a3e; font-weight:800; font-size:12.5px; text-transform:uppercase; letter-spacing:.04em;">Wet Season (Aug–Nov)</span>
          <span class="ahr-balance-meta" style="display:block; color:#64748b; font-size:12px; margin-top:4px;">{{ (int) $wetRecords }} harvests · {{ number_format((float) $wetArea, 1) }} ha</span>
          <span class="ahr-balance-value" style="display:block; color:#1d8a3e; font-weight:800; font-size:16px; margin-top:4px;">{{ number_format((float) $wetTotalYield, 1) }} MT</span>
          <span class="ahr-balance-meta" style="display:block; color:#64748b; font-size:12px; margin-top:4px;">{{ $wetYieldPerHa !== null ? number_format($wetYieldPerHa, 2) . ' MT/ha' : '—' }} · {{ $wetCostPerMt !== null ? '₱' . number_format($wetCostPerMt, 0) . '/MT' : '—' }}</span>
        </div>
        <div class="ahr-balance-col" style="padding:12px; background:#fef3c7; border:1px solid #fde68a; border-radius:10px;">
          <span class="ahr-balance-name dry" style="color:#b45309; font-weight:800; font-size:12.5px; text-transform:uppercase; letter-spacing:.04em;">Dry Season (Feb–May)</span>
          <span class="ahr-balance-meta" style="display:block; color:#64748b; font-size:12px; margin-top:4px;">{{ (int) $dryRecords }} harvests · {{ number_format((float) $dryArea, 1) }} ha</span>
          <span class="ahr-balance-value" style="display:block; color:#b45309; font-weight:800; font-size:16px; margin-top:4px;">{{ number_format((float) $dryTotalYield, 1) }} MT</span>
          <span class="ahr-balance-meta" style="display:block; color:#64748b; font-size:12px; margin-top:4px;">{{ $dryYieldPerHa !== null ? number_format($dryYieldPerHa, 2) . ' MT/ha' : '—' }} · {{ $dryCostPerMt !== null ? '₱' . number_format($dryCostPerMt, 0) . '/MT' : '—' }}</span>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- ── Latest comparison ────────────────────────────────────── --}}
  <div class="hp-card">
    <div class="hp-card-head hp-card-head-split">
      <div>
        <p class="hp-card-title">Latest comparison</p>
        <p class="hp-card-sub">{{ $recent ? $recent['recent_label'] . ' vs ' . $recent['previous_label'] : 'Needs at least two harvest periods.' }}</p>
      </div>
      <a href="{{ route('admin.harvests.history') }}" class="hp-fullview" title="Show every harvest period on record">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Full view
      </a>
    </div>
    <div class="hp-card-body">
      @if($recent)
        <div class="hp-chart"><canvas id="latestChart" aria-label="Yield per hectare, latest period against the one before it" role="img"></canvas></div>
        <div class="hp-row">
          <span class="hp-key">{{ $recent['recent_label'] }}</span>
          <span class="hp-val">{{ $recent['recent_yield_per_ha'] !== null ? number_format((float) $recent['recent_yield_per_ha'], 3) . ' MT/ha' : '—' }}</span>
        </div>
        <div class="hp-row">
          <span class="hp-key">{{ $recent['previous_label'] }}</span>
          <span class="hp-val">{{ $recent['previous_yield_per_ha'] !== null ? number_format((float) $recent['previous_yield_per_ha'], 3) . ' MT/ha' : '—' }}</span>
        </div>
        <div class="hp-row">
          <span class="hp-key">Change in yield per hectare</span>
          @php $delta = $recent['yield_per_ha_change_pct']; @endphp
          <span class="hp-val">
            @if($delta === null)
              <span class="hp-delta flat">—</span>
            @else
              <span class="hp-delta {{ $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat') }}">
                {{ $delta >= 0 ? '+' : '' }}{{ $delta }}%
              </span>
            @endif
          </span>
        </div>
      @else
        <p class="hp-empty">Only one harvest period exists so far. Once a second period has reports, the movement between them will appear here.</p>
      @endif
    </div>
  </div>

  {{-- ── Farmers to recognise ─────────────────────────────────── --}}
  <div class="hp-card">
    <div class="hp-card-head">
      <p class="hp-card-title">Farmers to recognise</p>
      <p class="hp-card-sub">Ranked by number of harvest reports filed.</p>
    </div>
    <div class="hp-card-body">
      @if($leaders)
        <ul class="hp-rank">
          @foreach($leaders as $i => $farmer)
            @php
              $rank  = $i + 1;
              $medal = ['gold', 'silver', 'bronze'][$rank - 1] ?? 'other';
            @endphp
            <li class="hp-rank-item">
              <span class="hp-medal {{ $medal }}" role="img" aria-label="Rank {{ $rank }}{{ $medal === 'other' ? '' : ' — ' . $medal }}">
                @if($rank <= 3)
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7.21 15 2.66 7.14a2 2 0 0 1 .13-2.2L4.4 2.8A2 2 0 0 1 6 2h12a2 2 0 0 1 1.6.8l1.6 2.14a2 2 0 0 1 .14 2.2L16.79 15"/>
                    <path d="M11 12 5.12 2.2"/>
                    <path d="m13 12 5.88-9.8"/>
                    <path d="M8 7h8"/>
                    <circle cx="12" cy="17" r="5"/>
                  </svg>
                @else
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="6"/>
                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                  </svg>
                @endif
              </span>
              <span class="hp-rank-name">#{{ $rank }} {{ $farmer['farmer'] }}</span>
              <span class="hp-rank-stats">{{ (int) $farmer['reports'] }} rpt &middot; {{ number_format((float) $farmer['yield_tons'], 2) }} MT</span>
            </li>
          @endforeach
        </ul>
      @else
        <p class="hp-empty">No farmer records to rank yet.</p>
      @endif
    </div>
  </div>

</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var hasChart = !!window.Chart;

    var baseOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#64748b', font: { size: 11 } }, grid: { color: '#f1f5f9' }, border: { display: false } },
        x: { ticks: { color: '#64748b', font: { size: 11 } }, grid: { display: false }, border: { display: false } }
      }
    };

    function seasonColor(season, alpha) {
      return season === 'wet'
        ? 'rgba(29, 78, 216, ' + alpha + ')'
        : 'rgba(180, 83, 9, ' + alpha + ')';
    }

    /* ── Latest comparison: the two most recent periods side by side ── */
    var cmpCanvas = document.getElementById('latestChart');
    var cmpLabels = @js($recent ? [$recent['previous_label'], $recent['recent_label']] : []);
    var cmpValues = @js($recent ? [$recent['previous_yield_per_ha'], $recent['recent_yield_per_ha']] : []);
    var cmpSeasons = @js($recent ? [
      $seasonByLabel[$recent['previous_label']] ?? null,
      $seasonByLabel[$recent['recent_label']] ?? null,
    ] : []);

    if (hasChart && cmpCanvas && cmpLabels.length === 2) {
      if (window.aqDestroyChart) aqDestroyChart(cmpCanvas);
      new Chart(cmpCanvas, {
        type: 'bar',
        data: {
          labels: cmpLabels,
          datasets: [{
            label: 'Yield per hectare (MT/ha)',
            data: cmpValues,
            backgroundColor: cmpSeasons.map(function (s) { return seasonColor(s, 0.72); }),
            hoverBackgroundColor: cmpSeasons.map(function (s) { return seasonColor(s, 1); }),
            borderRadius: 5,
            barPercentage: 0.55,
            categoryPercentage: 0.65
          }]
        },
        options: Object.assign({}, baseOptions, {
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  return ' ' + (ctx.parsed.y === null ? '—' : Number(ctx.parsed.y).toFixed(3)) + ' MT/ha';
                }
              }
            }
          },
          scales: {
            y: Object.assign({}, baseOptions.scales.y, {
              title: { display: true, text: 'MT per hectare', color: '#94a3b8', font: { size: 11, weight: '600' } }
            }),
            x: baseOptions.scales.x
          }
        })
      });
    }

  });
</script>
@endsection