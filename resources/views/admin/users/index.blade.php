@extends('layouts.sidebar')

@section('title', 'User Management')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">User Account Profiles</h1>
    <p style="color: #606d64; font-size: 14px;">Manage system authorization, monitor users, and assign account privileges.</p>
  </div>

  <style>
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
  </style>

  <div class="data-table-container">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700;">Registered System Accounts</h2>
      <button style="background: #143d23; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">+ Add New User</button>
    </div>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>User Account</th>
            <th>Email Vector</th>
            <th>Role Assignment</th>
            <th>Operational Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="font-weight: 600;">Juan Dela Cruz</td>
            <td>juan.farmer@aquilala.org</td>
            <td><span class="badge" style="background: #dcfce7; color: #15803d;">Farmer</span></td>
            <td><button style="color: #143d23; border: none; background: none; font-weight: 600; cursor: pointer;">Edit Settings</button></td>
          </tr>
          <tr>
            <td style="font-weight: 600;">Bulan Agri Supply</td>
            <td>merchant.one@supply.ph</td>
            <td><span class="badge" style="background: #fef3c7; color: #d97706;">Store Owner</span></td>
            <td><button style="color: #143d23; border: none; background: none; font-weight: 600; cursor: pointer;">Edit Settings</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection