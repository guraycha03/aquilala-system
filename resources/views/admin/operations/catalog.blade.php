@extends('layouts.sidebar')

@section('title', 'Product Catalog')

@section('content')

  <style>
    .cat-page { position: relative; }
    .cat-content { position: relative; z-index: 1; }

    .cat-head { margin: 0 0 10px; }
    .cat-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .cat-head p { font-size: 13px; color: #64748b; margin: 0; }

    .cat-kpis { display: grid; grid-template-columns: repeat(2, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; max-width: 560px; }
    .cat-kpi {
      padding: 16px 18px; border-radius: 10px; background: #ffffff;
      border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(15,23,42,0.04);
    }
    .cat-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .cat-kpi .kpi-value { font-size: 24px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1; }
    .cat-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }

    .cat-grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; margin-bottom: 22px; }
    .cat-panel {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;
      box-shadow: 0 1px 3px rgba(15,23,42,0.04);
      overflow: hidden;
    }
    .cat-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .cat-panel .panel-head h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .cat-panel .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    .cat-panel .panel-body { padding: 16px 20px; }

    .cat-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; }
    .cat-row + .cat-row { border-top: 1px outset rgba(0,0,0,0.04); }
    .cat-row .lbl { font-size: 13px; color: #475569; font-weight: 600; }
    .cat-track { flex: 1; max-width: 130px; height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
    .cat-fill { height: 100%; border-radius: 99px; background: #648268; }

    .quote-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
    .quote-item + .quote-item { border-top: 1px solid #f1f5f9; }
    .quote-rank {
      width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      background: #edf1e9; color: #648268; font-size: 12px; font-weight: 800;
    }
    .quote-name { flex: 1; min-width: 0; font-size: 13px; color: #1e293b; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .quote-count {
      display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
      font-size: 12px; font-weight: 700; color: #3f5a46;
      background: #f1f5f2; border: 1px solid #d5e0d8;
      padding: 4px 10px; border-radius: 999px;
    }

    .cat-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #94a3b8; font-size: 13px; text-align: center; }
    .cat-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 920px) {
      .cat-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
      .cat-kpis { grid-template-columns: 1fr; }
    }
  </style>

  <div class="cat-page">
    <div class="cat-content">

      <div class="cat-head">
        <h1>Product Catalog</h1>
        <p>Overview of the supply catalog and what farmers are asking for.</p>
      </div>


      <div class="cat-kpis kpi-strip">
        <div class="cat-kpi">
          <div class="kpi-label">Total Products</div>
          <div class="kpi-value">{{ $productStats['total'] }}</div>
          <div class="kpi-sub">Registered supply items</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Quotations This Month</div>
          <div class="kpi-value">{{ $productStats['quotations_this_month'] }}</div>
          <div class="kpi-sub">Created since {{ now()->startOfMonth()->format('M 1') }}</div>
        </div>
      </div>

      <div class="cat-grid">
        <div class="cat-panel">
          <div class="panel-head">
            <h3>Products by Category</h3>
            <p>Distribution of listed supply items</p>
          </div>
          <div class="panel-body">
            @if($productsByCategory->isEmpty())
              <div class="cat-empty">No catalog data yet.</div>
            @else
              @php $catMax = max($productsByCategory->max('count'), 1) ?: 1; @endphp
              @foreach($productsByCategory as $cat)
                @php $pct = $catMax > 0 ? round(($cat->count / $catMax) * 100) : 0; @endphp
                <div class="cat-row">
                  <span class="lbl">{{ $cat->category }}</span>
                  <div class="cat-track"><div class="cat-fill" style="width: {{ $pct }}%;"></div></div>
                  <span style="font-size:13px;font-weight:800;color:#1e2922;width:34px;text-align:right;">{{ $cat->count }}</span>
                </div>
              @endforeach
            @endif
          </div>
        </div>

        <div class="cat-panel">
          <div class="panel-head">
            <h3>What Farmers Ask For</h3>
            <p>Products ranked by how many farmer quotations include them</p>
          </div>
          <div class="panel-body">
            @if($topQuoted->isEmpty())
              <div class="cat-empty">No quotations generated yet. Products farmers request will appear here.</div>
            @else
              @foreach($topQuoted as $idx => $row)
                <div class="quote-item">
                  <span class="quote-rank">#{{ $idx + 1 }}</span>
                  <span class="quote-name">{{ $row->name }}</span>
                  <span class="quote-count">
                    <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    {{ $row->quote_count }} {{ Str::plural('quote', $row->quote_count) }}
                  </span>
                </div>
              @endforeach
            @endif
          </div>
        </div>
      </div>

    </div>
  </div>
@endsection
