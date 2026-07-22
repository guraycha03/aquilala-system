@extends('layouts.sidebar')

@section('title', 'Inventory Management')

@section('content')

<div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Crop Input Stock Management</h1>
    <p style="color: #606d64; font-size: 14px;">Monitor current warehouse volumes, adjust alert boundaries, and track item statuses in real-time.</p>
</div>

@if (session('success'))
<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; color: #166534; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
    <span style="font-size: 18px;">✓</span> {{ session('success') }}
</div>
@endif

<style>
.metrics-grid { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
.metric-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; min-width: 220px; flex: 1 1 calc(25% - 16px); box-sizing: border-box; }
.metric-card .icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.metric-card h3 { font-size: 12px; color: #606d64; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
.metric-card .val { font-size: 28px; font-weight: 800; color: #1e2922; line-height: 1.1; }
.metric-card .sub { font-size: 12px; color: #87a992; margin-top: 4px; }
.data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-top: 28px; }
.toolbar { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
th { background: #fafbfa; padding: 14px 16px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafbfa; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.badge-out { background: #fef2f2; color: #dc2626; }
.badge-low { background: #fffbeb; color: #d97706; }
.badge-ok { background: #f0fdf4; color: #16a34a; }
.empty-state { text-align: center; padding: 60px 20px; color: #87a992; }
</style>

<!-- Metrics Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="icon-wrap" style="background: #e0f2fe;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="#0369a1" stroke-width="2" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h3>Monitored Items</h3>
        <div class="val">{{ $totalItems }}</div>
        <div class="sub">Total product SKUs tracked</div>
    </div>

    <div class="metric-card">
        <div class="icon-wrap" style="background: #fffbeb;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="#d97706" stroke-width="2" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3>Low Stock Flags</h3>
        <div class="val">{{ $lowStockCount }}</div>
        <div class="sub">Items at or below reorder threshold</div>
    </div>

    <div class="metric-card">
        <div class="icon-wrap" style="background: #fef2f2;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="#dc2626" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <h3>Out of Stock</h3>
        <div class="val">{{ $outOfStockCount }}</div>
        <div class="sub">Unavailable for purchase</div>
    </div>

    <div class="metric-card">
        <div class="icon-wrap" style="background: #f3e8ff;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="#7c3aed" stroke-width="2" fill="none"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3>Total Stock Value</h3>
        <div class="val">₱{{ number_format($totalStockValue, 2) }}</div>
        <div class="sub">Estimated inventory worth</div>
    </div>
</div>

<!-- Table Container -->
<div class="data-table-container">
    <div class="toolbar">
        <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Stock Discrepancy Matrix</h2>
        <a href="{{ route('store.products.create') }}" style="background: #143d23; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">+ Register New Stock Batch</a>
    </div>
    
    <div style="overflow-x: auto;">
        @if($products->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU / Class</th>
                    <th>In Stock</th>
                    <th>Min Limit</th>
                    <th>Stock Health</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                @php
                    $qty = $product->stock_quantity ?? 0;
                    $thresh = $product->reorder_threshold ?? 1;
                    if ($qty <= 0) {
                        $badge = 'badge-out';
                        $text = 'Out of Stock';
                    } elseif ($qty <= $thresh) {
                        $badge = 'badge-low';
                        $text = 'Low Stock';
                    } else {
                        $badge = 'badge-ok';
                        $text = 'Optimal';
                    }
                @endphp
                <tr>
                    <td style="font-weight: 600;">{{ $product->name }}</td>
                    <td style="font-family: monospace; font-size: 13px; color: #64748b;">{{ $product->sku }}</td>
                    <td><strong>{{ $qty }}</strong> units</td>
                    <td>{{ $thresh }} units</td>
                    <td><span class="badge {{ $badge }}">{{ $text }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <h3 style="font-size: 18px; color: #4a574e;">No Inventory Data</h3>
            <p>No products registered yet. Add products to your catalog to start tracking inventory levels.</p>
        </div>
        @endif
    </div>
</div>

@endsection