@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('content')
<style>
  .glass-panel {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 18px;
    display: flex;
    flex-direction: column;
  }
  .glass-panel:hover { box-shadow: 0 14px 44px rgba(0,0,0,0.09), 0 2px 8px rgba(0,0,0,0.04); }

  .dash-grid-2col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    align-items: start;
    gap: 24px;
    margin-bottom: 32px;
  }

  .glass-panel > .header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  .glass-panel > .body {
    padding: 20px 24px 24px;
  }

  .step-item {
    position: relative;
    padding: 14px 0 14px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.25s ease;
  }
  .step-item:last-child { border-bottom: none; }
  .step-item:hover { transform: translateX(4px); }

  .step-dot {
    position: absolute;
    left: 0;
    top: 18px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2.5px solid;
    background: #ffffff;
    z-index: 2;
  }

  .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
  .admin-table thead tr { background: #f6f8f7; border-bottom: 1px solid #e2e8f0; }
  .admin-table th { padding: 12px 16px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
  .admin-table td { padding: 13px 16px; color: #334155; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
  .admin-table tbody tr { transition: background 0.15s ease; }
  .admin-table tbody tr:last-child td { border-bottom: none; }
  .admin-table tbody tr:hover { background: #fafbfa; }

  .role-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
  .role-admin { background: #e0f2fe; color: #0369a1; }
  .role-owner { background: #fef3c7; color: #d97706; }
  .role-farmer { background: #dcfce7; color: #15803d; }

  @media (max-width: 640px) {
    .dash-grid-2col { grid-template-columns: 1fr; }
  }
</style>

<!-- 1. HEADER -->
<div style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:26px;font-weight:800;color:#0f172a;margin:0 0 4px 0;letter-spacing:-0.02em;">System Administration</h1>
      <p style="color:#64748b;font-size:14px;margin:0;">Welcome back, <strong>{{ auth()->user()->name }}</strong>. Here's your platform overview.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" style="background:linear-gradient(135deg,#143d23,#1b5e3a);color:white;padding:11px 22px;border-radius:12px;text-decoration:none;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(20,61,35,0.25);">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Manage Users
    </a>
  </div>
</div>

<!-- 2. KPI METRICS -->
<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;">
  <x-kpi-card title="Total Platform Users" value="{{ number_format($totalUsers) }}" trendValue="{{ $storeOwnersCount }} store owners" trendDirection="up" trendLabel="across all roles" accent="#16a34a">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Active Store Owners" value="{{ number_format($storeOwnersCount) }}" trendValue="{{ $farmersCount }} farmers" trendDirection="neutral" trendLabel="registered roles" accent="#d97706">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M12 17H12.01"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Total Quotations" value="{{ number_format($totalQuotations) }}" trendValue="{{ $pendingQuotations }} pending" trendDirection="up" trendLabel="platform-wide" accent="#7c3aed">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
  </x-kpi-card>
  <x-kpi-card title="Active Products" value="{{ number_format($activeProducts) }}" trendValue="{{ $totalProducts }} total" trendDirection="neutral" trendLabel="in marketplace" accent="#2563eb">
    <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 7L12 3 4 7l8 4 8-4z"/><path d="M4 7v10l8 4 8-4V7"/></svg></x-slot:icon>
  </x-kpi-card>
</section>

<!-- 3. SECOND ROW: RECENT REGISTRATIONS + QUOTATION ACTIVITY -->
<div class="dash-grid-2col">
  <!-- Recent Platform Registrations -->
  <div class="glass-panel">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <div>
        <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;">Recent Platform Registrations</h2>
        <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Latest accounts created on the platform</p>
      </div>
      <a href="{{ route('admin.users.index') }}" style="font-size:12px;font-weight:700;color:#143d23;text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(20,61,35,0.06);display:inline-flex;align-items:center;gap:4px;white-space:nowrap;">
        View All <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    <div class="body" style="padding:12px 0 16px 0;">
      @if($recentUsers->isNotEmpty())
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Joined</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentUsers as $user)
                <tr>
                  <td>
                    <div style="font-weight:600;color:#1e2922;">{{ $user->name }}</div>
                    <div style="font-size:12px;color:#94a3b8;">{{ $user->email }}</div>
                  </td>
                  <td>
                    @if($user->role === 'admin')
                      <span class="role-badge role-admin">Admin</span>
                    @elseif($user->role === 'store_owner')
                      <span class="role-badge role-owner">Store Owner</span>
                    @else
                      <span class="role-badge role-farmer">Farmer</span>
                    @endif
                  </td>
                  <td style="color:#718096;font-size:13px;">{{ $user->created_at->diffForHumans() }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div style="text-align:center;padding:28px 16px;">
          <svg viewBox="0 0 24 24" width="32" height="32" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No platform registrations yet.</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Platform Quotation Activity -->
  <div class="glass-panel">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <div>
        <h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;">Quotation Activity</h2>
        <p style="font-size:12px;color:#64748b;margin:2px 0 0 0;">Recent requests across the platform</p>
      </div>
      <a href="{{ route('admin.quotations.index') }}" style="font-size:12px;font-weight:700;color:#143d23;text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(20,61,35,0.06);display:inline-flex;align-items:center;gap:4px;white-space:nowrap;">
        View All <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
    <div class="body">
      @if($recentPlatformQuotations->isNotEmpty())
        <div style="position:relative;">
          @foreach($recentPlatformQuotations as $qt)
            <div class="step-item">
              <div class="step-dot" style="border-color:{{ $qt->status_color }};"></div>
              @if(!$loop->last)
                <div style="position:absolute;left:5px;top:28px;bottom:-14px;width:2px;background:rgba(0,0,0,0.06);"></div>
              @endif
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                  <span style="font-weight:700;font-size:13px;color:#0f172a;font-family:monospace;">{{ $qt->quotation_number }}</span>
                  <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;background:{{ $qt->status_bg }};color:{{ $qt->status_color }};">
                    {{ $qt->status_label }}
                  </span>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.4;">
                  <strong>{{ $qt->crop_type }}</strong> · {{ ucfirst($qt->season) }} Season · {{ $qt->land_area }} ha
                  @if($qt->farmer)
                    <br><span style="color:#475569;">🧑‍🌾 {{ $qt->farmer->name }}</span>
                    @if($qt->storeOwner)
                      → 🏪 {{ $qt->storeOwner->store_name ?? $qt->storeOwner->name }}
                    @endif
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div style="text-align:center;padding:28px 16px;">
          <svg viewBox="0 0 24 24" width="32" height="32" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No quotation requests yet.</p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
