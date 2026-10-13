@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('content')
@php
  $user = auth()->user();
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<style>
  .dash-page { position: relative; }
  .dash-page::before,
  .dash-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .dash-page::before { width: 360px; height: 360px; background: rgba(16,185,129,0.18); top: -90px; right: -90px; }
  .dash-page::after { width: 300px; height: 300px; background: rgba(59,130,246,0.15); bottom: -70px; left: -70px; }
  .dash-content { position: relative; z-index: 1; }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  .hero-band {
    background: rgb(241, 250, 214);
    border-radius: 22px; padding: 30px 32px; color: #143d23; position: relative; overflow: hidden;
    box-shadow: 0 4px 16px rgba(20,61,35,0.10); margin-bottom: 26px;
  }
  .hero-band::before { content: ""; position: absolute; top: -110px; right: -60px; width: 280px; height: 280px; border-radius: 50%; background: rgba(16,185,129,0.15); }
  .hero-band::after { content: ""; position: absolute; bottom: -90px; left: 25%; width: 200px; height: 200px; border-radius: 50%; background: rgba(59,130,246,0.10); }
  .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
  .hero-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #166534; margin-bottom: 6px; }
  .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; }
  .hero-sub { font-size: 13px; color: #40632d; margin: 6px 0 0 0; }
  .hero-sub strong { color: #14532d; }

  /* Pending action queue */
  .queue-wrap { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; padding: 4px 20px 20px 20px; }
  .queue-item {
    display: flex; align-items: center; gap: 14px; text-decoration: none;
    background: rgba(255,255,255,0.7); border: 1px solid rgba(20,61,35,0.09);
    border-radius: 14px; padding: 16px 18px; transition: all 0.2s ease; position: relative;
  }
  .queue-item:hover { background: #ffffff; box-shadow: 0 8px 24px rgba(0,0,0,0.07); transform: translateY(-1px); border-color: rgba(20,61,35,0.18); }
  .queue-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .queue-icon svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .queue-info { min-width: 0; flex: 1; }
  .queue-info b { display: block; font-size: 13.5px; color: #1e293b; font-weight: 700; }
  .queue-info small { font-size: 11px; color: #94a3b8; display: block; margin-top: 2px; }
  .queue-count { font-size: 22px; font-weight: 800; color: #143d23; font-variant-numeric: tabular-nums; }
  .queue-arrow { width: 16px; height: 16px; stroke: #94a3b8; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
  .queue-empty { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: #40632d; font-weight: 600; }
  .queue-empty svg { width: 16px; height: 16px; stroke: #16a34a; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .btn-hero {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.10);
    color: #14532d; padding: 11px 20px; border-radius: 12px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(20,61,35,0.08);
  }
  .btn-hero:hover { background: #f0fdf4; transform: translateY(-1px); }
  .btn-hero svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .dash-panel {
    background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
  }
  .dash-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
  .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.06em; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; letter-spacing: 0.03em; }
  .panel-body { padding: 18px 22px; }

  .btn-ghost {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none; letter-spacing: 0.03em;
    padding: 7px 14px; border-radius: 9px; background: rgba(20,61,35,0.07);
    display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: rgba(20,61,35,0.13); }
  .btn-ghost svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  /* Dashboard grid */
  .dash-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 22px; align-items: start; }
  .dash-grid.equal { grid-template-columns: repeat(2, minmax(0, 1fr)); }

  /* Stock pills */
  .stock-pill { display: inline-block; padding: 3px 8px; border-radius: 7px; font-size: 10px; font-weight: 700; }
  .stock-ok { background: #f0fdf4; color: #16a34a; }
  .stock-low { background: #fef3c7; color: #d97706; }
  .stock-out { background: #f1f5f9; color: #64748b; }

  /* Table */
  .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
  .admin-table thead tr { background: #f6f8f7; }
  .admin-table th { padding: 11px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .admin-table td { padding: 12px 16px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .admin-table tbody tr { transition: background 0.15s ease; }
  .admin-table tbody tr:hover { background: #fafbfa; }
  .admin-table tbody tr:last-child td { border-bottom: none; }
  .table-avatar { width: 34px; height: 34px; border-radius: 10px; background: rgb(224, 235, 170); color: #49a750; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; text-transform: uppercase; flex-shrink: 0; }
  .role-badge { display: inline-block; padding: 4px 10px; border-radius: 7px; font-size: 11px; font-weight: 700; }
  .role-admin { background: #e0f2fe; color: #0369a1; }
  .role-owner { background: #fef3c7; color: #d97706; }
  .role-farmer { background: #dcfce7; color: #15803d; }

  /* Timeline */
  .qt-item { position: relative; padding: 13px 0 13px 24px; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .qt-item:last-child { border-bottom: none; }
  .qt-item:hover { transform: translateX(4px); }
  .qt-dot { position: absolute; left: 0; top: 18px; width: 12px; height: 12px; border-radius: 50%; border: 2.5px solid; background: #fff; }

  /* Product rows */
  .prod-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; }
  .prod-row + .prod-row { border-top: 1px solid rgba(0,0,0,0.045); }
  .prod-thumb { width: 42px; height: 42px; border-radius: 11px; background: #f4f7f5; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .prod-thumb svg { width: 20px; height: 20px; stroke: #94a3b8; }

  /* Chart summary panels */
  .chart-grid { display: flex; gap: 28px; padding: 24px 26px; align-items: center; flex-wrap: wrap; }
  .chart-box { flex: 0 1 170px; max-width: 170px; position: relative; }

  .chart-grid canvas { max-height: 220px; min-height: 200px; }
  .chart-caption { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 0; }
  .yield-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 26px 0; flex-wrap: wrap; }
  .yield-big { display: flex; align-items: baseline; gap: 6px; margin-bottom: 4px; }
  .yield-big-value { font-size: 28px; font-weight: 800; color: #143d23; font-variant-numeric: tabular-nums; line-height: 1; letter-spacing: 0.02em; }
  .yield-big-unit { font-size: 13px; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: 0.08em; }

  /* Harvest graph period filter (dropdown) */
  .harvest-head-actions { display: flex; align-items: center; gap: 8px; }
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
  .filter-option.is-active { background: #dcfce7; color: #166534; font-weight: 800; }
  .chart-empty {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; color: #64748b; font-weight: 600; text-align: center;
    background: rgba(255,255,255,0.78); border-radius: 10px; padding: 10px;
  }
  .chart-empty[hidden] { display: none; }

  @media (min-width: 981px) {
  }

  @media (max-width: 980px) {
    .dash-grid, .dash-grid.equal { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .hero-band { padding: 22px; }
    .hero-title { font-size: 21px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">


    <!-- 1. HERO -->
    <section class="hero-band reveal in">
      <div class="hero-inner">
        <div>
          <div class="hero-eyebrow">Aquilala IA Management Portal</div>
          <h1 class="hero-title"><span data-greeting>{{ $greeting }}</span>, {{ $user->name }}</h1>
          <p class="hero-sub">Association overview for <strong>{{ $now->format('l, F j, Y') }}</strong> &middot; currently in the <strong>{{ $currentSeasonLabel }}</strong>.</p>
        </div>
        <div class="hero-actions">
          <a href="{{ route('admin.users.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Manage Users
          </a>
          <a href="{{ route('admin.approvals.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Approvals
          </a>
        </div>
      </div>
    </section>

    <!-- 2. KPI CARDS -->
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:22px;">
      <x-kpi-card title="Total IA Members" value="214" trendLabel="Official farmer registry" accent="#16a34a" link="{{ route('admin.users.index') }}">
        <x-slot:icon><img src="{{ asset('images/farmer.png') }}" alt="Farmer" width="26" height="26" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Partner Stores" value="{{ $storeOwnersCount }}" trendLabel="Local supply stores" accent="#d97706" link="{{ route('admin.stores.index') }}">
        <x-slot:icon><img src="{{ asset('images/store.png') }}" alt="Store" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Active Store Items" value="{{ $activeProducts }}" trendLabel="Items listed in store catalogs" accent="#7c3aed" link="{{ route('admin.operations.catalog') }}">
        <x-slot:icon><img src="{{ asset('images/product.png') }}" alt="Product" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Seasonal Harvest Logs" value="{{ $currentSeasonHarvests }}" trendLabel="{{ $currentSeasonLabel }} yield records" accent="#2563eb" link="{{ route('admin.harvests.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 4. HARVEST REPORTS -->
<section style="margin-top:22px;">
  <div class="dash-panel reveal" data-reveal>
    <div class="panel-head">
      <div>
        <h3>Harvest Reports</h3>
        <p>Farmer harvest submissions across the platform</p>
      </div>
      <div class="harvest-head-actions">
        <a href="{{ route('admin.harvests.index') }}" class="btn-ghost">
          Full View
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
    <div class="panel-body" style="padding:0;">
      <div class="yield-row">
        <div>
          <div class="yield-big">
            <span class="yield-big-value" id="harvestYieldValue">0</span>
            <span class="yield-big-unit">MT</span>
          </div>
          <div class="chart-caption">Total yield</div>
        </div>
        <div class="filter-dropdown" id="harvestFilter">
          <button type="button" class="filter-btn" id="harvestFilterBtn" aria-haspopup="true" aria-expanded="false" aria-label="Filter harvest period">
            <svg class="funnel" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span id="harvestFilterLabel">This Year</span>
            <svg class="chev" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <ul class="filter-menu" id="harvestFilterMenu" role="menu" aria-label="Harvest period">
            <li role="none">
              <button type="button" class="filter-option is-active" data-period="year" data-label="This Year">This Year</button>
            </li>
            <li role="none">
              <button type="button" class="filter-option" data-period="month" data-label="This Month">This Month</button>
            </li>
            <li role="none">
              <button type="button" class="filter-option" data-period="week" data-label="This Week">This Week</button>
            </li>
            <li role="none">
              <button type="button" class="filter-option" data-period="days" data-label="Last 30 Days">Last 30 Days</button>
            </li>
          </ul>
        </div>
      </div>
      <div class="chart-grid" style="align-items:stretch;padding-top:18px;">
        <div class="chart-box" style="flex:1 1 340px; max-width:none; min-width:260px;">
          <div style="position:relative;height:220px;">
            <canvas id="harvestChart"></canvas>
            <div id="harvestEmpty" class="chart-empty" hidden></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function setGreeting() {
      var h = new Date().getHours();
      var el = document.querySelector('[data-greeting]');
      if (!el) return;
      el.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
    }
    setGreeting();
    setInterval(setGreeting, 60000);

    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
      var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            ro.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08 });
      revealEls.forEach(function (el) { ro.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    // Harvest report graph with Year / Month / Week / Days period filter (dropdown)
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
          // near-white with a green tint so bars stay crisp on the dark card
          return 'rgba(220, 252, 231, ' + (0.62 + 0.38 * t).toFixed(2) + ')';
        }
        return 'rgba(22, 163, 74, ' + (0.28 + 0.65 * t) + ')';
      });

      if (harvestChart) harvestChart.destroy();
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
  });
</script>
@endsection
