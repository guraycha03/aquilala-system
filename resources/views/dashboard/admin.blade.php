@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('nav-links')
  <li>
    <a href="#" class="active">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
      Overview Dashboard
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      User Management
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      System Controls
    </a>
  </li>
  <li>
    <a href="#">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Audit Logs
    </a>
  </li>
@endsection

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Administration</h1>
    <p style="color: #606d64; font-size: 14px;">Welcome back, {{ auth()->user()->name }}.</p>
  </div>

  <section class="metrics-grid">
    <div class="card">
      <h3>Total Platform Users</h3>
      <div class="val">{{ number_format($totalUsers) }}</div>
    </div>
    <div class="card">
      <h3>Active Store Owners</h3>
      <div class="val">{{ number_format($storeOwnersCount) }}</div>
    </div>
    <div class="card">
      <h3>Registered Farmers</h3>
      <div class="val">{{ number_format($farmersCount) }}</div>
    </div>
  </section>

  <div class="data-table-container">
    <div class="table-header">Recent Platform Registrations</div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Account Role</th>
            <th>Date Joined</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentUsers as $user)
            <tr>
              <td style="font-weight: 500; color: #1e2922;">{{ $user->name }}</td>
              <td style="color: #4a554f;">{{ $user->email }}</td>
              <td>
                @if($user->role === 'admin')
                  <span class="badge admin" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Admin</span>
                @elseif($user->role === 'store_owner')
                  <span class="badge owner" style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Store Owner</span>
                @else
                  <span class="badge farmer" style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">Farmer</span>
                @endif
              </td>
              <td style="color: #718096; font-size: 14px;">
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