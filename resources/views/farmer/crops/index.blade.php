@extends('layouts.sidebar')

@section('title', 'My Crops')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">My Crops</h1>
    <p style="color: #606d64; font-size: 14px;">Manage your active farm yields, crop tracking cycles, and harvests.</p>
  </div>

  <div class="data-table-container" style="padding: 24px;">
    <div class="card" style="text-align: center; border: 1px dashed #cbd5e1; background: #fafbfa;">
      <svg viewBox="0 0 24 24" style="width: 48px; height: 48px; stroke: #87a992; fill: none; stroke-width: 1.5; margin: 0 auto 12px auto;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
      <h3 style="font-size: 16px; color: #1e2922; margin-bottom: 4px;">No crop records found</h3>
      <p style="color: #606d64; font-size: 14px; margin-bottom: 16px;">You haven't logged any crop inventory details for this season cycle yet.</p>
      <button style="background: #143d23; color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 6px; cursor: pointer;">+ Add Crop Batch</button>
    </div>
  </div>
@endsection