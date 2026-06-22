@extends('layouts.sidebar')

@section('title', 'Store Owner Dashboard')

@section('nav-links')
  <li>
    <a href="#" class="active">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Store Front Profile
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Sourcing Orders
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><polygon points="12 22.08 12 12 3 6.92 3 17.08 12 22.08"/><polygon points="12 12 21 6.92 21 17.08 12 22.08 12 12"/><polygon points="12 2 3 6.92 12 12 21 6.92 12 2"/></svg>
      Inventory Stocks
    </a>
  </li>
@endsection

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Merchant Dashboard</h1>
    <p style="color: #606d64; font-size: 14px;">Welcome back, {{ auth()->user()->name }}.</p>
  </div>

  <section class="metrics-grid">
    <div class="card">
      <h3>Active Sourcing Orders</h3>
      <div class="val" style="color: #2563eb;">8 Channels</div>
    </div>
    <div class="card">
      <h3>Procurement Costs</h3>
      <div class="val" style="color: #2563eb;">₱18,250.00</div>
    </div>
    <div class="card">
      <h3>Linked Suppliers</h3>
      <div class="val" style="color: #2563eb;">12 Farmers</div>
    </div>
  </section>

  <div class="data-table-container" style="padding: 24px;">
    <h2 style="font-size: 16px; margin-bottom: 12px; color: #1e2922;">Market Activity Pipeline</h2>
    <p style="color: #606d64; font-size: 14px; line-height: 1.6;">Your storefront supply allocation tracks are secure. Use the procurement link on the sidebar navigation system to source more raw commodities from certified local producers.</p>
  </div>
@endsection