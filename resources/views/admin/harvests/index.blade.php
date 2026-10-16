@extends('layouts.sidebar')

@section('title', 'Harvest Performance')

@section('content')
@php
  $periods = $context['periods'] ?? [];
  $recent  = $context['recent_vs_previous'] ?? null;
  $leaders = array_slice($context['top_farmers_by_reports'] ?? [], 0, 5);

  $seasonByLabel = collect($periods)->mapWithKeys(fn (array $p) => [$p['label'] => $p['season']]);

  $periodMeta = collect($periods)->map(fn (array $p) => [
      'reports' => (int) $p['reports'],
      'area'    => (float) $p['area_ha'],
      'from'    => $p['first_harvest'],
      'to'      => $p['last_harvest'],
  ])->all();
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
    background: #16a34a; color: #ffffff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .btn-nav:hover { background: #15803d; transform: translateY(-1px); }

  .btn-nav.ghost {
    background: #f5f3ff; color: #6d28d9; box-shadow: none;
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

  .hp-modal-overlay {
    position: fixed; inset: 0; z-index: 60; display: none;
    align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.45); padding: 32px 20px 20px;
  }
  .hp-modal-overlay.open { display: flex; }
  .hp-modal {
    background: #ffffff; border-radius: 14px; width: 100%; max-width: 760px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    display: flex; flex-direction: column; overflow: hidden;
    animation: hpModalIn .18s ease-out;
  }
  @keyframes hpModalIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
  .hp-modal-head {
    padding: 18px 20px; border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-shrink: 0;
  }
  .hp-modal-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 3px 0; }
  .hp-modal-sub { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.5; }
  .hp-modal-close {
    background: transparent; border: none; font-size: 24px; line-height: 1;
    color: #94a3b8; cursor: pointer; padding: 2px 6px; border-radius: 8px;
    transition: all .15s; flex-shrink: 0;
  }
  .hp-modal-close:hover { background: #e2e8f0; color: #0f172a; }
  .hp-modal-body { padding: 18px 20px 22px; overflow-y: auto; flex: 1; min-height: 0; }
  .hp-modal-body .hp-list { margin-top: 14px; }
  .hp-chart-lg { height: 240px; }
  @media (max-width: 640px) {
    .hp-modal-overlay { padding-top: 56px; }
    .hp-modal { max-height: calc(100vh - 72px); }
  }

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
  .hp-medal.silver { background: #f1f5f9; color: #64748b; box-shadow: inset 0 0 0 1.5px #cbd5e1; }
  .hp-medal.bronze { background: #ffedd5; color: #9a3412; box-shadow: inset 0 0 0 1.5px #fdba74; }
  .hp-medal.other  { background: #f8fafc; color: #94a3b8; box-shadow: inset 0 0 0 1.5px #e2e8f0; }
  .hp-rank-name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 700; color: #1e293b; }
  .hp-rank-stats {
    font-size: 12.5px; color: #64748b; font-weight: 600;
    font-variant-numeric: tabular-nums; white-space: nowrap;
  }

  .hp-list { list-style: none; margin: 0; padding: 0; font-size: 13px; }
  .hp-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eef1f4; }
  .hp-list li:last-child { border-bottom: none; }
  .hp-list .k { color: #334155; font-weight: 600; }
  .hp-list .v { color: #64748b; font-variant-numeric: tabular-nums; white-space: nowrap; }

  .hp-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed #eef1f4; font-size: 13px; }
  .hp-row:last-child { border-bottom: none; }
  .hp-key { color: #64748b; font-weight: 500; }
  .hp-val { color: #143d23; font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; }

  .hp-delta { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12.5px; font-weight: 700; }
  .hp-delta.up { background: #ecfdf5; color: #15803d; }
  .hp-delta.down { background: #fef2f2; color: #b91c1c; }
  .hp-delta.flat { background: #f1f5f9; color: #64748b; }

  .pill { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
  .pill.wet { background: #eff6ff; color: #1d4ed8; }
  .pill.dry { background: #fffbeb; color: #b45309; }

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
    <a href="{{ route('admin.harvests.compare') }}" class="btn-nav" style="background: #0284c7; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);" title="Compare planting estimates against reported yield">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Estimate vs Harvest
    </a>
    <a href="{{ route('admin.harvests.ai') }}" class="btn-nav ghost" title="Explain this harvest data with AI">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      AI Insights
    </a>
  </div>
</div>

{{-- ── Harvest hub: the section's sub-pages at a glance ── --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <a href="{{ route('admin.harvests.reports') }}" style="text-decoration: none; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; display: flex; gap: 13px; align-items: center; transition: all .15s ease;">
    <span style="width: 40px; height: 40px; border-radius: 11px; background: #f0fdf4; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" stroke-width="1.8" fill="none"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </span>
    <span>
      <span style="display: block; font-size: 14px; font-weight: 700; color: #1a2b1e;">All Harvest Reports</span>
      <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 2px;">Filter, sort and export every report</span>
    </span>
  </a>
  <a href="{{ route('admin.harvests.compare') }}" style="text-decoration: none; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; display: flex; gap: 13px; align-items: center; transition: all .15s ease;">
    <span style="width: 40px; height: 40px; border-radius: 11px; background: #f0f9ff; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" stroke-width="1.8" fill="none"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
    </span>
    <span>
      <span style="display: block; font-size: 14px; font-weight: 700; color: #1a2b1e;">Estimate vs Harvest</span>
      <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 2px;">Yield estimate against actual harvest</span>
    </span>
  </a>
  <a href="{{ route('admin.harvests.ai') }}" style="text-decoration: none; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; display: flex; gap: 13px; align-items: center; transition: all .15s ease;">
    <span style="width: 40px; height: 40px; border-radius: 11px; background: #f5f3ff; color: #6d28d9; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" stroke-width="1.8" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
    </span>
    <span>
      <span style="display: block; font-size: 14px; font-weight: 700; color: #1a2b1e;">AI Harvest Insights</span>
      <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 2px;">Ask questions across the harvest data</span>
    </span>
  </a>
</div>

<!-- System-wide metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['farmers'] }}</div>
      <div class="stat-label">Farmers Reporting</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['reports'] }}</div>
      <div class="stat-label">Harvest Reports</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 20V10"/>
      <path d="M12 10C9 10 6 9 6 5c3 0 6 2 6 5"/>
      <path d="M12 14c3 0 6-1 6-5-3 0-6 2-6 5"/>
      <path d="M3 20h18"/>
      <path d="M3 12l4-4 4 4 5-5 5 5"/>
    </svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['yield'] }}</div>
      <div class="stat-label">Total Yield</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($stats['expenses'], 0) }}</div>
      <div class="stat-label">Total Expenses</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#7c3aed" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="3" x2="8" y2="21"/><path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/></svg>
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
      <button type="button" class="hp-fullview" data-hp-history-open aria-haspopup="dialog" title="Show every harvest period on record">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Full view
      </button>
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

{{-- ── Full past harvest, opened from the Latest comparison card ── --}}
<div class="hp-modal-overlay" id="hpHistoryModal" role="dialog" aria-modal="true" aria-labelledby="hpHistoryTitle">
  <div class="hp-modal">
    <div class="hp-modal-head">
      <div>
        <p class="hp-modal-title" id="hpHistoryTitle">Full harvest history</p>
        <p class="hp-modal-sub">Every harvest period on record, oldest first — grouped by harvest year and season.</p>
      </div>
      <button type="button" class="hp-modal-close" data-hp-history-close aria-label="Close">&times;</button>
    </div>
    <div class="hp-modal-body">
      @if($periods)
        <div class="hp-chart hp-chart-lg"><canvas id="periodsChart" aria-label="Yield per hectare over time, wet season against dry season" role="img"></canvas></div>
        <ul class="hp-list">
          @foreach($periods as $period)
            <li>
              <span class="k">
                {{ $period['label'] }}
                <span class="pill {{ $period['season'] ?? 'dry' }}" style="margin-left:6px;">{{ (int) $period['reports'] }} rpt</span>
              </span>
              <span class="v">
                {{ $period['yield_per_ha'] !== null ? number_format((float) $period['yield_per_ha'], 3) . ' MT/ha' : '—' }}
                <span style="color:#94a3b8; font-weight:500;">&middot; {{ $period['first_harvest'] }} &rarr; {{ $period['last_harvest'] }}</span>
              </span>
            </li>
          @endforeach
        </ul>
      @else
        <p class="hp-empty">No harvest reports recorded yet, so there are no periods to group.</p>
      @endif
    </div>
  </div>
</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var hasChart = !!window.Chart;

    /* ── Full harvest history modal ─────────────────────────────── */
    var historyModal = document.getElementById('hpHistoryModal');
    var historyOpeners = document.querySelectorAll('[data-hp-history-open]');

    function closeHistory() {
      if (!historyModal) return;
      historyModal.classList.remove('open');
      document.body.style.overflow = '';
    }

    historyOpeners.forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!historyModal) return;
        historyModal.classList.add('open');
        document.body.style.overflow = 'hidden';
        // The chart is built on first open so it measures a visible container.
        requestAnimationFrame(function () {
          renderPeriodsChart();
          if (periodsChart) periodsChart.resize();
        });
      });
    });

    if (historyModal) {
      historyModal.querySelectorAll('[data-hp-history-close]').forEach(function (el) {
        el.addEventListener('click', closeHistory);
      });
      historyModal.addEventListener('click', function (e) {
        if (e.target === historyModal) closeHistory();
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeHistory();
    });

    var WET = 'rgba(29, 78, 216, 0.78)';
    var DRY = 'rgba(180, 83, 9, 0.78)';
    var WET_HOVER = 'rgba(29, 78, 216, 1)';
    var DRY_HOVER = 'rgba(180, 83, 9, 1)';
    var WET_LINE = 'rgb(29, 78, 216)';
    var DRY_LINE = 'rgb(180, 83, 9)';

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

    /* ── Full harvest history: one line per season, trended over time ── */
    var periodLabels = @js(collect($periods)->pluck('label')->all());
    var periodYield  = @js(collect($periods)->pluck('yield_per_ha')->all());
    var periodSeason = @js(collect($periods)->pluck('season')->all());
    var periodMeta   = @js($periodMeta);
    var periodsChart = null;

    // Every period belongs to exactly one season, so the other line simply has
    // a gap there. spanGaps stitches each season's own points together, which
    // is what turns the two series into a trend rather than scattered dots.
    function seasonLine(season, label, color) {
      return {
        label: label,
        data: periodLabels.map(function (_, i) {
          return periodSeason[i] === season ? periodYield[i] : null;
        }),
        borderColor: color,
        backgroundColor: color,
        pointBackgroundColor: color,
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2.5,
        tension: 0.32,
        spanGaps: true,
        fill: false
      };
    }

    function renderPeriodsChart() {
      if (periodsChart || !hasChart) return;
      var periodsCanvas = document.getElementById('periodsChart');
      if (!periodsCanvas || !periodLabels.length) return;
      if (window.aqDestroyChart) aqDestroyChart(periodsCanvas);

      periodsChart = new Chart(periodsCanvas, {
        type: 'line',
        data: {
          labels: periodLabels,
          datasets: [
            seasonLine('wet', 'Wet season', WET_LINE),
            seasonLine('dry', 'Dry season', DRY_LINE)
          ]
        },
        options: Object.assign({}, baseOptions, {
          plugins: {
            legend: {
              display: true,
              position: 'top',
              align: 'end',
              labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8,
                padding: 14,
                color: '#475569',
                font: { size: 11.5, weight: '600' }
              }
            },
            tooltip: {
              callbacks: {
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
            x: baseOptions.scales.x
          }
        })
      });
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
