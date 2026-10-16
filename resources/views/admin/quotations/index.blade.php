@extends('layouts.sidebar')

@section('title', 'Cost Quotation Analytics')

@section('content')
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
  .stat-value { font-size: 28px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }
  .badge-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
  .avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 10px;
    flex-shrink: 0;
  }
  .chart-box { position: relative; width: 100%; }
  .chart-box canvas { cursor: pointer; }
  .chart-scroll { overflow-x: auto; }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .cell-strong { font-weight: 600; color: #1e2922; }
  .cell-muted { color: #64748b; }
  .cap { text-transform: capitalize; }

  .chart-hscroll {
    overflow-x: auto; overflow-y: hidden;
    padding: 20px 20px 8px;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .chart-hscroll::-webkit-scrollbar { height: 8px; }
  .chart-hscroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
  .chart-hscroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 999px; }

  .chart-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 12px 20px; border-top: 1px solid #f1f5f9;
    font-size: 13px; color: #64748b; min-height: 46px; flex-wrap: wrap;
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
    padding: 7px 13px; border-radius: 9px; border: 1px solid #e2e8f0;
    background: #ffffff; color: #143d23; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.15s ease; white-space: nowrap;
  }
  .btn-ghost:hover { background: #f1f5f9; }

  .seg {
    display: inline-flex; padding: 3px; gap: 2px;
    background: #f1f5f9; border-radius: 10px; flex-shrink: 0;
  }
  .seg-btn {
    border: 0; background: transparent; cursor: pointer;
    padding: 7px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #64748b;
    transition: all 0.15s ease; white-space: nowrap;
  }
  .seg-btn:hover { color: #143d23; }
  .seg-btn.is-active { background: #ffffff; color: #143d23; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }

  .mini { }
  .mini-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
  .mini-value { font-size: 20px; font-weight: 800; color: #143d23; margin-top: 4px; line-height: 1; }
</style>

<div class="header-panel" style="margin-bottom: 10px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Demand Trends</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">System-wide demand visibility — which products and crops are in demand.</p>
</div>

<!-- System-wide metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['total'] }}</div>
      <div class="stat-label">Total Estimates</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['computed'] }}</div>
      <div class="stat-label">Fresh Estimates</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['viewed'] }}</div>
      <div class="stat-label">Viewed by Stores</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#7c3aed" stroke-width="1.5" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['expired'] }}</div>
      <div class="stat-label">Expired</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#94a3b8" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
  </div>
</div>

<!-- Product & Crop Demand (de-identified — products/crops only) -->
<div class="data-table-container" style="margin-bottom: 24px;">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div>
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Product Demand</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Top {{ count($productDemand) }} most-quoted products across all estimates, with their crop types.</p>
    </div>
    <div class="scroll-ctl">
      @if($productTotal > count($productDemand))
        <a href="{{ route('admin.quotations.products') }}" class="btn-ghost">
          Show all {{ $productTotal }} products
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      @endif
      <button type="button" class="scroll-btn" data-product-scroll="prev" aria-label="Scroll graph left">
        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="scroll-btn" data-product-scroll="next" aria-label="Scroll graph right">
        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
  @if(count($productDemand) > 0)
    <div class="chart-hscroll" id="productScroll">
      <div class="chart-box" style="height: 300px;" id="productTrack">
        <canvas id="productDemandChart"></canvas>
      </div>
    </div>
    <div class="chart-bar" id="productReadout">
      <span class="chart-hint">Hover or click a bar to see the full product name.</span>
    </div>
  @else
    <div style="text-align: center; padding: 32px; color: #94a3b8;">
      <p style="margin: 0;">No product demand data yet.</p>
    </div>
  @endif
</div>

  <x-table-toolbar
      uid="admin-crops"
      work-table="adminCropTable"
      per-page="10"
      noun="crops"
      search-placeholder="Search crops…"
      :sorts="[
          ['key' => 'estimates', 'dir' => -1, 'type' => 'num', 'label' => 'Estimates (highest)'],
          ['key' => 'planted_area', 'dir' => -1, 'type' => 'num', 'label' => 'Planted area (largest)'],
          ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
      ]"
    />

