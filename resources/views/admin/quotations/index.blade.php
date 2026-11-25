@extends('layouts.sidebar')

@section('title', 'View Crop Trends')

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
  .variety-chip { background: #f1f5f9; color: #475569; }
  [data-theme="dark"] .variety-chip { background: #1f2937; color: #a7f3d0; }

  .product-rank-list { max-height: 430px; overflow-y: auto; scrollbar-width: thin; }
  .product-rank-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 20px; border-top: 1px solid #f1f5f9;
  }
  .product-rank-row:first-child { border-top: 0; }
  .product-rank-index {
    width: 22px; flex-shrink: 0; font-size: 12px; font-weight: 800; color: #94a3b8;
  }
  .product-rank-img {
    width: 54px; height: 54px; flex-shrink: 0; border-radius: 10px;
    object-fit: cover; background: #f1f5f9; border: 1px solid #e2e8f0;
  }
  .product-rank-main { min-width: 0; flex: 1 1 auto; }
  .product-rank-name {
    font-size: 14px; font-weight: 700; color: #1e2922;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .product-rank-sub { font-size: 12px; color: #64748b; margin-top: 3px; }
  .product-rank-stats {
    display: flex; gap: 18px; flex-shrink: 0; text-align: right;
    font-size: 12px; color: #64748b;
  }
  .product-rank-stats b { display: block; font-size: 14px; color: #143d23; }
  [data-theme="dark"] .product-rank-row { border-top-color: #334155; }
  [data-theme="dark"] .product-rank-name { color: #d6f5e3; }
  [data-theme="dark"] .product-rank-sub,
  [data-theme="dark"] .product-rank-stats { color: #9fb3a6; }
  [data-theme="dark"] .product-rank-stats b { color: #a7f3d0; }
  [data-theme="dark"] .product-rank-img { background: #1f2937; border-color: #334155; }
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

  /* Five bars only: keep them all in view, even on a narrow laptop screen. */
  .chart-fit { width: 100%; padding: 16px 20px 8px; overflow: hidden; }

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
    background: #edf1e9; color: #648268; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all 0.15s ease; flex-shrink: 0;
  }
  .scroll-btn:hover:not(:disabled) { background: #f1f5f9; color: #143d23; }
  .scroll-btn:disabled { opacity: 0.35; cursor: default; }
  .scroll-ctl { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-shrink: 0; white-space: nowrap; }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 13px; border-radius: 9px; border: 1px solid #e2e8f0;
    background: #ffffff; color: #143d23; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.15s ease; white-space: nowrap;
  }
  .btn-ghost:hover { background: #f1f5f9; }

  .crop-card-header {
    position: relative;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
  }
  .crop-card-header h2 { padding-right: 200px; }
  .crop-card-header p {
    padding-right: 200px;
    margin: 6px 0 20px;
  }
  .crop-all-btn {
    position: absolute;
    top: 20px;
    right: 20px;
  }
  @media (max-width: 720px) {
    .crop-card-header h2,
    .crop-card-header p { padding-right: 0; }
    .crop-all-btn { position: static; display: inline-flex; margin-bottom: 12px; }
  }

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
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">View Crop Trends</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">See the crops farmers are planting, harvesting and quoting &mdash; then the farm inputs those quotations call for.</p>
</div>

<!-- Crop-first system-wide metrics -->
<div class="kpi-strip">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $cropTotal }}</div>
      <div class="stat-label">Crops Tracked</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $plantingTotal }}</div>
      <div class="stat-label">Farmer Crop Batches</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $harvestTotal }}</div>
      <div class="stat-label">Harvest Reports</div>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['total'] }}</div>
      <div class="stat-label">Farmer Quotations</div>
    </div>
  </div>
</div>

