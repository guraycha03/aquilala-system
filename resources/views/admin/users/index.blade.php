@extends('layouts.sidebar')

@section('title', 'User Management')

@section('content')
  @php
    $totalUsers = $users->count();
    $ownerCount = $users->where('role', 'store_owner')->count();
    $farmerCount = $users->where('role', 'farmer')->count();
    $adminCount = $users->where('role', 'admin')->count();
    $activatedCount = $users->where('status', \App\Models\User::STATUS_ACTIVE)->count();
    $pendingCount = $users->filter(fn($u) => $u->isPendingVerification() || $u->isPendingApproval())->count();

    $roleMeta = [
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

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-users {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
      background: rgb(172, 242, 121); border: 1px solid rgba(20,61,35,0.12);
      color: #14532d; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08); cursor: pointer;
    }
    .btn-users:hover { background: rgb(160, 232, 108); transform: translateY(-1px); }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-users.ghost:hover { background: #f0fdf4; }

    .users-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .users-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .users-kpi .uk-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; margin-bottom: 6px; }
    .users-kpi .uk-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .users-kpi .uk-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .users-kpi .uk-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; vertical-align: 1px; }

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
        </div>
      </div>

      <!-- Summary KPIs -->
      <div class="users-kpis">
        <div class="users-kpi">
          <div class="uk-label">Total Accounts</div>
          <div class="uk-value">{{ $totalUsers }}</div>
          <div class="uk-sub">{{ $activatedCount }} active · {{ $pendingCount }} pending</div>
        </div>
        <div class="users-kpi">
          <div class="uk-label"><span class="uk-dot" style="background:#63856c;"></span>Farmers</div>
          <div class="uk-value">{{ $farmerCount }}</div>
          <div class="uk-sub">crop producers</div>
        </div>
        <div class="users-kpi">
          <div class="uk-label"><span class="uk-dot" style="background:#a57f43;"></span>Store Owners</div>
          <div class="uk-value">{{ $ownerCount }}</div>
          <div class="uk-sub">registered stores</div>
        </div>
        <div class="users-kpi">
          <div class="uk-label"><span class="uk-dot" style="background:#86799f;"></span>Administrators</div>
          <div class="uk-value">{{ $adminCount }}</div>
          <div class="uk-sub">platform operators</div>
        </div>
      </div>

      <!-- Accounts table -->
      <div class="users-panel">
        <div class="users-toolbar">
          <div class="search-box">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="searchInput" type="text" placeholder="Search by name, email, or location…">
          </div>
          <select id="roleFilter">
            <option value="all">All roles</option>
            <option value="farmer">Farmer</option>
            <option value="store_owner">Store Owner</option>
            <option value="admin">Administrator</option>
          </select>
          <select id="verifyFilter">
            <option value="all">Any status</option>
            <option value="verified">Active</option>
            <option value="approval">Pending approval</option>
            <option value="pending">Pending verification</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>

        <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
          <div class="app-scroll" id="usersScroll">
          @if($users->isNotEmpty())
            <table class="users-table">
              <thead>
                <tr>
                  <th>User Account</th>
                  <th>Contact</th>
                  <th>Role Assignment</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="usersRows">
                @foreach($users as $u)
                  @php
                    $role = $roleMeta[$u->role] ?? ['Member', '#64748b', '#f1f5f9'];
                    // Status-driven: pending invites await activation; every other
                    // usable account (admin, legacy/dev-seeded, activated) is Active.
                    $accountState = match ($u->status) {
                        \App\Models\User::STATUS_PENDING_APPROVAL    => 'approval',
                        \App\Models\User::STATUS_PENDING_VERIFICATION => 'pending',
                        \App\Models\User::STATUS_ACTIVE               => 'verified',
                        \App\Models\User::STATUS_SUSPENDED            => 'suspended',
                        default                                       => 'unverified',
                    };
                    $location = trim(implode(', ', array_filter([$u->barangay, $u->city])));
                    $contact = $u->phone ?? ($u->contact_number ?? ($location ?: '—'));
                    $search = strtolower(($u->name ?? '') . ' ' . ($u->email ?? '') . ' ' . ($u->phone ?? '') . ' ' . $location);
                  @endphp
                  <tr data-search="{{ $search }}" data-role="{{ $u->role }}" data-verified="{{ $accountState }}">
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
                      @if($accountState === 'verified')
                        <span class="verified-pill" style="background:#eef3ef;color:#3f7a4f;">
                          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                          Active
                        </span>
                      @elseif($accountState === 'approval')
                        <span class="verified-pill" style="background:#eef4ff;color:#3b6bb5;" title="Registration request awaiting admin approval">
                          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                          Pending Approval
                        </span>
                      @elseif($accountState === 'pending')
                        <span class="verified-pill" style="background:#fef9ec;color:#a57f43;" title="Awaiting email activation">
                          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                          Pending
                        </span>
                      @elseif($accountState === 'suspended')
                        <span class="verified-pill" style="background:#fdf2f2;color:#b91c1c;" title="Account suspended">
                          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                          Suspended
                        </span>
                      @else
                        <span class="verified-pill" style="background:#f1f5f9;color:#94a3b8;">
                          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                          Unverified
                        </span>
                      @endif
                    </td>
                    <td>
                      <span class="user-joined">{{ $u->created_at ? $u->created_at->format('M d, Y') : '—' }}</span>
                    </td>
                    <td>
                      <div class="user-actions">
                        @if($accountState === 'approval')
                          <button class="icon-btn approve-btn" title="Approve registration" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}">
                            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                          </button>
                          <button class="icon-btn reject-btn" title="Reject registration" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}" style="color:#b91c1c;">
                            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                          </button>
                        @elseif($accountState === 'pending')
                          <button class="icon-btn resend-btn" title="Resend activation email" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}">
                            <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                          </button>
                        @endif
                        <button class="icon-btn" title="Edit user" data-toast="Edit profile flow coming soon">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="icon-btn" title="View profile" data-toast="Profile view coming soon">
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

        <div class="users-footer">
          <span class="rows-info" id="rowsInfo"></span>
          <button id="clearFiltersBtn" class="btn-users ghost">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="user-toast" id="userToast"></div>

  <script>
    (function () {
      var rows = Array.prototype.slice.call(document.querySelectorAll('#usersRows tr'));
      var searchInput = document.getElementById('searchInput');
      var roleFilter = document.getElementById('roleFilter');
      var verifyFilter = document.getElementById('verifyFilter');
      var rowsInfo = document.getElementById('rowsInfo');
      var toast = document.getElementById('userToast');
      var toastTimer = null;

      function showToast(msg) {
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      // Flash message after redirect from the Register User page
      @if (session('success'))
        showToast(@js(session('success')));
      @endif
      @if (session('error'))
        showToast(@js(session('error')));
      @endif

      document.querySelectorAll('.icon-btn[data-toast]').forEach(function (btn) {
        btn.addEventListener('click', function () { showToast(btn.dataset.toast); });
      });

      // ── Resend activation email for pending accounts ──
      document.querySelectorAll('.resend-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Resend the activation email to ' + btn.dataset.userName + '?')) return;
          btn.disabled = true;
          fetch('/admin/users/' + btn.dataset.userId + '/resend-invitation', {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function (response) { return response.json().then(function (d) { return { ok: response.ok, data: d }; }); })
          .then(function (result) {
            showToast(result.data.message || (result.ok ? 'Invitation resent.' : 'Could not resend invitation.'));
            btn.disabled = false;
          })
          .catch(function () {
            showToast('Network failure — please try again.');
            btn.disabled = false;
          });
        });
      });

      // ── Approve / reject pending registration requests ──
      function runApprovalAction(btn, act) {
        if (!confirm((act === 'approve' ? 'Approve' : 'Reject') + ' the registration of ' + btn.dataset.userName + '?')) return;
        btn.disabled = true;
        fetch('/admin/users/' + btn.dataset.userId + '/' + act, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function (response) { return response.json().then(function (d) { return { ok: response.ok, data: d }; }); })
        .then(function (result) {
          if (!result.ok) { showToast(result.data.message || 'Could not complete the action.'); btn.disabled = false; return; }
          showToast(result.data.message || (act === 'approve' ? 'Registration approved.' : 'Registration rejected.'));
          setTimeout(function () { window.location.reload(); }, 900);
        })
        .catch(function () {
          showToast('Network failure — please try again.');
          btn.disabled = false;
        });
      }
      document.querySelectorAll('.approve-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { runApprovalAction(btn, 'approve'); });
      });
      document.querySelectorAll('.reject-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { runApprovalAction(btn, 'reject'); });
      });

      function applyFilters() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var role = roleFilter.value;
        var ver = verifyFilter.value;
        var shown = 0;
        rows.forEach(function (row) {
          var ok = true;
          if (q && row.getAttribute('data-search').indexOf(q) === -1) ok = false;
          if (ok && role !== 'all' && row.getAttribute('data-role') !== role) ok = false;
          if (ok && ver !== 'all' && row.getAttribute('data-verified') !== ver) ok = false;
          row.style.display = ok ? '' : 'none';
          if (ok) shown++;
        });
        rowsInfo.textContent = 'Showing ' + shown + ' of ' + rows.length + ' accounts';
      }

      searchInput.addEventListener('input', applyFilters);
      roleFilter.addEventListener('change', applyFilters);
      verifyFilter.addEventListener('change', applyFilters);
      document.getElementById('clearFiltersBtn').addEventListener('click', function () {
        searchInput.value = '';
        roleFilter.value = 'all';
        verifyFilter.value = 'all';
        applyFilters();
      });

      // Export CSV
      document.getElementById('exportBtn').addEventListener('click', function () {
        var shown = rows.filter(function (r) { return r.style.display !== 'none'; });
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

      applyFilters();
    })();
  </script>
@endsection
