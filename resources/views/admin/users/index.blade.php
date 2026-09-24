@extends('layouts.sidebar')

@section('title', 'User Management')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'all',       'label' => 'All Users', 'url' => route('admin.users.index')],
      ['key' => 'stores',    'label' => 'Stores',    'url' => route('admin.stores.index')],
    ],
    'activeTab' => 'all',
  ])
  @php
    $roleMeta = $roleMeta ?? [
        'admin'       => ['Administrator', '#86799f', '#f1eef6'],
        'store_owner' => ['Store Owner', '#a57f43', '#f6f0e6'],
        'farmer'      => ['Farmer', '#63856c', '#eef3ef'],
    ];
  @endphp

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    .users-page { position: relative; }
    .users-page::before,
    .users-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .users-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.16); top: -80px; right: -80px; }
    .users-page::after { width: 280px; height: 280px; background: rgba(93,194,87,0.12); bottom: -60px; left: -60px; }
    .users-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 28px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-users {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer;
      font-family: inherit; letter-spacing: 0.01em; line-height: 1;
      background: #14532d; border: 1px solid rgba(255,255,255,0.10);
      color: #f4faf5; padding: 11px 20px; border-radius: 11px; font-size: 13px; font-weight: 600;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(20,61,35,0.06), 0 4px 12px rgba(20,61,35,0.10);
    }
    .btn-users:hover { background: #0f4228; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(20,61,35,0.08), 0 7px 16px rgba(20,61,35,0.14); }
    .btn-users:active { transform: translateY(0); box-shadow: 0 1px 2px rgba(20,61,35,0.06); }
    .btn-users:focus-visible { outline: 3px solid rgba(22,163,74,0.28); outline-offset: 2px; }
    .btn-users:disabled { opacity: 0.5; cursor: not-allowed; transform: none; background: #e6ece6; color: #8aa296; border-color: #d8e3db; box-shadow: none; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; }

    .users-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 30px; }
    .users-kpi {
      padding: 24px 26px 22px; border-radius: 18px; background: #ffffff;
      border: 1px solid rgba(20,61,35,0.08);
      box-shadow: 0 4px 18px rgba(20,61,35,0.06), 0 1px 3px rgba(20,61,35,0.03);
    }
    .users-kpi .uk-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: #6b7c72; margin-bottom: 14px; }
    .users-kpi .uk-value { display: block; font-size: 30px; font-weight: 800; color: #143d23; letter-spacing: 0.02em; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .users-kpi .uk-sub { display: block; font-size: 12px; letter-spacing: 0.06em; color: #8da096; margin-top: 10px; }
    .users-kpi .uk-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 10px; vertical-align: 1px; }

    @media (max-width: 768px) {
      .users-kpis { grid-template-columns: repeat(2, 1fr); gap: 12px; }
      .users-kpi { padding: 20px 20px 18px; }
      .users-kpi .uk-value { font-size: 26px; }
    }

    /* Interactive filter cards */
    .filter-card {
      font-family: inherit; text-align: left; cursor: pointer; display: block; width: 100%;
      padding: 24px 26px 22px; border-radius: 18px; background: #ffffff;
      border: 1px solid rgba(20,61,35,0.08); box-shadow: 0 4px 18px rgba(20,61,35,0.06), 0 1px 3px rgba(20,61,35,0.03);
      transition: all 0.2s ease; position: relative;
    }
    .filter-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(20,61,35,0.10), 0 2px 6px rgba(20,61,35,0.05); border-color: rgba(20,61,35,0.16); }
    .filter-card:focus-visible { outline: 2px solid rgb(172, 242, 121); outline-offset: 2px; }
    .filter-card.active {
      background: #e9f6d2; border-color: #b5d97d;
      box-shadow: 0 10px 26px rgba(20,61,35,0.12);
    }
    .filter-card.active .uk-label { color: #4d7c0f; }
    .filter-card.active .uk-value { color: #14532d; }
    .filter-card.active .uk-sub { color: #5f7d5c; }
    .filter-card.active .uk-dot { box-shadow: 0 0 0 2px rgba(255,255,255,0.9); }

    .users-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }
    .users-toolbar {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 16px 20px; border-bottom: 1px solid #eef1ef;
    }
    .users-toolbar .search-box {
      display: flex; align-items: center; gap: 8px; flex: 1; min-width: 200px; max-width: 340px;
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px; padding: 8px 12px;
    }
    .users-toolbar .search-box svg { width: 14px; height: 14px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .users-toolbar .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #334155; }
    .users-toolbar select {
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px;
      padding: 8px 12px; font-size: 13px; color: #334155; outline: none; cursor: pointer;
    }

    .users-table { width: 100%; border-collapse: collapse; text-align: left; }
    .users-table thead tr { background: #fafbfa; }
    .users-table th { padding: 13px 20px; color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .users-table td { padding: 14px 20px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .users-table tbody tr { transition: background 0.15s ease; }
    .users-table tbody tr:hover { background: #fafbfa; }
    .users-table tbody tr:last-child td { border-bottom: none; }
    .user-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .user-avatar {
      width: 36px; height: 36px; border-radius: 10px; background: rgb(172, 242, 121); color: #14532d;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .user-avatar svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .user-cell .u-name { font-weight: 600; color: #1e293b; white-space: nowrap; }
    .user-cell .u-mail { font-size: 11px; color: #94a3b8; margin-top: 1px; }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 7px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .verified-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .verified-pill svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .user-joined { font-size: 12px; color: #64748b; white-space: nowrap; }
    .user-contact { font-size: 12px; color: #64748b; white-space: nowrap; }
    .user-actions { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
    .icon-btn {
      width: 30px; height: 30px; border-radius: 8px; border: none; background: transparent; color: #94a3b8;
      display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s ease;
    }
    .icon-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .icon-btn:hover { background: #eef3ef; color: #14532d; }
    .resend-btn {
      display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
      padding: 6px 12px; border-radius: 8px; cursor: pointer;
      border: 1px solid rgba(59, 107, 181, 0.28); background: #eef4ff; color: #3b6bb5;
      font-size: 11px; font-weight: 700; letter-spacing: 0.02em; font-family: inherit;
      transition: all 0.15s ease;
    }
    .resend-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
    .resend-btn:hover { background: #dfe9ff; border-color: rgba(59, 107, 181, 0.45); }
    .resend-btn:disabled { opacity: 0.6; cursor: wait; }

    /* Custom overlay scrollbar moved to shared layout (.app-scrollbar) */

    .users-footer {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 14px 20px; border-top: 1px solid #eef1ef;
    }
    .users-footer .rows-info { font-size: 12px; color: #94a3b8; }
    .users-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
    .users-empty svg { width: 40px; height: 40px; stroke: #cbd5e1; fill: none; stroke-width: 1.5; margin-bottom: 10px; }

    /* Toast */
    .user-toast {
      position: fixed; bottom: 24px; left: 50%; transform: translate(-50%, 16px);
      background: #143d23; color: #eaf7e3; padding: 12px 20px; border-radius: 12px;
      font-size: 13px; font-weight: 600; box-shadow: 0 8px 24px rgba(20,61,35,0.28);
      opacity: 0; pointer-events: none; transition: all 0.25s ease; z-index: 9999;
    }
    .user-toast.show { opacity: 1; transform: translate(-50%, 0); }

    /* Modal overlay + dialogs */
    .umodal-overlay {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px);
      z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;
      opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    .umodal-overlay.open { opacity: 1; visibility: visible; }
    .umodal {
      background: #ffffff; border-radius: 18px; box-shadow: 0 24px 60px rgba(0,0,0,0.24);
      width: 100%; max-width: 440px; max-height: 88vh; overflow: auto; transform: translateY(12px);
      transition: transform 0.22s ease;
    }
    .umodal-overlay.open .umodal { transform: translateY(0); }
    .umodal-head {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding: 18px 20px 0 20px;
    }
    .umodal-head h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
    .umodal-close {
      width: 30px; height: 30px; border: none; border-radius: 8px; background: #f1f5f9; color: #64748b;
      cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .umodal-close:hover { background: #e2e8f0; }
    .umodal-close svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .umodal-body { padding: 14px 20px 20px 20px; }
    .umodal-actions {
      display: flex; justify-content: flex-end; gap: 10px; padding: 0 0 20px 0;
    }
    .btn-modal {
      display: inline-flex; align-items: center; gap: 7px; border: none; cursor: pointer;
      font-family: inherit; font-size: 13px; font-weight: 700; padding: 9px 16px; border-radius: 10px;
      transition: all 0.15s ease;
    }
    .btn-modal svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-modal.cancel { background: #f1f5f9; color: #475569; }
    .btn-modal.cancel:hover { background: #e2e8f0; }
    .btn-modal.ok { background: rgb(172, 242, 121); color: #14532d; }
    .btn-modal.ok:hover { background: rgb(160, 232, 108); }
    .btn-modal.danger { background: #b0403a; color: #ffffff; }
    .btn-modal.danger:hover { background: #963530; }
    .btn-modal[disabled] { opacity: 0.6; cursor: not-allowed; }

    /* Profile modal specific */
    .profile-hero {
      display: flex; align-items: center; gap: 13px; padding: 4px 2px 14px 2px;
      border-bottom: 1px solid #f1f5f9; margin-bottom: 14px;
    }
    .profile-avatar {
      width: 46px; height: 46px; border-radius: 12px; background: rgb(172, 242, 121); color: #14532d;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; font-weight: 800;
    }
    .profile-hero .p-name { font-size: 15px; font-weight: 700; color: #0f172a; }
    .profile-hero .p-mail { font-size: 12px; color: #94a3b8; }
    .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
    .profile-grid .wide { grid-column: 1 / -1; }
    .p-item .p-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; margin-bottom: 3px; }
    .p-item .p-value { font-size: 13px; color: #334155; font-weight: 600; word-break: break-word; }
    .p-item .p-value .muted { color: #cbd5e1; font-weight: 400; }
    .p-status-pill {
      display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px;
      font-size: 11px; font-weight: 700;
    }
    .p-status-pill svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 768px) {
      .users-table th:nth-child(3), .users-table td:nth-child(3),
      .users-table th:nth-child(5), .users-table td:nth-child(5) { display: none; }
    }
  </style>

  <div class="users-page">
    <div class="users-content">
      <div class="header-row">
        <div>
          <h1>User Account Profiles</h1>
          <p>Manage system authorization, monitor accounts, and assign platform privileges.</p>
        </div>
        <div class="header-actions">
          <button id="exportBtn" class="btn-users ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </button>
          <a id="addUserBtn" href="{{ route('admin.users.create') }}" class="btn-users">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New User
          </a>
          <a id="importBtn" href="{{ route('admin.users.import') }}" class="btn-users ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import Members
          </a>
        </div>
      </div>

      <!-- Account filter cards (click to filter the table) -->
      <div class="users-kpis" id="userFilterCards">
        <button type="button" class="users-kpi filter-card active" data-user-filter="">
          <span class="uk-label"><span class="uk-dot" style="background:#3f6b3e;"></span>All Accounts</span>
          <span class="uk-value">{{ $totalUsers }}</span>
          <span class="uk-sub">{{ $activatedCount }} active · {{ $pendingCount }} pending</span>
        </button>
        <button type="button" class="users-kpi filter-card" data-user-filter="farmer">
          <span class="uk-label"><span class="uk-dot" style="background:#9dbe5c;"></span>Farmers</span>
          <span class="uk-value">{{ $farmerCount }}</span>
          <span class="uk-sub">Crop producers</span>
        </button>
        <button type="button" class="users-kpi filter-card" data-user-filter="store_owner">
          <span class="uk-label"><span class="uk-dot" style="background:#8b6a44;"></span>Store Owners</span>
          <span class="uk-value">{{ $ownerCount }}</span>
          <span class="uk-sub">Registered stores</span>
        </button>
        <button type="button" class="users-kpi filter-card" data-user-filter="admin">
          <span class="uk-label"><span class="uk-dot" style="background:#d4a42e;"></span>Officers/Admins</span>
          <span class="uk-value">{{ $adminCount }}</span>
          <span class="uk-sub">Association leadership</span>
        </button>
      </div>

<!-- Accounts table -->
        <x-table-toolbar
            uid="accounts"
            work-table="usersTable"
            per-page="10"
            noun="accounts"
            search-placeholder="Search by name, email, or location…"
            :filters="[
                ['key' => 'role', 'label' => 'All roles', 'options' => [['farmer', 'Farmer'], ['store_owner', 'Store Owner'], ['admin', 'Administrator']]],
                ['key' => 'verified', 'label' => 'Any status', 'options' => [['active', 'Active'], ['pending', 'Pending'], ['disabled', 'Disabled']]],
            ]"
            :sorts="[
                ['key' => 'name', 'dir' => 1, 'type' => 'str', 'label' => 'Name (A–Z)'],
                ['key' => 'name', 'dir' => -1, 'type' => 'str', 'label' => 'Name (Z–A)'],
                ['key' => 'joined', 'dir' => -1, 'type' => 'date', 'label' => 'Joined (newest)'],
                ['key' => 'joined', 'dir' => 1, 'type' => 'date', 'label' => 'Joined (oldest)'],
                ['key' => 'role', 'dir' => 1, 'type' => 'str', 'label' => 'Role (A–Z)'],
            ]"
          />

        <div class="users-panel">
          <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
          <div class="app-scroll" id="usersScroll">
          @if($users->isNotEmpty())
            <table id="usersTable" class="users-table">
              <thead>
                <tr>
                  <th>User Account</th>
                  <th>Contact</th>
                  <th>Role Assignment</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Resend Invitation</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="usersRows">
                @foreach($users as $u)
                  @php
                    $role = $roleMeta[$u->role] ?? ['Member', '#64748b', '#f1f5f9'];
                    // 3-state model: pending (awaiting activation), active, disabled.
                    $accountState = match ($u->status) {
                        \App\Models\User::STATUS_PENDING  => 'pending',
                        \App\Models\User::STATUS_DISABLED => 'disabled',
                        default                             => 'active',
                    };
                    // Invited (active) accounts that have not set a permanent
                    // password yet — they signed in with the emailed temp one.
                    $needsPasswordSetup = $accountState === 'active' && $u->mustChangePassword();
                    $location = trim(implode(', ', array_filter([$u->barangay, $u->city])));
                    $contact = $u->phone ?? ($u->contact_number ?? ($location ?: '—'));
                    $search = strtolower(($u->name ?? '') . ' ' . ($u->email ?? '') . ' ' . ($u->phone ?? '') . ' ' . $location);
                  @endphp
                  <tr data-search="{{ $search }}" data-role="{{ $u->role }}" data-verified="{{ $accountState }}"
                      data-name="{{ strtolower($u->name ?? '') }}"
                      data-joined="{{ $u->created_at?->format('Y-m-d') ?? '' }}">
                    <td>
                      <div class="user-cell">
                        <div class="user-avatar">
                          <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div style="min-width:0;">
                          <div class="u-name">{{ $u->name }}</div>
                          <div class="u-mail">{{ $u->email }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="user-contact">{{ $contact }}</div>
                    </td>
                    <td>
                      <span class="role-badge" data-inline-style="background:{{ $role[2] }};color:{{ $role[1] }};">{{ $role[0] }}</span>
                    </td>
                    <td>
                      @if($accountState === 'active')
                        <span class="verified-pill" style="background:#eef3ef;color:#3f7a4f;">
                          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                          Active
                        </span>
                      @elseif($accountState === 'pending')
                        <span class="verified-pill" style="background:#eef4ff;color:#3b6bb5;" title="Admin-created member — awaiting email verification and password setup">
                          <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                          Pending
                        </span>
                      @elseif($accountState === 'disabled')
                        <span class="verified-pill" style="background:#fdf2f2;color:#b91c1c;" title="Account disabled by an administrator">
                          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                          Disabled
                        </span>
                      @endif
                    </td>
                    <td>
                      <span class="user-joined">{{ $u->created_at ? $u->created_at->format('M d, Y') : '—' }}</span>
                    </td>
                    <td>
                      @if($accountState === 'pending')
                        <form class="resend-form" method="POST" action="{{ route('admin.users.resend', $u) }}"
                              data-user-name="{{ $u->name }}" data-user-email="{{ $u->email }}">
                          @csrf
                          <button type="submit" class="resend-btn" title="Resend activation email">
                            <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            Resend
                          </button>
                        </form>
                      @else
                        <span style="color:#cbd5e1;">—</span>
                      @endif
                    </td>
                    <td>
                      <div class="user-actions">
                        <a class="icon-btn" href="{{ route('admin.users.edit', $u) }}" title="Edit user">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <button class="icon-btn profile-btn" title="View profile" data-profile-id="{{ $u->id }}">
                          <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div class="users-empty">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <p>No user accounts registered yet.</p>
            </div>
          @endif
          </div>

          @if($users->isNotEmpty())
            <div class="app-scrollbar">
              <div class="app-scrollbar-track"></div>
              <div class="app-scrollbar-thumb"></div>
            </div>
          @endif
        </div>

      </div>
    </div>
  </div>

  <div class="user-toast" id="userToast"></div>

  <!-- Profile detail modal -->
  <div class="umodal-overlay" id="profileOverlay" aria-hidden="true">
    <div class="umodal" role="dialog" aria-modal="true">
      <div class="umodal-head">
        <h3>Account Profile</h3>
        <button type="button" class="umodal-close" id="profileClose" aria-label="Close">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="umodal-body" id="profileBody"></div>
    </div>
  </div>

  <!-- In-app confirmation dialog (replaces browser confirm) -->
  <div class="umodal-overlay" id="confirmOverlay" aria-hidden="true">
    <div class="umodal" role="dialog" aria-modal="true">
      <div class="umodal-head">
        <h3 id="confirmTitle">Are you sure?</h3>
        <button type="button" class="umodal-close" id="confirmClose" aria-label="Close">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="umodal-body" id="confirmBody">
        <p style="font-size:13px;color:#475569;line-height:1.55;margin:0 0 4px 0;" id="confirmMessage"></p>
        <div class="umodal-actions">
          <button type="button" class="btn-modal cancel" id="confirmCancel">Cancel</button>
          <button type="button" class="btn-modal ok" id="confirmOk">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <script id="usersProfiles" type="application/json">
    @json($profileData)
  </script>

  <script>
    (function () {
      var toast = document.getElementById('userToast');
      var toastTimer = null;

      function showToast(msg) {
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      // ── Flash message after redirect from the Register/Edit User page ──
      @if (session('success'))
        showToast(@js(session('success')));
      @endif
      @if (session('error'))
        showToast(@js(session('error')));
      @endif

      // ── In-app confirm dialog ──
      var confirmOverlay = document.getElementById('confirmOverlay');
      var confirmTitle = document.getElementById('confirmTitle');
      var confirmMessage = document.getElementById('confirmMessage');
      var confirmOk = document.getElementById('confirmOk');
      var confirmCancel = document.getElementById('confirmCancel');
      var confirmClose = document.getElementById('confirmClose');
      var confirmResolver = null;

      function askConfirm(opts) {
        confirmTitle.textContent = opts.title || 'Are you sure?';
        confirmMessage.textContent = opts.message || '';
        confirmOk.className = 'btn-modal ' + (opts.danger ? 'danger' : 'ok');
        confirmOk.textContent = opts.confirmLabel || 'Confirm';
        confirmOk.classList.remove('ok');
        confirmOverlay.classList.add('open');
        confirmOverlay.setAttribute('aria-hidden', 'false');
        return new Promise(function (resolve) {
          confirmResolver = resolve;
        });
      }
      function settleConfirm(value) {
        confirmOverlay.classList.remove('open');
        confirmOverlay.setAttribute('aria-hidden', 'true');
        if (confirmResolver) { confirmResolver(value); confirmResolver = null; }
      }
      confirmOk.addEventListener('click', function () { settleConfirm(true); });
      confirmCancel.addEventListener('click', function () { settleConfirm(false); });
      confirmClose.addEventListener('click', function () { settleConfirm(false); });
      confirmOverlay.addEventListener('click', function (e) {
        if (e.target === confirmOverlay) settleConfirm(false);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && confirmOverlay.classList.contains('open')) settleConfirm(false);
      });

      function bindToastButtons() {
        document.querySelectorAll('.icon-btn[data-toast]').forEach(function (btn) {
          btn.addEventListener('click', function () { showToast(btn.dataset.toast); });
        });
      }
      bindToastButtons();

      // ── Profile modal (real data) ──
      var profiles = {};
      try { profiles = JSON.parse(document.getElementById('usersProfiles').textContent); } catch (e) {}

      var profileOverlay = document.getElementById('profileOverlay');
      var profileBody = document.getElementById('profileBody');
      var profileClose = document.getElementById('profileClose');

      function statusPill(status) {
        var map = {
          active:       ['Active',   '#eef3ef', '#3f7a4f', '<polyline points="20 6 9 17 4 12"/>'],
          pending:      ['Pending', '#eef4ff', '#3b6bb5', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
          disabled:     ['Disabled', '#fdf2f2', '#b91c1c', '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>']
        };
        var m = map[status] || ['Unknown', '#f1f5f9', '#94a3b8', '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'];
        return '<span class="p-status-pill" style="background:' + m[1] + ';color:' + m[2] + ';">' +
               '<svg viewBox="0 0 24 24">' + m[3] + '</svg> ' + m[0] + '</span>';
      }

      function item(label, value, wide) {
        value = value || value === 0 ? value : null;
        return '<div class="p-item' + (wide ? ' wide' : '') + '">' +
               '<div class="p-label">' + label + '</div>' +
               '<div class="p-value">' + (value !== null ? String(value).replace(/</g, '&lt;') : '<span class="muted">—</span>') + '</div></div>';
      }

      function openProfile(id) {
        var p = profiles[id];
        if (!p) { showToast('Profile data not found.'); return; }
        var initials = (p.name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) {
          return w.charAt(0).toUpperCase();
        }).join('');
        var hero = '<div class="profile-hero">' +
          '<div class="profile-avatar">' + initials + '</div>' +
          '<div><div class="p-name">' + p.name + '</div>' +
          '<div class="p-mail">' + p.email + '</div></div></div>';
        var statusKey = p.status;
        var setupNote = (p.status === 'pending')
          ? '<div class="form-alert info" style="margin-bottom:14px;">' +
            '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>' +
            'Pending account — awaiting email verification and password setup. ' +
            'A new activation code can be sent from the Users list.</div>'
          : '';
        var body = hero + setupNote +
          '<div class="profile-grid">' +
          item('Role', p.role) +
          '<div class="p-item"><div class="p-label">Status</div><div class="p-value">' + statusPill(statusKey) + '</div></div>' +
          item('Phone', p.phone) +
          item('Joined', p.joined) +
          item('Barangay / District', p.barangay) +
          item('City / Municipality', p.city) +
          item('Province', p.province, true) +
          item('Sex', p.sex) +
          item('Birth Date', p.birth_date) +
          item('Civil Status', p.civil_status) +
          item('Farm Location', p.farm_location, true) +
          item('Land Ownership', p.land_ownership) +
          item('Years Farming', p.years_farming) +
          item('Association No.', p.assoc_member_no) +
          (p.products != null ? item('Listed Products', p.products) : '') +
          '</div>';
        profileBody.innerHTML = body;
        profileOverlay.classList.add('open');
        profileOverlay.setAttribute('aria-hidden', 'false');
      }
      function closeProfile() {
        profileOverlay.classList.remove('open');
        profileOverlay.setAttribute('aria-hidden', 'true');
      }
      function bindProfileButtons() {
        document.querySelectorAll('.profile-btn').forEach(function (btn) {
          btn.addEventListener('click', function () { openProfile(btn.dataset.profileId); });
        });
      }
      bindProfileButtons();
      profileClose.addEventListener('click', closeProfile);
      profileOverlay.addEventListener('click', function (e) {
        if (e.target === profileOverlay) closeProfile();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && profileOverlay.classList.contains('open')) closeProfile();
      });

      // ── Resend activation email for pending accounts ──
      function bindResendButtons() {
        document.querySelectorAll('.resend-form').forEach(function (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('.resend-btn');
            var userName = form.getAttribute('data-user-name') || 'this user';
            var userEmail = form.getAttribute('data-user-email') || '';
            var step = (typeof askConfirm === 'function')
              ? askConfirm({
                  title: 'Resend activation code?',
                  message: 'A new activation code will be sent to ' + userName +
                    ' (' + userEmail + '). Any previously sent code becomes void.',
                  confirmLabel: 'Resend Code'
                })
              : Promise.resolve(window.confirm(
                  'A new activation code will be sent to ' + userName + ' (' + userEmail + ').\n\nAny previously sent code becomes void.'
                ));

            step.then(function (ok) {
              if (!ok) return;
              btn.disabled = true;
              btn.textContent = 'Sending…';
              fetch(form.action, {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                  'X-Requested-With': 'XMLHttpRequest'
                }
              })
              .then(function (response) { return response.json().then(function (d) { return { ok: response.ok, data: d }; }); })
              .then(function (result) {
                showToast(result.data.message || (result.ok ? 'Activation code resent.' : 'Could not resend activation code.'));
                btn.disabled = false;
                btn.textContent = 'Resend';
              })
              .catch(function () {
                showToast('Network failure — please try again.');
                btn.disabled = false;
                btn.textContent = 'Resend';
              });
            });
          });
        });
      }
      bindResendButtons();

      // Export CSV
      document.getElementById('exportBtn').addEventListener('click', function () {
        var shown = Array.prototype.slice.call(document.querySelectorAll('#usersRows tr')).filter(function (r) { return r.style.display !== 'none'; });
        if (shown.length === 0) { showToast('No accounts to export'); return; }
        var lines = ['Name,Email,Role,Status,Joined'];
        shown.forEach(function (row) {
          var cells = row.querySelectorAll('td');
          var name = cells[0].querySelector('.u-name').innerText;
          var mail = cells[0].querySelector('.u-mail').innerText;
          var role = cells[2].innerText.trim();
          var status = cells[3].innerText.trim();
          var joined = cells[4].innerText.trim();
          lines.push([name, mail, role, status, joined].map(function (v) {
            return '"' + v.replace(/"/g, '""') + '"';
          }).join(','));
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'user-accounts.csv';
        a.click();
        URL.revokeObjectURL(url);
        showToast('Exported ' + shown.length + ' accounts');
      });

      })();
  </script>

  <script>
    (function () {
      function setActive(card) {
        document.querySelectorAll('#userFilterCards .filter-card').forEach(function (c) {
          c.classList.toggle('active', c === card);
        });
      }

      function applyRole(role, cards) {
        var tb = document.querySelector('[data-ttb="accounts"]');
        if (!tb) return false;
        var group = tb.querySelector('[data-ttb-filter="role"]');
        if (!group) return false;
        var option = group.querySelector('.ncb-select-option[data-value="' + role + '"]');
        if (!option) return false;
        option.click();
        var matched = cards.filter(function (c) { return c.getAttribute('data-user-filter') === role; })[0];
        if (matched) setActive(matched);
        return true;
      }

      function attach() {
        var container = document.getElementById('userFilterCards');
        if (!container || container.getAttribute('data-attached')) return;
        container.setAttribute('data-attached', '1');
        var cards = Array.prototype.slice.call(container.querySelectorAll('.filter-card'));
        cards.forEach(function (card) {
          card.addEventListener('click', function () {
            applyRole(card.getAttribute('data-user-filter'), cards);
          });
        });
        var tb = document.querySelector('[data-ttb="accounts"]');
        if (tb) {
          tb.addEventListener('click', function (e) {
            var opt = e.target.closest('.ncb-select-option');
            if (!opt) return;
            var group = opt.closest('[data-ttb-filter="role"]');
            if (!group) return;
            var role = opt.getAttribute('data-value') || '';
            var matched = cards.filter(function (c) { return c.getAttribute('data-user-filter') === role; })[0];
            setActive(matched || cards[0]);
          });
        }
      }

      window.TableToolbarPending = attach;
    })();
  </script>

@endsection
