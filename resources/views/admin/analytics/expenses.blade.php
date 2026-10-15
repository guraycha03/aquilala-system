@extends('layouts.sidebar')

@section('title', 'Expenses')

@section('content')

  <style>
    .exp-page { position: relative; }
    .exp-page::before,
    .exp-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .exp-page::before { width: 340px; height: 340px; background: rgba(239,68,68,0.10); top: -80px; right: -80px; }
    .exp-page::after { width: 300px; height: 300px; background: rgba(16,185,129,0.14); bottom: -60px; left: -60px; }
    .exp-content { position: relative; z-index: 1; }

    .exp-head { margin: 0 0 10px; }
    .exp-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .exp-head p { font-size: 13px; color: #64748b; margin: 0; }

    .exp-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .exp-kpi {
      padding: 18px 20px; border-radius: 16px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .exp-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .exp-kpi .kpi-value { font-size: 22px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1; }
    .exp-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }
    .exp-kpi.variance .kpi-value.pos { color: #16a34a; }
    .exp-kpi.variance .kpi-value.neg { color: #dc2626; }

    .exp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; }
    .exp-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      overflow: hidden;
    }
    .exp-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .exp-panel .panel-head h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .exp-panel .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
    .exp-table td.num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
    .exp-table td.crop { font-weight: 600; color: #1e293b; text-transform: capitalize; }
    .diff-ok { color: #16a34a; }
    .diff-over { color: #dc2626; }

    .exp-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 48px 20px; color: #94a3b8; font-size: 13px; text-align: center; }
    .exp-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 780px) {
      .exp-table th, .exp-table td { padding: 10px 12px; }
    }
  </style>

  @php
    $money = fn ($n) => '₱' . number_format((float) $n, 2);
  @endphp

  <div class="exp-page">
    <div class="exp-content">

      <div class="exp-head">
        <h1>Expense Analysis</h1>
        <p>Planned budget versus actual farm expenditures across harvest reports.</p>
      </div>


      <div class="exp-kpis">
        <div class="exp-kpi">
          <div class="kpi-label">Planned Budget</div>
          <div class="kpi-value">{{ $money($totals['planned']) }}</div>
          <div class="kpi-sub">Estimated cost per quotations</div>
        </div>
        <div class="exp-kpi">
          <div class="kpi-label">Actual Spend</div>
          <div class="kpi-value">{{ $money($totals['actual']) }}</div>
          <div class="kpi-sub">Reported at harvest time</div>
        </div>
        <div class="exp-kpi variance">
          <div class="kpi-label">Variance</div>
          <div class="kpi-value {{ $totals['variance'] <= 0 ? 'pos' : 'neg' }}">
            {{ ($totals['variance'] <= 0 ? '' : '+') . $money($totals['variance']) }}
          </div>
          <div class="kpi-sub">{{ $totals['variance'] <= 0 ? 'Under budget' : 'Over budget' }}</div>
        </div>
        <div class="exp-kpi">
          <div class="kpi-label">Harvest Records</div>
          <div class="kpi-value">{{ $totals['count'] }}</div>
          <div class="kpi-sub">{{ number_format((float) $totals['yield'], 1) }} t total yield</div>
        </div>
      </div>

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
              <p>Budget vs actual across crop types</p>
            </div>
            <x-table-toolbar
                uid="exp-crops"
                work-table="expCropTable"
                per-page="10"
                noun="crops"
                :search="false"
              />
            <table class="exp-table data-table" id="expCropTable">
              <thead>
                <tr>
                  <th>Crop</th>
                  <th class="num">Records</th>
                  <th class="num">Planned</th>
                  <th class="num">Actual</th>
                  <th class="num">Variance</th>
                </tr>
              </thead>
              <tbody>
                @foreach($byCrop as $row)
                  @php $diff = (float) $row['actual'] - (float) $row['planned']; @endphp
                  <tr>
                    <td class="crop">{{ $row['crop'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">{{ $money($row['planned']) }}</td>
                    <td class="num">{{ $money($row['actual']) }}</td>
                    <td class="num {{ $diff <= 0 ? 'diff-ok' : 'diff-over' }}">{{ ($diff <= 0 ? '' : '+') . $money($diff) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="exp-panel">
            <div class="panel-head">
              <h3>By Season</h3>
              <p>Budget vs actual per cropping season</p>
            </div>
            {{-- By Season can never exceed two rows (wet/dry) — no pagination needed. --}}
            <table class="exp-table data-table">
              <thead>
                <tr>
                  <th>Season</th>
                  <th class="num">Records</th>
                  <th class="num">Planned</th>
                  <th class="num">Actual</th>
                  <th class="num">Variance</th>
                </tr>
              </thead>
              <tbody>
                @foreach($bySeason as $row)
                  @php $diff = (float) $row['actual'] - (float) $row['planned']; @endphp
                  <tr>
                    <td class="crop">{{ $row['season'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">{{ $money($row['planned']) }}</td>
                    <td class="num">{{ $money($row['actual']) }}</td>
                    <td class="num {{ $diff <= 0 ? 'diff-ok' : 'diff-over' }}">{{ ($diff <= 0 ? '' : '+') . $money($diff) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

    </div>
  </div>
@endsection