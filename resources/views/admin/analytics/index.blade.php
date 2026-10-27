@extends('layouts.sidebar')

@section('title', 'System Analytics')

@section('content')
  <style>
    .ana-page { position: relative; }
    .ana-page::before,
    .ana-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .ana-page::before { width: 360px; height: 360px; background: rgba(94,144,112,0.16); top: -90px; right: -90px; }
    .ana-page::after { width: 300px; height: 300px; background: rgba(59,130,246,0.13); bottom: -70px; left: -70px; }
    .ana-content { position: relative; z-index: 1; }

    .ana-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .ana-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .ana-header p { font-size: 13px; color: #64748b; margin: 0; }

    .reveal-ready .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
    .reveal-ready .reveal.in { opacity: 1; transform: translateY(0); }

    /* KPI row */
    .ana-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .kpi-card {
      background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 16px; padding: 18px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
      display: flex; flex-direction: column;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(0,0,0,0.09); }
    .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .kpi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; }
    .kpi-ico {
      width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .kpi-ico svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .kpi-value { font-size: 24px; font-weight: 700; color: #0f172a; margin-top: 10px; font-variant-numeric: tabular-nums; letter-spacing: -0.02em; }
    .kpi-sub { font-size: 12px; color: #94a3b8; margin-top: 4px; }
    .spark { margin-top: auto; padding-top: 10px; width: 100%; height: 30px; display: block; }

    /* Panel */
    .ana-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
      display: flex; flex-direction: column;
    }
    .ana-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
    .panel-head { padding: 14px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    .panel-body { padding: 16px 20px 18px; display: flex; flex-direction: column; flex: 1; }
    .panel-body > .chart-wrap { flex: 1; min-height: 0; }

    /* Grid layouts — panels stretch to equal height */
    .ana-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: stretch; margin-bottom: 18px; }
    .ana-grid.equal { grid-template-columns: repeat(2, 1fr); }
    .ana-grid.triple { grid-template-columns: repeat(3, 1fr); }

    /* Limited-height list panels with "view full" */
    .panel-list { flex: 1; min-height: 0; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #dbe3df transparent; display: flex; flex-direction: column; }
    .panel-list .hbar-row { flex: 1; }
    .panel-list::-webkit-scrollbar { width: 5px; }
    .panel-list::-webkit-scrollbar-thumb { background: #dbe3df; border-radius: 999px; }
    .list-more {
      display: inline-flex; align-items: center; gap: 7px; align-self: flex-start;
      margin-top: 14px; padding: 8px 15px; border-radius: 10px;
      background: #f0fdf4; color: #14532d; font-size: 12px; font-weight: 700;
      border: 1px solid rgba(20,61,35,0.14); cursor: pointer; transition: all 0.2s ease;
      font-family: inherit;
    }
    .list-more:hover { background: #dcfce7; transform: translateY(-1px); }
    .list-more svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    /* Modal */
    .ana-modal {
      position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center;
      padding: 20px; opacity: 0; visibility: hidden; transition: opacity 0.22s ease, visibility 0.22s;
    }
    .ana-modal.open { opacity: 1; visibility: visible; }
    .ana-modal-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,0.15); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
    .ana-modal-card {
      position: relative; background: #ffffff; border-radius: 18px; box-shadow: 0 24px 60px rgba(0,0,0,0.25);
      width: 100%; max-width: 540px; max-height: min(82vh, 680px);
      display: flex; flex-direction: column; overflow: hidden;
      transform: translateY(16px) scale(0.98); transition: transform 0.25s cubic-bezier(0.4,0,0.2,1);
    }
    .ana-modal.open .ana-modal-card { transform: translateY(0) scale(1); }
    .ana-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 18px 22px; border-bottom: 1px solid #f1f5f9; }
    .ana-modal-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; }
    .ana-modal-head p { font-size: 12px; color: #64748b; margin: 3px 0 0 0; }
    .ana-modal-close { background: #f1f5f9; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0; transition: all 0.15s ease; }
    .ana-modal-close:hover { background: #e2e8f0; color: #0f172a; }
    .ana-modal-close svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .ana-modal-body { padding: 12px 22px 22px; overflow-y: auto; }
    .ana-modal-body .hbar-row:first-child { padding-top: 6px; }

    /* Chart */
    .chart-wrap { position: relative; min-height: 190px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .chart-wrap svg { width: 100%; height: auto; display: block; }
    /* Paged charts: arrows sit beside the chart in reserved space (never on top) */
    .chart-wrap-side { flex-direction: row; align-items: stretch; gap: 10px; padding: 0 2px; }
    .chart-wrap-side .chart-nav { position: static; transform: none; align-self: center; flex-shrink: 0; }
    .chart-wrap-side .chart-nav:hover { transform: scale(1.08); }
    .chart-fill { position: relative; flex: 1; min-width: 0; display: flex; align-items: center; justify-content: center; }
    .chart-fill svg { width: 100%; height: 100%; display: block; }
    .range-badge {
      position: absolute; top: 2px; left: 50%; transform: translateX(-50%);
      background: rgba(241,245,249,0.9); border: 1px solid #e2e8f0; color: #475569;
      font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
      padding: 3px 10px; border-radius: 999px; z-index: 6; white-space: nowrap;
    }
    .chart-nav {
      position: absolute; top: 50%; transform: translateY(-50%);
      width: 34px; height: 34px; border-radius: 50%;
      background: rgba(255,255,255,0.92); border: 1px solid #e2e8f0;
      box-shadow: 0 4px 14px rgba(15,23,42,0.14);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: #334155; z-index: 6; padding: 0;
      opacity: 0; pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .chart-nav svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
    .chart-nav-prev { left: 8px; }
    .chart-nav-next { right: 8px; }
    .chart-wrap:hover .chart-nav, .chart-nav.force { opacity: 1; pointer-events: auto; }
    .chart-nav:hover { background: #ffffff; box-shadow: 0 6px 18px rgba(15,23,42,0.22); transform: translateY(-50%) scale(1.08); }
    .chart-nav.dim, .chart-wrap:hover .chart-nav.dim { opacity: 0.3; pointer-events: none; }
    .chart-hint { font-size: 11px; color: #94a3b8; margin-top: 10px; display: flex; align-items: center; gap: 6px; }
    .chart-hint svg { width: 12px; height: 12px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Empty states */
    .ana-empty {
      flex: 1; min-height: 170px; display: flex; flex-direction: column;
      align-items: center; justify-content: center; gap: 10px;
      text-align: center; padding: 28px 18px; grid-column: 1 / -1;
    }
    .chart-wrap-side.has-empty .chart-nav { display: none; }
    .ana-empty-ico {
      width: 54px; height: 54px; border-radius: 16px; flex-shrink: 0;
      background: #edf1e9; color: #648268;
      display: flex; align-items: center; justify-content: center;
      box-shadow: inset 0 0 0 1px rgba(74,122,84,0.14);
    }
    .ana-empty-ico svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
    .ana-empty-title { font-size: 13px; font-weight: 700; color: #334155; }
    .ana-empty-sub { font-size: 12px; color: #94a3b8; max-width: 310px; line-height: 1.55; }

    /* Legend */
    .chart-legend { display: flex; gap: 16px; flex-wrap: wrap; }
    .lg-item { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; color: #475569; cursor: pointer; user-select: none; }
    .lg-item.dim { opacity: 0.35; }
    .lg-swatch { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* SVG */
    .ana-grid-line { stroke: #eef1ef; stroke-width: 1; }
    .ana-axis { font-size: 10px; fill: #94a3b8; font-variant-numeric: tabular-nums; }
    .ana-guide { stroke: #cbd5e1; stroke-width: 1; stroke-dasharray: 4 4; }

    /* Bars */
    .bar-g { transform-box: fill-box; transform-origin: bottom; transform: scaleY(0); transition: transform 0.7s cubic-bezier(0.25,0.8,0.25,1); }
    .bar-g.in { transform: scaleY(1); }

    /* Donut */
    .donut-center-total { font-size: 32px; font-weight: 800; fill: #0f172a; font-variant-numeric: tabular-nums; }
    .donut-center-cap { font-size: 10px; fill: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; }
    .donut-seg { transition: stroke-width 0.2s ease, opacity 0.2s ease; cursor: pointer; }
    .donut-seg:hover, .donut-seg.hot { stroke-width: 28; opacity: 1; }
    .donut-seg.dim { opacity: 0.25; }
    .d-legend { display: flex; flex-direction: column; gap: 10px; }
    .d-legend-row { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 6px 10px; border-radius: 9px; transition: background 0.15s ease; }
    .d-legend-row:hover { background: #f6f8f7; }
    .d-legend-row.dim { opacity: 0.4; }
    .d-legend-row .sw { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
    .d-legend-row .lbl { flex: 1; font-size: 12px; color: #475569; }
    .d-legend-row .cnt { font-size: 13px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .d-legend-row .pct { font-size: 11px; color: #94a3b8; width: 38px; text-align: right; font-variant-numeric: tabular-nums; }

    /* Products by Category — single-row (landscape) layout; legend tiles so the card stays compact */
    .cat-flex { flex: 1; min-height: 0; display: flex; align-items: center; gap: 20px; }
    .cat-flex-donut { width: 200px; max-width: 44%; flex-shrink: 0; }
    .cat-flex-donut svg { width: 100%; height: auto; display: block; }
    .cat-legend {
      flex: 1; min-width: 0; display: grid; gap: 8px 30px;
      grid-template-columns: repeat(3, 1fr); align-content: center;
    }
    .cat-legend .d-legend-row { padding: 6px 8px; min-width: 0; }

    /* Horizontal bars */
    .hbar-row { padding: 10px 0; }
    .hbar-row + .hbar-row { border-top: 1px solid #f1f3f2; }
    .hbar-meta { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
    .hbar-meta span { font-size: 13px; color: #64748b; font-weight: 500; }
    .hbar-meta b { font-size: 14px; color: #0f172a; font-weight: 700; font-variant-numeric: tabular-nums; }
    .hbar-track { height: 8px; border-radius: 999px; background: #eef1ef; overflow: hidden; }
    .hbar-fill { height: 100%; border-radius: 999px; width: 0; transition: width 1s cubic-bezier(0.4,0,0.2,1); }

    /* Summary stats rows (Harvest Report Summary) */
    .sum-row { flex: 1; display: flex; align-items: center; gap: 14px; padding: 13px 2px; min-height: 60px; }
    .sum-row + .sum-row { border-top: 1px solid #f1f3f2; }
    .sum-rank { width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .sum-rank svg { width: 17px; height: 17px; }
    .sum-mid { flex: 1; min-width: 0; }
    .sum-name { font-size: 13px; font-weight: 600; color: #334155; line-height: 1.35; }
    .sum-sub { font-size: 11.5px; color: #94a3b8; margin-top: 3px; line-height: 1.4; }
    .sum-val { font-size: 15px; font-weight: 700; color: #0f172a; white-space: nowrap; font-variant-numeric: tabular-nums; text-align: right; }

    /* Active yield-unit badge (set from Settings → Interface) */
    .unit-chip {
      display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
      color: #4d7c0f; background: #f4fbe9; border: 1px solid rgba(132,168,32,0.22);
      padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }

    /* Decision matrix — scrollable when long */
    .matrix-scroll { max-height: 440px; overflow-y: auto; padding-right: 4px; scrollbar-width: thin; scrollbar-color: #dbe3df transparent; }
    .matrix-scroll::-webkit-scrollbar { width: 5px; }
    .matrix-scroll::-webkit-scrollbar-thumb { background: #dbe3df; border-radius: 999px; }

    /* Decision Rule Matrix — compact card (full matrix on its own page) */
    .matrix-chips { display: flex; gap: 8px; flex-wrap: wrap; flex-shrink: 0; }
    .matrix-chips span {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 11px; font-weight: 700; color: #14532d;
      background: #f0fdf4; border: 1px solid rgba(20,61,35,0.12);
      padding: 6px 12px; border-radius: 999px; white-space: nowrap;
    }
    .matrix-chips svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .matrix-mini { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .mx-crop {
      background: #fbfcfb; border: 1px solid #eef1ef; border-radius: 14px; padding: 14px 15px;
      display: flex; flex-direction: column; gap: 9px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }
    .mx-crop:hover { border-color: #cce5a3; box-shadow: 0 6px 18px rgba(20,61,35,0.08); transform: translateY(-1px); }
    .mx-crop-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .mx-crop-name { font-size: 13px; font-weight: 700; color: #1e293b; text-transform: capitalize; }
    .mx-rules {
      font-size: 10px; font-weight: 700; color: #4d7c0f; background: #f4fbe9;
      padding: 3px 9px; border-radius: 999px; white-space: nowrap;
    }
    .mx-seasons { display: flex; gap: 6px; flex-wrap: wrap; }
    .mx-season { font-size: 10px; font-weight: 700; text-transform: capitalize; padding: 3px 9px; border-radius: 999px; }
    .mx-season.wet { background: #edf1e9; color: #648268; }
    .mx-season.dry { background: #f6f0e6; color: #a57f43; }
    .mx-prods { display: flex; flex-direction: column; gap: 5px; margin-top: 2px; }
    .mx-prod {
      font-size: 11px; color: #475569; display: flex; gap: 6px; align-items: baseline;
      padding: 7px 9px; background: #ffffff; border: 1px solid #eef1ef; border-radius: 9px;
    }
    .mx-prod b { font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mx-prod .r { margin-left: auto; font-weight: 700; color: #14532d; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .mx-extra {
      display: inline-flex; align-items: center; gap: 6px; width: fit-content;
      font-size: 10px; font-weight: 700; color: #94a3b8; margin-top: 2px;
    }
    .mx-extra svg { width: 12px; height: 12px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 900px) {
      .matrix-mini { grid-template-columns: 1fr; }
    }

    /* Tooltip */
    .ana-tip {
      position: fixed; z-index: 9999; background: #ffffff; color: #334155;
      padding: 10px 14px; border-radius: 10px; font-size: 12px; line-height: 1.55;
      border: 1px solid #e6ebe8; box-shadow: 0 8px 24px rgba(15,23,42,0.14);
      opacity: 0; pointer-events: none; transition: opacity 0.15s ease; min-width: 120px;
    }
    .ana-tip .tt-date { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; font-weight: 700; }
    .ana-tip .tt-row { display: flex; justify-content: space-between; gap: 18px; margin-top: 4px; }
    .ana-tip .tt-row b { color: #1e293b; font-variant-numeric: tabular-nums; }
    .ana-tip .tt-row .sw { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: 6px; }

    @media (max-width: 900px) {
      .ana-grid, .ana-grid.equal, .ana-grid.triple { grid-template-columns: 1fr; }
      .ana-kpis { grid-template-columns: repeat(2, 1fr); }
      .cat-legend { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
    }
    @media (max-width: 640px) {
      .ana-kpis { grid-template-columns: 1fr; }
      .ana-header { flex-direction: column; align-items: flex-start; }
      .cat-flex { flex-direction: column; align-items: center; }
      .cat-flex-donut { width: 190px; max-width: 100%; }
      .cat-legend { width: 100%; grid-template-columns: 1fr; }
    }
  </style>

  <div class="ana-page">
    <div class="ana-content">

      <!-- HEADER -->
      <div class="ana-header reveal in">
        <div>
          <h1>System Analytics</h1>
          <p>Platform activity, quotation generation, crop tracking, and harvest reporting.</p>
        </div>
      </div>

      <!-- KPI ROW -->
      <section class="ana-kpis kpi-strip" id="kpiRow">
        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Total Users</div>
            <div class="kpi-ico" style="background:#eef3ef;color:#63856c;">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
          </div>
          <div class="kpi-value">{{ number_format($totalUsers) }}</div>
          <div class="kpi-sub">{{ $farmers }} farmers · {{ $storeOwners }} stores</div>
          <svg class="spark" id="sparkUsers" viewBox="0 0 120 36" preserveAspectRatio="none"></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Quotations Generated</div>
            <div class="kpi-ico" style="background: #edf1e9; color: #648268;">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
          </div>
          <div class="kpi-value">{{ number_format($totalQuotations) }}</div>
          <div class="kpi-sub">{{ $quotationsThisMonth }} this month</div>
          <svg class="spark" id="sparkQuotations" viewBox="0 0 120 36" preserveAspectRatio="none"></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Crops Tracked</div>
            <div class="kpi-ico" style="background: #edf1e9; color: #648268;">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
            </div>
          </div>
          <div class="kpi-value">{{ number_format($totalCrops) }}</div>
          <div class="kpi-sub">{{ $activeCrops }} growing · {{ $harvestedCrops }} harvested</div>
          <svg class="spark" id="sparkCrops" viewBox="0 0 120 36" preserveAspectRatio="none"></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Activities Logged</div>
            <div class="kpi-ico" style="background:#faf5ff;color:#7c3aed;">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
          </div>
          <div class="kpi-value">{{ number_format($totalActivities) }}</div>
          <div class="kpi-sub">{{ $activitiesThisMonth }} this month</div>
          <svg class="spark" id="sparkActivities" viewBox="0 0 120 36" preserveAspectRatio="none"></svg>
        </div>
      </section>

      <!-- ROW 1: QUOTATION TREND + CROPS BY SEASON -->
      @php $yieldUnitLabel = \App\Helpers\SettingsHelper::yieldUnitLabel(); @endphp
      <section class="ana-grid" style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Quotation Generation</h3>
              <p>Daily cost estimates created by farmers over the last 30 days</p>
            </div>
          </div>
          <div class="panel-body">
            <div class="chart-wrap" id="quotationTrendWrap">
              <svg id="quotationTrendSvg" viewBox="0 0 700 260"></svg>
              <div id="quotationTrendGuide" style="position:absolute;top:0;bottom:0;width:1px;background:rgba(148,163,184,0.7);display:none;pointer-events:none;"></div>
            </div>
            <div class="chart-hint">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
              Hover to inspect daily quotation counts
            </div>
          </div>
        </div>

        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Crops by Season</h3>
              <p>Distribution between wet and dry cropping</p>
            </div>
          </div>
          <div class="panel-body" id="seasonBody" style="display:grid;grid-template-columns:minmax(0,190px) 1fr;gap:8px;align-items:center;">
          <div class="chart-wrap" style="max-width:210px;justify-self:center;">
            <svg id="seasonDonut" viewBox="0 0 220 220"></svg>
          </div>
            <div class="d-legend" id="seasonLegend"></div>
          </div>
        </div>
      </section>

      <!-- ROW 2A: USER LOGINS (full width) -->
      <section style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>User Logins</h3>
              <p>Farmer &amp; store owner sign-ins &middot; last 14 days</p>
            </div>
          </div>
          <div class="panel-body">
            <div class="chart-wrap chart-wrap-side" id="loginTrendWrap">
              <div class="chart-fill" id="loginTrendArea">
                <svg id="loginTrendSvg" viewBox="0 0 700 220"></svg>
                <span class="range-badge" id="loginRange"></span>
              </div>
            </div>
            <div class="d-legend" id="loginLegend" style="margin-top:12px;grid-template-columns:repeat(2,minmax(0,1fr));"></div>
          </div>
        </div>
      </section>

      <!-- ROW 2B: ACTIVITY BREAKDOWN -->
      <section style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Activity Breakdown</h3>
              <p>System events by type</p>
            </div>
          </div>
          <div class="panel-body">
            @php
              $actTypeColors = [
                'quotation' => '#a99f52', 'crop' => '#83a17b', 'product' => '#c6ad62',
                'auth' => '#5f8f68', 'land' => '#94a35c'
              ];
              $actMax = $activitiesByType->first()->count ?? 1;
            @endphp
            <div class="panel-list">
              @forelse($activitiesByType->take(5) as $act)
                @php
                  $typeLabel = ucfirst(str_replace('.', ' ', $act->type));
                  $colorKey = explode('.', $act->type)[0];
                  $barColor = $actTypeColors[$colorKey] ?? '#64748b';
                  $pct = $actMax > 0 ? round(($act->count / $actMax) * 100) : 0;
                @endphp
                <div class="hbar-row">
                  <div class="hbar-meta"><span>{{ $typeLabel }}</span><b>{{ number_format($act->count) }}</b></div>
                  <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:{{ $barColor }};"></div></div>
                </div>
              @empty
                <div class="ana-empty">
                  <div class="ana-empty-ico"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                  <div class="ana-empty-title">No activity data yet</div>
                  <div class="ana-empty-sub">Farmer and store actions — quotations, crop updates, logins — will appear here.</div>
                </div>
              @endforelse
            </div>
            @if($activitiesByType->count() > 5)
              <button type="button" class="list-more" onclick="openAnalyticsModal('activityModal')">
                <svg viewBox="0 0 24 24"><path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg>
                View Full Breakdown
              </button>
            @endif
          </div>
        </div>
      </section>

      <!-- ROW 3: CROP STAGES + PLATFORM SUMMARY -->
      <section class="ana-grid" style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Crop Growth Stages</h3>
              <p>Current status of all tracked crops</p>
            </div>
          </div>
          <div class="panel-body">
            @php
              $stageLabels = [
                'land_prep' => 'Land Preparation', 'seeding' => 'Seeding / Sowing',
                'vegetative' => 'Vegetative Growth', 'reproductive' => 'Flowering / Fruiting',
                'ripening' => 'Ripening', 'harvested' => 'Harvested', 'failed' => 'Failed'
              ];
              $stageColors = [
                'land_prep' => '#78716c', 'seeding' => '#2563eb', 'vegetative' => '#4A7A54',
                'reproductive' => '#d97706', 'ripening' => '#ea580c', 'harvested' => '#6d28d9', 'failed' => '#dc2626'
              ];
              $stageMax = $cropsByStage->first()->count ?? 1;
            @endphp
            <div class="panel-list">
              @forelse($cropsByStage->take(5) as $stageData)
                @php
                  $stageKey = $stageData->growth_stage;
                  $label = $stageLabels[$stageKey] ?? ucfirst($stageKey);
                  $color = $stageColors[$stageKey] ?? '#64748b';
                  $pct = $stageMax > 0 ? round(($stageData->count / $stageMax) * 100) : 0;
                @endphp
                <div class="hbar-row">
                  <div class="hbar-meta"><span>{{ $label }}</span><b>{{ number_format($stageData->count) }}</b></div>
                  <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:{{ $color }};"></div></div>
                </div>
              @empty
                <div class="ana-empty">
                  <div class="ana-empty-ico"><svg viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg></div>
                  <div class="ana-empty-title">No crops tracked yet</div>
                  <div class="ana-empty-sub">Farmers haven't started tracking crops — growth stages will appear here.</div>
                </div>
              @endforelse
            </div>
            @if($cropsByStage->count() > 5)
              <button type="button" class="list-more" onclick="openAnalyticsModal('stagesModal')">
                <svg viewBox="0 0 24 24"><path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg>
                View Full Breakdown
              </button>
            @endif
          </div>
        </div>

        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Harvest Report Summary</h3>
              <p>Actual yields and expenses by crop type</p>
            </div>
            <span class="unit-chip">
              <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              {{ $yieldUnitLabel }}
            </span>
          </div>
          <div class="panel-body">
            <div class="sum-row">
              <div class="sum-rank" style="background: #edf1e9; color: #648268;">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              </div>
              <div class="sum-mid">
                <div class="sum-name">Total Harvest Reports</div>
                <div class="sum-sub">Submitted by farmers</div>
              </div>
              <div class="sum-val">{{ number_format($totalHarvests) }}</div>
            </div>
            <div class="sum-row">
              <div class="sum-rank" style="background: #edf1e9; color: #648268;">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg>
              </div>
              <div class="sum-mid">
                <div class="sum-name">Total Yield</div>
                <div class="sum-sub">Actual harvest {{ \App\Helpers\SettingsHelper::yieldUnitCaption() }}</div>
              </div>
              <div class="sum-val" style="font-size:17px;">{{ \App\Helpers\SettingsHelper::formatYield($totalYield) }}</div>
            </div>
            <div class="sum-row">
              <div class="sum-rank" style="background: #edf1e9; color: #648268;">
                <x-peso-icon />
              </div>
              <div class="sum-mid">
                <div class="sum-name">Total Expenses</div>
                <div class="sum-sub">Reported actual farm expenses</div>
              </div>
              <div class="sum-val">₱{{ number_format($totalExpenses, 0) }}</div>
            </div>
            <div class="sum-row">
              <div class="sum-rank" style="background:#eef3ef;color:#63856c;">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              </div>
              <div class="sum-mid">
                <div class="sum-name">Active Products</div>
                <div class="sum-sub">Currently listed in catalog</div>
              </div>
              <div class="sum-val">{{ number_format($activeProducts) }}</div>
            </div>
            <div class="sum-row">
              <div class="sum-rank" style="background:#fef2f2;color:#dc2626;">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              </div>
              <div class="sum-mid">
                <div class="sum-name">Low Stock Products</div>
                <div class="sum-sub">Items below reorder threshold</div>
              </div>
              <div class="sum-val">{{ number_format($lowStockProducts) }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ROW 4: HARVEST YIELD BY CROP (full width) -->
      <section class="ana-panel reveal" data-reveal style="margin-bottom:22px;">
        <div class="panel-head">
            <div>
              <h3>Harvest Yield by Crop</h3>
              <p>Actual yields reported per crop type</p>
            </div>
            <span class="unit-chip">
              <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              {{ $yieldUnitLabel }}
            </span>
          </div>
          <div class="panel-body">
            @php
              $harvestMax = $harvestsByCrop->max('yield') ?? 1;
            @endphp
            <div class="panel-list">
              @forelse($harvestsByCrop->take(5) as $hv)
                @php
                  $pct = $harvestMax > 0 ? round(($hv->yield / $harvestMax) * 100) : 0;
                @endphp
                <div class="hbar-row">
                  <div class="hbar-meta"><span>{{ ucfirst($hv->crop_type) }}</span><b>{{ \App\Helpers\SettingsHelper::formatYield($hv->yield) }}</b></div>
                  <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:#63856c;"></div></div>
                  <div style="margin-top:6px;font-size:11px;color:#94a3b8;">{{ number_format($hv->count) }} report(s) · ₱{{ number_format($hv->expenses, 0) }} expenses</div>
                </div>
              @empty
                <div class="ana-empty">
                  <div class="ana-empty-ico"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14v4"/><path d="M12 10v8"/><path d="M17 6v12"/></svg></div>
                  <div class="ana-empty-title">No harvest reports yet</div>
                  <div class="ana-empty-sub">Farmers submit these after harvesting — yields and expenses will appear here.</div>
                </div>
              @endforelse
            </div>
            @if($harvestsByCrop->count() > 5)
              <button type="button" class="list-more" onclick="openAnalyticsModal('harvestModal')">
                <svg viewBox="0 0 24 24"><path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg>
                View Full Breakdown
              </button>
            @endif
          </div>
        </div>
      </section>

      <!-- ROW 4B: PRODUCTS BY CATEGORY — full-width, landscape -->
      <section class="ana-panel reveal" data-reveal style="margin-bottom:22px;">
        <div class="panel-head">
            <div>
              <h3>Products by Category</h3>
              <p>Catalog distribution across categories</p>
            </div>
          </div>
          <div class="panel-body cat-panel" id="categoryBody">
            <div class="cat-flex">
              <div class="cat-flex-donut">
                <svg id="categoryDonut" viewBox="0 0 220 220"></svg>
              </div>
              <div class="d-legend cat-legend" id="categoryLegend"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ROW 5: DECISION RULE MATRIX -->
      @php
        $ruleCount = $formulas->count();
        $cropCount = $grouped->count();
        $seasonLabels = $grouped->flatMap(fn($s) => $s->keys())->unique()->values()->map(fn($n) => ucfirst($n))->implode(' · ');
        $topCrops = $grouped->sortByDesc(fn($s) => $s->flatten()->count())->take(3);
      @endphp
      <section class="ana-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Decision Rule Matrix</h3>
            <p>Baseline application rates per hectare — managed by store owners</p>
          </div>
          @if($ruleCount > 0)
            <div class="matrix-chips">
              <span>
                <svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M16 5.15A3 3 0 0 1 13.3 1.5H12"/><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/></svg>
                {{ number_format($ruleCount) }} rules
              </span>
              <span>
                <svg viewBox="0 0 24 24"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
                {{ number_format($cropCount) }} crops
              </span>
              <span>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $seasonLabels }}
              </span>
            </div>
          @endif
        </div>
        <div class="panel-body">
          @if($ruleCount === 0)
            <div style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No formulas configured yet. Store owners define crop-specific formulas in their <strong>Input Formulas</strong> section.</p>
            </div>
          @else
            <div class="matrix-mini">
              @foreach($topCrops as $crop => $seasons)
                @php
                  $flattened = $seasons->flatten();
                  // Stage rows collapse into one line per product with the
                  // stages summed, so a basal + topdress rule isn't listed twice.
                  $perProduct = $flattened
                    ->groupBy('product_id')
                    ->map(fn ($rows) => [
                        'name' => $rows->first()->product->display_name ?? 'Unknown Product',
                        'rate' => (float) $rows->sum(fn ($f) => $f->effectiveRate()),
                        'unit' => $rows->first()->unit,
                    ]);
                @endphp
                <div class="mx-crop">
                  <div class="mx-crop-head">
                    <span class="mx-crop-name">{{ $crop }}</span>
                    <span class="mx-rules">{{ $flattened->count() }} rule{{ $flattened->count() === 1 ? '' : 's' }}</span>
                  </div>
                  <div class="mx-seasons">
                    @foreach($seasons as $season => $seasonFormulas)
                      <span class="mx-season {{ $season }}">{{ $season }}</span>
                    @endforeach
                  </div>
                  <div class="mx-prods">
                    @foreach($perProduct->take(3) as $summary)
                      <div class="mx-prod">
                        <b>{{ $summary['name'] }}</b>
                        <span class="r">{{ number_format($summary['rate'], 2) }} {{ $summary['unit'] }}/ha</span>
                      </div>
                    @endforeach
                  </div>
                  @if($perProduct->count() > 3)
                    <div class="mx-extra">
                      <svg viewBox="0 0 24 24"><path d="M18 6 6 18"/><path d="M6 6h12v12"/></svg>
                      +{{ $perProduct->count() - 3 }} more input{{ $perProduct->count() - 3 === 1 ? '' : 's' }}
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
            {{-- The standalone formula matrix page is gone; formulas now live
                 on each product's own info instead of in a full-screen list. --}}
          @endif
        </div>
      </section>

    </div>
  </div>

  <!-- MODALS -->
  @if($activitiesByType->count() > 5)
    <div class="ana-modal" id="activityModal">
      <div class="ana-modal-backdrop" onclick="closeAnalyticsModal('activityModal')"></div>
      <div class="ana-modal-card">
        <div class="ana-modal-head">
          <div>
            <h3>Activity Breakdown</h3>
            <p>All system events by type</p>
          </div>
          <button class="ana-modal-close" onclick="closeAnalyticsModal('activityModal')" aria-label="Close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="ana-modal-body">
          @foreach($activitiesByType as $act)
            @php
              $typeLabel = ucfirst(str_replace('.', ' ', $act->type));
              $colorKey = explode('.', $act->type)[0];
              $barColor = $actTypeColors[$colorKey] ?? '#64748b';
              $pct = $actMax > 0 ? round(($act->count / $actMax) * 100) : 0;
            @endphp
            <div class="hbar-row">
              <div class="hbar-meta"><span>{{ $typeLabel }}</span><b>{{ number_format($act->count) }}</b></div>
              <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:{{ $barColor }};"></div></div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  @if($cropsByStage->count() > 5)
    <div class="ana-modal" id="stagesModal">
      <div class="ana-modal-backdrop" onclick="closeAnalyticsModal('stagesModal')"></div>
      <div class="ana-modal-card">
        <div class="ana-modal-head">
          <div>
            <h3>Crop Growth Stages</h3>
            <p>All tracked crop stages</p>
          </div>
          <button class="ana-modal-close" onclick="closeAnalyticsModal('stagesModal')" aria-label="Close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="ana-modal-body">
          @foreach($cropsByStage as $stageData)
            @php
              $stageKey = $stageData->growth_stage;
              $label = $stageLabels[$stageKey] ?? ucfirst($stageKey);
              $color = $stageColors[$stageKey] ?? '#64748b';
              $pct = $stageMax > 0 ? round(($stageData->count / $stageMax) * 100) : 0;
            @endphp
            <div class="hbar-row">
              <div class="hbar-meta"><span>{{ $label }}</span><b>{{ number_format($stageData->count) }}</b></div>
              <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:{{ $color }};"></div></div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  @if($harvestsByCrop->count() > 5)
    <div class="ana-modal" id="harvestModal">
      <div class="ana-modal-backdrop" onclick="closeAnalyticsModal('harvestModal')"></div>
      <div class="ana-modal-card">
        <div class="ana-modal-head">
          <div>
            <h3>Harvest Yield by Crop</h3>
            <p>All reported harvest yields and expenses</p>
          </div>
          <button class="ana-modal-close" onclick="closeAnalyticsModal('harvestModal')" aria-label="Close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="ana-modal-body">
          @foreach($harvestsByCrop as $hv)
            @php
              $pct = $harvestMax > 0 ? round(($hv->yield / $harvestMax) * 100) : 0;
            @endphp
            <div class="hbar-row">
              <div class="hbar-meta"><span>{{ ucfirst($hv->crop_type) }}</span><b>{{ \App\Helpers\SettingsHelper::formatYield($hv->yield) }}</b></div>
              <div class="hbar-track"><div class="hbar-fill" data-w="{{ $pct }}" style="background:#63856c;"></div></div>
              <div style="margin-top:6px;font-size:11px;color:#94a3b8;">{{ number_format($hv->count) }} report(s) · ₱{{ number_format($hv->expenses, 0) }} expenses</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  <div class="ana-tip" id="chartTip"></div>

  <script>
    (function () {
      // ── Data from PHP ──
      var userSignups = @json($userSignups);
      var loginTrend = @json($loginTrend);
      var loginRoles = @json($loginRoleCounts->toArray());
      var totalLogins = @js($totalLogins);
      var quotationsByDay = @json($quotationsByDay);
      var cropsBySeason = @json($cropsBySeason);
      var cropsByDay = @json($cropsByDay);
      var activitiesByDay = @json($activitiesByDay);
      var productsByCategory = @json($productsByCategory);

      // ── Helpers ──
      var tip = document.getElementById('chartTip');
      function showTip(html, x, y) {
        tip.innerHTML = html;
        tip.style.opacity = '1';
        var w = tip.offsetWidth, h = tip.offsetHeight;
        var px = Math.max(8, Math.min(window.innerWidth - w - 8, x + 14));
        var py = y - h - 14;
        if (py < 8) py = y + 16;
        tip.style.left = px + 'px';
        tip.style.top = py + 'px';
      }
      function hideTip() { tip.style.opacity = '0'; }
      function fmtInt(n) { return Number(n).toLocaleString('en-PH'); }
      function shortDate(str) {
        var d = new Date(str);
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
      }
      function fullDate(str) {
        var d = new Date(str);
        return d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
      }

      // ── Empty states ──
      var DEF_EMPTY_ICO = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="15"/></svg>';
      function renderEmpty(container, title, sub, svgMarkup) {
        if (!container) return;
        container.classList.add('has-empty');
        var screen = container.querySelector('.ana-empty');
        if (screen) {
          screen.querySelector('.ana-empty-title').textContent = title;
          screen.querySelector('.ana-empty-sub').textContent = sub;
          return;
        }
        var el = document.createElement('div');
        el.className = 'ana-empty';
        el.innerHTML = '<div class="ana-empty-ico">' + (svgMarkup || DEF_EMPTY_ICO) + '</div>' +
          '<div class="ana-empty-title"></div><div class="ana-empty-sub"></div>';
        el.querySelector('.ana-empty-title').textContent = title;
        el.querySelector('.ana-empty-sub').textContent = sub;
        container.appendChild(el);
      }
      function clearEmpty(container) {
        if (!container) return;
        container.classList.remove('has-empty');
        var el = container.querySelector('.ana-empty');
        if (el) el.remove();
      }

      // ── Sparklines ──
      function drawSpark(id, values, color) {
        var svg = document.getElementById(id);
        if (!svg) return;
        if (!values || !values.length) { svg.innerHTML = ''; return; }
        var W = 120, H = 36, pad = 2;
        var nums = values.map(function(v) { return Number(v); });
        var min = Math.min.apply(null, nums), max = Math.max.apply(null, nums);
        var span = (max - min) || 1;
        var pts = nums.map(function (v, i) {
          var x = (nums.length === 1) ? pad + (W - pad * 2) / 2 : pad + (i / (nums.length - 1)) * (W - pad * 2);
          var y = H - pad - ((v - min) / span) * (H - pad * 2);
          return x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');
        var area = '0,' + (H - pad) + ' ' + pts + ' ' + (W - pad) + ',' + (H - pad);
        svg.innerHTML = '<polygon points="' + area + '" fill="' + color + '" opacity="0.12"></polygon>' +
          '<polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>';
      }

      drawSpark('sparkUsers', userSignups.slice(-30).map(function(r) { return r.count; }), '#63856c');
      drawSpark('sparkQuotations', quotationsByDay.map(function(r) { return r.count; }), '#62829b');
      drawSpark('sparkCrops', cropsByDay.map(function(r) { return r.count; }), '#4A7A54');
      drawSpark('sparkActivities', activitiesByDay.map(function(r) { return r.count; }), '#7c3aed');

      // ── Quotation Trend (Area chart) ──
      function renderQuotationTrend() {
        var svg = document.getElementById('quotationTrendSvg');
        if (!quotationsByDay.length) {
          svg.innerHTML = '';
          renderEmpty(document.getElementById('quotationTrendWrap'), 'No quotation data yet',
            'Farmers will see cost-estimate trends here once they generate quotations within the last 30 days.',
            '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>');
          return;
        }
        clearEmpty(document.getElementById('quotationTrendWrap'));
        var W = 700, H = 260, padL = 46, padR = 14, padT = 18, padB = 30;
        var plotW = W - padL - padR, plotH = H - padT - padB;
        var n = quotationsByDay.length;
        var maxCount = 0;
        quotationsByDay.forEach(function (r) { maxCount = Math.max(maxCount, r.count); });
        maxCount = Math.max(5, Math.ceil(maxCount / 5) * 5);

        var px = function (i) { return padL + (n === 1 ? plotW / 2 : (i / (n - 1)) * plotW); };
        var yC = function (v) { return padT + (1 - v / maxCount) * plotH; };

        var parts = [];
        for (var g = 0; g <= 4; g++) {
          var y = padT + (g / 4) * plotH;
          parts.push('<line class="ana-grid-line" x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '"/>');
          parts.push('<text class="ana-axis" x="' + (padL - 8) + '" y="' + (y + 3) + '" text-anchor="end">' + fmtInt(Math.round(maxCount - (g / 4) * maxCount)) + '</text>');
        }
        var tickCount = Math.min(n, 7);
        for (var t = 0; t < tickCount; t++) {
          var idx = (n === 1) ? 0 : Math.round((t / (tickCount - 1)) * (n - 1));
          parts.push('<text class="ana-axis" x="' + px(idx) + '" y="' + (H - 8) + '" text-anchor="middle">' + shortDate(quotationsByDay[idx].date) + '</text>');
        }

        var areaD = '', lineD = '';
        var pts = [];
        for (var i = 0; i < n; i++) {
          var x = px(i), y = yC(quotationsByDay[i].count);
          areaD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          lineD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          pts.push({ x: x, yC: y, i: i });
        }
        areaD += 'L' + px(n - 1).toFixed(1) + ' ' + (padT + plotH) + ' L' + px(0).toFixed(1) + ' ' + (padT + plotH) + ' Z';

        parts.push('<defs><linearGradient id="trendGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#62829b" stop-opacity="0.25"/><stop offset="100%" stop-color="#62829b" stop-opacity="0.02"/></linearGradient></defs>');
        parts.push('<path d="' + areaD + '" fill="url(#trendGrad)" style="opacity:0;transition:opacity 0.8s ease 0.3s;" id="trendFill"></path>');
        parts.push('<path d="' + lineD + '" fill="none" stroke="#62829b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="stroke-dasharray:1;stroke-dashoffset:1;transition:stroke-dashoffset 1s ease 0.1s;" id="trendLine" pathLength="1"></path>');

        if (n <= 20) {
          for (var d = 0; d < n; d++) {
            parts.push('<circle cx="' + pts[d].x.toFixed(1) + '" cy="' + pts[d].yC.toFixed(1) + '" r="3" fill="#fff" stroke="#62829b" stroke-width="2" class="dot" style="opacity:0;transition:opacity 0.3s ease ' + (0.3 + d * 0.02) + 's;"></circle>');
          }
        }

        parts.push('<rect x="' + padL + '" y="' + padT + '" width="' + plotW + '" height="' + plotH + '" fill="transparent" id="trendHit"></rect>');
        parts.push('<line id="trendVline" x1="0" y1="' + padT + '" x2="0" y2="' + (padT + plotH) + '" class="ana-guide" style="display:none;"></line>');

        svg.innerHTML = parts.join('');

        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            var f = document.getElementById('trendFill'); if (f) f.style.opacity = '1';
            var l = document.getElementById('trendLine'); if (l) l.style.strokeDashoffset = '0';
            svg.querySelectorAll('.dot').forEach(function (el) { el.style.opacity = '1'; });
          });
        });

        var hit = document.getElementById('trendHit');
        hit.addEventListener('mousemove', function (e) {
          var rect = svg.getBoundingClientRect();
          var scale = 700 / rect.width;
          var vx = (e.clientX - rect.left) * scale;
          var best = 0, bd = 1e9;
          pts.forEach(function (p) { var dd = Math.abs(p.x - vx); if (dd < bd) { bd = dd; best = p.i; } });
          var p = pts[best];
          document.getElementById('trendVline').setAttribute('x1', p.x);
          document.getElementById('trendVline').setAttribute('x2', p.x);
          document.getElementById('trendVline').style.display = 'block';
          var sx = rect.left + (p.x / 700) * rect.width;
          var row = quotationsByDay[best];
          var html = '<div class="tt-date">' + fullDate(row.date) + '</div>' +
            '<div class="tt-row"><span><span class="sw" style="background:#62829b;"></span>Quotations</span><b>' + fmtInt(row.count) + '</b></div>';
          showTip(html, sx, rect.top + 20);
        });
        hit.addEventListener('mouseleave', function () {
          document.getElementById('trendVline').style.display = 'none';
          hideTip();
        });
      }

      // ── Donut helper ──
      function renderDonut(svgId, legendId, data, colorMap, labelMap, emptyHost, emptyTitle, emptySub, emptyIcon) {
        var svg = document.getElementById(svgId);
        var legend = document.getElementById(legendId);
        if (!data.length) {
          svg.innerHTML = '';
          legend.innerHTML = '';
          if (emptyHost) renderEmpty(emptyHost, emptyTitle, emptySub, emptyIcon);
          return;
        }
        if (emptyHost) clearEmpty(emptyHost);
        var vals = data.map(function(d) { return Number(d.count); });
        var total = vals.reduce(function(a, b) { return a + b; }, 0);
        var r = 72, cx = 110, cy = 110, C = 2 * Math.PI * r;
        var acc = 0;
        var segData = [];
        var parts = [];

        data.forEach(function (item, i) {
          var frac = vals[i] / total;
          var off = acc * C;
          acc += frac;
          var key = item.season || item.category || item.status;
          var greenRamp = ['#3f6212', '#65a30d', '#15803d', '#4A7A54', '#84cc16', '#6b8f71', '#4d7c0f', '#94a35c', '#86a17b', '#9bbf4f'];
          var col = colorMap[key] || greenRamp[i % greenRamp.length];
          var lbl = labelMap[key] || key || 'Other';
          segData.push({ color: col, label: lbl, value: vals[i], frac: frac });
          parts.push('<circle class="donut-seg" data-i="' + i + '" cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + col + '" stroke-width="22" stroke-dasharray="0 ' + C + '" stroke-dashoffset="' + (-off) + '" transform="rotate(-90 ' + cx + ' ' + cy + ')"></circle>');
        });
        parts.push('<text class="donut-center-total" id="' + svgId + 'Total" x="' + cx + '" y="' + (cy - 2) + '" text-anchor="middle">' + fmtInt(total) + '</text>');
        parts.push('<text class="donut-center-cap" id="' + svgId + 'Cap" x="' + cx + '" y="' + (cy + 16) + '" text-anchor="middle">Total</text>');
        svg.innerHTML = parts.join('');

        legend.innerHTML = segData.map(function (s, i) {
          return '<div class="d-legend-row" data-i="' + i + '">' +
            '<span class="sw" style="background:' + s.color + ';"></span>' +
            '<span class="lbl">' + s.label + '</span>' +
            '<span class="cnt">' + fmtInt(s.value) + '</span>' +
            '<span class="pct">' + (s.frac * 100).toFixed(0) + '%</span></div>';
        }).join('');

        segData.forEach(function (s, i) {
          var circle = svg.querySelector('[data-i="' + i + '"]');
          requestAnimationFrame(function () {
            requestAnimationFrame(function () {
              circle.style.transition = 'stroke-dasharray 0.9s cubic-bezier(0.4,0,0.2,1) ' + (i * 0.12) + 's';
              circle.setAttribute('stroke-dasharray', (s.frac * C).toFixed(1) + ' ' + C);
            });
          });
          circle.addEventListener('mouseenter', function () { focusSeg(svgId, legendId, segData, i); });
          circle.addEventListener('mouseleave', function () { focusSeg(svgId, legendId, segData, -1); });
        });

        legend.querySelectorAll('.d-legend-row').forEach(function (row) {
          row.addEventListener('mouseenter', function () { focusSeg(svgId, legendId, segData, parseInt(row.dataset.i)); });
          row.addEventListener('mouseleave', function () { focusSeg(svgId, legendId, segData, -1); });
        });
      }

      function focusSeg(svgId, legendId, segData, hot) {
        var svg = document.getElementById(svgId);
        var legend = document.getElementById(legendId);
        segData.forEach(function (s, j) {
          var c = svg.querySelector('[data-i="' + j + '"]');
          var row = legend.querySelector('[data-i="' + j + '"]');
          if (hot === -1) { c.classList.remove('hot', 'dim'); row.classList.remove('dim'); }
          else { c.classList.toggle('hot', j === hot); c.classList.toggle('dim', j !== hot); row.classList.toggle('dim', j !== hot); }
        });
        var totalEl = document.getElementById(svgId + 'Total');
        var capEl = document.getElementById(svgId + 'Cap');
        if (hot >= 0) {
          totalEl.textContent = fmtInt(segData[hot].value);
          capEl.textContent = segData[hot].label;
        } else {
          var t = segData.reduce(function(a, b) { return a + b.value; }, 0);
          totalEl.textContent = fmtInt(t);
          capEl.textContent = 'Total';
        }
      }

      // ── User Logins (area chart, last 14 days, admins excluded) ──
      var LOGIN_CHART_COLOR = '#63856c';
      var LOGIN_ROLE_META = [
        { key: 'farmer', label: 'Farmers', color: '#4A7A54' },
        { key: 'store_owner', label: 'Store Owners', color: '#d97706' }
      ];

      function renderLoginTrend() {
        var area = document.getElementById('loginTrendArea');
        var svg = document.getElementById('loginTrendSvg');
        if (!area || !svg) return;
        var data = loginTrend;
        var W = Math.max(240, area.clientWidth || 600);
        var H = Math.max(160, area.clientHeight || 220);
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

        var n = data.length;
        var maxV = 1;
        data.forEach(function (d) { maxV = Math.max(maxV, d.count); });
        maxV = Math.ceil(maxV / 3) * 3;

        var padL = 40, padR = 12, padT = 18, padB = 30;
        var plotW = W - padL - padR, plotH = H - padT - padB;

        function px(i) { return padL + (n === 1 ? plotW / 2 : (i / (n - 1)) * plotW); }
        function yC(v) { return padT + plotH - (v / maxV) * plotH; }

        var parts = [];
        for (var g = 0; g <= 4; g++) {
          var y = padT + (g / 4) * plotH;
          parts.push('<line class="ana-grid-line" x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '"/>');
          parts.push('<text class="ana-axis" x="' + (padL - 8) + '" y="' + (y + 3) + '" text-anchor="end">' + fmtInt(Math.round(maxV - (g / 4) * maxV)) + '</text>');
        }

        var areaD = '', lineD = '', pts = [];
        for (var i = 0; i < n; i++) {
          var x = px(i), y = yC(data[i].count);
          areaD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          lineD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          pts.push({ x: x, y: y, i: i });
        }
        areaD += 'L' + px(n - 1).toFixed(1) + ' ' + (padT + plotH) + ' L' + px(0).toFixed(1) + ' ' + (padT + plotH) + ' Z';

        parts.push('<defs><linearGradient id="loginGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#63856c" stop-opacity="0.25"/><stop offset="100%" stop-color="#63856c" stop-opacity="0.02"/></linearGradient></defs>');
        parts.push('<path d="' + areaD + '" fill="url(#loginGrad)" style="opacity:0;transition:opacity 0.8s ease 0.3s;" id="loginFill"></path>');
        parts.push('<path d="' + lineD + '" fill="none" stroke="#63856c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="stroke-dasharray:1;stroke-dashoffset:1;transition:stroke-dashoffset 1s ease 0.1s;" id="loginLine" pathLength="1"></path>');

        if (n <= 20) {
          for (var d = 0; d < n; d++) {
            parts.push('<circle cx="' + pts[d].x.toFixed(1) + '" cy="' + pts[d].y.toFixed(1) + '" r="3" fill="#fff" stroke="#63856c" stroke-width="2" class="dot" style="opacity:0;transition:opacity 0.3s ease ' + (0.3 + d * 0.02) + 's;"></circle>');
          }
        }

        var showLabelStep = n <= 10 ? 1 : Math.ceil(n / 7);
        for (var k = 0; k < n; k++) {
          if (k % showLabelStep !== 0 && k !== n - 1) continue;
          parts.push('<text class="ana-axis" x="' + px(k).toFixed(1) + '" y="' + (H - 8) + '" text-anchor="middle">' + shortDate(data[k].date) + '</text>');
        }

        parts.push('<rect x="' + padL + '" y="' + padT + '" width="' + plotW + '" height="' + plotH + '" fill="transparent" id="loginHit"></rect>');
        parts.push('<line id="loginVline" x1="0" y1="' + padT + '" x2="0" y2="' + (padT + plotH) + '" class="ana-guide" style="display:none;"></line>');

        svg.innerHTML = parts.join('');

        var badge = document.getElementById('loginRange');
        if (badge) badge.textContent = shortDate(data[0].date) + ' – ' + shortDate(data[n - 1].date);

        // Legend: role split (admins excluded from the query)
        var legend = document.getElementById('loginLegend');
        if (legend) {
          legend.innerHTML = LOGIN_ROLE_META.map(function (r) {
            var v = Number(loginRoles[r.key] || 0);
            var pct = totalLogins > 0 ? Math.round(v / totalLogins * 100) : 0;
            return '<div class="d-legend-row">' +
              '<span class="sw" style="background:' + r.color + ';"></span>' +
              '<span class="lbl">' + r.label + '</span>' +
              '<span class="cnt">' + fmtInt(v) + '</span>' +
              '<span class="pct">' + pct + '%</span></div>';
          }).join('');
        }

        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            var f = document.getElementById('loginFill'); if (f) f.style.opacity = '1';
            var l = document.getElementById('loginLine'); if (l) l.style.strokeDashoffset = '0';
            svg.querySelectorAll('.dot').forEach(function (el) { el.style.opacity = '1'; });
          });
        });

        var hit = document.getElementById('loginHit');
        hit.addEventListener('mousemove', function (e) {
          var rect = svg.getBoundingClientRect();
          var scale = W / rect.width;
          var vx = (e.clientX - rect.left) * scale;
          var best = 0, bd = 1e9;
          pts.forEach(function (p) { var dd = Math.abs(p.x - vx); if (dd < bd) { bd = dd; best = p.i; } });
          var p = pts[best];
          document.getElementById('loginVline').setAttribute('x1', p.x);
          document.getElementById('loginVline').setAttribute('x2', p.x);
          document.getElementById('loginVline').style.display = 'block';
          var sx = rect.left + (p.x / W) * rect.width;
          var row = data[best];
          var html = '<div class="tt-date">' + fullDate(row.date) + '</div>' +
            '<div class="tt-row"><span><span class="sw" style="background:#63856c;"></span>Logins</span><b>' + fmtInt(row.count) + '</b></div>';
          showTip(html, sx, rect.top + 20);
        });
        hit.addEventListener('mouseleave', function () {
          document.getElementById('loginVline').style.display = 'none';
          hideTip();
        });
      }

      var loginResizeTimer;
      window.addEventListener('resize', function () {
        clearTimeout(loginResizeTimer);
        loginResizeTimer = setTimeout(renderLoginTrend, 160);
      });

      // ── Animate horizontal fills ──
      function animateFills() {
        document.querySelectorAll('.hbar-fill').forEach(function (el) {
          el.style.width = (el.dataset.w || '0') + '%';
        });
      }

      // ── Reveal on scroll (progressive enhancement: content visible by default) ──
      function initReveal() {
        var els = document.querySelectorAll('[data-reveal]');
        if (!els.length) return;
        if ('IntersectionObserver' in window) {
          document.documentElement.classList.add('reveal-ready');
          var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
              if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
            });
          }, { threshold: 0.08 });
          els.forEach(function (el) { io.observe(el); });
          // Safety net: force-show anything still hidden if the observer stalls
          setTimeout(function () {
            document.querySelectorAll('[data-reveal]:not(.in)').forEach(function (el) { el.classList.add('in'); });
          }, 2500);
        } else {
          els.forEach(function (el) { el.classList.add('in'); });
        }
      }

      // ── Modal ──
      function openAnalyticsModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
      function closeAnalyticsModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('open');
        if (!document.querySelectorAll('.ana-modal.open').length) {
          document.body.style.overflow = '';
        }
      }
      window.openAnalyticsModal = openAnalyticsModal;
      window.closeAnalyticsModal = closeAnalyticsModal;
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          document.querySelectorAll('.ana-modal.open').forEach(function (m) {
            closeAnalyticsModal(m.id);
          });
        }
      });

      // ── Init ──
      renderQuotationTrend();
      renderDonut('seasonDonut', 'seasonLegend', cropsBySeason, {
        'wet': '#62829b', 'dry': '#a57f43'
      }, {
        'wet': 'Wet Season', 'dry': 'Dry Season'
      }, document.getElementById('seasonBody'), 'No crops by season yet',
        'Farmer crop tracking will show the wet vs. dry season split here.',
        '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>');
      renderDonut('categoryDonut', 'categoryLegend', productsByCategory, {
        'Fertilizer': '#3f6212', 'Seeds': '#65a30d', 'Pesticide': '#15803d',
        'Herbicide': '#4A7A54', 'Tools': '#84cc16', 'Other': '#6b8f71'
      }, {
        'Fertilizer': 'Fertilizer', 'Seeds': 'Seeds', 'Pesticide': 'Pesticide',
        'Herbicide': 'Herbicide', 'Tools': 'Tools', 'Other': 'Other'
      }, document.getElementById('categoryBody'), 'No products by category yet',
        'Once store owners register products, the catalog distribution appears here.',
        '<svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>');
      renderLoginTrend();

      var fillTimer = setTimeout(animateFills, 600);
      window.addEventListener('load', function () { clearTimeout(fillTimer); animateFills(); });

      setTimeout(initReveal, 60);
    })();
  </script>
@endsection