@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('nav-links')
  <li>
    <a href="#" class="active">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Crops Marketplace
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M16 11l2 2 4-4"/></svg>
      Active Contract Offers
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      Supply Logistics
    </a>
  </li>
@endsection

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Producer Hub</h1>
    <p style="color: #606d64; font-size: 14px;">Welcome back, {{ auth()->user()->name }}.</p>
  </div>

  <div class="status-alert" style="display: flex; align-items: center; gap: 8px;">
    <svg viewBox="0 0 24 24" style="width:18px; height:18px; fill:none; stroke:#065f46; stroke-width:2; stroke-linecap:round; stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    <span><strong>Notice:</strong> 2 new retail store networks are looking to purchase root vegetable variants in your local logistics zone.</span>
  </div>

  <section class="metrics-grid">
    <div class="card">
      <h3>Total Crop Listings</h3>
      <div class="val" style="color: #436149;">14 Tons</div>
    </div>
    <div class="card">
      <h3>Delivered Orders</h3>
      <div class="val" style="color: #436149;">320 units</div>
    </div>
    <div class="card">
      <h3>Pending Payouts</h3>
      <div class="val" style="color: #436149;">₱45,400.00</div>
    </div>
  </section>

  <div class="data-table-container" style="padding: 24px;">
    <h2 style="font-size: 16px; margin-bottom: 12px; color: #1e2922;">Active Production Overview</h2>
    <p style="color: #606d64; font-size: 14px; line-height: 1.6;">No active crop batch logs found for this cycle segment yet. Use the marketplace menu link above to upload stock allocations instantly.</p>
  </div>
@endsection