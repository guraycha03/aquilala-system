@extends('layouts.sidebar')

@section('title', 'System Analytics')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Metrics &amp; Analytics</h1>
    <p style="color: #606d64; font-size: 14px;">Review statistical overview graphs mapping platform trends, item supply layouts, and DSS request metrics.</p>
  </div>

  <style>
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
    .card { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .card h3 { font-size: 13px; color: #606d64; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
    .card .val { font-size: 28px; font-weight: 700; color: #143d23; }
  </style>

  <section class="metrics-grid">
    <div class="card">
      <h3>DSS Engine Request Vol.</h3>
      <div class="val">142 Inquiries</div>
    </div>
    <div class="card">
      <h3>Market Catalog Volume</h3>
      <div class="val">₱184,500.00</div>
    </div>
  </section>
@endsection