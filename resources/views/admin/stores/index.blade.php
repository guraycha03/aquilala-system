@extends('layouts.sidebar')

@section('title', 'Stores')

@section('content')

  <style>
    .stores-page { position: relative; }
    .stores-page::before,
    .stores-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .stores-page::before { width: 340px; height: 340px; background: rgba(94,144,112,0.16); top: -80px; right: -80px; }
    .stores-page::after { width: 300px; height: 300px; background: rgba(93,194,87,0.12); bottom: -60px; left: -60px; }
    .stores-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 10px; }
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

    .stores-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .stores-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .stores-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .stores-kpi .kpi-value { font-size: 24px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1; }
    .stores-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }

    .users-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }
    /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
    .user-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
    /* Scoped to the table cell: an unscoped .user-avatar rule is emitted after
       the layout's own avatar rules and would repaint the sidebar profile. */
    .user-cell .user-avatar {
      width: 36px; height: 36px; border-radius: 10px; background: #f6f0e6; color: #a57f43;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .user-cell .user-avatar .lucide { width: 19px; height: 19px; stroke: currentColor; fill: none; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .user-cell .user-avatar svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .user-cell .u-name { font-weight: 600; color: #1e293b; white-space: nowrap; }
    .user-cell .u-mail { font-size: 11px; color: #94a3b8; margin-top: 1px; }
    .user-joined { font-size: 12px; color: #64748b; white-space: nowrap; }
    .user-contact { font-size: 12px; color: #64748b; white-space: nowrap; }
    .verified-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .verified-pill svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .user-actions { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
    .icon-btn {
      width: 30px; height: 30px; border-radius: 8px; border: none; background: transparent; color: #94a3b8;
      display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s ease;
    }
    .icon-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .icon-btn:hover { background: #eef3ef; color: #14532d; }
    .users-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
    .users-empty svg { width: 40px; height: 40px; stroke: #cbd5e1; fill: none; stroke-width: 1.5; margin-bottom: 10px; }

    @media (max-width: 768px) {
      .users-table th:nth-child(2), .users-table td:nth-child(2),
      .users-table th:nth-child(6), .users-table td:nth-child(6) { display: none; }
    }
  </style>

  <div class="stores-page">
    <div class="stores-content">

      <div class="header-row">
        <div>
          <h1>Stores</h1>
          <p>Accredited agricultural supply stores and their owners. Store owners are outsiders — they are registered
             here by an administrator, never created from the members list.</p>
        </div>
        <div class="header-actions">
          <a class="btn-users" href="{{ route('admin.stores.create') }}">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Store
          </a>
        </div>
      </div>

      <div class="stores-kpis kpi-strip">
        <div class="stores-kpi">
          <div class="kpi-label">Total Stores</div>
          <div class="kpi-value">{{ $totals['stores'] }}</div>
          <div class="kpi-sub">Distinct store registrations</div>
        </div>
        <div class="stores-kpi">
          <div class="kpi-label">Products Listed</div>
          <div class="kpi-value">{{ $totals['products'] }}</div>
          <div class="kpi-sub">Across all store catalogs</div>
        </div>
        <div class="stores-kpi">
          <div class="kpi-label">Inactive Stores</div>
          <div class="kpi-value">{{ $totals['inactive'] }}</div>
          <div class="kpi-sub">Disabled store accounts</div>
        </div>
      </div>

      <x-table-toolbar
        uid="stores"
        work-table="storesTable"
        per-page="10"
        noun="stores"
        search-placeholder="Search by store, owner, or location…"
        :filters="[
            ['key' => 'status', 'label' => 'Any status', 'options' => [['active', 'Active'], ['pending', 'Pending'], ['disabled', 'Disabled']]],
        ]"
        :sorts="[
            ['key' => 'store', 'dir' => 1, 'type' => 'str', 'label' => 'Store name (A–Z)'],
            ['key' => 'store', 'dir' => -1, 'type' => 'str', 'label' => 'Store name (Z–A)'],
            ['key' => 'approved', 'dir' => -1, 'type' => 'date', 'label' => 'Approved (newest)'],
            ['key' => 'approved', 'dir' => 1, 'type' => 'date', 'label' => 'Approved (oldest)'],
            ['key' => 'products', 'dir' => -1, 'type' => 'num', 'label' => 'Most products'],
            ['key' => 'papers', 'dir' => -1, 'type' => 'num', 'label' => 'Most paperwork'],
        ]"
      />

      <div class="users-panel">
        <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
        <div class="app-scroll" id="storesScroll">
        @if($storeOwners->isNotEmpty())
          <table id="storesTable" class="users-table data-table">
            <thead>
              <tr>
                <th>Store</th>
                <th>Location</th>
                <th>Products</th>
                <th>Papers</th>
                <th>Status</th>
                <th>Approved</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($storeOwners as $store)
                @php
                  $location = trim(implode(', ', array_filter([$store->barangay, $store->city, $store->province]))) ?: '—';
                  $statusValue = match ($store->status) {
                      \App\Models\User::STATUS_ACTIVE   => 'active',
                      \App\Models\User::STATUS_DISABLED => 'disabled',
                      default                           => 'pending',
                  };
                  $search = strtolower(($store->store_name ?? '') . ' ' . ($store->name ?? '') . ' ' . ($store->email ?? '') . ' ' . $location);
                @endphp
                <tr data-search="{{ $search }}"
                    data-status="{{ $statusValue }}"
                    data-store="{{ strtolower($store->store_name ?: $store->name) }}"
                    data-approved="{{ $store->approved_at?->format('Y-m-d') ?? '' }}"
                    data-papers="{{ $store->documents_count }}"
                    data-products="{{ $store->products_count }}">
                  <td>
                    <div class="user-cell">
                      <div class="user-avatar">
                        <i data-lucide="store" aria-label="Store"></i>
                      </div>
                      <div style="min-width:0;">
                        <div class="u-name">{{ $store->store_name ?: $store->name }}</div>
                        <div class="u-mail">Managed by {{ $store->name }} · {{ $store->email }}</div>
                      </div>
                    </div>
                  </td>
                  <td><div class="user-contact">{{ $location }}</div></td>
                  <td><div class="user-joined">{{ $store->products_count }}</div></td>
                  <td>
                    <div class="user-joined" title="{{ $store->documents_count }} legal paper(s) filed">
                      {{ $store->documents_count }}
                      @if($store->documents_count === 0)
                        <span style="color:#b45309;font-weight:700;">— none yet</span>
                      @endif
                    </div>
                  </td>
                  <td>
                    @if($statusValue === 'active')
                      <span class="verified-pill" style="background:#eef3ef;color:#3f7a4f;">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Active
                      </span>
                    @elseif($statusValue === 'pending')
                      <span class="verified-pill" style="background:#eef4ff;color:#3b6bb5;" title="Store owner awaiting activation">
                        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Pending
                      </span>
                    @elseif($statusValue === 'disabled')
                      <span class="verified-pill" style="background:#fdf2f2;color:#b91c1c;" title="Account disabled by an administrator">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        Disabled
                      </span>
                    @endif
                  </td>
                  <td><span class="user-joined">{{ $store->approved_at?->format('M d, Y') ?: '—' }}</span></td>
                  <td>
                    <div class="user-actions">
                      <a class="icon-btn" href="{{ route('admin.stores.edit', $store) }}" title="Manage store details and legal papers">
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
            <svg viewBox="0 0 24 24"><path d="M21 10c0-7-9-13-9-13s-9 6-9 13a9 9 0 0 0 18 0z"/><circle cx="12" cy="10" r="3"/><path d="M2 21h20"/></svg>
            <p style="margin:0;">No stores registered yet — use <b>Add Store</b> to register one.</p>
          </div>
        @endif
        </div>

        @if($storeOwners->isNotEmpty())
          <div class="app-scrollbar">
            <div class="app-scrollbar-track"></div>
            <div class="app-scrollbar-thumb"></div>
          </div>
        @endif
      </div>

    </div>
  </div>
@endsection