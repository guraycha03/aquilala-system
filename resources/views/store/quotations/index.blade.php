@extends('layouts.sidebar')

@section('title', 'Cost Quotations')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Inbound Cost Quotations</h1>
    <p style="color: #606d64; font-size: 14px;">Review cost calculation inquiries submitted by members of the Aquilala Irrigators Association.</p>
  </div>

  <style>
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .badge-issued { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .action-btn { background: #143d23; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
  </style>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Quotation Inquiry Workspace</h2>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Reference Code</th>
            <th>Farmer Name</th>
            <th>Target Area Size</th>
            <th>Requested Date</th>
            <th>Processing Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-weight: 600; color: #143d23;">#QT-2026-004</td>
            <td>Juan Dela Cruz</td>
            <td>2.5 Hectares</td>
            <td>June 24, 2026</td>
            <td><span class="badge-pending">Pending Response</span></td>
            <td><button class="action-btn">Compute Prices</button></td>
          </tr>
          <tr>
            <td style="font-weight: 600; color: #143d23;">#QT-2026-003</td>
            <td>Maria Santos</td>
            <td>1.2 Hectares</td>
            <td>June 22, 2026</td>
            <td><span class="badge-issued">Estimate Dispatched</span></td>
            <td><button class="action-btn" style="background: #475569;">Modify Estimate</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection