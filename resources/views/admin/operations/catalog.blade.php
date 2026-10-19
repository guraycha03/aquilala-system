@extends('layouts.sidebar')

@section('title', 'Product Catalog')

@section('content')

  <style>
    .cat-page { position: relative; }
    .cat-page::before,
    .cat-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .cat-page::before { width: 340px; height: 340px; background: rgba(147,51,234,0.12); top: -80px; right: -80px; }
    .cat-page::after { width: 300px; height: 300px; background: rgba(16,185,129,0.14); bottom: -60px; left: -60px; }
    .cat-content { position: relative; z-index: 1; }

    .cat-head { margin: 0 0 10px; }
    .cat-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .cat-head p { font-size: 13px; color: #64748b; margin: 0; }

    .cat-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .cat-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .cat-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .cat-kpi .kpi-value { font-size: 24px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1; }
    .cat-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }

    .cat-grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; margin-bottom: 22px; }
    .cat-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
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
    .cat-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #86efac, #16a34a); }

    .quote-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
    .quote-item + .quote-item { border-top: 1px solid #f1f5f9; }
    .quote-rank {
      width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      background: #f0fdf4; color: #16a34a; font-size: 12px; font-weight: 800;
    }
    .quote-name { flex: 1; min-width: 0; font-size: 13px; color: #1e293b; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .quote-count {
      display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
      font-size: 12px; font-weight: 700; color: #16a34a;
      background: #f0fdf4; border: 1px solid #bbf7d0;
      padding: 4px 10px; border-radius: 999px;
    }

    .cat-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #94a3b8; font-size: 13px; text-align: center; }
    .cat-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 920px) {
      .cat-grid { grid-template-columns: 1fr; }
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
          <div class="kpi-label">Active Listings</div>
          <div class="kpi-value">{{ $productStats['active'] }}</div>
          <div class="kpi-sub">Visible to farmers</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Out of Stock</div>
          <div class="kpi-value">{{ $productStats['out'] }}</div>
          <div class="kpi-sub">Low / zero inventory</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Most Quoted</div>
          <div class="kpi-value">{{ $topQuoted->first()->quote_count ?? 0 }}</div>
          <div class="kpi-sub">Most farmer quotations</div>
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
