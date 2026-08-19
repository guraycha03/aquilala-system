@extends('layouts.sidebar')

@section('title', 'Registration Approvals')

@section('content')
  @php
    // SAMPLE placeholder data — swap with a real user-registration model later.
    $applications = [
        [
            'id' => 'A-1043', 'name' => 'Delia Marquez', 'role' => 'Farmer',
            'email' => 'delia.marquez@gmail.com', 'phone' => '0917 123 4567',
            'barangay' => 'Inararan', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '2.5 hectares', 'crops' => 'Rice, Vegetables',
            'date' => 'June 25, 2026', 'status' => 'pending',
            'note' => 'Registered for Wet Season marketplace. Farmer ID attached.',
        ],
        [
            'id' => 'A-1044', 'name' => 'Ramon Villanueva', 'role' => 'Farmer',
            'email' => 'ramon.v@gmail.com', 'phone' => '0918 234 5678',
            'barangay' => 'J.P. Laurel', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '1.8 hectares', 'crops' => 'Rice, Corn',
            'date' => 'July 2, 2026', 'status' => 'pending',
            'note' => 'Referred by Bulan Farm Cooperative.',
        ],
        [
            'id' => 'A-1045', 'name' => 'Liza Bautista', 'role' => 'User',
            'email' => 'liza.bautista@yahoo.com', 'phone' => '0919 345 6789',
            'barangay' => 'Aquino', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '—', 'crops' => '—',
            'date' => 'July 8, 2026', 'status' => 'pending',
            'note' => 'New applicant. Awaiting valid ID verification.',
        ],
        [
            'id' => 'A-1046', 'name' => 'Nena Reyes', 'role' => 'Farmer',
            'email' => 'nena.reyes@outlook.com', 'phone' => '0920 456 7890',
            'barangay' => 'Somagongsong', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '3.0 hectares', 'crops' => 'Coconut, Abaca',
            'date' => 'July 12, 2026', 'status' => 'pending',
            'note' => 'Coconut farmer. Barangay certificate attached.',
        ],
        [
            'id' => 'A-1031', 'name' => 'Maria Santos', 'role' => 'Farmer',
            'email' => 'maria.santos@gmail.com', 'phone' => '0917 555 0102',
            'barangay' => 'A. Bonifacio', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '1.2 hectares', 'crops' => 'Rice, Leafy Vegetables',
            'date' => 'June 18, 2026', 'status' => 'approved',
            'note' => 'Verified on-site. Farmer ID and land docs on file.',
        ],
        [
            'id' => 'A-1028', 'name' => 'Jose Ramos', 'role' => 'User',
            'email' => 'jose.ramos@gmail.com', 'phone' => '0918 555 0113',
            'barangay' => 'Managanaga', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '—', 'crops' => '—',
            'date' => 'June 2, 2026', 'status' => 'approved',
            'note' => 'Community member. ID and proof of address verified.',
        ],
        [
            'id' => 'A-1022', 'name' => 'Elena Cruz', 'role' => 'Farmer',
            'email' => 'elena.cruz@gmail.com', 'phone' => '0919 555 0144',
            'barangay' => 'Inararan', 'city' => 'Bulan', 'province' => 'Sorsogon',
            'farm_size' => '0.8 hectares', 'crops' => 'Vegetables, Herbs',
            'date' => 'May 28, 2026', 'status' => 'rejected',
            'note' => 'Incomplete registration documents. May re-submit.',
        ],
    ];

    $statusMeta = [
        'pending'  => ['Pending', '#a57f43', '#f6f0e6'],
        'approved' => ['Approved', '#63856c', '#eef3ef'],
        'rejected' => ['Rejected', '#a86464', '#f6ecec'],
    ];
  @endphp

  <style>
    .app-page { position: relative; }
    .app-page::before,
    .app-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .app-page::before { width: 340px; height: 340px; background: rgba(16,185,129,0.16); top: -90px; right: -90px; }
    .app-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.13); bottom: -70px; left: -70px; }
    .app-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }

    .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
    .reveal.in { opacity: 1; transform: translateY(0); }

    /* KPIs */
    .app-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .app-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .app-kpi .ak-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; margin-bottom: 6px; }
    .app-kpi .ak-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .app-kpi .ak-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    /* Panel + toolbar */
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

    /* Application cards */
    .app-list { padding: 6px 20px 20px; }
    .app-card {
      display: flex; align-items: center; gap: 16px; padding: 16px 8px;
      transition: background 0.2s ease, opacity 0.3s ease;
    }
    .app-card + .app-card { border-top: 1px solid #f1f3f2; }
    .app-card:hover { background: #fafbfa; border-radius: 12px; }
    .app-avatar {
      width: 46px; height: 46px; border-radius: 13px; background: rgb(172, 242, 121); color: #14532d;
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

    /* Details drawer */
    .app-overlay {
      position: fixed; inset: 0; background: rgba(15,23,42,0.35); z-index: 800;
      opacity: 0; visibility: hidden; transition: opacity 0.25s ease, visibility 0.25s;
    }
    .app-overlay.open { opacity: 1; visibility: visible; }
    .app-drawer {
      position: fixed; top: 0; right: 0; bottom: 0; width: 400px; max-width: 92vw; z-index: 900;
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
    .d-check { display: flex; align-items: center; gap: 9px; font-size: 12px; color: #475569; padding: 7px 0; }
    .d-check svg { width: 15px; height: 15px; stroke: #63856c; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .d-note {
      background: #f6f8f7; border: 1px solid #eef1ef; border-radius: 10px; padding: 12px 14px;
      font-size: 12px; color: #475569; line-height: 1.55;
    }
    .drawer-foot { padding: 16px 22px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }
    .drawer-foot .btn-app { flex: 1; justify-content: center; padding: 11px; }

    /* Toast */
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
      <div class="header-row reveal in">
        <div>
          <h1>Registration Approvals</h1>
          <p>Review and approve individual farmers and users applying to join the association's marketplace.</p>
        </div>
      </div>

      <!-- KPIs -->
      <section class="app-kpis">
        <div class="app-kpi reveal" data-reveal>
          <div class="ak-label">Needs Review</div>
          <div class="ak-value" id="kpiPending">0</div>
          <div class="ak-sub">registrations awaiting action</div>
        </div>
        <div class="app-kpi reveal" data-reveal>
          <div class="ak-label">Approved</div>
          <div class="ak-value" id="kpiApproved">0</div>
          <div class="ak-sub">verified this period</div>
        </div>
        <div class="app-kpi reveal" data-reveal>
          <div class="ak-label">Rejected</div>
          <div class="ak-value" id="kpiRejected">0</div>
          <div class="ak-sub">declined applications</div>
        </div>
        <div class="app-kpi reveal" data-reveal>
          <div class="ak-label">Verification Rate</div>
          <div class="ak-value" id="kpiRate">0%</div>
          <div class="ak-sub">of reviewed registrations</div>
        </div>
      </section>

      <div class="app-panel reveal" data-reveal>
        <div class="app-toolbar">
          <div class="app-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="searchInput" type="text" placeholder="Search farmer, name, or email…">
          </div>
          <div class="status-seg" id="statusSeg">
            <button data-status="all" class="active">All <span class="c" id="segAll">0</span></button>
            <button data-status="pending">Pending <span class="c" id="segPending">0</span></button>
            <button data-status="approved">Approved <span class="c" id="segApproved">0</span></button>
            <button data-status="rejected">Rejected <span class="c" id="segRejected">0</span></button>
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

  <!-- Details drawer -->
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
          <div><div class="d-label">Address</div><div class="d-value" id="drLocation">—</div></div>
        </div>
        <div class="d-row">
          <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <div><div class="d-label">Farm Details</div><div class="d-value" id="drFarmDetails">—</div></div>
        </div>
        <div class="d-sep"></div>

        <div class="d-sec-title">Submitted Documents</div>
        <div class="d-check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Valid government ID</div>
        <div class="d-check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Proof of address</div>
        <div class="d-check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Contact details verified</div>
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
      var apps = @json($applications);
      var status = 'all';
      var current = null;
      var confirmTimer = null;

      var listEl = document.getElementById('appList');
      var emptyEl = document.getElementById('appEmpty');
      var searchInput = document.getElementById('searchInput');

      function countBy(st) {
        return apps.filter(function (a) { return a.status === st; }).length;
      }
      function statusBadgeHTML(st) {
        var m = meta[st];
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
            var hay = (a.name + ' ' + a.role + ' ' + a.email + ' ' + a.barangay + ' ' + a.city).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
          }
          return true;
        });

        listEl.innerHTML = shown.map(function (a) {
          var m = meta[a.status];
          var actions = a.status === 'pending'
            ? '<button class="btn-app btn-view" data-act="view" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Details</button>' +
              '<button class="btn-app btn-ok" data-act="approve" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Approve</button>' +
              '<button class="btn-app btn-no" data-act="reject" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Reject</button>'
            : '<button class="btn-app btn-view" data-act="view" data-id="' + a.id + '"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Details</button>';

          return '<div class="app-card" data-id="' + a.id + '">' +
            '<div class="app-avatar">' + farmerIcon() + '</div>' +
            '<div class="app-mid">' +
              '<div class="app-name">' + a.name + '</div>' +
              '<div class="app-sub">' + a.barangay + ', ' + a.city + ', ' + a.province + '</div>' +
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
        var p = countBy('pending'), ok = countBy('approved'), no = countBy('rejected');
        document.getElementById('kpiPending').textContent = p;
        document.getElementById('kpiApproved').textContent = ok;
        document.getElementById('kpiRejected').textContent = no;
        var reviewed = ok + no;
        document.getElementById('kpiRate').textContent = reviewed ? Math.round((ok / reviewed) * 100) + '%' : '—';
        document.getElementById('segAll').textContent = apps.length;
        document.getElementById('segPending').textContent = p;
        document.getElementById('segApproved').textContent = ok;
        document.getElementById('segRejected').textContent = no;
      }

      var toastTimer = null;
      function showToast(msg) {
        document.getElementById('toastMsg').textContent = msg;
        document.getElementById('appToast').classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { document.getElementById('appToast').classList.remove('show'); }, 2600);
      }

      function setStatus(id, newStatus) {
        var a = apps.find(function (x) { return x.id === id; });
        if (!a) return;
        var old = a.status;
        a.status = newStatus;
        updateKpis();
        render();
        showToast(old === 'pending'
          ? (newStatus === 'approved' ? 'Registration approved — farmer can now access the marketplace.' : 'Registration rejected.')
          : 'Status updated to ' + meta[newStatus][0] + '.');
        if (current && current.id === id) { current = a; fillDrawer(); }
      }

      // Segmented status filter
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

      // Confirm-step actions
      function armConfirm(btn, cls) {
        btn.classList.add(cls);
        clearTimeout(confirmTimer);
        confirmTimer = setTimeout(function () { btn.classList.remove(cls); }, 2200);
      }

      listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var id = btn.dataset.id;
        var act = btn.dataset.act;
        if (act === 'view') { openDrawer(id); return; }
        if (btn.classList.contains('confirm-ok') || btn.classList.contains('confirm-no')) {
          setStatus(id, act === 'approve' ? 'approved' : 'rejected');
          return;
        }
        armConfirm(btn, act === 'approve' ? 'confirm-ok' : 'confirm-no');
        btn.innerHTML = (act === 'approve'
          ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Confirm approve?'
          : '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Confirm reject?');
      });

      // Drawer
      var drawer = document.getElementById('appDrawer');
      var overlay = document.getElementById('appOverlay');
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
        clearTimeout(confirmTimer);
      }
      function fillDrawer() {
        if (!current) return;
        document.getElementById('drId').textContent = 'Registration ' + current.id;
        document.getElementById('drName').textContent = current.name;
        document.getElementById('drStatus').innerHTML = statusBadgeHTML(current.status);
        document.getElementById('drApplicant').textContent = current.name;
        document.getElementById('drEmail').textContent = current.email;
        document.getElementById('drPhone').textContent = current.phone;
        document.getElementById('drLocation').textContent = current.barangay + ', ' + current.city + ', ' + current.province;
        document.getElementById('drFarmDetails').textContent = current.farm_size + ' — ' + current.crops;
        document.getElementById('drNote').textContent = current.note;
        var isPending = current.status === 'pending';
        document.getElementById('drApprove').style.display = isPending ? '' : 'none';
        document.getElementById('drReject').style.display = isPending ? '' : 'none';
      }
      document.getElementById('drawerClose').addEventListener('click', closeDrawer);
      overlay.addEventListener('click', closeDrawer);
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
      document.getElementById('drApprove').addEventListener('click', function () {
        if (current) setStatus(current.id, 'approved');
      });
      document.getElementById('drReject').addEventListener('click', function () {
        if (current) setStatus(current.id, 'rejected');
      });

      // Reveal
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-reveal]').forEach(function (el, i) {
          setTimeout(function () { el.classList.add('in'); }, 60 + i * 70);
        });
      });

      render();
      updateKpis();
    })();
  </script>
@endsection
