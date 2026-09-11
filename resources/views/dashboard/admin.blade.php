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
    background: rgb(230, 245, 195);
    border-radius: 22px; padding: 30px 32px; color: #143d23; position: relative;
    box-shadow: 0 4px 16px rgba(20,61,35,0.10); margin-bottom: 26px;
  }
  .hero-inner { display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
  .hero-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #166534; margin-bottom: 6px; }
  .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; }
  .hero-sub { font-size: 13px; color: #40632d; margin: 6px 0 0 0; }
  .hero-sub strong { color: #14532d; }

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
  .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
  .panel-body { padding: 18px 22px; }

  .btn-ghost {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none;
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
  .chart-legend { flex: 1 1 180px; display: flex; flex-direction: column; gap: 11px; }
  .legend-row { display: flex; align-items: center; gap: 10px; }
  .legend-dot { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }
  .legend-label { font-size: 13px; color: #64748b; flex: 1; }
  .legend-value { font-size: 14px; font-weight: 800; color: #1e2922; }
  .legend-pct { font-size: 11px; color: #94a3b8; width: 40px; text-align: right; }

  .bar-list { padding: 22px 24px; display: flex; flex-direction: column; gap: 16px; }
  .bar-row { display: flex; align-items: center; gap: 12px; }
  .bar-label { font-size: 12px; font-weight: 600; color: #475569; width: 86px; flex-shrink: 0; text-align: right; }
  .bar-track { flex: 1; height: 24px; background: #f1f5f9; border-radius: 8px; overflow: hidden; }
  .bar-fill { width: 0; height: 100%; border-radius: 8px; transition: width 0.75s cubic-bezier(0.4,0,0.2,1); min-width: 0; }
  .bar-fill.empty { background: transparent !important; }
  .bar-val { font-size: 13px; font-weight: 800; color: #1e2922; width: 30px; }
  .bar-pct { font-size: 11px; color: #94a3b8; width: 42px; text-align: right; }

  .chart-grid canvas { max-height: 220px; min-height: 200px; }
  .chart-caption { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }

  @media (min-width: 981px) {
    .chart-legend {
      background: #f8fafc;
      border: 1px solid rgba(0,0,0,0.05);
      border-radius: 14px;
      padding: 16px 20px;
      max-width: 220px;
    }
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
          <div class="hero-eyebrow">System Administration</div>
          <h1 class="hero-title"><span data-greeting>{{ $greeting }}</span>, {{ $user->name }}</h1>
          <p class="hero-sub">Here's your <strong>platform overview</strong> for {{ $now->format('l, F j, Y') }}.</p>
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
      <x-kpi-card title="Total Users" value="{{ $totalUsers }}" trendValue="{{ $farmersCount }} farmers" trendDirection="{{ $totalUsers > 0 ? 'up' : 'neutral' }}" trendLabel="registered accounts" accent="#2563eb" link="{{ route('admin.users.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Store Owners" value="{{ $storeOwnersCount }}" trendValue="{{ $activeProducts }} active" trendDirection="{{ $activeProducts > 0 ? 'up' : 'neutral' }}" trendLabel="products listed" accent="#d97706" link="{{ route('admin.users.index') }}">
        <x-slot:icon><img src="{{ asset('images/store.png') }}" alt="Store" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Quotations" value="{{ $totalQuotations }}" trendValue="{{ $computedQuotations }} estimate-ready" trendDirection="{{ $computedQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="{{ $viewedQuotations }} viewed" accent="#7c3aed" link="{{ route('admin.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Active Products" value="{{ $activeProducts }}" trendValue="{{ $outOfStockProducts }} out of stock" trendDirection="{{ $outOfStockProducts > 0 ? 'down' : 'up' }}" trendLabel="needs restocking" accent="#16a34a" link="{{ route('admin.analytics.index') }}">
        <x-slot:icon><img src="{{ asset('images/product.png') }}" alt="Product" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
    </section>

<!-- 3. REGISTRATION OVERVIEW -->
<section style="margin-bottom:22px;">
  <div class="dash-panel reveal" data-reveal>
    <div class="panel-head">
      <div>
        <h3>Registration Overview</h3>
        <p>Account distribution across the platform</p>
      </div>
      <a href="{{ route('admin.users.index') }}" class="btn-ghost">
        Full View
        <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    @if($totalUsers > 0)
      <div class="chart-grid" style="align-items:stretch;">
        <div class="chart-box" style="flex:1 1 340px; max-width:none; min-width:260px;">
          <div class="chart-caption">New accounts &middot; last 14 days</div>
          <canvas id="roleChart"></canvas>
        </div>
        <div class="chart-legend" id="roleLegend" style="justify-content:center;"></div>
      </div>
    @else
      <div class="panel-body" style="text-align:center;padding:34px 16px;">
        <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No platform registrations yet.</p>
      </div>
    @endif
  </div>
</section>

<!-- 4. MARKETPLACE + QUOTATION SUMMARIES -->
<section class="dash-grid equal">
  <!-- Marketplace stock health -->
  <div class="dash-panel reveal" data-reveal>
    <div class="panel-head">
      <div>
        <h3>Marketplace Stock</h3>
        <p>Inventory health across stores</p>
      </div>
      <a href="{{ route('admin.analytics.index') }}" class="btn-ghost">
        Full View
        <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    <div class="bar-list">
      @php $stockMax = max(1, $stockCounts['in_stock'], $stockCounts['low_stock'], $stockCounts['out_of_stock']); @endphp
      @foreach([
        ['label' => 'In stock', 'count' => $stockCounts['in_stock'], 'color' => '#16a34a'],
        ['label' => 'Low stock', 'count' => $stockCounts['low_stock'], 'color' => '#f59e0b'],
        ['label' => 'Out of stock', 'count' => $stockCounts['out_of_stock'], 'color' => '#94a3b8'],
      ] as $stockRow)
        <div class="bar-row">
          <span class="bar-label">{{ $stockRow['label'] }}</span>
          <div class="bar-track">
            <div class="bar-fill {{ $stockRow['count'] > 0 ? '' : 'empty' }}"
                 style="background:{{ $stockRow['color'] }};" data-w="{{ $stockRow['count'] > 0 ? round($stockRow['count'] / $stockMax * 100) : 0 }}"></div>
          </div>
          <span class="bar-val">{{ $stockRow['count'] }}</span>
          <span class="bar-pct">{{ $stockRow['count'] > 0 ? round($stockRow['count'] / $stockMax * 100) : 0 }}%</span>
        </div>
      @endforeach
    </div>
  </div>

  <!-- Quotation status summary -->
  <div class="dash-panel reveal" data-reveal>
    <div class="panel-head">
      <div>
        <h3>Quotation Status</h3>
        <p>Current state of platform quotations</p>
      </div>
      <a href="{{ route('admin.quotations.index') }}" class="btn-ghost">
        Full View
        <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    <div class="bar-list">
      @php $qtMax = max(1, $quotationStatusCounts['computed'], $quotationStatusCounts['viewed'], $quotationStatusCounts['expired']); @endphp
      @foreach([
        ['label' => 'Estimate-ready', 'key' => 'computed', 'color' => '#16a34a'],
        ['label' => 'Viewed', 'key' => 'viewed', 'color' => '#2563eb'],
        ['label' => 'Expired', 'key' => 'expired', 'color' => '#94a3b8'],
      ] as $qtRow)
        @php $qtCount = $quotationStatusCounts[$qtRow['key']]; @endphp
        <div class="bar-row">
          <span class="bar-label">{{ $qtRow['label'] }}</span>
          <div class="bar-track">
            <div class="bar-fill {{ $qtCount > 0 ? '' : 'empty' }}"
                 style="background:{{ $qtRow['color'] }};" data-w="{{ $qtCount > 0 ? round($qtCount / $qtMax * 100) : 0 }}"></div>
          </div>
          <span class="bar-val">{{ $qtCount }}</span>
          <span class="bar-pct">{{ $qtCount > 0 ? round($qtCount / $qtMax * 100) : 0 }}%</span>
        </div>
      @endforeach
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

    // Animated summary bar fills
    document.querySelectorAll('.bar-fill').forEach(function (fill, i) {
      setTimeout(function () {
        fill.style.width = (fill.dataset.w || '0') + '%';
      }, 350 + i * 120);
    });

    // Registration activity over the past 14 days + account distribution
    var regData = @js($registrationTrend);
    var roleData = @js([
      ['label' => 'Admins', 'value' => $adminCount, 'color' => '#0369a1'],
      ['label' => 'Store Owners', 'value' => $storeOwnersCount, 'color' => '#d97706'],
      ['label' => 'Farmers', 'value' => $farmersCount, 'color' => '#16a34a'],
    ]);
    var regCanvas = document.getElementById('roleChart');
    if (regCanvas && window.Chart) {
      new Chart(regCanvas, {
        type: 'line',
        data: {
          labels: regData.map(function (d) { return d.label; }),
          datasets: [{
            label: 'New accounts',
            data: regData.map(function (d) { return d.count; }),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#fff',
            pointBorderWidth: 1.5,
            hoverRadius: 5
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function (items) {
                  var idx = items[0].dataIndex;
                  return regData[idx].date;
                },
                label: function (ctx) {
                  return ctx.parsed.y + (ctx.parsed.y === 1 ? ' new account' : ' new accounts');
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: '#94a3b8', maxTicksLimit: 7, maxRotation: 0, autoSkipPadding: 14 },
              border: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { precision: 0, color: '#94a3b8', maxTicksLimit: 5 },
              grid: { color: 'rgba(0,0,0,0.05)' },
              border: { display: false }
            }
          }
        }
      });

      var legend = document.getElementById('roleLegend');
      var total = roleData.reduce(function (sum, r) { return sum + r.value; }, 0);
      roleData.forEach(function (r) {
        var row = document.createElement('div');
        row.className = 'legend-row';
        row.innerHTML =
          '<span class="legend-dot" style="background:' + r.color + '"></span>' +
          '<span class="legend-label">' + r.label + '</span>' +
          '<span class="legend-value">' + r.value + '</span>' +
          '<span class="legend-pct">' + (total > 0 ? Math.round(r.value / total * 100) : 0) + '%</span>';
        legend.appendChild(row);
      });
    }
  });
</script>
@endsection
