@extends('layouts.sidebar')

@section('title', 'Notifications')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Notification Feed</h1>
    <p style="color: #606d64; font-size: 14px;">System dispatches, system logs, contract approvals, and platform system messages.</p>
  </div>

  <div class="data-table-container" style="padding: 8px 0;">
    <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 14px; background: #fafbfa;">
      <div style="background: #e1f5fe; padding: 8px; border-radius: 50%; color: #0288d1; font-size: 16px; line-height: 1;">📢</div>
      <div>
        <h4 style="font-size: 14px; font-weight: 700; color: #1e2922; margin-bottom: 2px;">Welcome to Aquilala Hub Instance!</h4>
        <p style="color: #4a574e; font-size: 13px;">Your farmer account space configuration is fully synced with the database layer. Welcome aboard.</p>
        <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">Just now</span>
      </div>
    </div>
  </div>
@endsection