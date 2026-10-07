@extends('layouts.sidebar')

@section('title', 'Product Demand — All Products')

@section('content')
@include('partials.admin-tabs', [
  'tabs' => [
    ['key' => 'demand',   'label' => 'Demand Trends',       'url' => route('admin.quotations.index')],
    ['key' => 'harvests', 'label' => 'Harvest Performance',  'url' => route('admin.harvests.index')],
    ['key' => 'ai',       'label' => 'AI Insights',          'url' => route('admin.harvests.ai')],
  ],
  'activeTab' => 'demand',
])
<style>
  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #fafbfa; padding: 14px 16px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
  td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
  tbody tr:last-child td { border-bottom: 0; }
  tbody tr:hover { background: #fafbfa; }

  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
  }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .cell-strong { font-weight: 600; color: #1e2922; }
  .cell-muted { color: #64748b; }
  .cap { text-transform: capitalize; }
  .rank {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 7px;
    background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 700;
  }
  .rank.top { background: #143d23; color: #fff; }

  .chart-hscroll {
    overflow-x: auto; overflow-y: hidden;
    padding: 20px 20px 8px;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .chart-hscroll::-webkit-scrollbar { height: 8px; }
  .chart-hscroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
  .chart-hscroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 999px; }
  .chart-box { position: relative; width: 100%; }
  .chart-box canvas { cursor: pointer; }

  .chart-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 12px 20px; border-top: 1px solid #f1f5f9;
    font-size: 13px; color: #64748b; min-height: 46px;
  }
  .chart-bar .pd-name { font-weight: 700; color: #1e2922; font-size: 14px; }
  .chart-bar .pd-meta { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
  .chart-bar .pd-meta b { color: #143d23; }
  .chart-hint { font-size: 12px; color: #94a3b8; }

  .scroll-btn {
    width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: #ffffff; color: #475569; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all 0.15s ease; flex-shrink: 0;
  }
  .scroll-btn:hover:not(:disabled) { background: #f1f5f9; color: #143d23; }
  .scroll-btn:disabled { opacity: 0.35; cursor: default; }
  .scroll-ctl { display: flex; align-items: center; gap: 8px; }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 9px; border: 1px solid #e2e8f0;
    background: #ffffff; color: #143d23; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.15s ease;
  }
  .btn-ghost:hover { background: #f1f5f9; }

  .stat-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px;
    padding: 18px 22px; display: flex; align-items: center; justify-content: space-between;
  }
  .stat-value { font-size: 26px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }
</style>

<div class="header-panel" style="margin-bottom: 14px;">
  <a href="{{ route('admin.quotations.index') }}" class="btn-ghost" style="margin-bottom: 12px;">
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Demand Trends
  </a>
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">All Product Demand</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">Every product that appears in an estimate, ranked by how often it is quoted.</p>
</div>

@if($summary['total'] > 0)
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
    <div class="stat-card">
      <div>
        <div class="stat-value">{{ $summary['total'] }}</div>
        <div class="stat-label">Products in Demand</div>
      </div>
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-value">{{ number_format($summary['estimates']) }}</div>
        <div class="stat-label">Product Citations</div>
      </div>
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-value">{{ rtrim(rtrim(number_format($summary['totalQty'], 2), '0'), '.') }}</div>
        <div class="stat-label">Total Quantity</div>
      </div>
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#7c3aed" stroke-width="1.5" fill="none"><path d="M3 6h18M3 12h18M3 18h12"/></svg>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-value">₱{{ number_format($summary['totalValue'], 0) }}</div>
        <div class="stat-label">Total Quoted Value</div>
      </div>
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#d97706" stroke-width="1.5" fill="none"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
  </div>

  <div class="data-table-container" style="margin-bottom: 24px;">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <div>
        <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Top {{ count($topProducts) }} Product Demand</h2>
        <p class="chart-hint" style="margin: 4px 0 0 0;">Hover or click a bar to reveal the full product name. The remaining {{ $summary['total'] - count($topProducts) }} product(s) are in the table below.</p>
      </div>
      <div class="scroll-ctl">
        <button type="button" class="scroll-btn" data-scroll="prev" aria-label="Scroll graph left">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button type="button" class="scroll-btn" data-scroll="next" aria-label="Scroll graph right">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>

    <div class="chart-hscroll" id="productScroll">
      <div class="chart-box" style="height: 320px;" id="productTrack">
        <canvas id="allProductsChart"></canvas>
      </div>
    </div>

    <div class="chart-bar" id="productReadout">
      <span class="chart-hint">Hover or click a bar for full details.</span>
    </div>
  </div>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Ranked Breakdown</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">All {{ $summary['total'] }} product(s), highest demand first.</p>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th style="width: 52px;">#</th>
            <th>Product</th>
            <th>Category</th>
            <th>Crops</th>
            <th class="num">Estimates</th>
            <th class="num">Quantity</th>
            <th class="num">Value</th>
          </tr>
        </thead>
        <tbody>
          @foreach($products as $i => $prod)
            <tr>
              <td><span class="rank{{ $i < 3 ? ' top' : '' }}">{{ $i + 1 }}</span></td>
              <td class="cell-strong">{{ $prod['display_name'] ?? $prod['name'] }}</td>
              <td><span class="badge" style="background: #e0f2fe; color: #0284c7;">{{ $prod['category'] }}</span></td>
              <td class="cell-muted cap">{{ $prod['crops'] ?: '—' }}</td>
              <td class="num">{{ $prod['quotation_count'] }}</td>
              <td class="num">{{ rtrim(rtrim(number_format($prod['total_qty'], 2), '0'), '.') }} {{ $prod['unit'] }}</td>
              <td class="num">₱{{ number_format($prod['total_value'], 0) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@else
  <div class="data-table-container">
    <div style="text-align: center; padding: 48px; color: #94a3b8;">
      <p style="margin: 0;">No product demand data yet.</p>
    </div>
  </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    var products = @js($topProducts);
    var canvas = document.getElementById('allProductsChart');
    var scroll = document.getElementById('productScroll');
    var track = document.getElementById('productTrack');
    var readout = document.getElementById('productReadout');
    if (!canvas || !products.length) return;

    // Two-line labels run to ~18 characters, so a slot narrower than this
    // would let neighbouring labels overlap.
    var MIN_SLOT = 148;
    var pinned = -1;
    var hovered = -1;
    var chart = null;

    function peso(n) {
      return '\u20B1' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
    }
    function qty(n) {
      return Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }
    // Product names carry a trailing pack size ("\u2014 50 kg") that adds nothing
    // to a rank axis. Drop it for the tick and wrap what is left onto two
    // lines; the untouched name stays in the tooltip, readout and table.
    function stripPackSize(name) {
      var s = String(name || '');
      var i = Math.max(s.lastIndexOf('\u2014'), s.lastIndexOf('\u2013'));
      if (i > 0) s = s.slice(0, i);
      else if (/\s-\s/.test(s)) s = s.split(/\s-\s/)[0];
      return s.replace(/\s+$/, '');
    }
    function chartLabel(name) {
      var words = stripPackSize(name).split(' ');
      var lines = [];
      var line = '';
      words.forEach(function (w) {
        var probe = line ? line + ' ' + w : w;
        if (line && probe.length > 16) { lines.push(line); line = w; }
        else { line = probe; }
      });
      if (line) lines.push(line);
      if (lines.length > 2) {
        lines[1] = lines.slice(1).join(' ');
        lines.length = 2;
        if (lines[1].length > 16) lines[1] = lines[1].slice(0, 15) + '\u2026';
      }
      return lines;
    }

    // One name at a time: the axis keeps the short label at all times, hover
    // reveals the full name in the tooltip, and a click pins it in the
    // readout underneath. Nothing is drawn on top of the bar.
    function paintBars() {
      if (!chart) return;
      var active = hovered >= 0 ? hovered : pinned;
      chart.data.datasets[0].backgroundColor = products.map(function (p, i) {
        if (i === active) return 'rgba(20, 61, 35, 1)';
        return pinned >= 0 ? 'rgba(20, 61, 35, 0.32)' : 'rgba(20, 61, 35, 0.8)';
      });
      chart.update('none');
    }

    function showReadout(idx) {
      if (!readout) return;
      if (idx < 0) {
        readout.innerHTML = '<span class="chart-hint">Hover or click a bar for full details.</span>';
        return;
      }
      var p = products[idx];
      readout.innerHTML =
        '<span><span class="pd-name">' + p.name + '</span> &mdash; ' + p.category + '</span>' +
        '<span class="pd-meta">' +
          '<span>Crops: ' + (p.crops || '\u2014') + '</span>' +
          '<span><b>' + p.quotation_count + '</b> estimate(s)</span>' +
          '<span>' + qty(p.total_qty) + ' ' + p.unit + '</span>' +
          '<span>' + peso(p.total_value) + '</span>' +
        '</span>';
    }

    function syncScrollButtons() {
      document.querySelectorAll('[data-scroll]').forEach(function (btn) {
        var max = scroll.scrollWidth - scroll.clientWidth;
        btn.disabled = max <= 2 || (btn.dataset.scroll === 'prev' ? scroll.scrollLeft <= 2 : scroll.scrollLeft >= max - 2);
      });
    }

    function build() {
      var slot = Math.max(MIN_SLOT, Math.floor(scroll.clientWidth / products.length));
      track.style.width = slot * products.length + 'px';
      if (chart) chart.destroy();
      chart = new Chart(canvas, {
        type: 'bar',
        data: {
          labels: products.map(function (p) { return chartLabel(p.name); }),
          datasets: [{
            label: 'Estimates',
            data: products.map(function (p) { return p.quotation_count; }),
            backgroundColor: products.map(function (p, i) {
              return i === pinned ? 'rgba(20, 61, 35, 1)' : 'rgba(20, 61, 35, 0.8)';
            }),
            hoverBackgroundColor: 'rgba(20, 61, 35, 1)',
            borderRadius: 5,
            maxBarThickness: 120,
            categoryPercentage: 0.9,
            barPercentage: 0.72
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
            layout: { padding: { right: 6 } },
            interaction: { mode: 'index', intersect: false },
            onHover: function (evt, items) {
              var next = items.length ? items[0].index : -1;
              if (next === hovered) return;
              hovered = next;
              paintBars();
            },
            onClick: function (evt, items) {
              pinned = (items.length && items[0].index !== pinned) ? items[0].index : -1;
              hovered = -1;
              paintBars();
              showReadout(pinned);
            },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function (items) { return products[items[0].dataIndex].name; },
                label: function (ctx) {
                  var p = products[ctx.dataIndex];
                  return [
                    ctx.parsed.y + ' estimate(s)',
                    'Crops: ' + (p.crops || '\u2014'),
                    qty(p.total_qty) + ' ' + p.unit + ' \u00B7 ' + peso(p.total_value)
                  ];
                }
              }
            }
          },
          scales: {
            x: {
              ticks: { color: '#334155', font: { weight: '600', size: 12 }, maxRotation: 0, autoSkip: false },
              grid: { display: false },
              border: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { color: '#334155', font: { weight: '600', size: 12 }, precision: 0, maxTicksLimit: 5 },
              grid: { color: 'rgba(0,0,0,0.08)' },
              border: { display: false }
            }
            }
          }
        });

        syncScrollButtons();
      }

    build();

    document.querySelectorAll('[data-scroll]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        scroll.scrollBy({ left: (btn.dataset.scroll === 'next' ? 1 : -1) * Math.max(240, scroll.clientWidth * 0.8), behavior: 'smooth' });
      });
    });
    scroll.addEventListener('scroll', syncScrollButtons);
    window.addEventListener('resize', build);
  });
</script>
@endsection
