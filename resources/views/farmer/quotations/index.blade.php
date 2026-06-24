@extends('layouts.sidebar')

@section('title', 'My Quotations')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">My Quotation Dispatches</h1>
    <p style="color: #606d64; font-size: 14px;">Track ongoing negotiations, price feedback states, and structured store approvals.</p>
  </div>

  <div class="data-table-container">
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Reference ID</th>
          <th>Destination Store</th>
          <th>Submission Date</th>
          <th>Negotiation Status</th>
          <th>Action Log</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="5" style="text-align: center; padding: 32px; color: #606d64;">No formal quotation profiles recorded in your account pipeline.</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection