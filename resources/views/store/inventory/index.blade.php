@extends('layouts.sidebar')

@section('title', 'Inventory Management')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Crop Input Stock Management</h1>
    <p style="color: #606d64; font-size: 14px;">Monitor current warehouse volumes, adjust alert boundaries, and track item statuses.</p>
  </div>

  <style>
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .card { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .card h3 { font-size: 13px; color: #606d64; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
    .card .val { font-size: 28px; font-weight: 700; color: #1e2922; }
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    .badge-alert { background: #ffe4e6; color: #b91c1c; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .badge-ok { background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
  </style>

  <section class="metrics-grid">
    <div class="card">
      <h3>Monitored Items</h3>
      <div class="val" style="color: #143d23;">12 Classes</div>
    </div>
    <div class="card">
      <h3>Low Stock Flags</h3>
      <div class="val" style="color: #dc2626;">2 Flags</div>
    </div>
  </section>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Stock Discrepancy Matrix</h2>
      <button style="background: #143d23; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">+ Register New Stock Batch</button>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Product Name</th>
            <th>SKU / Class</th>
            <th>In Stock</th>
            <th>Min Limit Boundary</th>
            <th>Stock Health</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-weight: 600;">Urea Fertilizer (46-0-0)</td>
            <td>FERT-UREA-50KG</td>
            <td>8 Bags</td>
            <td>20 Bags</td>
            <td><span class="badge-alert">Low Stock Alert</span></td>
          </tr>
          <tr>
            <td style="font-weight: 600;">Complete Fertilizer (14-14-14)</td>
            <td>FERT-COMP-50KG</td>
            <td>12 Bags</td>
            <td>25 Bags</td>
            <td><span class="badge-alert">Low Stock Alert</span></td>
          </tr>
          <tr>
            <td style="font-weight: 600;">Muriate of Potash (0-0-60)</td>
            <td>FERT-MOP-50KG</td>
            <td>85 Bags</td>
            <td>15 Bags</td>
            <td><span class="badge-ok">Optimal</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection