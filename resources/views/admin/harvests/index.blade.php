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

  /* Harvest period filter dropdown */
  .filter-dropdown { position: relative; }
  .filter-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font: inherit; font-size: 12px; font-weight: 900; letter-spacing: 0.04em; color: #14532d;
    padding: 6px 11px; border-radius: 9px;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.14);
    cursor: pointer; transition: all 0.18s ease;
  }
  .filter-btn:hover { background: #f8faf9; border-color: rgba(20,61,35,0.28); }
  .filter-btn .funnel { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .filter-btn .chev { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.2s ease; }
  .filter-dropdown.open .filter-btn .chev { transform: rotate(180deg); }
  .filter-menu {
    position: absolute; top: calc(100% + 6px); right: 0; z-index: 60;
    min-width: 168px; padding: 6px; margin: 0; list-style: none;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.12);
    border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,0.12);
    display: none;
  }
  .filter-dropdown.open .filter-menu { display: block; }
  .filter-option {
    display: block; width: 100%; text-align: left; font: inherit;
    font-size: 12.5px; font-weight: 600; color: #40632d; letter-spacing: 0.02em;
    background: transparent; border: none; cursor: pointer;
    padding: 8px 12px; border-radius: 8px; transition: background 0.15s ease;
    white-space: nowrap;
  }
  .filter-option:hover { background: #f0fdf4; }
  .filter-option.is-active { background: #edf1e9; color: #648268; font-weight: 800; }
  .chart-empty {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; color: #64748b; font-weight: 600; text-align: center;
    background: rgba(255,255,255,0.78); border-radius: 10px; padding: 10px;
  }
  .chart-empty[hidden] { display: none; }

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
<div class="hp-grid">

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
      <div class="yield-row">
        <div>
          <div class="yield-big">
            <span class="yield-big-value" id="harvestYieldValue">0</span>
            <span class="yield-big-unit">MT</span>
          </div>
          <div class="chart-caption">Total yield</div>
        </div>
      </div>
      <div class="hp-chart" style="height:220px; padding-top:18px;">
        <div style="position:relative;height:100%;">
          <canvas id="harvestChart"></canvas>
          <div id="harvestEmpty" class="chart-empty" hidden></div>
        </div>
      </div>
    </div>
  </div>

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

    /* ── Harvest Reports chart (full-association, with period filter) ── */
    var harvestPeriods = {
      year: @js($harvestGraphYear),
      month: @js($harvestGraphMonth),
      week: @js($harvestGraphWeek),
      days: @js($harvestGraphDays)
    };
    var harvestCanvas = document.getElementById('harvestChart');
    var harvestYieldValue = document.getElementById('harvestYieldValue');
    var harvestEmptyEl = document.getElementById('harvestEmpty');
    var harvestChart = null;
    var harvestFilter = document.getElementById('harvestFilter');
    var harvestFilterBtn = document.getElementById('harvestFilterBtn');
    var harvestFilterLabel = document.getElementById('harvestFilterLabel');

    function buildHarvestChart(periodKey) {
      if (!harvestCanvas || !window.Chart) return;
      var data = harvestPeriods[periodKey] || [];
      var yTotal = 0;
      data.forEach(function (d) { yTotal += d.yield; });

      if (harvestYieldValue) {
        harvestYieldValue.textContent = yTotal > 0 ? yTotal.toFixed(1) : '0';
      }

      if (harvestEmptyEl) {
        harvestEmptyEl.textContent = yTotal === 0
          ? 'No harvest reports submitted in this period yet.'
          : '';
        harvestEmptyEl.hidden = yTotal !== 0;
      }

      var isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';
      var fillColors = data.map(function (d, i) {
        var t = Math.min(1, i / (data.length - 1 || 1));
        if (isDarkTheme) {
          return 'rgba(220, 252, 231, ' + (0.62 + 0.38 * t).toFixed(2) + ')';
        }
        return 'rgba(22, 163, 74, ' + (0.28 + 0.65 * t).toFixed(2) + ')';
      });

      if (harvestChart) harvestChart.destroy();
      if (window.aqDestroyChart) aqDestroyChart(harvestCanvas);
      harvestChart = new Chart(harvestCanvas, {
        type: 'bar',
        data: {
          labels: data.map(function (d) { return d.label; }),
          datasets: [{
            label: 'Total yield (MT)',
            data: data.map(function (d) { return d.yield; }),
            backgroundColor: fillColors,
            hoverBackgroundColor: isDarkTheme ? 'rgba(240, 253, 244, 1)' : 'rgba(22, 163, 74, 0.92)',
            borderRadius: 5,
            barPercentage: periodKey === 'month' || periodKey === 'days' ? 0.7 : 0.6,
            categoryPercentage: 0.6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  var v = ctx.parsed.y;
                  return v.toFixed(1) + ' MT total yield';
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: isDarkTheme ? '#cbd5e1' : '#334155', font: { weight: '600', size: 12 }, maxRotation: 0, autoSkipPadding: 6, maxTicksLimit: periodKey === 'days' ? 10 : 14 },
              border: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { color: isDarkTheme ? '#cbd5e1' : '#334155', font: { weight: '600', size: 12 }, maxTicksLimit: 5 },
              grid: { color: isDarkTheme ? 'rgba(255,255,255,0.14)' : 'rgba(0,0,0,0.08)' },
              border: { display: false }
            }
          }
        }
      });
    }

    function setHarvestPeriod(periodKey, label, activeBtn) {
      document.querySelectorAll('.filter-option').forEach(function (o) {
        o.classList.toggle('is-active', o === activeBtn || o.dataset.period === periodKey);
      });
      if (harvestFilterLabel) harvestFilterLabel.textContent = label;
      if (harvestFilterBtn) harvestFilterBtn.setAttribute('aria-label', 'Filter harvest period: ' + label);
      buildHarvestChart(periodKey);
      closeHarvestFilter();
    }

    function closeHarvestFilter() {
      if (harvestFilter) harvestFilter.classList.remove('open');
      if (harvestFilterBtn) harvestFilterBtn.setAttribute('aria-expanded', 'false');
    }

    if (harvestFilterBtn) {
      harvestFilterBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = harvestFilter.classList.toggle('open');
        harvestFilterBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
    document.addEventListener('click', function (e) {
      if (harvestFilter && !harvestFilter.contains(e.target)) closeHarvestFilter();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeHarvestFilter();
    });
    document.querySelectorAll('.filter-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        setHarvestPeriod(opt.dataset.period, opt.dataset.label, opt);
      });
    });

    buildHarvestChart('year');

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
