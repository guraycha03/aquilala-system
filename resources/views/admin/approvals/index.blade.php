@extends('layouts.sidebar')

@section('title', 'Registration Approvals')

@section('content')
  @php
    $pendingApplications = $applications->filter(fn($u) => $u->isPending());
    $approvedApplications = $applications->filter(fn($u) => !$u->isPending());

    $serialized = $applications->map(function ($u) {
        $location = trim(implode(', ', array_filter([$u->barangay, $u->city, $u->province])));
        $lang = $u->default_land_area
            ? number_format((float) $u->default_land_area, 2) . ' ' . ($u->default_land_area_unit ?? '')
            : '—';
        return [
            'id'        => $u->assoc_member_no ?: ('REG-' . str_pad((string) $u->id, 4, '0', STR_PAD_LEFT)),
            'user_id'   => $u->id,
            'name'      => $u->name,
            'role'      => 'Farmer',
            'email'     => $u->email,
            'phone'     => $u->phone,
            'address'   => trim(implode(', ', array_filter([$u->address, $u->barangay]))),
            'location'  => $location,
            'barangay'  => $u->barangay,
            'city'      => $u->city,
            'province'  => $u->province,
            'sex'       => $u->sex,
            'birth_date'=> $u->birth_date ? $u->birth_date->format('M d, Y') : '—',
            'civil_status' => $u->civil_status,
            'farm_location' => $u->farm_location,
            'land_area' => $lang,
            'land_ownership' => $u->land_ownership,
            'years_farming'  => $u->years_farming !== null ? $u->years_farming : '—',
            'farm_details' => trim(implode(' · ', array_filter([$lang, $u->farm_location]))),
            'date'      => $u->created_at ? $u->created_at->format('M d, Y') : '—',
            'note'      => trim(implode(' · ', array_filter([
                $u->sex ? ('Sex: ' . $u->sex) : null,
                $u->civil_status ? ('Civil status: ' . $u->civil_status) : null,
                $u->land_ownership ? ('Land: ' . $u->land_ownership) : null,
                $u->years_farming !== null ? ($u->years_farming . ' yr(s) farming') : null,
            ]))) ?: 'Awaiting activation of the member account.',
            'status'    => $u->isPending() ? 'pending' : 'approved',
        ];
    })->values()->all();

    $statusMeta = [
        'pending'  => ['Pending activation', '#a57f43', '#f6f0e6'],
        'approved' => ['Approved', '#63856c', '#eef3ef'],
        'rejected' => ['Rejected', '#a86464', '#f6ecec'],
    ];
  @endphp

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    .app-page { position: relative; }
    .app-page::before,
    .app-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .app-page::before { width: 340px; height: 340px; background: rgba(16,185,129,0.16); top: -90px; right: -90px; }
    .app-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.13); bottom: -70px; left: -70px; }
    .app-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 10px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }

    .app-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .app-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .app-kpi .ak-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; margin-bottom: 6px; }
    .app-kpi .ak-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .app-kpi .ak-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    .app-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }
    .app-toolbar {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 16px 20px; border-bottom: 1px solid #eef1ef;
    }
    .app-search {
      display: flex; align-items: center; gap: 8px; flex: 1; min-width: 200px; max-width: 320px;
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px; padding: 8px 12px;
    }
    .app-search svg { width: 14px; height: 14px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .app-search input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #334155; }
    .status-seg { display: inline-flex; background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 12px; padding: 4px; gap: 2px; flex-wrap: wrap; }
    .status-seg button {
      border: none; background: transparent; font-size: 12px; font-weight: 700; color: #64748b;
      padding: 8px 14px; border-radius: 9px; cursor: pointer; transition: all 0.2s ease; font-family: inherit;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .status-seg button .c { font-size: 10px; font-weight: 800; background: #e6ebe8; color: #64748b; border-radius: 999px; padding: 1px 7px; }
    .status-seg button.active { background: #143d23; color: #ffffff; box-shadow: 0 2px 8px rgba(20,61,35,0.22); }
    .status-seg button.active .c { background: rgba(255,255,255,0.2); color: #ffffff; }

    .app-list { padding: 6px 20px 20px; }
    .app-card {
      display: flex; align-items: center; gap: 16px; padding: 16px 8px;
      transition: background 0.2s ease, opacity 0.3s ease;
    }
    .app-card + .app-card { border-top: 1px solid #f1f3f2; }
    .app-card:hover { background: #fafbfa; border-radius: 12px; }
    .app-avatar {
      width: 46px; height: 46px; border-radius: 13px; background: #e6e6e6; color: #6f8f71;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .app-avatar svg { width: 21px; height: 21px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .app-mid { flex: 1; min-width: 0; }
    .app-name { font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .app-sub { font-size: 12px; color: #64748b; margin-top: 3px; }
    .app-meta { display: flex; align-items: center; gap: 12px; margin-top: 6px; flex-wrap: wrap; }
    .app-meta span { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: #94a3b8; }
    .app-meta svg { width: 11px; height: 11px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .app-right { display: flex; flex-direction: column; align-items: flex-end; gap: 9px; flex-shrink: 0; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize; }
    .app-actions { display: flex; gap: 8px; }

    .btn-app {
      display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; cursor: pointer;
      font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 9px; transition: all 0.18s ease;
      font-family: inherit;
    }
    .btn-app svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-view { background: #ffffff; border-color: #e2e8f0; color: #475569; }
    .btn-view:hover { background: #f6f8f7; }
    .btn-ok { background: #eef3ef; color: #63856c; border-color: #dbe5dd; }
    .btn-ok:hover { background: #63856c; color: #ffffff; }
    .btn-no { background: #f6ecec; color: #a86464; border-color: #efdcdc; }
    .btn-no:hover { background: #a86464; color: #ffffff; }
    .btn-app.confirm-ok { background: #63856c; color: #ffffff; border-color: #63856c; animation: appPulse 0.4s ease; }
    .btn-app.confirm-no { background: #a86464; color: #ffffff; border-color: #a86464; animation: appPulse 0.4s ease; }
    @keyframes appPulse { 0% { transform: scale(1); } 50% { transform: scale(1.06); } 100% { transform: scale(1); } }

    .app-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
    .app-empty svg { width: 38px; height: 38px; stroke: #cbd5e1; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .app-empty p { font-size: 13px; color: #64748b; margin: 10px 0 0 0; }

    .app-overlay {
      position: fixed; inset: 0; background: rgba(15,23,42,0.15); z-index: 800;
      opacity: 0; visibility: hidden; transition: opacity 0.25s ease, visibility 0.25s;
    }
    .app-overlay.open { opacity: 1; visibility: visible; }
    .app-drawer {
      position: fixed; top: 0; right: 0; bottom: 0; width: 420px; max-width: 92vw; z-index: 900;
      background: #ffffff; box-shadow: -12px 0 40px rgba(15,23,42,0.16);
      transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
      display: flex; flex-direction: column; overflow: hidden;
    }
    .app-drawer.open { transform: translateX(0); }
    .drawer-head {
      padding: 20px 22px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
    }
    .drawer-close {
      border: none; background: #f6f8f7; width: 32px; height: 32px; border-radius: 9px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0; transition: all 0.15s ease;
    }
    .drawer-close:hover { background: #eef1ef; color: #1e293b; }
    .drawer-close svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .drawer-body { flex: 1; overflow-y: auto; padding: 20px 22px; scrollbar-width: none; }
    .drawer-body::-webkit-scrollbar { display: none; }
    .d-info { display: flex; flex-direction: column; gap: 14px; }
    .d-row { display: flex; align-items: flex-start; gap: 12px; }
    .d-row svg { width: 15px; height: 15px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; margin-top: 2px; flex-shrink: 0; }
    .d-row .d-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 3px; }
    .d-row .d-value { font-size: 13px; color: #334155; line-height: 1.5; }
    .d-sep { height: 1px; background: #f1f5f9; margin: 4px 0; }
    .d-sec-title { font-size: 12px; font-weight: 700; color: #1e293b; letter-spacing: 0.03em; margin-bottom: 10px; }
    .d-note {
      background: #f6f8f7; border: 1px solid #eef1ef; border-radius: 10px; padding: 12px 14px;
      font-size: 12px; color: #475569; line-height: 1.55;
    }
    .drawer-foot { padding: 16px 22px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }
    .drawer-foot .btn-app { flex: 1; justify-content: center; padding: 11px; }

    .app-toast {
      position: fixed; bottom: 26px; left: 50%; transform: translate(-50%, 20px); z-index: 1000;
      background: #143d23; color: #ffffff; font-size: 13px; font-weight: 600;
      padding: 12px 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(20,61,35,0.35);
      display: flex; align-items: center; gap: 9px; opacity: 0; visibility: hidden;
      transition: all 0.25s cubic-bezier(0.4,0,0.2,1); white-space: nowrap; max-width: 90vw;
    }
    .app-toast.show { opacity: 1; visibility: visible; transform: translate(-50%, 0); }
    .app-toast svg { width: 16px; height: 16px; stroke: #6aeb2f; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

    @media (max-width: 768px) {
      .app-card { flex-direction: column; align-items: flex-start; gap: 12px; }
      .app-right { align-items: flex-start; width: 100%; }
      .app-actions { width: 100%; }
      .btn-app { flex: 1; justify-content: center; }
    }
  </style>

  <div class="app-page">
    <div class="app-content">
      <div class="header-row">
        <div>
          <h1>Registration Approvals</h1>
          <p>Review member applications and grant access after approving their registration request.</p>
        </div>
      </div>

      <section class="app-kpis">
        <div class="app-kpi">
          <div class="ak-label">Needs Review</div>
          <div class="ak-value" id="kpiPending">{{ $pendingApplications->count() }}</div>
          <div class="ak-sub">Registrations awaiting action</div>
        </div>
        <div class="app-kpi">
          <div class="ak-label">Approved</div>
          <div class="ak-value" id="kpiApproved">{{ $approvedApplications->count() }}</div>
          <div class="ak-sub">Members approved</div>
        </div>
        <div class="app-kpi">
          <div class="ak-label">Rejected</div>
          <div class="ak-value" id="kpiRejected">0</div>
          <div class="ak-sub">Declined applications</div>
        </div>
        <div class="app-kpi">
          <div class="ak-label">Total Applications</div>
          <div class="ak-value" id="kpiTotal">{{ $applications->count() }}</div>
          <div class="ak-sub">All submitted registrations</div>
        </div>
      </section>

      <div class="app-panel">
        <div class="app-toolbar" data-fc>
          <div class="app-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="searchInput" type="text" placeholder="Search farmer, name, or email…">
          </div>
          <button type="button" class="btn-filter" data-fc-toggle aria-expanded="false"
                  aria-label="Show filter options" title="Filters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span class="btn-filter-dot" data-fc-dot hidden></span>
          </button>
          <div class="filter-panel" data-fc-panel hidden>
            <div class="status-seg" id="statusSeg">
              <button data-status="all" class="active">All <span class="c" id="segAll">0</span></button>
              <button data-status="pending">Pending <span class="c" id="segPending">0</span></button>
              <button data-status="approved">Approved <span class="c" id="segApproved">0</span></button>
            </div>
          </div>
        </div>

        <div class="app-list" id="appList"></div>
        <div class="app-empty" id="appEmpty" style="display:none;">
          <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/></svg>
          <p>No registrations match your search or filter.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="app-overlay" id="appOverlay"></div>
  <aside class="app-drawer" id="appDrawer" aria-hidden="true">
    <div class="drawer-head">
      <div>
        <div id="drId" style="font-size:11px;color:#94a3b8;font-weight:700;letter-spacing:0.06em;">—</div>
        <div id="drName" style="font-size:18px;font-weight:800;color:#1e293b;margin-top:3px;">Applicant</div>
        <div id="drStatus" style="margin-top:7px;"></div>
      </div>
      <button class="drawer-close" id="drawerClose">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="drawer-body">
      <div class="d-info">
        <div class="d-sec-title">Applicant Information</div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <div><div class="d-label">Name</div><div class="d-value" id="drApplicant">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <div><div class="d-label">Email</div><div class="d-value" id="drEmail">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <div><div class="d-label">Contact Number</div><div class="d-value" id="drPhone">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <div><div class="d-label">Home Address</div><div class="d-value" id="drLocation">—</div></div>
        </div>
        <div class="d-sep"></div>

        <div class="d-sec-title">Personal Details</div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <div><div class="d-label">Sex / Civil Status</div><div class="d-value" id="drPersonal">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <div><div class="d-label">Date of Birth</div><div class="d-value" id="drBirth">—</div></div>
        </div>
        <div class="d-sep"></div>

        <div class="d-sec-title">Farm Details</div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <div><div class="d-label">Farm Location</div><div class="d-value" id="drFarmLocation">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M12 22v-9"/><path d="M8 6l4-4 4 4"/><path d="M17 6v6H7V6"/><path d="M5 22h14"/></svg>
          <div><div class="d-label">Land Area</div><div class="d-value" id="drLandArea">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01"/><path d="M9 13h.01"/><path d="M9 17h.01"/></svg>
          <div><div class="d-label">Ownership / Experience</div><div class="d-value" id="drExperience">—</div></div>
        </div>
        <div class="d-sep"></div>

        <div class="d-sec-title">Review Note</div>
        <div class="d-note" id="drNote">—</div>
      </div>
    </div>
    <div class="drawer-foot">
      <button class="btn-app btn-no" id="drReject"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Reject</button>
      <button class="btn-app btn-ok" id="drApprove"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Approve</button>
    </div>
  </aside>

  <div class="app-toast" id="appToast"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><span id="toastMsg"></span></div>

  <script>
    (function () {
      var meta = @json($statusMeta);
      var apps = @json($serialized);
      var status = 'all';
      var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      var listEl = document.getElementById('appList');
      var emptyEl = document.getElementById('appEmpty');
      var searchInput = document.getElementById('searchInput');

      @if (session('success'))
        showToast(@js(session('success')));
      @endif
      @if (session('error'))
        showToast(@js(session('error')));
      @endif

      function statusBadgeHTML(st) {
        var m = meta[st] || meta.pending;
        return '<span class="status-badge" style="background:' + m[2] + ';color:' + m[1] + ';">' + m[0] + '</span>';
      }
      function farmerIcon() {
        return '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
      }

      function render() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var shown = apps.filter(function (a) {
          if (status !== 'all' && a.status !== status) return false;
          if (q) {
            var hay = (a.name + ' ' + a.email + ' ' + a.barangay + ' ' + a.city + ' ' + a.province).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
          }
          return true;
        });

        listEl.innerHTML = shown.map(function (a) {
          var m = meta[a.status] || meta.pending;
          var actions = a.status === 'pending'
            ? '<button class="btn-app btn-view" data-act="view" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Details</button>' +
              '<button class="btn-app btn-ok" data-act="approve" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Approve</button>' +
              '<button class="btn-app btn-no" data-act="reject" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Reject</button>'
            : '<button class="btn-app btn-view" data-act="view" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Details</button>';

          return '<div class="app-card" data-id="' + a.id + '">' +
            '<div class="app-avatar">' + farmerIcon() + '</div>' +
            '<div class="app-mid">' +
              '<div class="app-name">' + a.name + '</div>' +
              '<div class="app-sub">' + (a.location || 'Location not specified') + '</div>' +
              '<div class="app-meta">' +
                '<span><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + a.role + '</span>' +
                '<span><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>' + a.email + '</span>' +
                '<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' + a.date + '</span>' +
              '</div>' +
            '</div>' +
            '<div class="app-right">' +
              statusBadgeHTML(a.status) +
              '<div class="app-actions">' + actions + '</div>' +
            '</div>' +
          '</div>';
        }).join('');

        emptyEl.style.display = shown.length ? 'none' : 'block';
      }

      function updateKpis() {
        var p = apps.filter(function (x) { return x.status === 'pending'; }).length;
        var ok = apps.filter(function (x) { return x.status === 'approved'; }).length;
        document.getElementById('kpiPending').textContent = p;
        document.getElementById('kpiApproved').textContent = ok;
        document.getElementById('kpiRejected').textContent = 0;
        document.getElementById('kpiTotal').textContent = apps.length;
        document.getElementById('segAll').textContent = apps.length;
        document.getElementById('segPending').textContent = p;
        document.getElementById('segApproved').textContent = ok;
      }

      var toastTimer = null;
      function showToast(msg) {
        if (!msg) return;
        document.getElementById('toastMsg').textContent = msg;
        document.getElementById('appToast').classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { document.getElementById('appToast').classList.remove('show'); }, 3200);
      }

      function doAction(userId, act, btn) {
        btn.disabled = true;
        fetch('/admin/users/' + userId + '/' + act, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function (response) { return response.json().then(function (d) { return { ok: response.ok, data: d }; }); })
        .then(function (result) {
          if (!result.ok) {
            showToast(result.data.message || (act === 'approve' ? 'Could not approve.' : 'Could not reject.'));
            btn.disabled = false;
            return;
          }
          showToast(result.data.message || (act === 'approve' ? 'Registration approved — secure link emailed.' : 'Registration rejected.'));
          var idx = apps.findIndex(function (x) { return String(x.user_id) === String(userId); });
          if (idx !== -1) apps.splice(idx, 1);
          updateKpis();
          render();
          if (current && String(current.user_id) === String(userId)) closeDrawer();
        })
        .catch(function () {
          showToast('Network failure — please try again.');
          btn.disabled = false;
        });
      }

      document.querySelectorAll('#statusSeg button').forEach(function (b) {
        b.addEventListener('click', function () {
          document.querySelectorAll('#statusSeg button').forEach(function (x) { x.classList.remove('active'); });
          b.classList.add('active');
          status = b.dataset.status;
          render();
        });
      });

      var searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(render, 120);
      });

      function armConfirm(btn, cls) {
        btn.classList.add(cls);
        setTimeout(function () { btn.classList.remove(cls); }, 2200);
      }

      listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var id = btn.dataset.id;
        var act = btn.dataset.act;
        if (act === 'view') { openDrawer(id); return; }
        if (btn.classList.contains('confirm-ok') || btn.classList.contains('confirm-no')) {
          var a = apps.find(function (x) { return x.id === id; });
          if (a) doAction(a.user_id, act === 'approve' ? 'approve' : 'reject', btn);
          return;
        }
        armConfirm(btn, act === 'approve' ? 'confirm-ok' : 'confirm-no');
        btn.innerHTML = (act === 'approve'
          ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Confirm approve?'
          : '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Confirm reject?');
      });

      var drawer = document.getElementById('appDrawer');
      var overlay = document.getElementById('appOverlay');
      var current = null;
      function openDrawer(id) {
        current = apps.find(function (x) { return x.id === id; }) || null;
        fillDrawer();
        drawer.classList.add('open');
        overlay.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
      }
      function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
      }
      function fillDrawer() {
        if (!current) return;
        document.getElementById('drId').textContent = 'Registration ' + current.id;
        document.getElementById('drName').textContent = current.name;
        document.getElementById('drStatus').innerHTML = statusBadgeHTML(current.status);
        document.getElementById('drApplicant').textContent = current.name;
        document.getElementById('drEmail').textContent = current.email;
        document.getElementById('drPhone').textContent = current.phone;
        document.getElementById('drLocation').textContent = current.location || '—';
        document.getElementById('drPersonal').textContent = [current.sex, current.civil_status].filter(Boolean).join(' · ') || '—';
        document.getElementById('drBirth').textContent = current.birth_date;
        document.getElementById('drFarmLocation').textContent = current.farm_location || '—';
        document.getElementById('drLandArea').textContent = current.land_area;
        document.getElementById('drExperience').textContent = [current.land_ownership, current.years_farming !== '—' ? current.years_farming + ' year(s)' : null].filter(Boolean).join(' · ') || '—';
        document.getElementById('drNote').textContent = current.note;
        var isPending = current.status === 'pending';
        document.getElementById('drApprove').style.display = isPending ? '' : 'none';
        document.getElementById('drReject').style.display = isPending ? '' : 'none';
      }
      document.getElementById('drawerClose').addEventListener('click', closeDrawer);
      overlay.addEventListener('click', closeDrawer);
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
      document.getElementById('drApprove').addEventListener('click', function () {
        if (current) doAction(current.user_id, 'approve', this);
      });
      document.getElementById('drReject').addEventListener('click', function () {
        if (current) doAction(current.user_id, 'reject', this);
      });

      render();
      updateKpis();
    })();
  </script>
@endsection
