@extends('layouts.sidebar')

@section('title', 'Inventory Management')

@section('content')
<style>
  .inv-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
  }
  .inv-header h1 {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
  }
  .inv-header p { color: #64748b; font-size: 14px; margin: 0; }
  .btn-add {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, #14532d, #166534);
    color: #fff; padding: 11px 20px; border-radius: 10px;
    font-size: 14px; font-weight: 600; text-decoration: none;
    box-shadow: 0 4px 14px rgba(20,83,45,0.25); transition: all .2s; border: none; cursor: pointer;
  }
  .btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(20,83,45,0.3); }
  .btn-add svg { width: 16px; height: 16px; stroke: currentColor; }

  .alert-success {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: 14px 18px; margin-bottom: 20px; color: #15803d;
    font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;
  }

  /* Metrics */
  .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
  .metric-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;
    display: flex; flex-direction: column; position: relative; overflow: hidden;
    transition: all .2s ease;
  }
  .metric-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.05); transform: translateY(-2px); }
  .metric-top { display: flex; align-items: center; gap: 12px; }
  .metric-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .metric-icon svg { width: 20px; height: 20px; stroke-width: 2; fill: none; }
  .metric-label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
  .metric-value { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1; }
  .metric-foot { margin-top: 12px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }

  /* Dashboard body */
  .health-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; align-items: stretch; }
  @media (max-width: 1024px) { .health-grid { grid-template-columns: 1fr; } }
  .bar-chart { position: relative; height: 250px; width: 100%; }

  .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
  .panel-head {
    padding: 16px 18px; border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;
  }
  .panel-head h2 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
  .panel-head .sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }

  /* Chart */
  .chart-wrap { padding: 18px; display: flex; flex-direction: column; align-items: center; }
  .donut-container { position: relative; width: 200px; height: 200px; }
  .donut-center {
    position: absolute; inset: 0; display: flex; flex-direction: column;
    align-items: center; justify-content: center; pointer-events: none;
  }
  .donut-center .big { font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1; }
  .donut-center .lbl { font-size: 11px; color: #94a3b8; margin-top: 4px; text-align: center; padding: 0 8px; }

  .health-legend { width: 100%; margin-top: 16px; display: flex; flex-direction: column; gap: 8px; }
  .legend-item { display: flex; align-items: center; gap: 10px; font-size: 13px; }
  .legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
  .legend-item .lname { color: #475569; font-weight: 500; flex: 1; }
  .legend-item .lnum { font-weight: 700; color: #0f172a; }

  /* Tools */
  .tools { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .search-box { position: relative; flex: 1; min-width: 180px; }
  .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; stroke: #94a3b8; }
  .search-box input {
    width: 100%; padding: 10px 12px 10px 36px; border: 1px solid #cbd5e1; border-radius: 9px;
    font-size: 14px; color: #1e293b; outline: none; transition: all .2s;
  }
  .search-box input:focus { border-color: #14532d; box-shadow: 0 0 0 3px rgba(20,83,45,.1); }
  .filter-select {
    padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 9px; background: #fff;
    font-size: 14px; color: #334155; font-weight: 500; outline: none; cursor: pointer;
  }
  .filter-select:focus { border-color: #14532d; }

  /* Table */
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th {
    background: #f8fafc; padding: 12px 16px; font-weight: 600; color: #475569;
    border-bottom: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; text-align: left; white-space: nowrap;
  }
  td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tbody tr { transition: background .15s; }
  tbody tr:hover { background: #f8fafc; }

  .prod-cell { display: flex; align-items: center; gap: 12px; min-width: 220px; }
  .prod-thumb {
    width: 44px; height: 44px; border-radius: 10px; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; border: 1px solid #e2e8f0;
  }
  .prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .prod-thumb svg { width: 20px; height: 20px; stroke: #94a3b8; }
  .prod-name { font-weight: 600; color: #0f172a; line-height: 1.2; }
  .prod-sku { font-size: 12px; color: #94a3b8; font-family: monospace; margin-top: 2px; }
  .cat-pill { display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #0f766e; background: #ccfbf1; padding: 2px 7px; border-radius: 5px; letter-spacing: .03em; }

  .stock-bar { width: 110px; height: 8px; background: #eef2f1; border-radius: 6px; overflow: hidden; }
  .stock-bar > div { height: 100%; border-radius: 6px; }

  .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
  .badge-ok { background: #f0fdf4; color: #16a34a; }
  .badge-low { background: #fffbeb; color: #d97706; }
  .badge-out { background: #fef2f2; color: #dc2626; }
  .badge-disc { background: #f1f5f9; color: #64748b; }

  .row-actions { display: flex; gap: 6px; align-items: center; }
  .btn-icon {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    padding: 7px 11px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: pointer; text-decoration: none; transition: all .15s;
  }
  .btn-icon svg { width: 14px; height: 14px; stroke: currentColor; }
  .btn-icon:hover { background: #f1f5f9; }
  .btn-icon.primary { background: #14532d; color: #fff; border-color: #14532d; }
  .btn-icon.primary:hover { background: #166534; }

  .empty-state { text-align: center; padding: 60px 20px; color: #87a992; }
  .empty-state h3 { font-size: 18px; color: #4a574e; margin-bottom: 8px; }
  .empty-state p { font-size: 14px; line-height: 1.6; }

  /* Inline edit modal/drawer */
  .modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,.15);
    z-index: 200; align-items: center; justify-content: center; padding: 20px;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #fff; border-radius: 16px; width: 100%; max-width: 420px;
    box-shadow: 0 20px 40px rgba(0,0,0,.2); overflow: hidden; animation: pop .2s ease;
  }
  @keyframes pop { from { transform: scale(.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
  .modal-head { padding: 18px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
  .modal-head h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
  .modal-close { background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; line-height: 1; }
  .modal-body { padding: 20px; }
  .field { margin-bottom: 16px; }
  .field label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em; }
  .field input {
    width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 9px;
    font-size: 14px; color: #1e293b; outline: none; transition: all .2s;
  }
  .field input:focus { border-color: #14532d; box-shadow: 0 0 0 3px rgba(20,83,45,.1); }
  .field .hint { font-size: 11px; color: #94a3b8; margin-top: 5px; }
  .modal-foot { padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
  .btn { padding: 10px 18px; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .2s; }
  .btn-ghost { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }
  .btn-ghost:hover { background: #e2e8f0; }
  .btn-save { background: #14532d; color: #fff; }
  .btn-save:hover { background: #166534; }

  .panel-foot { padding: 12px 18px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; }
  .btn-link-ghost {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #14532d; background: #f0fdf4; border: 1px solid #bbf7d0; cursor: pointer; transition: all .15s;
  }
  .btn-link-ghost:hover { background: #dcfce7; }
  .btn-link-ghost svg { width: 14px; height: 14px; stroke: currentColor; }

  /* Floating full-breakdown modal (no overlay) */
  .info-modal {
    position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%) scale(.96);
    width: calc(100% - 32px); max-width: 760px; max-height: 82vh;
    background: #fff; border-radius: 16px; box-shadow: 0 28px 70px rgba(15,23,42,.32);
    z-index: 300; display: flex; flex-direction: column; overflow: hidden;
    visibility: hidden; opacity: 0; transition: all .2s ease;
  }
  .info-modal.open { visibility: visible; opacity: 1; transform: translate(-50%, -50%) scale(1); }
  .info-modal-head {
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
  }
  .info-modal-head h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
  .info-modal-head .sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }
  .info-modal-body { overflow-y: auto; padding: 6px 0; }
  .info-modal-body table th { position: sticky; top: 0; z-index: 1; }

  @media (max-width: 640px) {
    .metrics-grid { grid-template-columns: repeat(2, 1fr); }
    .inv-header h1 { font-size: 20px; }
  }
</style>

<div class="inv-header">
  <div>
    <h1>Crop Input Stock Management</h1>
    <p>Monitor warehouse volumes, adjust alert boundaries, and track item statuses in real time.</p>
  </div>
  <a href="{{ route('store.products.create') }}" class="btn-add">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Register Stock Batch
  </a>
</div>

@if (session('success'))
<div class="alert-success">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  {{ session('success') }}
</div>
@endif

<!-- Metrics -->
<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-icon" style="background:#e0f2fe;">
        <svg viewBox="0 0 24 24" stroke="#0369a1"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      </div>
      <div>
        <div class="metric-label">Monitored Items</div>
        <div class="metric-value">{{ $totalItems }}</div>
      </div>
    </div>
    <div class="metric-foot">
      <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
      {{ $healthyCount }} healthy
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-icon" style="background:#fffbeb;">
        <svg viewBox="0 0 24 24" stroke="#d97706"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div>
        <div class="metric-label">Low Stock Flags</div>
        <div class="metric-value">{{ $lowStockCount }}</div>
      </div>
    </div>
    <div class="metric-foot">Items at or below reorder threshold</div>
  </div>

  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-icon" style="background:#fef2f2;">
        <svg viewBox="0 0 24 24" stroke="#dc2626"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <div>
        <div class="metric-label">Out of Stock</div>
        <div class="metric-value">{{ $outOfStockCount }}</div>
      </div>
    </div>
    <div class="metric-foot">Unavailable for purchase</div>
  </div>

  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-icon" style="background:#f3e8ff;">
        <svg viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      </div>
      <div>
        <div class="metric-label">Total Units in Stock</div>
        <div class="metric-value">{{ number_format($totalUnits) }}</div>
      </div>
    </div>
    <div class="metric-foot">Combined stock across all products</div>
  </div>
</div>

<!-- Stock health visuals -->
<div class="health-grid">
  <!-- Donut: health breakdown -->
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>Stock Health</h2>
        <div class="sub">Live breakdown of your inventory</div>
      </div>
    </div>
    <div class="chart-wrap">
      <div class="donut-container">
        <canvas id="stockHealthChart"></canvas>
        <div class="donut-center">
          <div class="big">{{ $totalItems }}</div>
          <div class="lbl">Total Items</div>
        </div>
      </div>
      <div class="health-legend" id="healthLegend">
        <div class="legend-item">
          <span class="legend-dot" style="background:#16a34a;"></span>
          <span class="lname">Healthy Stock</span>
          <span class="lnum" id="legendHealthy">{{ $healthyCount }}</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot" style="background:#f59e0b;"></span>
          <span class="lname">Low Stock</span>
          <span class="lnum" id="legendLow">{{ $lowStockCount }}</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot" style="background:#dc2626;"></span>
          <span class="lname">Out of Stock</span>
          <span class="lnum" id="legendOut">{{ $outOfStockCount }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Horizontal bar: top stock vs minimum limits by product -->
  @php
    $chartProducts = $products->sortByDesc(fn ($p) => (int) ($p->stock_quantity ?? 0))->take(5)->values();
    $barLabels = $chartProducts->pluck('name')->toArray();
    $barQtys = $chartProducts->map(fn ($p) => (int) ($p->stock_quantity ?? 0))->toArray();
    $barThresholds = $chartProducts->map(fn ($p) => (int) ($p->reorder_threshold ?? 0))->toArray();
    $barColors = $chartProducts->map(function ($p) {
        $qty = (int) ($p->stock_quantity ?? 0);
        $thresh = (int) ($p->reorder_threshold ?? 0);
        if ($qty <= 0 || $p->status === 'out_of_stock') return '#dc2626';
        if ($qty <= $thresh) return '#f59e0b';
        return '#16a34a';
    })->toArray();
  @endphp
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>Stock Levels by Product</h2>
        <div class="sub">Top products by current stock vs their minimum limits</div>
      </div>
    </div>
    <div class="chart-wrap" style="padding: 18px 18px 6px; align-items: stretch;">
      <div class="bar-chart">
        <canvas id="stockLevelChart"></canvas>
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;font-size:12px;color:#64748b;">
        <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:3px;background:#16a34a;display:inline-block;"></span> In stock</span>
        <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:3px;background:#f59e0b;display:inline-block;"></span> Minimum limit</span>
      </div>
    </div>
    <div class="panel-foot">
      <button type="button" class="btn-link-ghost" onclick="openStockBreakdown()">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        View all {{ $totalItems }} products
      </button>
    </div>
  </div>
</div>

<!-- Inventory table (full width) -->
<div class="panel">
  <div class="tools">
    <div class="search-box">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="invSearch" placeholder="Search by name or SKU...">
    </div>
    <select id="statusFilter" class="filter-select">
      <option value="all">All Status</option>
      <option value="healthy">Healthy</option>
      <option value="low">Low Stock</option>
      <option value="out">Out of Stock</option>
    </select>
  </div>

  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
    @if($products->count() > 0)
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>In Stock</th>
          <th>Min Limit</th>
          <th>Stock Health</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody id="invTable">
        @foreach($products as $product)
        @php
          $qty = $product->stock_quantity ?? 0;
          $thresh = $product->reorder_threshold ?? 1;
          $isDisc = $product->status === 'discontinued';
          if ($qty <= 0 || $product->status === 'out_of_stock') {
              $badge = 'badge-out'; $text = 'Out of Stock'; $dataStatus = 'out';
          } elseif ($qty <= $thresh) {
              $badge = 'badge-low'; $text = 'Low Stock'; $dataStatus = 'low';
          } else {
              $badge = 'badge-ok'; $text = 'Optimal'; $dataStatus = 'healthy';
          }
          if ($isDisc) { $badge = 'badge-disc'; $text = 'Discontinued'; $dataStatus = 'out'; }
          $pct = $thresh > 0 ? min(100, round(($qty / max($thresh * 2, 1)) * 100)) : 100;
          $barColor = $qty <= 0 ? '#dc2626' : ($qty <= $thresh ? '#f59e0b' : '#16a34a');
          $stockBarStyle = 'width:' . $pct . '%;background:' . $barColor . ';';
        @endphp
        <tr data-name="{{ strtolower($product->name) }} {{ strtolower($product->sku) }}" data-status="{{ $dataStatus }}">
          <td>
            <div class="prod-cell">
              <div class="prod-thumb">
                @if($product->mainImage && !empty($product->mainImage->file_path))
                  <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                @endif
              </div>
              <div>
                <div class="prod-name">{{ $product->name }}</div>
                <div class="prod-sku">{{ $product->sku }}</div>
              </div>
            </div>
          </td>
          <td><span class="cat-pill">{{ $product->category->name ?? $product->category ?? 'General' }}</span></td>
          <td>
            <strong>{{ $qty }}</strong> {{ $product->stock_unit ?? 'pieces' }}
          </td>
          <td>{{ $thresh }} {{ $product->stock_unit ?? 'pieces' }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="stock-bar"><div data-inline-style="{{ $stockBarStyle }}"></div></div>
              <span class="badge {{ $badge }}">{{ $text }}</span>
            </div>
          </td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;">
              <button class="btn-icon" onclick="openEdit({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $qty }}, {{ $thresh }})" title="Update stock levels">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Update Stock
              </button>
              <a href="{{ route('store.products.edit', $product->id) }}" class="btn-icon" title="Edit product">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div class="empty-state">
      <h3>No Inventory Data</h3>
      <p>No products registered yet. Add products to your catalog to start tracking inventory levels.</p>
    </div>
    @endif
    </div>
    <div class="app-scrollbar">
      <div class="app-scrollbar-track"></div>
      <div class="app-scrollbar-thumb"></div>
    </div>
  </div>
</div>

<!-- Full stock breakdown modal (no overlay) -->
<div class="info-modal" id="stockBreakdownModal" role="dialog" aria-modal="true" aria-labelledby="stockBreakdownTitle">
  <div class="info-modal-head">
    <div>
      <h3 id="stockBreakdownTitle">Stock Levels by Product</h3>
      <div class="sub">Full list — {{ $totalItems }} product(s), complete names and live quantities</div>
    </div>
    <button class="modal-close" onclick="closeStockBreakdown()" aria-label="Close">×</button>
  </div>
  <div class="info-modal-body">
    @if($products->count() > 0)
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>In Stock</th>
          <th>Min Limit</th>
          <th>Health</th>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $product)
        @php
          $mQty = $product->stock_quantity ?? 0;
          $mThresh = $product->reorder_threshold ?? 1;
          $mDisc = $product->status === 'discontinued';
          if ($mQty <= 0 || $product->status === 'out_of_stock') {
              $mBadge = 'badge-out'; $mText = 'Out of Stock';
          } elseif ($mQty <= $mThresh) {
              $mBadge = 'badge-low'; $mText = 'Low Stock';
          } else {
              $mBadge = 'badge-ok'; $mText = 'Optimal';
          }
          if ($mDisc) { $mBadge = 'badge-disc'; $mText = 'Discontinued'; }
          $mPct = $mThresh > 0 ? min(100, round(($mQty / max($mThresh * 2, 1)) * 100)) : 100;
          $mBarColor = $mQty <= 0 ? '#dc2626' : ($mQty <= $mThresh ? '#f59e0b' : '#16a34a');
          $mBarStyle = 'width:' . $mPct . '%;background:' . $mBarColor . ';';
        @endphp
        <tr>
          <td>
            <div class="prod-cell" style="min-width:0;">
              <div class="prod-thumb" style="width:38px;height:38px;">
                @if($product->mainImage && !empty($product->mainImage->file_path))
                  <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                @endif
              </div>
              <div>
                <div class="prod-name">{{ $product->name }}</div>
                <div class="prod-sku">{{ $product->sku }}</div>
              </div>
            </div>
          </td>
          <td><span class="cat-pill">{{ $product->category->name ?? $product->category ?? 'General' }}</span></td>
          <td><strong>{{ $mQty }}</strong> {{ $product->stock_unit ?? 'pieces' }}</td>
          <td>{{ $mThresh }} {{ $product->stock_unit ?? 'pieces' }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="stock-bar"><div data-inline-style="{{ $mBarStyle }}"></div></div>
              <span class="badge {{ $mBadge }}">{{ $mText }}</span>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div class="empty-state">
      <h3>No Inventory Data</h3>
      <p>No products registered yet. Add products to your catalog to start tracking inventory levels.</p>
    </div>
    @endif
  </div>
</div>

<!-- Update Stock Modal -->
<div class="modal-overlay" id="adjustModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Update Stock — <span id="adjustName"></span></h3>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <form id="adjustForm" method="POST">
      @csrf
      <div class="modal-body">
        <div class="field">
          <label>Stock Quantity</label>
          <input type="number" name="stock_quantity" id="adjustQty" min="0" required>
          <div class="hint">Current stock available for sale.</div>
        </div>
        <div class="field">
          <label>Reorder Threshold (Min Limit)</label>
          <input type="number" name="reorder_threshold" id="adjustThresh" min="0" required>
          <div class="hint">Stock levels at or below this flag a low-stock alert.</div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
  // ---- Stock health donut chart ----
  const healthy = {{ $healthyCount }};
  const low = {{ $lowStockCount }};
  const out = {{ $outOfStockCount }};
  const total = {{ $totalItems }};

  const ctx = document.getElementById('stockHealthChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Healthy', 'Low Stock', 'Out of Stock'],
      datasets: [{
        data: [healthy, low, out],
        backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
        borderWidth: 3,
        borderColor: '#ffffff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '72%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (c) {
              const totalVal = healthy + low + out;
              const pct = totalVal ? Math.round((c.parsed / totalVal) * 100) : 0;
              return ' ' + c.label + ': ' + c.parsed + ' (' + pct + '%)';
            }
          }
        }
      }
    }
  });

  // ---- Stock levels by product (horizontal bar) ----
  const barLabels = @json($barLabels);
  const barQtys = @json($barQtys);
  const barThresholds = @json($barThresholds);
  const barColors = @json($barColors);

  if (barLabels.length > 0) {
    const barCtx = document.getElementById('stockLevelChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: barLabels,
        datasets: [
          {
            label: 'In stock',
            data: barQtys,
            backgroundColor: barColors,
            borderRadius: 5,
            barThickness: 14,
            stack: 'stock'
          },
          {
            label: 'Minimum limit',
            data: barThresholds,
            backgroundColor: '#f8fafc',
            borderColor: '#f59e0b',
            borderWidth: 2,
            borderRadius: 5,
            barThickness: 6,
            stack: 'limit'
          }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (c) {
                const name = this.chart.data.labels[c.dataIndex];
                const stock = this.chart.data.datasets[0].data[c.dataIndex];
                const lim = this.chart.data.datasets[1].data[c.dataIndex];
                if (c.datasetIndex === 1) return 'Minimum limit: ' + lim;
                return 'In stock: ' + stock + ' (min ' + lim + ')';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { color: '#f1f5f9' },
            ticks: { color: '#94a3b8', precision: 0 },
            beginAtZero: true
          },
          y: {
            grid: { display: false },
            ticks: { color: '#475569', font: { weight: 600 } }
          }
        }
      }
    });
  }

  // ---- Live search + status filter ----
  const searchInput = document.getElementById('invSearch');
  const statusFilter = document.getElementById('statusFilter');
  const rows = document.querySelectorAll('#invTable tr');

  function applyFilters() {
    const q = (searchInput.value || '').toLowerCase().trim();
    const status = statusFilter.value;
    rows.forEach(function (row) {
      const matchQ = !q || (row.dataset.name || '').includes(q);
      const matchS = status === 'all' || row.dataset.status === status;
      row.style.display = (matchQ && matchS) ? '' : 'none';
    });
  }
  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);

  // ---- Quick adjust modal ----
  const adjustForm = document.getElementById('adjustForm');
  function openEdit(id, name, qty, thresh) {
    document.getElementById('adjustName').textContent = name;
    document.getElementById('adjustQty').value = qty;
    document.getElementById('adjustThresh').value = thresh;
    adjustForm.action = "{{ url('store/inventory') }}/" + id + "/update-stock";
    document.getElementById('adjustModal').classList.add('open');
  }
  function closeEdit() {
    document.getElementById('adjustModal').classList.remove('open');
  }
  document.getElementById('adjustModal').addEventListener('click', function (e) {
    if (e.target === this) closeEdit();
  });

  // ---- Full stock breakdown modal (no overlay) ----
  const breakModal = document.getElementById('stockBreakdownModal');
  function openStockBreakdown() { breakModal.classList.add('open'); }
  function closeStockBreakdown() { breakModal.classList.remove('open'); }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeStockBreakdown();
  });
</script>
@endsection
