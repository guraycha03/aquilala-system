@extends('layouts.sidebar')

@section('title', 'Members')

@section('content')
  <style>
    .users-page { position: relative; }
    .users-page::before,
    .users-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .users-page::before { width: 320px; height: 320px; background: rgba(94,144,112,0.16); top: -80px; right: -80px; }
    .users-page::after { width: 280px; height: 280px; background: rgba(93,194,87,0.12); bottom: -60px; left: -60px; }
    .users-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 28px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; max-width: 640px; line-height: 1.55; }
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
    .btn-users:focus-visible { outline: 3px solid rgba(74,122,84,0.28); outline-offset: 2px; }
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
    .users-kpi .uk-sub { display: block; font-size: 12px; letter-spacing: 0.04em; color: #8da096; margin-top: 10px; line-height: 1.5; }
    .users-kpi .uk-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 10px; vertical-align: 1px; }

    a.users-kpi { display: block; text-decoration: none; transition: all 0.2s ease; }
    a.users-kpi:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(20,61,35,0.10), 0 2px 6px rgba(20,61,35,0.05); border-color: rgba(20,61,35,0.16); }
    a.users-kpi.active { background: #e9f6d2; border-color: #b5d97d; box-shadow: 0 10px 26px rgba(20,61,35,0.12); }
    a.users-kpi.active .uk-label { color: #4d7c0f; }
    a.users-kpi.active .uk-value { color: #14532d; }
    a.users-kpi.active .uk-sub { color: #5f7d5c; }

    @media (max-width: 768px) {
      .users-kpis { grid-template-columns: repeat(2, 1fr); gap: 12px; }
      .users-kpi { padding: 20px 20px 18px; }
      .users-kpi .uk-value { font-size: 26px; }
    }

    .users-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }

    .user-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
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
    .icon-btn.danger:hover { background: #fdf2f2; color: #b91c1c; }
    .icon-btn:disabled { opacity: 0.35; cursor: not-allowed; }

    .acct-btn {
      display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
      padding: 6px 12px; border-radius: 8px; cursor: pointer; text-decoration: none;
      border: 1px solid rgba(63, 122, 79, 0.30); background: #eef3ef; color: #3f7a4f;
      font-size: 11px; font-weight: 700; letter-spacing: 0.02em; font-family: inherit;
      transition: all 0.15s ease;
    }
    .acct-btn:hover { background: #e3ede6; border-color: rgba(63,122,79,0.45); }
    .acct-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }

    .users-empty { text-align: center; padding: 56px 20px; color: #94a3b8; }
    .users-empty svg { width: 44px; height: 44px; stroke: currentColor; fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; opacity: 0.7; }
    .users-empty p { font-size: 14px; margin: 14px 0 0 0; line-height: 1.6; }
    .bulk-selbar { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 70; display: flex; align-items: center; gap: 12px; width: max-content; max-width: calc(100vw - 16px); background: #f2f6f1; color: #2c3a2f; border: 1px solid #d8e2d5; padding: 10px 12px 10px 18px; border-radius: 999px; box-shadow: 0 14px 34px rgba(31, 41, 33, 0.18); }
    .bulk-selbar[hidden] { display: none; }
    .bulk-selbar form { display: flex; align-items: center; gap: 10px; margin: 0; }
    .bulk-selbar-count { font-size: 13.5px; font-weight: 600; white-space: nowrap; color: #4a5a4d; }
    .bulk-selbar-count b { font-size: 14.5px; color: #166534; }
    .bulk-selbar button { border-radius: 999px; padding: 7px 14px; cursor: pointer; white-space: nowrap; font-size: 13px; font-weight: 700; }
    .bulk-selbar-clear { border: 1px solid #ccd8c9; background: #fff; color: #5b6b5a; }
    .bulk-selbar-archive { border: 1px solid #bcd4bb; background: #dcecda; color: #166534; }
    .bulk-selbar-archive:disabled { opacity: .45; cursor: not-allowed; }
    .col-sel { width: 34px; padding-left: 14px !important; padding-right: 0 !important; }
    .col-sel input[type="checkbox"] { width: 15px; height: 15px; margin: 0; accent-color: #15803d; cursor: pointer; }

    .user-toast {
      position: fixed; bottom: 24px; left: 50%; transform: translate(-50%, 20px);
      background: #143d23; color: #f4faf5; padding: 12px 20px; border-radius: 11px;
      font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none;
      transition: all 0.25s ease; z-index: 9999; max-width: 90vw; box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    }
    .user-toast.show { opacity: 1; transform: translate(-50%, 0); }
    .user-toast.error { background: #7f1d1d; }
  </style>

  <div class="users-page">
    <div class="users-content">
      <div class="header-row">
        <div>
          <h1>Association Members</h1>
          <p>The list of people who belong to the association. Forms, file uploads and scans all land here —
             a system account is always made by picking someone from this list, never by typing a new person in.</p>
        </div>
        <div class="header-actions">
          <a href="{{ route('admin.members.uploads') }}" class="btn-users ghost" title="Every file this importer was ever given — spreadsheets and scanned forms alike">
            <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            Uploads
          </a>
          <a href="{{ route('admin.members.import') }}" class="btn-users ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import Members
          </a>
          <a href="{{ route('admin.members.create') }}" class="btn-users">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Member
          </a>
        </div>
      </div>

      <!-- Overview — each card filters the list below -->
      <div class="users-kpis">
        <a href="{{ route('admin.members.index') }}" class="users-kpi {{ $account === '' && $status === '' && $q === '' ? 'active' : '' }}">
          <span class="uk-label"><span class="uk-dot" style="background:#3f6b3e;"></span>All Members</span>
          <span class="uk-value">{{ $stats['total'] }}</span>
          <span class="uk-sub">{{ $stats['active'] }} active · {{ $stats['inactive'] }} inactive</span>
        </a>
        <a href="{{ route('admin.members.index', ['account' => 'none']) }}" class="users-kpi {{ $account === 'none' ? 'active' : '' }}">
          <span class="uk-label"><span class="uk-dot" style="background:#9dbe5c;"></span>No Account Yet</span>
          <span class="uk-value">{{ $stats['without_account'] }}</span>
          <span class="uk-sub">Listed, cannot sign in yet</span>
        </a>
        <a href="{{ route('admin.members.index', ['account' => 'linked']) }}" class="users-kpi {{ $account === 'linked' ? 'active' : '' }}">
          <span class="uk-label"><span class="uk-dot" style="background:#8b6a44;"></span>With Account</span>
          <span class="uk-value">{{ $stats['with_account'] }}</span>
          <span class="uk-sub">Already given sign-in access</span>
        </a>
        <a href="{{ route('admin.members.index', ['status' => 'inactive']) }}" class="users-kpi {{ $status === 'inactive' ? 'active' : '' }}">
          <span class="uk-label"><span class="uk-dot" style="background:#d4a42e;"></span>Inactive</span>
          <span class="uk-value">{{ $stats['inactive'] }}</span>
          <span class="uk-sub">Suspended member</span>
        </a>
      </div>

      <x-table-toolbar
          uid="members"
          work-table="membersTable"
          per-page="10"
          noun="members"
          search-placeholder="Search by name, member no, contact or location…"
          :filters="[
              ['key' => 'status', 'label' => 'Any status', 'options' => [['active', 'Active'], ['inactive', 'Inactive']]],
              ['key' => 'account', 'label' => 'Any account', 'options' => [['none', 'No account'], ['linked', 'Has account']]],
          ]"
          :sorts="[
              ['key' => 'name', 'dir' => 1, 'type' => 'str', 'label' => 'Name (A–Z)'],
              ['key' => 'name', 'dir' => -1, 'type' => 'str', 'label' => 'Name (Z–A)'],
              ['key' => 'joined', 'dir' => -1, 'type' => 'date', 'label' => 'Added (newest)'],
              ['key' => 'joined', 'dir' => 1, 'type' => 'date', 'label' => 'Added (oldest)'],
          ]"
      />

      <div class="users-panel">
        <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
        <div class="app-scroll" id="membersScroll">
          @if($members->isNotEmpty())
            <table id="membersTable" class="data-table">
              <thead>
                <tr>
                  <th class="col-sel"><input type="checkbox" id="memberSelAll" aria-label="Select every member matching the current filters"></th>
                  <th>Member</th>
                  <th>Contact</th>
                  <th>Location</th>
                  <th>Farming</th>
                  <th>Status</th>
                  <th>Account</th>
                  <th>Added</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="memberRows">
                @foreach($members as $m)
                  @php
                    $location = trim(implode(', ', array_filter([$m->barangay, $m->city])));
                    $contact = $m->email ?: ($m->phone ?: '—');
                    $farming = trim($m->land_ownership . ($m->years_farming !== null ? ' · ' . $m->years_farming . ' yr' . ($m->years_farming == 1 ? '' : 's') : ''), ' ·');
                    $accountLabel = $m->user ? ($m->user->role === 'store_owner' ? 'Store Owner' : 'Farmer') : '';
                    $search = strtolower($m->fullName() . ' ' . ($m->assoc_member_no ?? '') . ' ' . ($m->email ?? '') . ' ' . ($m->phone ?? '') . ' ' . $location . ' ' . $farming);
                  @endphp
                  <tr data-id="{{ $m->id }}" data-search="{{ $search }}"
                      data-status="{{ $m->status }}"
                      data-account="{{ $m->user_id !== null ? 'linked' : 'none' }}"
                      data-name="{{ strtolower($m->fullName()) }}"
                      data-joined="{{ $m->created_at?->format('Y-m-d') ?? '' }}">
                    <td>
                      <input type="checkbox" class="member-sel" value="{{ $m->id }}" aria-label="Select {{ $m->fullName() }}">
                    </td>
                    <td>
                      <div class="user-cell">
                        <x-user-avatar :user="$m" :size="36" />
                        <div style="min-width:0;">
                          <div class="u-name">{{ $m->fullName() }}</div>
                          <div class="u-mail">{{ $m->assoc_member_no ?: 'No member no.' }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="user-contact">{{ $contact }}</div>
                    </td>
                    <td>
                      <div class="user-contact">{{ $location ?: '—' }}</div>
                    </td>
                    <td>
                      <div class="user-contact">{{ $farming ?: '—' }}</div>
                    </td>
                    <td>
                      @if($m->status === \App\Models\Member::STATUS_ACTIVE)
                        <span class="verified-pill" style="background:#eef3ef;color:#3f7a4f;">
                          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                          Active
                        </span>
                      @else
                        <span class="verified-pill" style="background:#fdf2f2;color:#b91c1c;" title="Member is suspended">
                          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                          Inactive
                        </span>
                      @endif
                    </td>
                    <td>
                      @if($m->user_id !== null)
                        <span class="role-badge" style="background:#eef3ef;color:#3f7a4f;" title="{{ $m->user->name }} — {{ $m->user->email }}">
                          {{ $accountLabel }}
                        </span>
                      @else
                        <span class="role-badge" style="background:#fbf7ee;color:#8a6d34;">No account</span>
                      @endif
                    </td>
                    <td>
                      <span class="user-joined">{{ $m->created_at ? $m->created_at->format('M d, Y') : '—' }}</span>
                    </td>
                    <td>
                      <div class="user-actions">
                        @if($m->user_id === null)
                          <a class="acct-btn" href="{{ route('admin.users.create', ['member' => $m->id]) }}" title="Create a system account for this member">
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                            Create Account
                          </a>
                        @endif
                        <a class="icon-btn" href="{{ route('admin.members.edit', $m) }}" title="Edit member record">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div class="users-empty">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <p>
                @if($q !== '' || $status !== '' || $account !== '')
                  No members match this filter.
                @else
                  No members yet.<br>
                  Add people one at a time, or bring in a whole list from a file or a scan.
                @endif
              </p>
            </div>
          @endif
        </div>

        @if($members->isNotEmpty())
          <div class="app-scrollbar">
            <div class="app-scrollbar-track"></div>
            <div class="app-scrollbar-thumb"></div>
          </div>
        @endif
        </div>
      </div>
    </div>
  </div>

  <div class="bulk-selbar" id="memberSelBar" hidden>
    <span class="bulk-selbar-count"><b id="memberSelCount">0</b> selected</span>
    <form method="POST" action="{{ route('admin.members.archive') }}">
      @csrf
      <input type="hidden" name="ids" id="memberSelIds">
      <button type="button" class="bulk-selbar-clear" id="memberSelClear">Clear</button>
      <button type="submit" class="bulk-selbar-archive" id="memberSelArchive"
              data-aqui-tone="danger"
              data-aqui-title="Archive members?"
              data-aqui-confirm-label="Archive members"
              data-aqui-confirm="Selected member records will be archived, not permanently deleted. They will no longer appear in All Members.">
        Archive selected
      </button>
    </form>
  </div>

  <div class="user-toast" id="memberToast"></div>

  <script>
    (function () {
      var toast = document.getElementById('memberToast');
      var timer = null;

      function showToast(msg, isError) {
        toast.textContent = msg;
        toast.classList.toggle('error', !!isError);
        toast.classList.add('show');
        clearTimeout(timer);
        timer = setTimeout(function () { toast.classList.remove('show'); }, 3200);
      }

      @if (session('success'))
        showToast(@js(session('success')));
      @endif
      @if (session('error'))
        showToast(@js(session('error')), true);
      @endif

      var table = document.getElementById('membersTable');
      var bar = document.getElementById('memberSelBar');
      var selAll = document.getElementById('memberSelAll');
      if (table && bar && selAll) {
        var countEl = document.getElementById('memberSelCount');
        var idsInput = document.getElementById('memberSelIds');
        var clearBtn = document.getElementById('memberSelClear');
        var archiveBtn = document.getElementById('memberSelArchive');
        var selected = new Set();
        var matched = [];
        function rowId(row) { return String(row.getAttribute('data-id') || ''); }
        function sync() {
          table.querySelectorAll('tbody tr[data-id]').forEach(function (row) {
            var box = row.querySelector('.member-sel');
            if (box) box.checked = selected.has(rowId(row));
          });
          var matchedSelected = matched.filter(function (row) { return selected.has(rowId(row)); }).length;
          selAll.disabled = matched.length === 0;
          selAll.checked = matched.length > 0 && matchedSelected === matched.length;
          selAll.indeterminate = matchedSelected > 0 && matchedSelected < matched.length;
          bar.hidden = selected.size === 0;
          countEl.textContent = String(selected.size);
          idsInput.value = Array.from(selected).join(',');
          archiveBtn.disabled = selected.size === 0;
        }
        function receive(rows) {
          matched = rows || [];
          var keep = new Set(matched.map(rowId));
          selected.forEach(function (id) { if (!keep.has(id)) selected.delete(id); });
          sync();
        }
        document.addEventListener('ttb:render', function (e) {
          if (e.detail && e.detail.table === 'membersTable') receive(e.detail.matched);
        });
        function refresh() { receive(table.__ttbMatched || []); }
        document.addEventListener('turbo:load', refresh);
        selAll.addEventListener('change', function () {
          matched.forEach(function (row) {
            var id = rowId(row);
            if (selAll.checked) selected.add(id); else selected.delete(id);
          });
          sync();
        });
        table.addEventListener('change', function (e) {
          var box = e.target.closest ? e.target.closest('.member-sel') : null;
          if (!box) return;
          if (box.checked) selected.add(box.value); else selected.delete(box.value);
          sync();
        });
        clearBtn.addEventListener('click', function () { selected.clear(); sync(); });
        refresh();
      }
    })();
  </script>
@endsection
