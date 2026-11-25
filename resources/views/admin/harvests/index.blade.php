@extends('layouts.sidebar')

@section('title', 'Harvest Performance')

@section('content')
@php
  $periods = $context['periods'] ?? [];

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

  $totalYieldAll = collect($years)->sum('total_yield_mt');

  // Soft pastel matte bars, with slightly deeper ink for readable labels.
  // The Wet/Dry balance card that used to share these now lives on the admin
  // dashboard; these shades drive the Year-by-Year chart and metric switch.
  $wetColor = '#A9C9DF';
  $wetInk = '#55799B';
  $wetSoft = '#F2F7FB';

  $dryColor = '#E3C9AF';
  $dryInk = '#8F6B4B';

  // The Year-by-Year chart has three readings of the same seasons. Keep the
  // metric maths here so every panel uses exactly the figures above.
  $metricValue = function (array $year, string $metric, string $season) {
    $yield = $season === 'wet' ? $year['wet_yield_mt'] : ($season === 'dry' ? $year['dry_yield_mt'] : $year['total_yield_mt']);
    $area  = $season === 'wet' ? $year['wet_area_ha'] : ($season === 'dry' ? $year['dry_area_ha'] : $year['total_area_ha']);
    $spend = $season === 'wet' ? $year['wet_expenses_php'] : ($season === 'dry' ? $year['dry_expenses_php'] : $year['total_expenses_php']);

    if ($metric === 'rate') return $area > 0 ? $yield / $area : 0;
    if ($metric === 'expenses') return (float) $spend;
    return (float) $yield;
  };

  // Keep expense labels short: ₱1.72M / ₱720k instead of full peso amounts
  // that overrun the y-axis gutter and clip the peso sign.
  $pesoShort = function (float $value) {
    if ($value >= 1000000) return '₱' . number_format($value / 1000000, 2) . 'M';
    if ($value >= 1000) return '₱' . number_format($value / 1000, 0) . 'k';
    return '₱' . number_format($value, 0);
  };

  $metricTick = function (float $value, string $metric) use ($pesoShort) {
    if ($metric === 'expenses') return $pesoShort($value);
    if ($metric === 'rate') return number_format($value, 1);
    return number_format($value, 0);
  };

  $metricBarLabel = function (float $value, string $metric) use ($pesoShort) {
    if ($metric === 'expenses') return $pesoShort($value);
    if ($metric === 'rate') return number_format($value, 1);
    return number_format($value, 0);
  };
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
      Harvest Reports
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
  <div id="year-by-year" class="hp-card" style="grid-column:1 / -1; scroll-margin-top:96px;">
    <div class="hp-card-head hp-card-head-split">
      <div>
        <p class="hp-card-title">Year-by-Year Seasonal Performance</p>
        <p class="hp-card-sub">Wet monsoon vs. dry irrigated harvest cycles in the selected scope</p>
      </div>
      <div class="ahr-metric-switch no-print" role="group" aria-label="Chart metric" style="display:inline-flex; gap:6px; padding:4px; background:#f8faf9; border:1px solid #eef1f4; border-radius:8px;">
        <button type="button" class="ahr-metric-btn active" data-metric-btn="yield" aria-pressed="true" style="padding:6px 12px; border-radius:6px; border:none; background:{{ $wetSoft }}; color:{{ $wetInk }}; font-weight:700; font-size:12px; cursor:pointer;">Yield (MT)</button>
        <button type="button" class="ahr-metric-btn" data-metric-btn="rate" aria-pressed="false" style="padding:6px 12px; border-radius:6px; border:none; background:transparent; color:#64748b; font-weight:600; font-size:12px; cursor:pointer;">MT / ha</button>
        <button type="button" class="ahr-metric-btn" data-metric-btn="expenses" aria-pressed="false" style="padding:6px 12px; border-radius:6px; border:none; background:transparent; color:#64748b; font-weight:600; font-size:12px; cursor:pointer;">Expenses</button>
      </div>
    </div>
    <div class="hp-card-body" style="padding:0;">
      <div class="ahr-chart" style="padding:18px 20px;">
        <div class="ahr-readout" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 0 10px; flex-wrap:wrap;">
          <span><strong>{{ $years[count($years)-1]['year'] }}</strong> · {{ (int) $years[count($years)-1]['total_yield_mt'] > 0 ? number_format($years[count($years)-1]['total_yield_mt'], 1) . ' MT' : 'No data' }}</span>
          <div class="ahr-readout-values" style="display:flex; gap:16px; align-items:center; font-size:13px; font-weight:600;">
            @if(true)
              <span style="color:{{ $wetInk }}">Wet: <strong>{{ number_format(collect($years)->sum('wet_yield_mt'), 1) }} MT</strong></span>
            @endif
            @if(true)
              <span style="color:{{ $dryInk }}">Dry: <strong>{{ number_format(collect($years)->sum('dry_yield_mt'), 1) }} MT</strong></span>
            @endif
            <span>Total: <strong>{{ number_format(collect($years)->sum('total_yield_mt'), 1) }} MT</strong></span>
          </div>
        </div>
        {{-- Wide, short viewBox: the SVG scales with the card instead of
             letterboxing a small chart inside a fixed-height scroll box.
             Each metric gets its own panel so the switch actually changes
             the bars and the axis, not just the button colour. --}}
        @foreach(['yield' => 'Yield (MT)', 'rate' => 'MT / ha', 'expenses' => 'Expenses'] as $metricId => $metricTitle)
          @php
            $max = 0;
            foreach ($years as $year) {
              $max = max($max, $metricValue($year, $metricId, 'wet'), $metricValue($year, $metricId, 'dry'));
            }
            $chartMax = ($max > 0 ? $max : 10) * 1.08;
          @endphp
          <div data-metric-panel="{{ $metricId }}" {{ $metricId !== 'yield' ? 'hidden' : '' }}>
            <div class="ahr-chart-scroll" style="overflow-x:auto; padding-bottom:8px;">
              <svg class="ahr-chart-svg" viewBox="0 0 1100 280" role="img" aria-label="{{ $metricTitle }} by year" style="display:block; width:100%; height:auto; min-width:960px;">
                @foreach([0, .25, .5, .75, 1] as $step)
                  @php
                    $yPos = 232 - $step * 190;
                    $tick = $chartMax * $step;
                  @endphp
                  <line class="ahr-gridline" x1="60" y1="{{ $yPos }}" x2="1052" y2="{{ $yPos }}" style="stroke:rgba(0,0,0,0.06); stroke-width:1;"></line>
                  <text class="ahr-axis-text" x="8" y="{{ $yPos + 4 }}" text-anchor="start" style="font-size:11px; fill:#64748b; font-weight:500;">{{ $metricTick($tick, $metricId) }}</text>
                @endforeach
                @foreach($years as $index => $year)
                  @php
                    $slot = 1000 / max(count($years), 1);
                    $center = 60 + $slot * $index + $slot / 2;
                    $vals = [
                      'wet' => $metricValue($year, $metricId, 'wet'),
                      'dry' => $metricValue($year, $metricId, 'dry'),
                    ];
                    $barWidth = min(90, $slot * .28);
                    $gap = 12;
                    $wetX = $center - $barWidth - $gap / 2;
                    $dryX = $center + $gap / 2;
                    $wetH = max(2, ($vals['wet'] / $chartMax) * 190);
                    $dryH = max(2, ($vals['dry'] / $chartMax) * 190);
                  @endphp
                  <g>
                    <rect x="{{ $wetX }}" y="{{ 232 - $wetH }}" width="{{ $barWidth }}" height="{{ $wetH }}" rx="6" fill="{{ $wetColor }}"></rect>
                    <text class="ahr-bar-label" x="{{ $wetX + $barWidth / 2 }}" y="{{ 232 - $wetH - 7 }}" text-anchor="middle" fill="{{ $wetInk }}" style="font-size:11px; font-weight:700;">{{ $metricBarLabel($vals['wet'], $metricId) }}</text>
                    <rect x="{{ $dryX }}" y="{{ 232 - $dryH }}" width="{{ $barWidth }}" height="{{ $dryH }}" rx="6" fill="{{ $dryColor }}"></rect>
                    <text class="ahr-bar-label" x="{{ $dryX + $barWidth / 2 }}" y="{{ 232 - $dryH - 7 }}" text-anchor="middle" fill="{{ $dryInk }}" style="font-size:11px; font-weight:700;">{{ $metricBarLabel($vals['dry'], $metricId) }}</text>
                    <text class="ahr-axis-text" x="{{ $center }}" y="258" text-anchor="middle" style="font-weight:700; font-size:12px; fill:#334155;">{{ $year['year'] }}</text>
                  </g>
                @endforeach
              </svg>
            </div>
          </div>
        @endforeach
        <div class="ahr-legend" style="display:flex; align-items:center; gap:16px; padding:10px 0 0; font-size:12px; font-weight:600;">
          <span><span class="ahr-legend-dot" style="width:10px; height:10px; border-radius:50%; background:{{ $wetColor }}; display:inline-block; margin-right:6px; vertical-align:middle;"></span>Wet Season (Aug–Nov)</span>
          <span><span class="ahr-legend-dot" style="width:10px; height:10px; border-radius:50%; background:{{ $dryColor }}; display:inline-block; margin-right:6px; vertical-align:middle;"></span>Dry Season (Feb–May)</span>
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
          @php
            $cropYield = (float) ($crop['yield_tons'] ?? 0);
            $sharePct = $totalYieldAll > 0 ? (int) round(($cropYield / $totalYieldAll) * 100) : 0;
          @endphp
          <div class="ahr-crop-row" style="display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px dashed #eef1f4;">
            <div class="ahr-crop-row-head" style="display:flex; align-items:center; gap:8px; min-width:180px;">
              <span class="ahr-crop-row-name" style="font-weight:700; color:#1e293b; font-size:13.5px;">{{ $crop['crop'] }}</span>
              <span class="ahr-crop-row-yield" style="color:#64748b; font-size:12.5px; font-weight:600; white-space:nowrap;">{{ number_format($cropYield, 1) }} MT ({{ $sharePct }}%)</span>
            </div>
            <div class="ahr-share-track" style="flex:1; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden;">
              <div class="ahr-share-fill" style="width:{{ max(4, $sharePct) }}%; height:100%; background:#1d8a3e; border-radius:3px;"></div>
            </div>
            <div class="ahr-crop-row-meta" style="display:flex; flex-direction:column; gap:2px; min-width:160px; text-align:right; font-size:12px; color:#64748b;">
              <span>{{ number_format((float) ($crop['yield_per_ha'] ?? 0), 2) }} MT/ha · {{ number_format((float) ($crop['area_ha'] ?? 0), 1) }} ha</span>
              <span>₱{{ number_format((float) ($crop['expenses_per_ton'] ?? 0), 0) }}/MT</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  @endif

</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  (function () {
    // Delegate on document so the switch keeps working across Turbo visits,
    // where the body (and its buttons) is replaced but document is not.
    if (window.__aqMetricSwitchBound) return;
    window.__aqMetricSwitchBound = true;

    document.addEventListener('click', function (e) {
      var button = e.target && e.target.closest ? e.target.closest('[data-metric-btn]') : null;
      if (!button) return;

      var metric = button.getAttribute('data-metric-btn');
      document.querySelectorAll('[data-metric-btn]').forEach(function (item) {
        var active = item === button;
        item.classList.toggle('active', active);
        item.setAttribute('aria-pressed', active ? 'true' : 'false');
        item.style.background = active ? '{{ $wetSoft }}' : 'transparent';
        item.style.color = active ? '{{ $wetInk }}' : '#64748b';
        item.style.fontWeight = active ? '700' : '600';
      });
      document.querySelectorAll('[data-metric-panel]').forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-metric-panel') !== metric;
      });
    });
  })();
</script>
@endsection