<div class="data-table-container" style="margin-bottom: 24px;">
  <div class="crop-card-header">
    <a href="{{ route('admin.quotations.crops') }}" class="btn-ghost crop-all-btn">View all {{ $cropTotal }} crops &rarr;</a>
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Crop Trends</h2>
    <p style="font-size: 13px; color: #64748b;">Top {{ min(5, $cropTotal) }} crops by harvest, planting or quotations. Switch tabs to change the bars.</p>
    <div class="seg" role="tablist" aria-label="Crop view">
      <button type="button" class="seg-btn is-active" data-crop-mode="harvest" role="tab" aria-selected="true">Most Harvested</button>
      <button type="button" class="seg-btn" data-crop-mode="planted" role="tab" aria-selected="false">Most Planted</button>
      <button type="button" class="seg-btn" data-crop-mode="quote" role="tab" aria-selected="false">Most Quoted</button>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9;">
    <div class="mini">
      <div class="mini-label">Crops Tracked</div>
      <div class="mini-value">{{ $cropTotal }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Crops With Harvest Reports</div>
      <div class="mini-value">{{ collect($cropRows)->where('harvest_reports', '>', 0)->count() }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Crops Quoted</div>
      <div class="mini-value">{{ collect($cropRows)->where('estimates', '>', 0)->count() }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Crops Planted</div>
      <div class="mini-value">{{ collect($cropRows)->where('batches', '>', 0)->count() }}</div>
    </div>
    <div class="mini">
      <div class="mini-label">Varieties Seen</div>
      <div class="mini-value">{{ $varietyTotal }}</div>
    </div>
  </div>

  @if(count($cropRows) > 0)
    <div class="chart-fit" id="cropScroll">
      <div class="chart-box" style="height: 250px;" id="cropTrack">
        <canvas id="cropDemandChart"></canvas>
      </div>
    </div>
    <div class="chart-bar" id="cropReadout">
      <span class="chart-hint" id="cropReadoutHint">Hover or click a bar for the value.</span>
    </div>
    <p style="margin: 0; padding: 0 20px 12px; font-size: 12px; color: #94a3b8;" id="cropChartNote"></p>
  @else
    <div style="text-align: center; padding: 32px; color: #94a3b8;">
      <p style="margin: 0;">No crop, harvest or quotation data yet.</p>
    </div>
  @endif
</div>

<!-- Kept last: the products behind the crop trends tell the association head
     what free farm inputs (seed, fertilizer, plant medicines) farmers need. -->
<div class="data-table-container" style="margin-bottom: 24px;">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div style="min-width: 0; flex: 1 1 auto;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Most Quoted Products</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Top {{ count($productDemand) }} most-quoted products across all quotations, with the crops they are for.</p>
    </div>
    @if($productTotal > count($productDemand))
      <div class="scroll-ctl">
        <a href="{{ route('admin.quotations.products') }}" class="btn-ghost">
          Show all {{ $productTotal }} products
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.2" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    @endif
  </div>
  @if(count($productDemand) > 0)
    <div class="product-rank-list">
      @foreach($productDemand as $index => $product)
        <div class="product-rank-row">
          <div class="product-rank-index">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
          <img src="{{ $product['image'] }}" alt="{{ $product['display_name'] ?: $product['name'] }}" class="product-rank-img" loading="lazy">
          <div class="product-rank-main">
            <div class="product-rank-name">{{ $product['display_name'] ?: $product['name'] }}</div>
            <div class="product-rank-sub">{{ $product['category'] }} &middot; Crops: {{ $product['crops'] ?: '—' }}</div>
          </div>
          <div class="product-rank-stats">
            <span><b>{{ $product['quotation_count'] }}</b> quotes</span>
            <span><b>{{ number_format($product['total_qty'], 1) }}</b> {{ $product['unit'] }}</span>
            <span><b>₱{{ number_format($product['total_value'], 0) }}</b> value</span>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div style="text-align: center; padding: 32px; color: #94a3b8;">
      <p style="margin: 0;">No product demand data yet.</p>
    </div>
  @endif
</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    var crops = @js($cropRows);

    function peso(n) {
      return '₱' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
    }
    var cropCanvas = document.getElementById('cropDemandChart');
    var cropReadout = document.getElementById('cropReadout');
    var cropNote = document.getElementById('cropChartNote');

    if (cropCanvas && crops.length) {
      var CROP_TOP = 5;
      var cropMode = 'harvest';
      var cropPinned = -1;
      var cropHovered = -1;
      var cropChart = null;

      // Three crop views: harvested volume, planted area, quoted crops.
      // The hover card shows only the two numbers that match the active tab.
      var CROP_MODES = {
        harvest: {
          value: 'harvest_yield',
          unit: 'MT harvested',
          note: 'harvested volume',
          color: 'rgba(143, 168, 128, 0.72)',
          rank: function (a, b) { return b.harvest_yield - a.harvest_yield; },
          detail: function (c) {
            return [
              number1(c.harvest_yield) + ' MT harvested',
              c.harvest_reports + ' reports'
            ];
          }
        },
        planted: {
          value: 'planted_area',
          unit: 'ha planted',
          note: 'planted area',
          color: 'rgba(112, 145, 104, 0.72)',
          rank: function (a, b) { return b.planted_area - a.planted_area; },
          detail: function (c) {
            return [
              number1(c.planted_area) + ' ha planted',
              c.batches + ' crop batches'
            ];
          }
        },
        quote: {
          value: 'estimates',
          unit: 'quotations',
          note: 'quotation demand',
          color: 'rgba(174, 194, 158, 0.72)',
          rank: function (a, b) { return b.estimates - a.estimates; },
          detail: function (c) {
            return [
              c.estimates + ' quotations',
              peso(c.quoted_value) + ' quoted'
            ];
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

      // The graph only ever plots the top 5 of the current mode; the full
      // table lives on its own page linked from the card header.
      function cropTop() {
        var cfg = CROP_MODES[cropMode];
        return crops.slice().sort(cfg.rank).filter(function (c) {
          return Number(c[cfg.value]) > 0;
        }).slice(0, CROP_TOP);
      }

      function showCrop(idx, rows) {
        if (!cropReadout) return;
        if (idx < 0) {
          cropReadout.innerHTML = '<span class="chart-hint" id="cropReadoutHint">Hover or click a bar for the value.</span>';
          return;
        }
        var c = rows[idx];
        var cfg = CROP_MODES[cropMode];
        cropReadout.innerHTML =
          '<span><span class="pd-name">' + c.crop + '</span></span>' +
          '<span class="pd-meta">' +
            cfg.detail(c).map(function (t) { return '<span>' + t + '</span>'; }).join('') +
          '</span>';
      }

      function paintCropBars(rows) {
        if (!cropChart) return;
        var active = cropHovered >= 0 ? cropHovered : cropPinned;
        cropChart.data.datasets[0].backgroundColor = rows.map(function (c, i) {
          if (i === active) return 'rgba(88, 120, 82, 0.92)';
          return cropPinned >= 0 ? 'rgba(112, 145, 104, 0.58)' : CROP_MODES[cropMode].color;
        });
        cropChart.update('none');
      }

      function buildCropChart() {
        var rows = cropTop();
        var cfg = CROP_MODES[cropMode];
        cropPinned = -1;
        cropHovered = -1;

        // One palette for the whole system: the pale Crop Directory tint as
        // the card background and its darker same-hue ink for every line.
        function paletteFor(ctx) {
          var row = rows[ctx && ctx.dataIndex !== undefined ? ctx.dataIndex : 0];
          return (row && row.palette) || { bg: '#F3F7F2', ink: '#2F6B45' };
        }

        if (cropNote) {
          cropNote.textContent = 'Top ' + rows.length + ' of ' + crops.length + ' crops by ' + cfg.note +
            '. Open “View all ' + crops.length + ' crops” for the complete table.';
        }
        showCrop(-1);

        if (!rows.length) return;

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
              hoverBackgroundColor: 'rgba(88, 120, 82, 0.92)',
              borderRadius: 4,
              maxBarThickness: 64,
              categoryPercentage: 0.96,
              barPercentage: 0.78
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
                backgroundColor: function (ctx) { return paletteFor(ctx).bg; },
                titleColor: function (ctx) { return paletteFor(ctx).ink; },
                bodyColor: function (ctx) { return paletteFor(ctx).ink; },
                borderColor: function (ctx) { return paletteFor(ctx).ink; },
                borderWidth: 1,
                cornerRadius: 10,
                padding: 12,
                displayColors: false,
                titleFont: { weight: '700', size: 13 },
                bodyFont: { size: 12, weight: '500' },
                callbacks: {
                  title: function (items) { return rows[items[0].dataIndex].crop; },
                  label: function (ctx) {
                    return CROP_MODES[cropMode].detail(rows[ctx.dataIndex]);
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
                  callback: function (v) {
                    if (cfg.value === 'planted_area') return v + ' ha';
                    if (cfg.value === 'harvest_yield') return v + ' MT';
                    return v;
                  }
                },
                grid: { color: 'rgba(148, 163, 184, 0.20)' },
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

      // Chart.js handles resize on its own; rebuilding here would reset the
      // selected bar and flash the chart every time the window changes size.
    }
  });
</script>
@endsection
