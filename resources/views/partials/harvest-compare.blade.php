{{--
  Shared Estimate vs Actual body for farmer + admin.

  Expects (from HarvestCompareService::compare()):
    $rows, $kpis, $chart, $options, $group_by, $filters
  Plus from the page:
    $compareRoute  route name for the GET filter form
    $isAdmin       bool — adds the farmer filter column/badge
--}}
@php
  $cmpRoute = $compareRoute ?? 'farmer.harvests.compare';
  $cmpIsAdmin = $isAdmin ?? false;
  $f = $filters ?? ['year' => '', 'season' => '', 'crop' => '', 'farmer' => ''];
@endphp
<style>
  /* Filter bar + table base live in the shared .table-toolbar / .data-table /
     select rules (layouts.sidebar) — this form dresses exactly like every
     other filter bar in the app and auto-applies when a select changes. */
  .cmp-filters { align-items: center; gap: 10px; flex-wrap: wrap; }
  .cmp-filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; width: 100%; }
  .cmp-filters select {
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    color: #1a2b1e;
    font-size: 13px;
    font-weight: 500;
    min-width: 160px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .cmp-filters select:focus {
    outline: none;
    border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.15);
  }
  .cmp-filters select option { font-weight: 400; }
  .btn-clear {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 10px;
    border: 1px solid #e5e7eb; background: #fff; color: #4b5c50;
    font-size: 12.5px; font-weight: 600; text-decoration: none;
    transition: all 0.15s ease;
  }
  .btn-clear:hover { background: #edf1e9; border-color: #c8d5c5; color: #344b38; }

  .cmp-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); gap: 14px; margin-bottom: 18px; }
  .cmp-kpi { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 18px; }
  .cmp-kpi .k { font-size: 12px; color: #64748b; font-weight: 600; }
  .cmp-kpi .v { font-size: 24px; font-weight: 800; color: #143d23; margin-top: 5px; line-height: 1; font-variant-numeric: tabular-nums; }
  .cmp-kpi .s { font-size: 11.5px; color: #94a3b8; margin-top: 5px; }
  .cmp-kpi .v.pos { color: #15803d; }
  .cmp-kpi .v.neg { color: #b91c1c; }

  .cmp-grid { display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px; }
  .cmp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
  .cmp-card-head { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
  .cmp-card-title { font-size: 14.5px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .cmp-card-sub { font-size: 12.5px; color: #94a3b8; margin: 3px 0 0 0; }
  .cmp-chart { position: relative; height: 320px; padding: 14px 18px 18px; }

  .cmp-legend { display: flex; gap: 14px; align-items: center; font-size: 12px; color: #475569; font-weight: 600; }
  .cmp-legend .sw { width: 11px; height: 11px; border-radius: 3px; display: inline-block; margin-right: 5px; vertical-align: -1px; }

  .cmp-table-wrap { overflow-x: auto; }
  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
  .cmp-table { min-width: 780px; }
  .cmp-table .num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
  .cmp-var { display: inline-flex; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
  .cmp-var.up { background: #edf1e9; color: #648268; }
  .cmp-var.down { background: #fef2f2; color: #b91c1c; }
  .cmp-var.flat { background: #edf1e9; color: #648268; }
  .cmp-src {
    display: inline-block; padding: 2px 8px; border-radius: 14px;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
  }
  .cmp-src.batch { background: #edf1e9; color: #648268; }
  .cmp-src.baseline { background: #edf1e9; color: #648268; }
  .cmp-pill { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .cmp-pill.wet { background: #edf1e9; color: #648268; }
  .cmp-pill.dry { background: #edf1e9; color: #648268; }

  .cmp-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
  .cmp-empty b { display: block; color: #475569; font-size: 15px; margin: 8px 0 4px; }
</style>

{{-- ── Filters (shared .table-toolbar + .btn-filter collapse — auto-applies on change) ── --}}
<form class="table-toolbar cmp-filters" method="GET" action="{{ route($cmpRoute) }}">
  <div class="cmp-filter-row">
    <select name="year" aria-label="Year" onchange="this.form.submit()">
      <option value="all">All years</option>
      @foreach($options['years'] as $y)
        <option value="{{ $y }}" {{ (string) $f['year'] === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
      @endforeach
    </select>
    <select name="season" aria-label="Season" onchange="this.form.submit()">
      <option value="">All seasons</option>
      <option value="wet" {{ $f['season'] === 'wet' ? 'selected' : '' }}>Wet</option>
      <option value="dry" {{ $f['season'] === 'dry' ? 'selected' : '' }}>Dry</option>
    </select>
    <select name="crop" aria-label="Crop" onchange="this.form.submit()">
      <option value="">All crops</option>
      @foreach($options['crops'] as $c)
        <option value="{{ $c }}" {{ $f['crop'] === $c ? 'selected' : '' }}>{{ $c }}</option>
      @endforeach
    </select>
    @if($cmpIsAdmin && count($options['farmers']))
      <select name="farmer" aria-label="Farmer" onchange="this.form.submit()">
        <option value="">All farmers</option>
        @foreach($options['farmers'] as $name)
          <option value="{{ $name }}" {{ $f['farmer'] === $name ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
      </select>
    @endif
    <select name="group_by" aria-label="Group chart by" onchange="this.form.submit()">
      <option value="crop" {{ $group_by === 'crop' ? 'selected' : '' }}>Chart by crop</option>
      <option value="period" {{ $group_by === 'period' ? 'selected' : '' }}>Chart by year + season</option>
    </select>
    @php
      $hasCmpFilters = in_array($f['year'] ?? '', ['', 'all'], true) === false
          || ($f['season'] ?? '') !== ''
          || ($f['crop'] ?? '') !== ''
          || ($f['farmer'] ?? '') !== '';
    @endphp
    @if($hasCmpFilters)
      <a class="btn-clear" href="{{ route($cmpRoute) }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear
      </a>
    @endif
  </div>
{{-- ── Chart ── --}}
<div class="cmp-grid">
  <div class="cmp-card">
    <div class="cmp-card-head">
      <div>
        <p class="cmp-card-title">Estimate vs Actual Yield</p>
        <p class="cmp-card-sub">
          {{ $group_by === 'crop' ? 'Grouped by crop' : 'Grouped by year and season' }} · metric tons
          @if($f['year'] !== '' && $f['year'] !== 'all') · {{ $f['year'] }} @endif
          @if($f['season'] !== '') · {{ ucfirst($f['season']) }} season @endif
        </p>
      </div>
      <div class="cmp-legend">
        <span><span class="sw" style="background:#9AA7B4;"></span>Estimate</span>
        <span><span class="sw" style="background:#648268;"></span>Actual</span>
      </div>
    </div>
    {{-- Compact KPI row ── --}}
    <div class="cmp-kpis" style="display:flex; gap:16px; flex-wrap:wrap; padding:0 18px 14px; border-bottom:1px solid #f1f5f9; margin-bottom:8px;">
      <div style="flex:1; min-width:140px;">
        <div class="k" style="font-size:11px;">Estimated</div>
        <div class="v" style="font-size:18px;">{{ number_format($kpis['total_estimate_mt'], 2) }} <span style="font-size:11px;font-weight:700;color:#94a3b8;">MT</span></div>
      </div>
      <div style="flex:1; min-width:140px;">
        <div class="k" style="font-size:11px;">Actual</div>
        <div class="v pos" style="font-size:18px;">{{ number_format($kpis['total_actual_mt'], 2) }} <span style="font-size:11px;font-weight:700;color:#94a3b8;">MT</span></div>
      </div>
      <div style="flex:1; min-width:140px;">
        <div class="k" style="font-size:11px;">Variance</div>
        <div class="v {{ $kpis['variance_mt'] > 0.005 ? 'pos' : ($kpis['variance_mt'] < -0.005 ? 'neg' : '') }}" style="font-size:18px;">
          {{ $kpis['variance_mt'] >= 0 ? '+' : '' }}{{ number_format($kpis['variance_mt'], 2) }} <span style="font-size:11px;font-weight:700;color:#94a3b8;">MT</span>
        </div>
      </div>
      <div style="flex:1; min-width:140px;">
        <div class="k" style="font-size:11px;">Accuracy</div>
        <div class="v {{ $kpis['accuracy_pct'] === null ? '' : ($kpis['accuracy_pct'] >= 100 ? 'pos' : 'neg') }}" style="font-size:18px;">
          {{ $kpis['accuracy_pct'] === null ? '—' : $kpis['accuracy_pct'] . '%' }}
        </div>
      </div>
    </div>
    {{-- Filters (inline below chart header) ── --}}
    <form class="table-toolbar cmp-filters" method="GET" action="{{ route($cmpRoute) }}">
      <div class="cmp-filter-row">
        <select name="year" aria-label="Year" onchange="this.form.submit()">
          <option value="all">All years</option>
          @foreach($options['years'] as $y)
            <option value="{{ $y }}" {{ (string) $f['year'] === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
          @endforeach
        </select>
        <select name="season" aria-label="Season" onchange="this.form.submit()">
          <option value="">All seasons</option>
          <option value="wet" {{ $f['season'] === 'wet' ? 'selected' : '' }}>Wet</option>
          <option value="dry" {{ $f['season'] === 'dry' ? 'selected' : '' }}>Dry</option>
        </select>
        <select name="crop" aria-label="Crop" onchange="this.form.submit()">
          <option value="">All crops</option>
          @foreach($options['crops'] as $c)
            <option value="{{ $c }}" {{ $f['crop'] === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
        @if($cmpIsAdmin && count($options['farmers']))
          <select name="farmer" aria-label="Farmer" onchange="this.form.submit()">
            <option value="">All farmers</option>
            @foreach($options['farmers'] as $name)
              <option value="{{ $name }}" {{ $f['farmer'] === $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        @endif
        <select name="group_by" aria-label="Group chart by" onchange="this.form.submit()">
          <option value="crop" {{ $group_by === 'crop' ? 'selected' : '' }}>Chart by crop</option>
          <option value="period" {{ $group_by === 'period' ? 'selected' : '' }}>Chart by year + season</option>
        </select>
        @php
          $hasCmpFilters = in_array($f['year'] ?? '', ['', 'all'], true) === false
              || ($f['season'] ?? '') !== ''
              || ($f['crop'] ?? '') !== ''
              || ($f['farmer'] ?? '') !== '';
        @endphp
        @if($hasCmpFilters)
          <a class="btn-clear" href="{{ route($cmpRoute) }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear
          </a>
        @endif
      </div>
    </form>
    <div class="cmp-chart">
      @if($kpis['pairs'] > 0)
        <canvas id="cmpChart" aria-label="Grouped bar chart comparing estimated and actual yield in metric tons" role="img"></canvas>
      @else
        <div class="cmp-empty">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3v18h18"/><path d="M7 9h4v4H7zM14 6h3v7h-3z"/></svg>
          <b>No harvest pairs match these filters</b>
          Widen the filters above, or wait for farmers to file harvest reports.
        </div>
      @endif
    </div>
  </div>
</div>

{{-- ── Detail table ── --}}
<x-table-toolbar
    uid="cmpDetail"
    work-table="cmpTable"
    per-page="10"
    noun="pairs"
    search-placeholder="Search crop, batch, or farmer…"
  />
<div class="cmp-card">
  <div class="cmp-card-head">
    <div>
      <p class="cmp-card-title">Comparison Detail</p>
      <p class="cmp-card-sub">
        {{ number_format($kpis['pairs']) }} harvest pair{{ $kpis['pairs'] === 1 ? '' : 's' }}
        — {{ $kpis['over_count'] }} over, {{ $kpis['under_count'] }} under{{ $kpis['on_count'] ? ', ' . $kpis['on_count'] . ' on target' : '' }}. Newest harvest first.
      </p>
    </div>
  </div>
  @if(count($rows))
    <div class="cmp-table-wrap table-scroll">
      <table id="cmpTable" class="cmp-table data-table">
        <thead>
          <tr>
            @if($cmpIsAdmin)<th>Farmer</th>@endif
            <th>Crop</th>
            <th>Batch</th>
            <th>Season</th>
            <th>Harvest Date</th>
            <th class="num">Area (ha)</th>
            <th class="num">Estimate (MT)</th>
            <th class="num">Actual (MT)</th>
            <th class="num">Variance</th>
            <th>Estimate Source</th>
          </tr>
        </thead>
        <tbody>
          @php $rowsDesc = array_reverse($rows); @endphp
          @foreach($rowsDesc as $row)
            @php
              $var = $row['actual_mt'] - $row['estimate_mt'];
              $varClass = abs($var) < 0.005 ? 'flat' : ($var > 0 ? 'up' : 'down');
              $varPct = $row['estimate_mt'] > 0 ? round(($var / $row['estimate_mt']) * 100, 1) : null;
            @endphp
            <tr>
              @if($cmpIsAdmin)<td style="font-weight:600;color:#334155;">{{ $row['farmer'] }}</td>@endif
              <td style="font-weight:700;color:#1a2b1e;">{{ $row['crop'] }}</td>
              <td style="color:#64748b;">{{ $row['batch'] ?? '—' }}</td>
              <td><span class="cmp-pill {{ $row['season'] }}">{{ ucfirst($row['season'] ?? '—') }}</span></td>
              <td>{{ $row['harvest_date'] ? \Illuminate\Support\Carbon::parse($row['harvest_date'])->format('M d, Y') : '—' }}</td>
              <td class="num">{{ rtrim(rtrim(number_format($row['land_area'], 2, '.', ''), '0'), '.') }}</td>
              <td class="num">{{ number_format($row['estimate_mt'], 2) }}</td>
              <td class="num" style="font-weight:700;color:#14532d;">{{ number_format($row['actual_mt'], 2) }}</td>
              <td class="num">
                <span class="cmp-var {{ $varClass }}">
                  {{ $var >= 0 ? '+' : '' }}{{ number_format($var, 2) }}{{ $varPct !== null ? ' (' . ($varPct >= 0 ? '+' : '') . $varPct . '%)' : '' }}
                </span>
              </td>
              <td>
                <span class="cmp-src {{ $row['estimate_source'] }}">{{ $row['estimate_source'] === 'batch' ? 'Planting estimate' : 'Baseline' }}</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="cmp-empty">
      <b>Nothing to compare yet</b>
      Pairs appear once harvest reports exist for the selected filters.
    </div>
  @endif
</div>

@if($kpis['pairs'] > 0)
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    var canvas = document.getElementById('cmpChart');
    if (!canvas) return;
    if (window.aqDestroyChart) aqDestroyChart(canvas);

    var labels = @js($chart['labels']);
    var estimate = @js($chart['estimate']);
    var actual = @js($chart['actual']);

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            // Muted on purpose: the chart is a reading, not a poster.
            // Estimate sits in soft grey-blue, actual in the app's sage.
            label: 'Estimate',
            data: estimate,
            backgroundColor: 'rgba(154, 167, 180, 0.85)',
            hoverBackgroundColor: 'rgba(133, 146, 159, 1)',
            borderColor: 'rgba(120, 133, 145, 0.9)',
            borderWidth: 1,
            borderRadius: 5,
            barPercentage: 0.7,
            categoryPercentage: 0.7
          },
          {
            label: 'Actual',
            data: actual,
            backgroundColor: 'rgba(100, 130, 104, 0.88)',
            hoverBackgroundColor: 'rgba(74, 122, 84, 1)',
            borderColor: 'rgba(74, 122, 84, 0.9)',
            borderWidth: 1,
            borderRadius: 5,
            barPercentage: 0.7,
            categoryPercentage: 0.7
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var pairs = @js($chart['pairs']);
                var extra = pairs && pairs[ctx.dataIndex] !== undefined ? ' · ' + pairs[ctx.dataIndex] + ' harvest pair' + (pairs[ctx.dataIndex] === 1 ? '' : 's') : '';
                return ' ' + ctx.dataset.label + ': ' + Number(ctx.parsed.y).toFixed(3) + ' MT' + extra;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: '#64748b', font: { size: 11 } },
            grid: { color: '#f1f5f9' },
            border: { display: false },
            title: { display: true, text: 'Metric tons (MT)', color: '#94a3b8', font: { size: 11, weight: '600' } }
          },
          x: {
            ticks: { color: '#64748b', font: { size: 11 } },
            grid: { display: false },
            border: { display: false }
          }
        }
      }
    });
  });
</script>
@endif
