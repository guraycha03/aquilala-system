@extends('layouts.sidebar')

@section('title', 'Merchant Approvals')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Merchant Registration Approvals</h1>
    <p style="color: #606d64; font-size: 14px;">Review and authorize registration applications from external farm supply stores seeking marketplace entry.</p>
  </div>

  <style>
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    .btn-approve { background: #15803d; color: white; padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .btn-reject { background: #b91c1c; color: white; padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; margin-left: 4px; }
  </style>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Pending Business Applications</h2>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Enterprise Business Name</th>
            <th>Contact Identity</th>
            <th>Submission Date</th>
            <th>Verification Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-weight: 600;">Sorsogon Agro-Chemical Center</td>
            <td>sorsogon.agro@gmail.com</td>
            <td>June 25, 2026</td>
            <td>
              <button class="btn-approve">Approve Node</button>
              <button class="btn-reject">Deny</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection