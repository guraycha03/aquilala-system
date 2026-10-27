@extends('layouts.sidebar')

@section('title', 'Farming Cost Reports')

@section('content')

  <style>
    .exp-page { position: relative; }
    .exp-page::before,
    .exp-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .exp-page::before { width: 340px; height: 340px; background: rgba(239,68,68,0.08); top: -80px; right: -80px; }
    .exp-page::after { width: 300px; height: 300px; background: rgba(94,144,112,0.12); bottom: -60px; left: -60px; }
    .exp-content { position: relative; z-index: 1; }

    .exp-head { margin: 0 0 14px; }
    .exp-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .exp-head p { font-size: 13px; color: #64748b; margin: 0; }

    /* ── KPI row ── */
    .exp-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 18px; }
    .exp-kpi {
      padding: 16px 18px; border-radius: 16px; background: rgba(255,255,255,0.88);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
      min-width: 0;
    }
    .exp-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .exp-kpi .kpi-value { font-size: 21px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1.1; overflow-wrap: anywhere; }
    .exp-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }
    .exp-kpi.variance .kpi-value.pos { color: #4A7A54; }
    .exp-kpi.variance .kpi-value.neg { color: #dc2626; }

    /* ── Note: where the estimate comes from ── */
    .exp-note {
      display: flex; gap: 14px; align-items: flex-start;
      padding: 15px 18px; margin-bottom: 18px;
      background: #f4f8f5; border: 1px solid #dfeade; border-radius: 14px;
    }
    .exp-note svg { width: 20px; height: 20px; flex: none; margin-top: 1px; stroke: #4A7A54; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .exp-note strong { display: block; font-size: 13px; color: #1e293b; margin-bottom: 3px; }
    .exp-note p { margin: 0; font-size: 12.5px; line-height: 1.6; color: #52606d; }
    .exp-note--compact p { margin: 0; font-size: 12.5px; color: #52606d; }

    /* ── Breakdown panels ── */
    /* min() keeps the floor from overflowing narrow screens; 560px is the
       width at which every breakdown (6 columns worst case) fits without
       clipping, so side-by-side only happens when both panels truly fit. */
    .exp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(560px, 100%), 1fr)); gap: 18px; align-items: start; }
    .exp-panel {
      min-width: 0;
      background: rgba(255,255,255,0.88); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      overflow: hidden;
    }
    .exp-panel--wide { grid-column: 1 / -1; }
    .exp-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .exp-panel .panel-head h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .exp-panel .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }

    /* Wide tables scroll inside their panel instead of stretching the page.
       The class also tells the table-toolbar engine that the PANEL (not the
       scroll box) owns the shared pagination bar. */
    .exp-panel .table-scroll { overflow-x: auto; }

    /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
    .exp-table td.num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
    .exp-table td.crop { font-weight: 600; color: #1e293b; text-transform: capitalize; }
    .diff-ok { color: #4A7A54; }
    .diff-over { color: #dc2626; }

    /* Share cell: percentage with a slim bar under it. */
    .exp-table td.share .share-num { display: block; }
    .exp-table td.share .share-bar { display: block; height: 4px; margin-top: 4px; background: #edf2ee; border-radius: 2px; overflow: hidden; }
    .exp-table td.share .share-bar i { display: block; height: 100%; background: #4A7A54; border-radius: 2px; }

    .exp-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 48px 20px; color: #94a3b8; font-size: 13px; text-align: center; }
    .exp-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 780px) {
      .exp-table th, .exp-table td { padding: 10px 12px; }
      .exp-grid { grid-template-columns: minmax(0, 1fr); }
    }
  </style>

  @php
    $money = fn ($n) => '₱' . number_format((float) $n, 2);
    // Share of the whole spend this row accounts for.
    $share = fn ($actual) => $totals['actual'] > 0
        ? round(((float) $actual) / ((float) $totals['actual']) * 100, 1)
        : 0.0;
  @endphp

  <div class="exp-page">
    <div class="exp-content">

      <div class="exp-head">
        <h1>Farming Cost Reports</h1>
        <p>What farming actually cost the association — by crop, season and harvest year.</p>
      </div>

      <div class="exp-kpis kpi-strip">
        @if($hasBudget)
          <div class="exp-kpi">
            <div class="kpi-label">Planned Budget</div>
            <div class="kpi-value">{{ $money($totals['planned']) }}</div>
            <div class="kpi-sub">Quoted input cost linked to reports</div>
          </div>
        @endif
        <div class="exp-kpi">
          <div class="kpi-label">Actual Spend</div>
          <div class="kpi-value">{{ $money($totals['actual']) }}</div>
          <div class="kpi-sub">Reported at harvest time</div>
        </div>
        @if($hasBudget)
          <div class="exp-kpi variance">
            <div class="kpi-label">Variance</div>
            <div class="kpi-value {{ $totals['variance'] <= 0 ? 'pos' : 'neg' }}">
              {{ ($totals['variance'] <= 0 ? '' : '+') . $money($totals['variance']) }}
            </div>
            <div class="kpi-sub">{{ $totals['variance'] <= 0 ? 'Under budget' : 'Over budget' }}</div>
          </div>
        @endif
        <div class="exp-kpi">
          <div class="kpi-label">Cost per Hectare</div>
          <div class="kpi-value">{{ $totals['area'] > 0 ? $money($totals['actual'] / $totals['area']) : '—' }}</div>
          <div class="kpi-sub">across {{ number_format((float) $totals['area'], 1) }} ha recorded</div>
        </div>
        <div class="exp-kpi">
          <div class="kpi-label">Cost per MT</div>
          <div class="kpi-value">{{ $totals['yield'] > 0 ? $money($totals['actual'] / $totals['yield']) : '—' }}</div>
          <div class="kpi-sub">per metric ton harvested</div>
        </div>
        <div class="exp-kpi">
          <div class="kpi-label">Harvest Records</div>
          <div class="kpi-value">{{ $totals['count'] }}</div>
          <div class="kpi-sub">{{ number_format((float) $totals['yield'], 1) }} t total yield</div>
        </div>
      </div>

      @if(! $hasBudget && $totals['count'] > 0)
        {{-- Nothing has been quoted yet, so there is no budget to show — say
             where the estimate WOULD come from instead of painting ₱0
             planned and the whole spend as "over budget". --}}
        <div class="exp-note">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <div>
            <strong>Where the estimate comes from</strong>
            <p>
              A <b>planned budget</b> on this page is the total of a store <b>quotation</b> — the priced list of
              inputs (fertilizer, seed, chemicals) built for a crop and season — read from the quotation linked
              to a harvest report. Imported history and the manual report form record only what was
              <b>actually spent</b>, and no report carries a linked quotation yet, so there is nothing to
              compare against: this page shows actual spending only. Budget and variance columns appear
              as soon as reports carry a linked quotation.
            </p>
          </div>
        </div>
      @elseif($hasBudget)
        <div class="exp-note exp-note--compact">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <p>Planned figures come from the store quotations linked to each report.</p>
        </div>
      @endif

      @if($totals['count'] === 0)
        <div class="exp-panel">
          <div class="exp-empty">
            <svg viewBox="0 0 24 24"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
            <span>No harvest reports recorded yet.</span>
          </div>
        </div>
      @else
        <div class="exp-grid">

          <div class="exp-panel">
            <div class="panel-head">
              <h3>By Crop</h3>
              <p>Actual spend per crop — largest first</p>
            </div>
            <x-table-toolbar
                uid="exp-crops"
                work-table="expCropTable"
                per-page="10"
                noun="crops"
                :search="false"
              />
            <div class="table-scroll">
              <table class="exp-table data-table" id="expCropTable">
                <thead>
                  <tr>
                    <th>Crop</th>
                    <th class="num">Records</th>
                    <th class="num">Actual Spend</th>
                    <th class="num">Share</th>
                    <th class="num">₱ / ha</th>
                    @if($hasBudget)
                      <th class="num">Planned</th>
                      <th class="num">Variance</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($byCrop as $row)
                    @php
                      $pct = $share($row['actual']);
                      $diff = (float) $row['actual'] - (float) $row['planned'];
                    @endphp
                    <tr>
                      <td class="crop">{{ $row['crop'] }}</td>
                      <td class="num">{{ $row['count'] }}</td>
                      <td class="num">{{ $money($row['actual']) }}</td>
                      <td class="num share">
                        <span class="share-num">{{ $pct }}%</span>
                        <span class="share-bar"><i style="width: {{ max((float) $pct, 1) }}%"></i></span>
                      </td>
                      <td class="num">{{ $row['area'] > 0 ? $money($row['actual'] / $row['area']) : '—' }}</td>
                      @if($hasBudget)
                        <td class="num">{{ $money($row['planned']) }}</td>
                        <td class="num {{ $diff <= 0 ? 'diff-ok' : 'diff-over' }}">{{ ($diff <= 0 ? '' : '+') . $money($diff) }}</td>
                      @endif
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="exp-panel">
            <div class="panel-head">
              <h3>By Season</h3>
              <p>Wet vs dry cropping season</p>
            </div>
            <div class="table-scroll">
              <table class="exp-table data-table">
                <thead>
                  <tr>
                    <th>Season</th>
                    <th class="num">Records</th>
                    <th class="num">Actual Spend</th>
                    <th class="num">Share</th>
                    <th class="num">Yield (MT)</th>
                    @if($hasBudget)
                      <th class="num">Planned</th>
                      <th class="num">Variance</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($bySeason as $row)
                    @php
                      $pct = $share($row['actual']);
                      $diff = (float) $row['actual'] - (float) $row['planned'];
                    @endphp
                    <tr>
                      <td class="crop">{{ $row['season'] }}</td>
                      <td class="num">{{ $row['count'] }}</td>
                      <td class="num">{{ $money($row['actual']) }}</td>
                      <td class="num share">
                        <span class="share-num">{{ $pct }}%</span>
                        <span class="share-bar"><i style="width: {{ max((float) $pct, 1) }}%"></i></span>
                      </td>
                      <td class="num">{{ number_format((float) $row['yield'], 2) }}</td>
                      @if($hasBudget)
                        <td class="num">{{ $money($row['planned']) }}</td>
                        <td class="num {{ $diff <= 0 ? 'diff-ok' : 'diff-over' }}">{{ ($diff <= 0 ? '' : '+') . $money($diff) }}</td>
                      @endif
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="exp-panel exp-panel--wide">
            <div class="panel-head">
              <h3>By Year</h3>
              <p>Every harvest year on record — imported history, oldest first</p>
            </div>
            <div class="table-scroll">
              <table class="exp-table data-table">
                <thead>
                  <tr>
                    <th>Year</th>
                    <th class="num">Records</th>
                    <th class="num">Actual Spend</th>
                    <th class="num">Yield (MT)</th>
                    <th class="num">₱ / MT</th>
                    @if($hasBudget)
                      <th class="num">Planned</th>
                      <th class="num">Variance</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($byYear as $row)
                    @php
                      $diff = (float) $row['actual'] - (float) $row['planned'];
                    @endphp
                    <tr>
                      <td class="crop">{{ $row['year'] }}</td>
                      <td class="num">{{ $row['count'] }}</td>
                      <td class="num">{{ $money($row['actual']) }}</td>
                      <td class="num">{{ number_format((float) $row['yield'], 2) }}</td>
                      <td class="num">{{ $row['yield'] > 0 ? $money($row['actual'] / $row['yield']) : '—' }}</td>
                      @if($hasBudget)
                        <td class="num">{{ $money($row['planned']) }}</td>
                        <td class="num {{ $diff <= 0 ? 'diff-ok' : 'diff-over' }}">{{ ($diff <= 0 ? '' : '+') . $money($diff) }}</td>
                      @endif
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

        </div>
      @endif

    </div>
  </div>
@endsection
