@extends('layouts.sidebar')

@section('title', 'Harvest Performance')

@section('content')
@php
  $periods = $context['periods'] ?? [];
  $recent  = $context['recent_vs_previous'] ?? null;
  $leaders = array_slice($context['top_farmers_by_reports'] ?? [], 0, 5);

  $seasonByLabel = collect($periods)->mapWithKeys(fn (array $p) => [$p['label'] => $p['season']]);
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
  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <a href="{{ route('admin.harvests.reports') }}" class="btn-nav" title="Browse, filter and export every harvest report">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      All Harvest Reports
    </a>
    <a href="{{ route('admin.harvests.compare') }}" class="btn-nav" style="background: #166534; box-shadow: 0 4px 12px rgba(22, 101, 52, 0.25);" title="Compare planting estimates against reported yield">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Estimate vs Harvest
    </a>
    <a href="{{ route('admin.harvests.ai') }}" class="btn-nav ghost" title="Explain this harvest data with AI">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      AI Insights
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
