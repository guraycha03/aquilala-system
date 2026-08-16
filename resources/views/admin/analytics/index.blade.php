@extends('layouts.sidebar')

@section('title', 'System Analytics')

@section('content')
  <style>
    .ana-page { position: relative; }
    .ana-page::before,
    .ana-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .ana-page::before { width: 360px; height: 360px; background: rgba(16,185,129,0.16); top: -90px; right: -90px; }
    .ana-page::after { width: 300px; height: 300px; background: rgba(59,130,246,0.13); bottom: -70px; left: -70px; }
    .ana-content { position: relative; z-index: 1; }

    .ana-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .ana-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .ana-header p { font-size: 13px; color: #64748b; margin: 0; }
    .ana-header-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    .seg {
      display: inline-flex; background: rgba(255,255,255,0.8); border: 1px solid #e6ebe8;
      border-radius: 12px; padding: 4px; gap: 2px;
    }
    .seg button {
      border: none; background: transparent; font-size: 12px; font-weight: 700; color: #64748b;
      padding: 8px 16px; border-radius: 9px; cursor: pointer; transition: all 0.2s ease; font-family: inherit;
    }
    .seg button:hover { color: #334155; }
    .seg button.active { background: #143d23; color: #ffffff; box-shadow: 0 2px 8px rgba(20,61,35,0.25); }

    .btn-ana {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
      background: #143d23; border: 1px solid #143d23; color: #ffffff;
      padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer;
      transition: all 0.2s ease; font-family: inherit;
    }
    .btn-ana:hover { background: #1a4a2a; transform: translateY(-1px); }
    .btn-ana svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-ana.ghost { background: #ffffff; border-color: rgba(20,61,35,0.12); color: #14532d; box-shadow: 0 2px 8px rgba(20,61,35,0.06); }
    .btn-ana.ghost:hover { background: #f0fdf4; }

    .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
    .reveal.in { opacity: 1; transform: translateY(0); }

    /* KPI row */
    .ana-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .kpi-card {
      background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 16px; padding: 18px 20px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(0,0,0,0.09); }
    .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .kpi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; }
    .kpi-ico {
      width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .kpi-ico svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .kpi-value { font-size: 26px; font-weight: 700; color: #0f172a; margin-top: 10px; font-variant-numeric: tabular-nums; letter-spacing: -0.02em; }
    .kpi-foot { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
    .kpi-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 7px; }
    .kpi-trend svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
    .trend-up { background: #eef3ef; color: #63856c; }
    .trend-down { background: #f6ecec; color: #a86464; }
    .kpi-sub { font-size: 11px; color: #94a3b8; }
    .spark { margin-top: 12px; width: 100%; height: 34px; display: block; }

    /* Panels + grids */
    .ana-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
    }
    .ana-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
    .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    .panel-body { padding: 16px 22px 20px; }
    .ana-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 22px; align-items: start; margin-bottom: 22px; }
    .ana-grid.equal { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    .chart-wrap { position: relative; }
    .chart-wrap svg { width: 100%; height: auto; display: block; }
    .chart-hint { font-size: 11px; color: #94a3b8; margin-top: 8px; display: flex; align-items: center; gap: 6px; }
    .chart-hint svg { width: 12px; height: 12px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Chart legend */
    .chart-legend { display: flex; gap: 16px; flex-wrap: wrap; }
    .lg-item { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; color: #475569; cursor: pointer; user-select: none; }
    .lg-item.dim { opacity: 0.35; }
    .lg-swatch { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* SVG common */
    .ana-grid-line { stroke: #eef1ef; stroke-width: 1; }
    .ana-axis { font-size: 10px; fill: #94a3b8; font-variant-numeric: tabular-nums; }
    .ana-guide { stroke: #cbd5e1; stroke-width: 1; stroke-dasharray: 4 4; }

    /* Bars */
    .bar-g { transform-box: fill-box; transform-origin: bottom; transform: scaleY(0); transition: transform 0.7s cubic-bezier(0.25,0.8,0.25,1); }
    .bar-g.in { transform: scaleY(1); }
    .bar-g rect { transition: fill 0.15s ease; }
    .bar-hit { fill: transparent; }
    .bar-col-q { fill: #62829b; }
    .bar-col-p { fill: #a57f43; }
    .bar-col-s { fill: #63856c; }
    .bar-g:hover rect { filter: brightness(0.94); }

    /* Donut */
    .donut-center-total { font-size: 30px; font-weight: 800; fill: #0f172a; font-variant-numeric: tabular-nums; }
    .donut-center-cap { font-size: 10px; fill: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; }
    .donut-center-label { font-size: 12px; fill: #475569; }
    .donut-seg { transition: stroke-width 0.2s ease, opacity 0.2s ease; cursor: pointer; }
    .donut-seg:hover, .donut-seg.hot { stroke-width: 26; opacity: 1; }
    .donut-seg.dim { opacity: 0.25; }
    .d-legend { display: flex; flex-direction: column; gap: 9px; }
    .d-legend-row { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px 8px; border-radius: 9px; transition: background 0.15s ease; }
    .d-legend-row:hover { background: #f6f8f7; }
    .d-legend-row.dim { opacity: 0.4; }
    .d-legend-row .sw { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
    .d-legend-row .lbl { flex: 1; font-size: 12px; color: #475569; }
    .d-legend-row .cnt { font-size: 13px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .d-legend-row .pct { font-size: 11px; color: #94a3b8; width: 38px; text-align: right; font-variant-numeric: tabular-nums; }

    /* DSS horizontal bars */
    .dss-row { padding: 12px 0; }
    .dss-row + .dss-row { border-top: 1px solid #f1f3f2; }
    .dss-meta { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
    .dss-meta span { font-size: 13px; color: #64748b; font-weight: 500; }
    .dss-meta b { font-size: 14px; color: #0f172a; font-weight: 700; font-variant-numeric: tabular-nums; }
    .dss-track { height: 6px; border-radius: 999px; background: #eef1ef; overflow: hidden; }
    .dss-fill { height: 100%; border-radius: 999px; width: 0; transition: width 1s cubic-bezier(0.4,0,0.2,1); }

    /* Product / store rows */
    .row-item { display: flex; align-items: center; gap: 12px; padding: 11px 0; }
    .row-item + .row-item { border-top: 1px solid rgba(0,0,0,0.045); }
    .row-rank {
      width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 800; color: #63856c; background: #eef3ef; flex-shrink: 0;
    }
    .row-thumb { width: 40px; height: 40px; border-radius: 10px; background: #f4f7f5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .row-thumb svg { width: 18px; height: 18px; stroke: #94a3b8; fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
    .row-mid { flex: 1; min-width: 0; }
    .row-name { font-size: 13px; font-weight: 700; color: #1e2922; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .row-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .row-right { text-align: right; flex-shrink: 0; }
    .row-right .v { font-size: 13px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
    .row-right .s { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .mini-track { height: 5px; border-radius: 999px; background: #eef1ef; margin-top: 7px; overflow: hidden; }
    .mini-fill { height: 100%; border-radius: 999px; width: 0; transition: width 1s cubic-bezier(0.4,0,0.2,1); }

    /* Read-only rule matrix */
    .rule-table { width: 100%; border-collapse: collapse; text-align: left; }
    .rule-table th { padding: 10px 14px; color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: #fafbfa; }
    .rule-table td { padding: 12px 14px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .rule-table tbody tr { transition: background 0.15s ease; }
    .rule-table tbody tr:hover { background: #fafbfa; }
    .rule-table tbody tr:last-child td { border-bottom: none; }
    .season-pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize; }
    .season-wet { background: #eef2f6; color: #62829b; }
    .season-dry { background: #f6f0e6; color: #a57f43; }
    .rate-chip2 { background: #f1f5f9; border-radius: 8px; padding: 5px 10px; font-size: 12px; display: inline-flex; align-items: baseline; gap: 4px; }
    .rate-chip2 b { color: #143d23; font-variant-numeric: tabular-nums; }

    /* Floating chart tooltip */
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

    @media (max-width: 980px) {
      .ana-grid, .ana-grid.equal { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .ana-header { flex-direction: column; align-items: flex-start; }
    }
  </style>

  <div class="ana-page">
    <div class="ana-content">

      <!-- HEADER -->
      <div class="ana-header reveal in">
        <div>
          <h1>System Metrics &amp; Analytics</h1>
          <p>Statistical overview of DSS engine requests, quotation activity, and marketplace volume.</p>
        </div>
        <div class="ana-header-actions">
          <div class="seg" id="rangeSeg">
            <button data-range="7" class="active">7D</button>
            <button data-range="30">30D</button>
            <button data-range="90">90D</button>
          </div>
          <button id="exportBtn" class="btn-ana ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </button>
        </div>
      </div>

      <!-- KPI ROW -->
      <section class="ana-kpis" id="kpiRow">
        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Gross Quotation Value</div>
            <div class="kpi-ico" style="background:#eef3ef;color:#63856c;">
              <svg viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
          </div>
          <div class="kpi-value" id="kpiValue1">₱0</div>
          <div class="kpi-foot">
            <span class="kpi-trend trend-up" id="kpiTrend1"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg><span id="kpiTrendPct1">0%</span></span>
            <span class="kpi-sub">vs previous period</span>
          </div>
          <svg class="spark" id="spark1" viewBox="0 0 120 34" preserveAspectRatio="none"></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Quotation Requests</div>
            <div class="kpi-ico" style="background:#eef2f6;color:#62829b;">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
          </div>
          <div class="kpi-value" id="kpiValue2">0</div>
          <div class="kpi-foot">
            <span class="kpi-trend trend-up" id="kpiTrend2"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg><span id="kpiTrendPct2">0%</span></span>
            <span class="kpi-sub">vs previous period</span>
          </div>
          <svg class="spark" id="spark2" viewBox="0 0 120 34" preserveAspectRatio="none"></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">Active Products</div>
            <div class="kpi-ico" style="background:#eef3ef;color:#16a34a;">
              <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
          </div>
          <div class="kpi-value">96</div>
          <div class="kpi-foot">
            <span class="kpi-trend trend-down"><svg viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>12</span>
            <span class="kpi-sub">low-stock items need restock</span>
          </div>
          <svg class="spark" viewBox="0 0 120 34" preserveAspectRatio="none"><polyline points="0,18 12,16 24,19 36,14 48,15 60,11 72,13 84,9 96,11 108,7 120,8" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>

        <div class="kpi-card reveal" data-reveal>
          <div class="kpi-top">
            <div class="kpi-label">DSS / AI Requests</div>
            <div class="kpi-ico" style="background:#f1eef6;color:#86799f;">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
          </div>
          <div class="kpi-value">318</div>
          <div class="kpi-foot">
            <span class="kpi-trend trend-up"><svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>3.1%</span>
            <span class="kpi-sub">recommendations generated</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ROW E: DECISION RULE MATRIX (read-only) -->
      <section class="ana-panel reveal" data-reveal style="margin-top:22px;">
        <div class="panel-head">
          <div>
            <h3>Decision Rule Matrix</h3>
            <p>Read-only baseline application rates per hectare — managed by store owners</p>
          </div>
        </div>
        <div class="panel-body">
          @if(($formulas ?? collect())->count() === 0)
            <div style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No formulas configured yet. Store owners define crop-specific formulas in their <strong>Input Formulas</strong> section.</p>
            </div>
          @else
            @foreach($grouped as $crop => $seasons)
              <div style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="font-size:14px;font-weight:700;color:#1e293b;text-transform:capitalize;">{{ $crop }}</span>
                  <span style="font-size:11px;color:#94a3b8;">{{ $seasons->flatten()->count() }} rule(s)</span>
                </div>
                @foreach($seasons as $season => $seasonFormulas)
                  <div style="display:flex;align-items:center;gap:10px;margin:14px 0 8px;">
                    <span class="season-pill {{ $season === 'wet' ? 'season-wet' : 'season-dry' }}">
                      <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        @if($season === 'wet')
                          <path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/>
                        @else
                          <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        @endif
                      </svg>
                      {{ $season }} season
                    </span>
                  </div>
                  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
                    <div class="app-scroll">
                      <table class="rule-table">
                      <thead>
                        <tr><th>Product</th><th>Rate / Hectare</th><th>Notes</th></tr>
                      </thead>
                      <tbody>
                        @foreach($seasonFormulas as $formula)
                          <tr>
                            <td style="font-weight:600;">{{ $formula->product->name ?? 'Unknown Product' }}</td>
                            <td>
                              <span class="rate-chip2">
                                <b>{{ number_format((float) $formula->rate_per_hectare, 2) }}</b>
                                <span style="color:#94a3b8;">{{ $formula->unit }} / ha</span>
                              </span>
                            </td>
                            <td style="color:#64748b;font-size:12px;max-width:340px;">{{ $formula->notes ?? '—' }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                    </div>
                    <div class="app-scrollbar">
                      <div class="app-scrollbar-track"></div>
                      <div class="app-scrollbar-thumb"></div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endforeach
          @endif
        </div>
      </section>

      <!-- ROW B: AREA + DONUT -->
      <section class="ana-grid" style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Quotation Volume &amp; Market Value</h3>
              <p id="areaRangeLabel">Last 7 days</p>
            </div>
            <div class="chart-legend">
              <span class="lg-item" id="lgQuotations">
                <span class="lg-swatch" style="background:#63856c;"></span> Quotations
              </span>
              <span class="lg-item" id="lgRevenue">
                <span class="lg-swatch" style="background:#62829b;"></span> Revenue
              </span>
            </div>
          </div>
          <div class="panel-body">
            <div class="chart-wrap" id="areaWrap">
              <svg id="areaSvg" viewBox="0 0 700 280"></svg>
              <div id="areaGuide" style="position:absolute;top:0;bottom:0;width:1px;background:rgba(148,163,184,0.7);display:none;pointer-events:none;"></div>
            </div>
            <div class="chart-hint">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
              Hover the chart to inspect daily values. Click a legend item to toggle a series.
            </div>
          </div>
        </div>

        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Quotation Status</h3>
              <p>Distribution of requests by stage</p>
            </div>
          </div>
          <div class="panel-body" style="display:grid;grid-template-columns:minmax(0,150px) 1fr;gap:6px;align-items:center;">
            <div class="chart-wrap" style="max-width:170px;justify-self:center;">
              <svg id="donutSvg" viewBox="0 0 220 220"></svg>
            </div>
            <div class="d-legend" id="donutLegend"></div>
          </div>
        </div>
      </section>

      <!-- ROW C: BARS + DSS -->
      <section class="ana-grid equal" style="margin-bottom:22px;">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Daily Platform Activity</h3>
              <p>Quotations, product updates &amp; sign-ups per period</p>
            </div>
          </div>
          <div class="panel-body">
            <div class="chart-wrap" id="barsWrap">
              <svg id="barsSvg" viewBox="0 0 700 240"></svg>
              <div id="barsGuide" style="position:absolute;top:0;bottom:0;width:1px;background:rgba(148,163,184,0.7);display:none;pointer-events:none;"></div>
            </div>
            <div class="chart-hint" style="gap:14px;">
              <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:9px;height:9px;border-radius:2px;background:#62829b;display:inline-block;"></span> Quotations</span>
              <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:9px;height:9px;border-radius:2px;background:#a57f43;display:inline-block;"></span> Product updates</span>
              <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:9px;height:9px;border-radius:2px;background:#63856c;display:inline-block;"></span> Sign-ups</span>
            </div>
          </div>
        </div>

        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>DSS Engine Requests</h3>
              <p>Recommendations by crop type</p>
            </div>
          </div>
          <div class="panel-body">
            <div id="dssRows">
              <div class="dss-row">
                <div class="dss-meta"><span>Rice</span><b>118</b></div>
                <div class="dss-track"><div class="dss-fill" data-w="100" style="background:#63856c;"></div></div>
              </div>
              <div class="dss-row">
                <div class="dss-meta"><span>Corn</span><b>86</b></div>
                <div class="dss-track"><div class="dss-fill" data-w="73" style="background:#62829b;"></div></div>
              </div>
              <div class="dss-row">
                <div class="dss-meta"><span>Vegetable</span><b>64</b></div>
                <div class="dss-track"><div class="dss-fill" data-w="54" style="background:#a57f43;"></div></div>
              </div>
              <div class="dss-row">
                <div class="dss-meta"><span>Coconut</span><b>50</b></div>
                <div class="dss-track"><div class="dss-fill" data-w="42" style="background:#86799f;"></div></div>
              </div>
              <div class="dss-row">
                <div class="dss-meta"><span>Fertilizer Blend</span><b>38</b></div>
                <div class="dss-track"><div class="dss-fill" data-w="32" style="background:#a86464;"></div></div>
              </div>
            </div>
            <div class="chart-hint">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
              Requests handled by the recommendation engine (Wet Season).
            </div>
          </div>
        </div>
      </section>

      <!-- ROW D: TOP PRODUCTS + TOP STORES -->
      <section class="ana-grid equal">
        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Top Products</h3>
              <p>Highest-selling items across stores</p>
            </div>
          </div>
          <div class="panel-body">
            <div id="topProducts">
              <div class="row-item">
                <div class="row-rank">1</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Urea Fertilizer 46-0-0</div>
                  <div class="row-sub">Rice Mill &amp; Agri Supplies</div>
                  <div class="mini-track"><div class="mini-fill" data-w="100" style="background:#63856c;"></div></div>
                </div>
                <div class="row-right"><div class="v">214 sold</div><div class="s">₱1,150.00</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">2</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Rice Seeds NSIC Rc222</div>
                  <div class="row-sub">Bulan Farm Depot</div>
                  <div class="mini-track"><div class="mini-fill" data-w="82" style="background:#62829b;"></div></div>
                </div>
                <div class="row-right"><div class="v">176 sold</div><div class="s">₱1,280.00</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">3</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Insecticide Fury 500ml</div>
                  <div class="row-sub">Greenfield Agro Store</div>
                  <div class="mini-track"><div class="mini-fill" data-w="66" style="background:#a57f43;"></div></div>
                </div>
                <div class="row-right"><div class="v">142 sold</div><div class="s">₱620.00</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">4</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">NPK 14-14-14 Fertilizer</div>
                  <div class="row-sub">Fresh Harvest Trading</div>
                  <div class="mini-track"><div class="mini-fill" data-w="55" style="background:#86799f;"></div></div>
                </div>
                <div class="row-right"><div class="v">118 sold</div><div class="s">₱980.00</div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="ana-panel reveal" data-reveal>
          <div class="panel-head">
            <div>
              <h3>Top Stores</h3>
              <p>Highest revenue contribution</p>
            </div>
          </div>
          <div class="panel-body">
            <div id="topStores">
              <div class="row-item">
                <div class="row-rank">1</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="M2 9h20l-1-4H3L2 9z"/><path d="M6 14h4v4H6v-4z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Rice Mill &amp; Agri Supplies</div>
                  <div class="row-sub">Cagba, Bulan</div>
                  <div class="mini-track"><div class="mini-fill" data-w="100" style="background:#63856c;"></div></div>
                </div>
                <div class="row-right"><div class="v">₱64.2k</div><div class="s">14.2% of total</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">2</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="M2 9h20l-1-4H3L2 9z"/><path d="M6 14h4v4H6v-4z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Bulan Farm Depot</div>
                  <div class="row-sub">Poblacion, Bulan</div>
                  <div class="mini-track"><div class="mini-fill" data-w="82" style="background:#62829b;"></div></div>
                </div>
                <div class="row-right"><div class="v">₱52.8k</div><div class="s">11.7% of total</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">3</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="M2 9h20l-1-4H3L2 9z"/><path d="M6 14h4v4H6v-4z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Greenfield Agro Store</div>
                  <div class="row-sub">San Francisco, Bulan</div>
                  <div class="mini-track"><div class="mini-fill" data-w="64" style="background:#a57f43;"></div></div>
                </div>
                <div class="row-right"><div class="v">₱41.3k</div><div class="s">9.2% of total</div></div>
              </div>
              <div class="row-item">
                <div class="row-rank">4</div>
                <div class="row-thumb"><svg viewBox="0 0 24 24"><path d="M3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="M2 9h20l-1-4H3L2 9z"/><path d="M6 14h4v4H6v-4z"/></svg></div>
                <div class="row-mid">
                  <div class="row-name">Fresh Harvest Trading</div>
                  <div class="row-sub">Vista Hermosa, Bulan</div>
                  <div class="mini-track"><div class="mini-fill" data-w="45" style="background:#86799f;"></div></div>
                </div>
                <div class="row-right"><div class="v">₱28.9k</div><div class="s">6.4% of total</div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>

  <div class="ana-tip" id="chartTip"></div>

  <script>
    (function () {
      var ranges = { 7: 7, 30: 30, 90: 90 };
      var currentRange = 7;
      var series = buildSeries(7);

      // Deterministic pseudo-random noise so the charts look organic but stable
      function noise(i) {
        var x = Math.sin(i * 12.9898) * 43758.5453;
        return x - Math.floor(x);
      }

      function buildSeries(days) {
        var out = [];
        var today = new Date();
        for (var i = days - 1; i >= 0; i--) {
          var d = new Date(today);
          d.setDate(today.getDate() - i);
          var t = (days - i) / days;
          var wave = Math.sin(t * Math.PI) * 0.5 + 0.5;
          var k = days - i;
          out.push({
            d: d,
            q: Math.round(11 + wave * 9 + noise(k) * 6),
            r: Math.round(5600 + wave * 5600 + noise(k + 7) * 3400),
            p: Math.round(5 + wave * 4 + noise(k + 3) * 4),
            s: Math.round(3 + wave * 3 + noise(k + 11) * 3)
          });
        }
        return out;
      }

      var tip = document.getElementById('chartTip');
      function showTip(html, x, y) {
        tip.innerHTML = html;
        tip.style.opacity = '1';
        var w = tip.offsetWidth;
        var h = tip.offsetHeight;
        var px = Math.max(8, Math.min(window.innerWidth - w - 8, x + 14));
        var py = y - h - 14;
        if (py < 8) py = y + 16;
        tip.style.left = px + 'px';
        tip.style.top = py + 'px';
      }
      function hideTip() { tip.style.opacity = '0'; }
      function fmtInt(n) { return n.toLocaleString('en-PH'); }
      function fmtPeso(n) {
        if (n >= 1000) return '₱' + (n / 1000).toFixed(n >= 100000 ? 0 : 1) + 'k';
        return '₱' + fmtInt(n);
      }
      function shortDate(d) {
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
      }

      /* ---------------- KPI + SPARKLINES ---------------- */
      function drawSpark(id, values, color) {
        var svg = document.getElementById(id);
        if (!svg) return;
        var W = 120, H = 34, pad = 2;
        var min = Math.min.apply(null, values), max = Math.max.apply(null, values);
        var span = (max - min) || 1;
        var pts = values.map(function (v, i) {
          var x = pad + (i / (values.length - 1)) * (W - pad * 2);
          var y = H - pad - ((v - min) / span) * (H - pad * 2);
          return x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');
        var area = '0,' + (H - pad) + ' ' + pts + ' ' + (W - pad) + ',' + (H - pad);
        svg.innerHTML = '<polygon points="' + area + '" fill="' + color + '" opacity="0.10"></polygon>' +
          '<polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>';
      }

      function updateKpis() {
        var sumR = 0, sumQ = 0;
        series.forEach(function (p) { sumR += p.r; sumQ += p.q; });
        var half = Math.floor(series.length / 2);
        var first = series.slice(0, half), last = series.slice(half);
        var sumF = function (arr, f) { return arr.reduce(function (a, b) { return a + f(b); }, 0); };
        var prevR = sumF(first, function (p) { return p.r; });
        var curR = sumF(last, function (p) { return p.r; });
        var prevQ = sumF(first, function (p) { return p.q; });
        var curQ = sumF(last, function (p) { return p.q; });

        document.getElementById('kpiValue1').textContent = fmtPeso(sumR);
        document.getElementById('kpiValue2').textContent = fmtInt(sumQ);

        setTrend('kpiTrendPct1', 'kpiTrend1', curR, prevR);
        setTrend('kpiTrendPct2', 'kpiTrend2', curQ, prevQ);

        drawSpark('spark1', series.map(function (p) { return p.r; }), '#63856c');
        drawSpark('spark2', series.map(function (p) { return p.q; }), '#62829b');
      }

      function setTrend(pctId, chipId, cur, prev) {
        var el = document.getElementById(pctId);
        var chip = document.getElementById(chipId);
        var pct = prev === 0 ? 0 : ((cur - prev) / prev) * 100;
        el.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '%';
        chip.className = 'kpi-trend ' + (pct >= 0 ? 'trend-up' : 'trend-down');
        chip.innerHTML = (pct >= 0
          ? '<svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>'
          : '<svg viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>')
          + '<span>' + el.textContent + '</span>';
      }

      /* ---------------- AREA CHART ---------------- */
      var showQ = true, showR = true;
      var areaPts = [];

      function renderArea() {
        var svg = document.getElementById('areaSvg');
        var n = series.length;
        var W = 700, H = 280, padL = 46, padR = 14, padT = 18, padB = 30;
        var plotW = W - padL - padR, plotH = H - padT - padB;
        var maxQ = 0, maxR = 0;
        series.forEach(function (p) { maxQ = Math.max(maxQ, p.q); maxR = Math.max(maxR, p.r); });
        maxQ = Math.max(10, Math.ceil(maxQ / 10) * 10);
        maxR = Math.max(1000, Math.ceil(maxR / 1000) * 1000);

        var px = function (i) { return padL + (n === 1 ? 0 : (i / (n - 1)) * plotW); };
        var yq = function (v) { return padT + (1 - v / maxQ) * plotH; };
        var yr = function (v) { return padT + (1 - v / maxR) * plotH; };

        var parts = [];
        // grid + left axis
        for (var g = 0; g <= 4; g++) {
          var y = padT + (g / 4) * plotH;
          var val = Math.round(maxQ - (g / 4) * maxQ);
          parts.push('<line class="ana-grid-line" x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '"/>');
          parts.push('<text class="ana-axis" x="' + (padL - 8) + '" y="' + (y + 3) + '" text-anchor="end">' + fmtInt(val) + '</text>');
        }
        // right axis (revenue)
        for (var g2 = 0; g2 <= 4; g2++) {
          var y2 = padT + (g2 / 4) * plotH;
          var rv = Math.round(maxR - (g2 / 4) * maxR);
          parts.push('<text class="ana-axis" x="' + (W - padR + 8) + '" y="' + (y2 + 3) + '">' + fmtPeso(rv) + '</text>');
        }
        // x labels
        var tickCount = n <= 7 ? n : 7;
        for (var t = 0; t < tickCount; t++) {
          var idx = Math.round((t / (tickCount - 1)) * (n - 1));
          parts.push('<text class="ana-axis" x="' + px(idx) + '" y="' + (H - 8) + '" text-anchor="middle">' + shortDate(series[idx].d) + '</text>');
        }

        // area path (quotations)
        var areaD = '', lineD = '', revD = '';
        areaPts = [];
        for (var i = 0; i < n; i++) {
          var x = px(i), y = yq(series[i].q);
          areaD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          lineD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
          revD += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + yr(series[i].r).toFixed(1) + ' ';
          areaPts.push({ x: x, yq: y, yr: yr(series[i].r), i: i });
        }
        areaD += 'L' + px(n - 1).toFixed(1) + ' ' + (padT + plotH) + ' L' + px(0).toFixed(1) + ' ' + (padT + plotH) + ' Z';

        var grad = '<defs><linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">' +
          '<stop offset="0%" stop-color="#63856c" stop-opacity="0.28"/>' +
          '<stop offset="100%" stop-color="#63856c" stop-opacity="0.02"/></linearGradient></defs>';

        parts.push(grad);
        parts.push('<path id="areaFill" d="' + areaD + '" fill="url(#areaGrad)" style="opacity:0;transition:opacity 0.8s ease 0.5s;"></path>');
        parts.push('<path id="qLine" class="drawline" d="' + lineD + '" fill="none" stroke="#63856c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" pathLength="1" style="stroke-dasharray:1;stroke-dashoffset:1;transition:stroke-dashoffset 1.1s cubic-bezier(0.4,0,0.2,1) 0.15s;"></path>');
        parts.push('<path id="rLine" class="drawline" d="' + revD + '" fill="none" stroke="#62829b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="6 4" pathLength="1" style="stroke-dasharray:1;stroke-dashoffset:1;transition:stroke-dashoffset 1.1s cubic-bezier(0.4,0,0.2,1) 0.3s;"></path>');

        // dots (only when not too dense)
        if (n <= 15) {
          for (var d = 0; d < n; d++) {
            parts.push('<circle cx="' + areaPts[d].x.toFixed(1) + '" cy="' + areaPts[d].yq.toFixed(1) + '" r="3.2" fill="#ffffff" stroke="#63856c" stroke-width="2" class="dot qdot" style="opacity:0;transition:opacity 0.4s ease ' + (0.4 + d * 0.03) + 's;"></circle>');
            parts.push('<circle cx="' + areaPts[d].x.toFixed(1) + '" cy="' + areaPts[d].yr.toFixed(1) + '" r="3" fill="#ffffff" stroke="#62829b" stroke-width="2" class="dot rdot" style="opacity:0;transition:opacity 0.4s ease ' + (0.4 + d * 0.03) + 's;"></circle>');
          }
        }

        // hover hit area
        parts.push('<rect x="' + padL + '" y="' + padT + '" width="' + plotW + '" height="' + plotH + '" fill="transparent" id="areaHit"></rect>');
        // guide line
        parts.push('<line id="areaVline" x1="0" y1="' + padT + '" x2="0" y2="' + (padT + plotH) + '" class="ana-guide" style="display:none;"></line>');

        svg.innerHTML = parts.join('');

        // animate after paint
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            document.getElementById('areaFill').style.opacity = '1';
            document.getElementById('qLine').style.strokeDashoffset = '0';
            document.getElementById('rLine').style.strokeDashoffset = '0';
            document.querySelectorAll('#areaSvg .dot').forEach(function (el) { el.style.opacity = '1'; });
          });
        });
        applySeriesVisibility();

        // hover
        var hit = document.getElementById('areaHit');
        hit.addEventListener('mousemove', function (e) {
          var rect = svg.getBoundingClientRect();
          var scale = 700 / rect.width;
          var vx = (e.clientX - rect.left) * scale;
          var best = 0, bd = 1e9;
          areaPts.forEach(function (p) { var dd = Math.abs(p.x - vx); if (dd < bd) { bd = dd; best = p.i; } });
          var p = areaPts[best];
          var gline = document.getElementById('areaVline');
          gline.setAttribute('x1', p.x); gline.setAttribute('x2', p.x);
          gline.style.display = 'block';
          var sx = rect.left + (p.x / 700) * rect.width;
          var row = series[best];
          var html = '<div class="tt-date">' + row.d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }) + '</div>' +
            '<div class="tt-row"><span><span class="sw" style="background:#63856c;"></span>Quotations</span><b>' + fmtInt(row.q) + '</b></div>' +
            '<div class="tt-row"><span><span class="sw" style="background:#62829b;"></span>Revenue</span><b>' + fmtPeso(row.r) + '</b></div>';
          showTip(html, sx, rect.top + 20);
        });
        hit.addEventListener('mouseleave', function () {
          document.getElementById('areaVline').style.display = 'none';
          hideTip();
        });
      }

      function applySeriesVisibility() {
        document.getElementById('areaFill').style.display = showQ ? '' : 'none';
        document.getElementById('qLine').style.display = showQ ? '' : 'none';
        document.getElementById('rLine').style.display = showR ? '' : 'none';
        document.querySelectorAll('#areaSvg .qdot').forEach(function (el) { el.style.display = showQ ? '' : 'none'; });
        document.querySelectorAll('#areaSvg .rdot').forEach(function (el) { el.style.display = showR ? '' : 'none'; });
        document.getElementById('lgQuotations').classList.toggle('dim', !showQ);
        document.getElementById('lgRevenue').classList.toggle('dim', !showR);
      }

      document.getElementById('lgQuotations').addEventListener('click', function () { showQ = !showQ; applySeriesVisibility(); });
      document.getElementById('lgRevenue').addEventListener('click', function () { showR = !showR; applySeriesVisibility(); });

      /* ---------------- DONUT ---------------- */
      var donutData = [
        { label: 'Pending Response', color: '#a57f43' },
        { label: 'Estimate Computed', color: '#62829b' },
        { label: 'Estimate Dispatched', color: '#86799f' },
        { label: 'Accepted', color: '#63856c' },
        { label: 'Rejected', color: '#a86464' }
      ];
      var donutValues = [34, 41, 23, 29, 15];
      var segEls = [];

      function donutScale() {
        var m = { 7: 0.35, 30: 1, 90: 2.6 };
        return m[currentRange] || 1;
      }

      function renderDonut() {
        var svg = document.getElementById('donutSvg');
        var vals = donutValues.map(function (v) { return Math.max(1, Math.round(v * donutScale())); });
        var total = vals.reduce(function (a, b) { return a + b; }, 0);
        var r = 72, cx = 110, cy = 110, C = 2 * Math.PI * r;
        var acc = 0;
        segEls = [];
        var parts = [];

        donutData.forEach(function (item, i) {
          var frac = vals[i] / total;
          var off = acc * C;
          acc += frac;
          segEls.push({
            color: item.color,
            label: item.label,
            value: vals[i],
            frac: frac
          });
          parts.push('<circle class="donut-seg" data-i="' + i + '" cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + item.color + '" stroke-width="22" stroke-dasharray="0 ' + C + '" stroke-dashoffset="' + (-off) + '" transform="rotate(-90 ' + cx + ' ' + cy + ')"></circle>');
        });

        parts.push('<text class="donut-center-total" id="donutTotal" x="' + cx + '" y="' + (cy - 2) + '" text-anchor="middle">' + fmtInt(total) + '</text>');
        parts.push('<text class="donut-center-cap" id="donutCap" x="' + cx + '" y="' + (cy + 16) + '" text-anchor="middle">Requests</text>');
        parts.push('<text class="donut-center-label" id="donutLabel" x="' + cx + '" y="' + (cy + 30) + '" text-anchor="middle"></text>');

        svg.innerHTML = parts.join('');

        // legend
        var legend = document.getElementById('donutLegend');
        legend.innerHTML = donutData.map(function (item, i) {
          return '<div class="d-legend-row" data-i="' + i + '">' +
            '<span class="sw" style="background:' + item.color + ';"></span>' +
            '<span class="lbl">' + item.label + '</span>' +
            '<span class="cnt">' + fmtInt(vals[i]) + '</span>' +
            '<span class="pct">' + (fracPct(vals[i] / total)) + '</span></div>';
        }).join('');

        // draw animation
        segEls.forEach(function (s, i) {
          var circle = svg.querySelector('[data-i="' + i + '"]');
          requestAnimationFrame(function () {
            requestAnimationFrame(function () {
              circle.style.transition = 'stroke-dasharray 0.9s cubic-bezier(0.4,0,0.2,1) ' + (i * 0.13) + 's';
              circle.setAttribute('stroke-dasharray', (s.frac * C).toFixed(1) + ' ' + C);
            });
          });
          circle.addEventListener('mouseenter', function () { focusDonut(i, true); });
          circle.addEventListener('mouseleave', function () { focusDonut(-1, true); });
        });

        // legend hover
        legend.querySelectorAll('.d-legend-row').forEach(function (row) {
          row.addEventListener('mouseenter', function () { focusDonut(parseInt(row.dataset.i), false); });
          row.addEventListener('mouseleave', function () { focusDonut(-1, false); });
        });
      }

      function fracPct(f) { return (f * 100).toFixed(0) + '%'; }

      function focusDonut(i, swapCenter) {
        segEls.forEach(function (s, j) {
          var c = document.getElementById('donutSvg').querySelector('[data-i="' + j + '"]');
          var row = document.querySelector('#donutLegend [data-i="' + j + '"]');
          var hot = i === j;
          if (i === -1) { c.classList.remove('hot'); c.classList.remove('dim'); row.classList.remove('dim'); }
          else { c.classList.toggle('hot', hot); c.classList.toggle('dim', !hot); row.classList.toggle('dim', !hot); }
        });
        if (swapCenter && i !== -1) {
          document.getElementById('donutTotal').textContent = fmtInt(segEls[i].value);
          document.getElementById('donutCap').textContent = segEls[i].label;
          document.getElementById('donutLabel').textContent = fracPct(segEls[i].frac);
        } else if (i === -1) {
          var total = segEls.reduce(function (a, b) { return a + b.value; }, 0);
          document.getElementById('donutTotal').textContent = fmtInt(total);
          document.getElementById('donutCap').textContent = 'Requests';
          document.getElementById('donutLabel').textContent = '';
        }
      }

      /* ---------------- STACKED BARS ---------------- */
      var barsPts = [];

      function aggregateBars(days, groups) {
        var per = Math.max(1, Math.round(days / groups));
        var out = [];
        for (var g = 0; g < groups; g++) {
          var chunk = series.slice(g * per, (g + 1) * per);
          if (!chunk.length) break;
          out.push({
            d: chunk[0].d,
            q: chunk.reduce(function (a, b) { return a + b.q; }, 0),
            p: chunk.reduce(function (a, b) { return a + b.p; }, 0),
            s: chunk.reduce(function (a, b) { return a + b.s; }, 0)
          });
        }
        return out;
      }

      function renderBars() {
        var svg = document.getElementById('barsSvg');
        var groups = currentRange === 7 ? 7 : (currentRange === 30 ? 10 : 12);
        var bars = aggregateBars(currentRange, groups);
        var W = 700, H = 240, padL = 40, padR = 10, padT = 16, padB = 30;
        var plotW = W - padL - padR, plotH = H - padT - padB;
        var maxV = 0;
        bars.forEach(function (b) { maxV = Math.max(maxV, b.q + b.p + b.s); });
        maxV = Math.max(10, Math.ceil(maxV / 20) * 20);
        var bw = plotW / bars.length;
        var bx = function (i) { return padL + i * bw; };
        barsPts = [];

        var parts = [];
        for (var g = 0; g <= 4; g++) {
          var y = padT + (g / 4) * plotH;
          parts.push('<line class="ana-grid-line" x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y + '"/>');
          parts.push('<text class="ana-axis" x="' + (padL - 8) + '" y="' + (y + 3) + '" text-anchor="end">' + fmtInt(Math.round(maxV - (g / 4) * maxV)) + '</text>');
        }

        bars.forEach(function (b, i) {
          var x = bx(i), w = Math.min(46, bw * 0.6);
          var hq = (b.q / maxV) * plotH, hp = (b.p / maxV) * plotH, hs = (b.s / maxV) * plotH;
          var base = padT + plotH;
          var yS = base - hs, yP = yS - hp, yQ = yP - hq;
          barsPts.push({ x: x + w / 2, b: b, yTop: yQ });
          parts.push('<g class="bar-g">' +
            '<rect class="bar-col-s" x="' + x.toFixed(1) + '" y="' + yS.toFixed(1) + '" width="' + w.toFixed(1) + '" height="' + hs.toFixed(1) + '" rx="3"></rect>' +
            '<rect class="bar-col-p" x="' + x.toFixed(1) + '" y="' + yP.toFixed(1) + '" width="' + w.toFixed(1) + '" height="' + hp.toFixed(1) + '" rx="3"></rect>' +
            '<rect class="bar-col-q" x="' + x.toFixed(1) + '" y="' + yQ.toFixed(1) + '" width="' + w.toFixed(1) + '" height="' + hq.toFixed(1) + '" rx="3"></rect>' +
            '<rect class="bar-hit" x="' + x.toFixed(1) + '" y="' + padT + '" width="' + w.toFixed(1) + '" height="' + plotH + '" rx="3"></rect>' +
            '</g>');
          parts.push('<text class="ana-axis" x="' + (x + w / 2).toFixed(1) + '" y="' + (H - 8) + '" text-anchor="middle">' + shortDate(b.d) + '</text>');
        });

        svg.innerHTML = parts.join('');

        // animate
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            svg.querySelectorAll('.bar-g').forEach(function (el) { el.classList.add('in'); });
          });
        });

        // hover
        svg.querySelectorAll('.bar-hit').forEach(function (hit, i) {
          var p = barsPts[i];
          hit.addEventListener('mousemove', function (e) {
            var rect = svg.getBoundingClientRect();
            var scale = 700 / rect.width;
            var sx = rect.left + (p.x / 700) * rect.width;
            var html = '<div class="tt-date">' + p.b.d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + '</div>' +
              '<div class="tt-row"><span><span class="sw" style="background:#62829b;"></span>Quotations</span><b>' + fmtInt(p.b.q) + '</b></div>' +
              '<div class="tt-row"><span><span class="sw" style="background:#a57f43;"></span>Product updates</span><b>' + fmtInt(p.b.p) + '</b></div>' +
              '<div class="tt-row"><span><span class="sw" style="background:#63856c;"></span>Sign-ups</span><b>' + fmtInt(p.b.s) + '</b></div>';
            showTip(html, sx, rect.top + 20);
          });
          hit.addEventListener('mouseleave', hideTip);
        });
      }

      /* ---------------- RANGE SWITCH ---------------- */
      function setRange(days) {
        currentRange = days;
        document.querySelectorAll('#rangeSeg button').forEach(function (b) {
          b.classList.toggle('active', parseInt(b.dataset.range) === days);
        });
        series = buildSeries(days);
        document.getElementById('areaRangeLabel').textContent = 'Last ' + days + ' days';
        updateKpis();
        renderArea();
        renderDonut();
        renderBars();
      }

      document.querySelectorAll('#rangeSeg button').forEach(function (b) {
        b.addEventListener('click', function () { setRange(parseInt(b.dataset.range)); });
      });

      /* ---------------- EXPORT ---------------- */
      document.getElementById('exportBtn').addEventListener('click', function () {
        var lines = ['Date,Quotations,Revenue (PHP),Product Updates,Sign-ups'];
        series.forEach(function (p) {
          var d = p.d.toISOString().slice(0, 10);
          lines.push([d, p.q, p.r, p.p, p.s].join(','));
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'aquilala-analytics-' + currentRange + 'd.csv';
        a.click();
        URL.revokeObjectURL(url);
      });

      /* ---------------- INIT ---------------- */
      updateKpis();
      renderArea();
      renderDonut();
      renderBars();

      // DSS + mini bar reveals
      document.addEventListener('DOMContentLoaded', function () {
        var revealEls = document.querySelectorAll('[data-reveal]');
        var io = 'IntersectionObserver' in window
          ? new IntersectionObserver(function (entries) {
              entries.forEach(function (en) {
                if (en.isIntersecting) {
                  en.target.classList.add('in');
                  io.unobserve(en.target);
                }
              });
            }, { threshold: 0.08 })
          : null;
        revealEls.forEach(function (el) { io ? io.observe(el) : el.classList.add('in'); });
      });

      // animate fills when visible
      function animateFills() {
        document.querySelectorAll('.dss-fill, .mini-fill').forEach(function (el) {
          el.style.width = (el.dataset.w || '0') + '%';
        });
      }
      var fillTimer = setTimeout(animateFills, 700);
      window.addEventListener('load', function () { clearTimeout(fillTimer); animateFills(); });
    })();
  </script>
@endsection
