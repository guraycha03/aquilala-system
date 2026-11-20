{{--
  Shared harvest report chart.

  The admin dashboard and the Harvest Performance page must show the same
  chart: same left padding, same bold total-yield figure, same period filter
  and the same colours. Keeping one copy here means a fix to one page can
  never silently leave the other one behind.

  Expects the four $harvestGraph* arrays and the #harvestFilter dropdown to
  be present on the page (both hosts already provide them).
--}}
<style>
  /* Chart summary panels — copied verbatim from the admin dashboard. */
  .chart-grid { display: flex; gap: 28px; padding: 24px 26px; align-items: center; flex-wrap: wrap; }
  .chart-box { flex: 0 1 170px; max-width: 170px; position: relative; }
  .chart-grid canvas { max-height: 220px; min-height: 200px; }
  .chart-caption { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 0; }
  .yield-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 26px 0; flex-wrap: wrap; }
  .yield-big { display: flex; align-items: baseline; gap: 6px; margin-bottom: 4px; }
  .yield-big-value { font-size: 28px; font-weight: 800; color: #143d23; font-variant-numeric: tabular-nums; line-height: 1; letter-spacing: 0.02em; }
  .yield-big-unit { font-size: 13px; font-weight: 700; color: #4A7A54; text-transform: uppercase; letter-spacing: 0.08em; }

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
  .filter-option.is-active { background: #edf1e9; color: #648268; font-weight: 800; }
  .chart-empty {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; color: #64748b; font-weight: 600; text-align: center;
    background: rgba(255,255,255,0.78); border-radius: 10px; padding: 10px;
  }
  .chart-empty[hidden] { display: none; }
</style>

<div class="yield-row">
  <div>
    <div class="yield-big">
      <span class="yield-big-value" id="harvestYieldValue">0</span>
      <span class="yield-big-unit">MT</span>
    </div>
    <div class="chart-caption">Total yield</div>
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
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
  });
</script>