<div class="data-table-container" style="margin-bottom: 24px;">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div>
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Crop Demand &amp; Planting</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Most-quoted crop types from estimates, against what farmers have actually planned or planted.</p>
    </div>
    <div class="seg" role="tablist" aria-label="Crop view">
      <button type="button" class="seg-btn is-active" data-crop-mode="quote" role="tab" aria-selected="true">Most Quoted</button>
      <button type="button" class="seg-btn" data-crop-mode="planted" role="tab" aria-selected="false">Most Planted</button>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9;">
    <div class="mini">
      <div class="mini-label">Crops Tracked</div>
      <div class="mini-value">{{ $cropTotal }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Quoted Crops</div>
      <div class="mini-value">{{ collect($cropRows)->where('estimates', '>', 0)->count() }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Planted Crops</div>
      <div class="mini-value">{{ collect($cropRows)->where('batches', '>', 0)->count() }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Farmer Crop Batches</div>
      <div class="mini-value">{{ $plantingTotal }}</div>
    </div>
  </div>

  @if(count($cropRows) > 0)
    <div class="chart-hscroll" id="cropScroll">
      <div class="chart-box" style="height: 300px;" id="cropTrack">
        <canvas id="cropDemandChart"></canvas>
      </div>
    </div>
    <div class="chart-bar" id="cropReadout">
      <span class="chart-hint" id="cropReadoutHint">Hover or click a bar to see the full breakdown.</span>
    </div>
    <p style="margin: 0; padding: 0 20px 12px; font-size: 12px; color: #94a3b8;" id="cropChartNote"></p>
    <div class="chart-scroll table-scroll">
      <table id="adminCropTable" class="data-table">
        <thead>
          <tr>
            <th>Crop</th>
            <th class="num">Estimates</th>
            <th class="num">Quoted Area</th>
            <th class="num">Quoted Value</th>
            <th class="num">Batches</th>
            <th class="num">Active</th>
            <th class="num">Farmers</th>
            <th class="num">Planted Area</th>
          </tr>
        </thead>
        <tbody>
          @foreach($cropRows as $crop)
            <tr data-crop="{{ strtolower($crop['crop']) }}"
                data-estimates="{{ $crop['estimates'] }}"
                data-planted_area="{{ $crop['planted_area'] }}">
              <td class="cell-strong cap">{{ $crop['crop'] }}</td>
              <td class="num">{{ $crop['estimates'] }}</td>
              <td class="num">{{ number_format($crop['quoted_area'], 1) }} ha</td>
              <td class="num">₱{{ number_format($crop['quoted_value'], 0) }}</td>
              <td class="num">{{ $crop['batches'] }}</td>
              <td class="num">{{ $crop['active'] }}</td>
              <td class="num">{{ $crop['farmers'] }}</td>
              <td class="num">{{ number_format($crop['planted_area'], 1) }} ha</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div style="text-align: center; padding: 32px; color: #94a3b8;">
      <p style="margin: 0;">No crop demand or planting data yet.</p>
    </div>
  @endif
</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    var products = @js($productDemand);
    var crops = @js($cropRows);

    function peso(n) {
      return '₱' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
    }
    function qty(n) {
      return Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }

    var productCanvas = document.getElementById('productDemandChart');
    var productScroll = document.getElementById('productScroll');
    var productTrack = document.getElementById('productTrack');
    var productReadout = document.getElementById('productReadout');
    if (productCanvas && productScroll && productTrack && products.length) {
      // Two-line labels run to ~18 characters, so a slot narrower than this
      // would let neighbouring labels overlap.
      var MIN_SLOT = 128;
      var pinned = -1;
      var hovered = -1;
      var productChart = null;

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
      function paintProductBars() {
        if (!productChart) return;
        var active = hovered >= 0 ? hovered : pinned;
        productChart.data.datasets[0].backgroundColor = products.map(function (p, i) {
          if (i === active) return 'rgba(20, 61, 35, 1)';
          return pinned >= 0 ? 'rgba(20, 61, 35, 0.32)' : 'rgba(20, 61, 35, 0.8)';
        });
        productChart.update('none');
      }

      function showProduct(idx) {
        if (!productReadout) return;
        if (idx < 0) {
          productReadout.innerHTML = '<span class="chart-hint">Hover or click a bar to see the full product name.</span>';
          return;
        }
        var p = products[idx];
        productReadout.innerHTML =
          '<span><span class="pd-name">' + p.name + '</span> &mdash; ' + p.category + '</span>' +
          '<span class="pd-meta">' +
            '<span>Crops: ' + (p.crops || '\u2014') + '</span>' +
            '<span><b>' + p.quotation_count + '</b> estimate(s)</span>' +
            '<span>' + qty(p.total_qty) + ' ' + p.unit + '</span>' +
            '<span>' + peso(p.total_value) + '</span>' +
          '</span>';
      }

      function syncProductScroll() {
        document.querySelectorAll('[data-product-scroll]').forEach(function (btn) {
          var max = productScroll.scrollWidth - productScroll.clientWidth;
          btn.disabled = max <= 2 || (btn.dataset.productScroll === 'prev'
            ? productScroll.scrollLeft <= 2
            : productScroll.scrollLeft >= max - 2);
        });
      }

      function buildProductChart() {
        // Top 5 normally share the full card width, so the strip only starts
        // scrolling once a slot would drop below MIN_SLOT.
        var slot = Math.max(MIN_SLOT, Math.floor(productScroll.clientWidth / products.length));
        productTrack.style.width = slot * products.length + 'px';
        if (productChart) productChart.destroy();
        if (window.aqDestroyChart) aqDestroyChart(productCanvas);

        productChart = new Chart(productCanvas, {
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
              paintProductBars();
            },
            onClick: function (evt, items) {
              pinned = (items.length && items[0].index !== pinned) ? items[0].index : -1;
              hovered = -1;
              paintProductBars();
              showProduct(pinned);
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

        syncProductScroll();
      }

      buildProductChart();

      document.querySelectorAll('[data-product-scroll]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          productScroll.scrollBy({
            left: (btn.dataset.productScroll === 'next' ? 1 : -1) * Math.max(240, productScroll.clientWidth * 0.8),
            behavior: 'smooth'
          });
        });
      });
      productScroll.addEventListener('scroll', syncProductScroll);
      window.addEventListener('resize', buildProductChart);
    }

    var cropCanvas = document.getElementById('cropDemandChart');
    var cropScroll = document.getElementById('cropScroll');
    var cropTrack = document.getElementById('cropTrack');
    var cropReadout = document.getElementById('cropReadout');
    var cropNote = document.getElementById('cropChartNote');

    if (cropCanvas && cropScroll && cropTrack && crops.length) {
      var CROP_MIN_SLOT = 128;
      var CROP_TOP = 5;
      var cropMode = 'quote';
      var cropPinned = -1;
      var cropHovered = -1;
      var cropChart = null;

      // "Most Quoted" is demand (estimates stores requested); "Most Planted" is
      // supply (crop batches farmers have planned or put in the ground).
      var CROP_MODES = {
        quote: {
          value: 'estimates',
          unit: 'estimate(s)',
          color: 'rgba(2, 132, 199, 0.78)',
          rank: function (a, b) { return b.estimates - a.estimates; },
          detail: function (c) {
            return [
              c.estimates + ' estimate(s)',
              Number(c.quoted_area).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + ' ha quoted area',
              peso(c.quoted_value) + ' quoted value'
            ];
          },
          supply: function (c) {
            return c.batches > 0
              ? c.batches + ' batch(es) \u00B7 ' + c.active + ' active \u00B7 ' + number1(c.planted_area) + ' ha planted'
              : 'Not planted yet';
          }
        },
        planted: {
          value: 'planted_area',
          unit: 'ha planted',
          color: 'rgba(22, 163, 74, 0.78)',
          rank: function (a, b) { return b.planted_area - a.planted_area; },
          detail: function (c) {
            return [
              number1(c.planted_area) + ' ha planted',
              c.batches + ' batch(es) \u00B7 ' + c.active + ' active',
              c.farmers + ' farmer(s)'
            ];
          },
          supply: function (c) {
            return c.estimates > 0
              ? c.estimates + ' estimate(s) \u00B7 ' + peso(c.quoted_value) + ' quoted value'
              : 'Not quoted yet';
          }
        }
      };

      function number1(n) {
        return Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 1 });
      }
      function cropLabel(name) {
        var s = String(name || '');
        var words = s.split(' ');
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

      // The graph only ever plots the top 5 of the current mode; the table
      // underneath carries every crop.
      function cropTop() {
        var cfg = CROP_MODES[cropMode];
        return crops.slice().sort(cfg.rank).filter(function (c) {
          return Number(c[cfg.value]) > 0;
        }).slice(0, CROP_TOP);
      }

      function showCrop(idx, rows) {
        if (!cropReadout) return;
        if (idx < 0) {
          cropReadout.innerHTML = '<span class="chart-hint" id="cropReadoutHint">Hover or click a bar to see the full breakdown.</span>';
          return;
        }
        var c = rows[idx];
        var cfg = CROP_MODES[cropMode];
        cropReadout.innerHTML =
          '<span><span class="pd-name">' + c.crop + '</span></span>' +
          '<span class="pd-meta">' +
            cfg.detail(c).concat([cfg.supply(c)]).map(function (t) { return '<span>' + t + '</span>'; }).join('') +
          '</span>';
      }

      function paintCropBars(rows) {
        if (!cropChart) return;
        var active = cropHovered >= 0 ? cropHovered : cropPinned;
        cropChart.data.datasets[0].backgroundColor = rows.map(function (c, i) {
          if (i === active) return 'rgba(20, 61, 35, 1)';
          return cropPinned >= 0 ? 'rgba(20, 61, 35, 0.32)' : CROP_MODES[cropMode].color;
        });
        cropChart.update('none');
      }

      function buildCropChart() {
        var rows = cropTop();
        var cfg = CROP_MODES[cropMode];
        cropPinned = -1;
        cropHovered = -1;

        if (cropNote) {
          cropNote.textContent = 'Graph shows the top ' + rows.length + ' of ' + crops.length +
            ' crop(s) by ' + (cropMode === 'quote' ? 'estimates' : 'planted area') +
            ' \u2014 all ' + crops.length + ' are listed below.';
        }
        showCrop(-1);

        if (!rows.length) return;

        var slot = Math.max(CROP_MIN_SLOT, Math.floor(cropScroll.clientWidth / rows.length));
        cropTrack.style.width = slot * rows.length + 'px';

        if (cropChart) cropChart.destroy();
        if (window.aqDestroyChart) aqDestroyChart(cropCanvas);
        cropChart = new Chart(cropCanvas, {
          type: 'bar',
          data: {
            labels: rows.map(function (c) { return cropLabel(c.crop); }),
            datasets: [{
              label: cfg.unit,
              data: rows.map(function (c) { return c[cfg.value]; }),
              backgroundColor: rows.map(function () { return cfg.color; }),
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
              if (next === cropHovered) return;
              cropHovered = next;
              paintCropBars(rows);
            },
            onClick: function (evt, items) {
              cropPinned = (items.length && items[0].index !== cropPinned) ? items[0].index : -1;
              cropHovered = -1;
              paintCropBars(rows);
              showCrop(cropPinned, rows);
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  title: function (items) { return rows[items[0].dataIndex].crop; },
                  label: function (ctx) {
                    return CROP_MODES[cropMode].detail(rows[ctx.dataIndex])
                      .concat([CROP_MODES[cropMode].supply(rows[ctx.dataIndex])]);
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
                ticks: {
                  color: '#334155', font: { weight: '600', size: 12 },
                  maxTicksLimit: 5,
                  callback: function (v) { return cfg.value === 'planted_area' ? v + ' ha' : v; }
                },
                grid: { color: 'rgba(0,0,0,0.08)' },
                border: { display: false }
              }
            }
          }
        });
      }

      buildCropChart();

      document.querySelectorAll('[data-crop-mode]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (btn.dataset.cropMode === cropMode) return;
          cropMode = btn.dataset.cropMode;
          document.querySelectorAll('[data-crop-mode]').forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          buildCropChart();
        });
      });

      window.addEventListener('resize', buildCropChart);
    }
  });
</script>
@endsection
