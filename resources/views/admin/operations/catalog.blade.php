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

    .cat-grid { margin-bottom: 22px; }
    .cat-panel {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;
      box-shadow: 0 1px 3px rgba(15,23,42,0.04); overflow: hidden;
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

    .cat-table-panel { margin-bottom: 22px; }
    .cat-table-scroll { overflow-x: auto; }
    .cat-table-note { font-size: 12px; color: #64748b; margin: 4px 0 0; }
    .cat-table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .cat-table .cell-strong { color: #1e2922; font-weight: 600; }
    .cat-table .cell-muted { color: #64748b; }
    .cat-status {
      display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 6px;
      font-size: 12px; font-weight: 600; text-transform: capitalize;
    }
    .cat-status-active { background: #edf1e9; color: #3f5a46; }
    .cat-status-inactive { background: #f1f5f9; color: #64748b; }
    .cat-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #94a3b8; font-size: 13px; text-align: center; }

    @media (max-width: 480px) {
      .cat-kpis { grid-template-columns: 1fr; }
    }
  </style>

  <div class="cat-page">
    <div class="cat-content">
      <div class="cat-head">
        <h1>Product Catalog</h1>
        <p>Monitor the supply catalog and store inventory.</p>
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
                @php $pct = round(($cat->count / $catMax) * 100); @endphp
                <div class="cat-row">
                  <span class="lbl">{{ $cat->category }}</span>
                  <div class="cat-track"><div class="cat-fill" style="width: {{ $pct }}%;"></div></div>
                  <span style="font-size:13px;font-weight:800;color:#1e2922;width:34px;text-align:right;">{{ $cat->count }}</span>
                </div>
              @endforeach
            @endif
          </div>
        </div>
      </div>

      <div class="cat-panel cat-table-panel">
        <div class="panel-head">
          <h3>Product Inventory</h3>
          <p class="cat-table-note">View-only inventory monitoring. Stores manage their own products and stock.</p>
        </div>
        <div class="cat-table-scroll">
          <table class="data-table cat-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Store</th>
                <th>Category</th>
                <th class="num">Price</th>
                <th class="num">Stock</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($products as $product)
                @php
                  $storeName = $product->storeOwner?->store_name
                      ?: trim(($product->storeOwner?->first_name ?? '') . ' ' . ($product->storeOwner?->last_name ?? ''));
                  $stock = (int) ($product->stock_quantity ?? 0);
                @endphp
                <tr>
                  <td class="cell-strong">{{ $product->name }}</td>
                  <td class="cell-muted">{{ $storeName ?: 'Unknown store' }}</td>
                  <td class="cell-muted">{{ $product->category?->name ?? $product->category ?? 'Uncategorized' }}</td>
                  <td class="num">₱{{ number_format((float) $product->price, 2) }}</td>
                  <td class="num">{{ number_format($stock) }} {{ $product->stock_unit ?: 'units' }}</td>
                  <td>
                    <span class="cat-status {{ $product->status === 'active' ? 'cat-status-active' : 'cat-status-inactive' }}">
                      {{ str_replace('_', ' ', $product->status ?: 'unknown') }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6"><div class="cat-empty">No catalog data yet.</div></td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
