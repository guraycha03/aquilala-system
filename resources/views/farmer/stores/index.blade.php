@extends('layouts.sidebar')

@section('title', 'Stores Directory')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Partner Store Locations</h1>
    <p style="color: #606d64; font-size: 14px;">Contact information and physical operating addresses for authorized affiliate stores.</p>
  </div>

  <div class="metrics-grid">
    <div class="card" style="display: flex; flex-direction: column; gap: 8px;">
      <div style="font-size: 11px; font-weight: 700; color: #6aeb2f; background: #143d23; padding: 4px 8px; border-radius: 4px; width: max-content;">ACTIVE PARTNER</div>
      <h2 style="font-size: 18px; font-weight: 700; color: #1e2922;">Aquilala Central Depot</h2>
      <p style="font-size: 13px; color: #606d64;">Main Highway Junction, Sector 4, Calabarzon Zone</p>
      <div style="font-size: 13px; color: #2c3530; margin-top: 8px; font-weight: 600;">✉ depot@aquilala.org</div>
    </div>
  </div>
@endsection