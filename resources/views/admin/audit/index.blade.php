@extends('layouts.sidebar')

@section('title', 'Audit Trail Logs')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Security Audit Trail</h1>
    <p style="color: #606d64; font-size: 14px;">Immutable event log capturing authentication steps, formula overrides, and account modifications.</p>
  </div>

  <style>
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    .code-span { font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
  </style>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Security Vector Monitoring</h2>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Account Actor</th>
            <th>Event Context Log</th>
            <th>IP Address Target</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>2026-06-25 17:15:22</td>
            <td style="font-weight: 600;">System Admin</td>
            <td><span class="code-span">Approved Merchant Node ID #4</span></td>
            <td>192.168.1.42</td>
          </tr>
          <tr>
            <td>2026-06-25 16:42:10</td>
            <td style="font-weight: 600;">Juan Dela Cruz</td>
            <td><span class="code-span">Dispatched Cost Quotation Inquiry</span></td>
            <td>192.168.1.108</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection