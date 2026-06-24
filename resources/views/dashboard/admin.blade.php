@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Administration</h1>
    <p style="color: #606d64; font-size: 14px;">Welcome back, {{ auth()->user()->name }}.</p>
  </div>

  <section class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
    
    <x-kpi-card 
        title="Total Platform Users" 
        value="{{ number_format($totalUsers) }}" 
        trendValue="+8.4%" 
        trendDirection="up" 
        trendLabel="vs last week"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card 
        title="Active Store Owners" 
        value="{{ number_format($storeOwnersCount) }}" 
        trendValue="0%" 
        trendDirection="neutral" 
        trendLabel="no change today"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M12 17H12.01"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card 
        title="Registered Farmers" 
        value="{{ number_format($farmersCount) }}" 
        trendValue="+12.3%" 
        trendDirection="up" 
        trendLabel="vs last month"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

  </section>

  <div class="data-table-container" style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
    <div class="table-header" style="font-size: 16px; font-weight: 600; padding: 16px; color: #1e2922; border-bottom: 1px solid #e5e7eb;">Recent Platform Registrations</div>
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <th style="padding: 12px 16px; color: #64748b; font-size: 13px; font-weight: 600;">Name</th>
            <th style="padding: 12px 16px; color: #64748b; font-size: 13px; font-weight: 600;">Email</th>
            <th style="padding: 12px 16px; color: #64748b; font-size: 13px; font-weight: 600;">Account Role</th>
            <th style="padding: 12px 16px; color: #64748b; font-size: 13px; font-weight: 600;">Date Joined</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentUsers as $user)
            <tr style="border-bottom: 1px solid #f1f5f9;">
              <td style="padding: 14px 16px; font-weight: 500; color: #1e2922;">{{ $user->name }}</td>
              <td style="padding: 14px 16px; color: #4a554f;">{{ $user->email }}</td>
              <td style="padding: 14px 16px;">
                @if($user->role === 'admin')
                  <span class="badge admin" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Admin</span>
                @elseif($user->role === 'store_owner')
                  <span class="badge owner" style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Store Owner</span>
                @else
                  <span class="badge farmer" style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Farmer</span>
                @endif
              </td>
              <td style="padding: 14px 16px; color: #718096; font-size: 14px;">
                {{ $user->created_at->diffForHumans() }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="text-align: center; color: #8a998f; padding: 32px; font-style: italic;">
                No platform registrations recorded inside the database yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection