@extends('layouts.sidebar')

@section('title', 'Quotation Logs')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Cost Quotation Logs</h1>
    <p style="color: #606d64; font-size: 14px;">Monitor financial interactions and procurement queries executed between farmers and merchants.</p>
  </div>

  <style>
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
  </style>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Global System Transaction Log</h2>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Reference ID</th>
            <th>Farmer Profile</th>
            <th>Merchant Node</th>
            <th>Evaluation Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-weight: 600; color: #143d23;">#QT-2026-004</td>
            <td>Juan Dela Cruz</td>
            <td>Bulan Agri Supply</td>
            <td><span style="color: #d97706; font-weight: 600;">Pending Merchant Computation</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection