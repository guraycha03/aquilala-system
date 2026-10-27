@extends('layouts.sidebar')

@section('title', 'Full Harvest History')

@section('content')
@php
  $periodMeta = collect($periods)->map(fn (array $p) => [
      'reports' => (int) $p['reports'],
      'area'    => (float) $p['area_ha'],
      'from'    => $p['first_harvest'],
      'to'      => $p['last_harvest'],
  ])->all();
@endphp
<style>
  .fh-page { max-width: 1080px; }

  .fh-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.03);
  }
  .fh-card-head { padding: 18px 20px; border-bottom: 1px solid #eef1f4; }
  .fh-card-title { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .fh-card-sub { font-size: 12.5px; color: #879a8c; margin: 3px 0 0 0; }
  .fh-card-body { padding: 18px 20px 22px; }

  .fh-chart { position: relative; height: 420px; }
  .fh-empty { font-size: 13.5px; color: #94a3b8; line-height: 1.65; padding: 46px 8px; text-align: center; }

  /* Table shell + season badge: the same local styles the reports subpage
     uses, over the shared .data-table rules that live in layouts.sidebar. */
  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  .table-head { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }

  .season-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.01em;
  }
  .season-badge.wet { background: #edf1e9; color: #648268; }
  .season-badge.dry { background: #edf1e9; color: #648268; }
  .season-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

  @media (max-width: 640px) {
    .fh-chart { height: 300px; }
    .table-head { padding: 16px 14px; gap: 12px; }
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'Harvest Performance', 'url' => route('admin.harvests.index')],
  ['label' => 'Full harvest history'],
]"/>

<div class="fh-page">
  <div class="header-panel" style="margin-bottom: 16px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0;">Full harvest history</h1>
    <p style="font-size: 13px; color: #64748b; margin: 5px 0 0 0;">
      @if($periods)
        Every harvest period on record, oldest first — {{ count($periods) }} period{{ count($periods) === 1 ? '' : 's' }} from {{ $from }} to {{ $to }}, wet and dry season compared.
      @else
        Wet season against dry season, period by period.
      @endif
    </p>
  </div>

  <div class="fh-card">
    <div class="fh-card-head">
      <p class="fh-card-title">Yield per hectare over time</p>
      <p class="fh-card-sub">One bar per harvest period, coloured by season — the same colours as the Latest comparison chart on Harvest Performance, so the two read as one story.</p>
    </div>
    <div class="fh-card-body">
      @if($periods)
        <div class="fh-chart"><canvas id="periodsChart" aria-label="Yield per hectare per harvest period, coloured by season" role="img"></canvas></div>
      @else
        <p class="fh-empty">No harvest reports recorded yet, so there are no periods to group.</p>
      @endif
    </div>
  </div>

  @if($periods)
    <div class="data-table-container" style="margin-top: 20px;">
      <div class="table-head">
        <div>
          <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Every harvest period</h2>
          <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">The numbers behind the chart above.</p>
        </div>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Period</th>
            <th>Season</th>
            <th>Reports</th>
            <th>Yield</th>
            <th>Harvested</th>
          </tr>
        </thead>
        <tbody>
          @foreach($periods as $period)
            <tr>
              <td style="font-weight: 600; color: #1e293b;">{{ $period['label'] }}</td>
              <td>
                <span class="season-badge {{ $period['season'] ?? 'dry' }}">
                  <span class="season-badge-dot"></span>
                  {{ ucfirst($period['season'] ?? 'dry') }}
                </span>
              </td>
              <td style="font-variant-numeric: tabular-nums;">{{ (int) $period['reports'] }} rpt</td>
              <td style="font-weight: 700; font-variant-numeric: tabular-nums;">
                {{ $period['yield_per_ha'] !== null ? number_format((float) $period['yield_per_ha'], 3) . ' MT/ha' : '—' }}
              </td>
              <td style="font-size: 13px; color: #475569; font-weight: 600;">{{ $period['first_harvest'] }} &rarr; {{ $period['last_harvest'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

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

    var periodLabels = @js(collect($periods)->pluck('label')->all());
    var periodYears  = @js(collect($periods)->pluck('year')->map(fn ($y) => (string) $y)->all());
    var periodYield  = @js(collect($periods)->pluck('yield_per_ha')->all());
    var periodSeason = @js(collect($periods)->pluck('season')->all());
    var periodMeta   = @js($periodMeta);

    var canvas = document.getElementById('periodsChart');
    if (!canvas || !periodLabels.length) return;
    if (window.aqDestroyChart) aqDestroyChart(canvas);

    // The axis says the year only — "2020 Dry Season" three times over is
    // what made the old dialog slant its labels and crowd its footing. The
    // season lives in the tick's colour instead, matching the bar above it.
    function tickSeasonIndex(ctx) {
      if (!ctx) return -1;
      if (typeof ctx.index === 'number' && ctx.index >= 0) return ctx.index;
      var t = ctx.tick;
      if (t && typeof t.value === 'number' && t.value >= 0) return t.value;
      if (t && typeof t.label === 'string') return periodYears.indexOf(t.label);
      if (t && typeof t.value === 'string') return periodYears.indexOf(t.value);
      return -1;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: periodYears,
        datasets: [{
          label: 'Yield per hectare (MT/ha)',
          data: periodYield,
          backgroundColor: periodSeason.map(function (s) { return seasonColor(s, 0.72); }),
          hoverBackgroundColor: periodSeason.map(function (s) { return seasonColor(s, 1); }),
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
              // The axis carries the year, so the tooltip carries the full
              // period name the reader would otherwise have to guess.
              title: function (items) {
                var i = items.length ? items[0].dataIndex : -1;
                return i >= 0 ? periodLabels[i] : '';
              },
              label: function (ctx) {
                var i = ctx.dataIndex;
                var m = periodMeta[i] || {};
                var season = periodSeason[i];
                var lines = [
                  ' ' + (ctx.parsed.y === null ? '—' : Number(ctx.parsed.y).toFixed(3)) + ' MT/ha'
                  + ' · ' + (season ? season.charAt(0).toUpperCase() + season.slice(1) : '') + ' season'
                ];
                if (m.reports !== undefined) lines.push(' ' + m.reports + ' reports · ' + m.area.toFixed(2) + ' ha');
                if (m.from) lines.push(' ' + m.from + ' → ' + m.to);
                return lines;
              }
            }
          }
        },
        scales: {
          y: Object.assign({}, baseOptions.scales.y, {
            title: { display: true, text: 'MT per hectare', color: '#94a3b8', font: { size: 11, weight: '600' } }
          }),
          x: {
            ticks: {
              color: function (ctx) {
                var i = tickSeasonIndex(ctx);
                return i >= 0 && periodSeason[i] ? seasonColor(periodSeason[i], 1) : '#64748b';
              },
              font: { size: 11, weight: '600' },
              // Four-digit years fit side by side, and the auto-skipper would
              // quietly drop some of them — every period keeps its footing.
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0
            },
            grid: { display: false },
            border: { display: false }
          }
        }
      })
    });
  });
</script>
@endsection
