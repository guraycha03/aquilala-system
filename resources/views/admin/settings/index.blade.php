@extends('layouts.sidebar')

@section('title', 'System Settings')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Platform Settings Configuration</h1>
    <p style="color: #606d64; font-size: 14px;">Configure general variables, metadata headers, and global system operational constraints.</p>
  </div>

  <style>
    .form-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; max-width: 600px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #1e2922; margin-bottom: 6px; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
  </style>

  <div class="form-card">
    <div class="form-group">
      <label>Platform Name Identity Header</label>
      <input type="text" value="Aquilala Irrigators Association Inc. System">
    </div>
    <div class="form-group">
      <label>Session Expiration Window (Minutes)</label>
      <input type="number" value="120">
    </div>
    <button style="background: #143d23; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">Save Settings</button>
  </div>
@endsection