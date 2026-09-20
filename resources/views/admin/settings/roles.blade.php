@extends('layouts.sidebar')

@section('title', 'Roles & Access')

@section('content')

  <style>
    .roles-page { position: relative; }
    .roles-page::before,
    .roles-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .roles-page::before { width: 340px; height: 340px; background: rgba(134,121,159,0.14); top: -80px; right: -80px; }
    .roles-page::after { width: 300px; height: 300px; background: rgba(166,127,67,0.12); bottom: -60px; left: -60px; }
    .roles-content { position: relative; z-index: 1; }

    .roles-head { margin: 0 0 10px; }
    .roles-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .roles-head p { font-size: 13px; color: #64748b; margin: 0; }

    .roles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .scope-card {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.6); border-radius: 16px; padding: 18px 20px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .scope-top { margin-bottom: 12px; }
    .scope-badge {
      display: inline-block; font-size: 9.5px; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.09em; padding: 4px 10px; border-radius: 999px;
    }
    .scope-card h4 { font-size: 14.5px; font-weight: 800; }
    .scope-body { font-size: 12.5px; line-height: 1.65; color: #64748b; margin: 0; }

    .matrix-table td { white-space: nowrap; }
    .perm-cell {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 11.5px; font-weight: 700; padding: 4px 11px; border-radius: 999px; white-space: nowrap;
    }
    .perm-cell svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .perm-cell.full { background: #eef3ef; color: #3f7a4f; }
    .perm-cell.part { background: #fef9ec; color: #a57f43; }
    .perm-cell.none { background: #f1f5f9; color: #94a3b8; }
    @media (max-width: 720px) {
      .matrix-table th:nth-child(2), .matrix-table td:nth-child(2) { display: none; }
    }

    .roles-stack { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px; }
    .roles-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      overflow: hidden;
    }
    .roles-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .roles-panel .panel-head h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .roles-panel .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    .roles-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .roles-table th {
      background: #fafbfa; padding: 10px 18px; color: #94a3b8; font-size: 10.5px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.08em; text-align: left; border-bottom: 1px solid #eef1ef;
    }
    .roles-table td { padding: 12px 18px; border-bottom: 1px solid #f1f5f9; color: #3d4a41; vertical-align: middle; }
    .roles-table tr:last-child td { border-bottom: none; }
    .role-avatar {
      width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: 12px; margin-right: 10px;
    }
    .role-cell { display: flex; align-items: center; }
    .role-cell b { font-size: 13.5px; color: #1e293b; font-weight: 700; }
    .role-cell span { display: block; font-size: 11.5px; color: #64748b; margin-top: 1px; }

    .status-chip {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
      border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; }
    .status-chip.active { background: #eef3ef; color: #63856c; } .status-chip.active .dot { background: #16a34a; }
    .status-chip.disabled { background: #f6ecec; color: #a86464; } .status-chip.disabled .dot { background: #dc2626; }
    .status-chip.pending { background: #eef4ff; color: #3b6bb5; } .status-chip.pending .dot { background: #2563eb; }

    .roles-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #94a3b8; font-size: 13px; text-align: center; }

    @media (max-width: 800px) {
      .roles-table th, .roles-table td { padding: 10px 12px; }
    }
  </style>

  @php
    $statusChip = function ($status) {
        return match ($status) {
            \App\Models\User::STATUS_ACTIVE   => ['active', 'Active'],
            \App\Models\User::STATUS_DISABLED => ['disabled', 'Disabled'],
            \App\Models\User::STATUS_PENDING  => ['pending', 'Pending'],
            default => ['pending', ucwords(str_replace('_', ' ', $status))],
        };
    };

    $avatarBg = ['#86799f', '#a57f43', '#63856c', '#4f6f8f'][0];

    $roleScopes = [
        'officer' => [
            'title' => 'Association Officer (Admin)',
            'color' => '#86799f',
            'bg'    => '#f1eef6',
            'scope' => 'Full authority over every module — member accounts, approvals, stores, catalog moderation, harvest records, quotations, analytics, and system settings.',
        ],
        'store_owner' => [
            'title' => 'Store Owner',
            'color' => '#a57f43',
            'bg'    => '#f6f0e6',
            'scope' => 'Manages their own accredited supply store — catalogs, input formulas per crop, stock levels, and assist farmers with cost quotations.',
        ],
        'farmer' => [
            'title' => 'Farmer (IA Member)',
            'color' => '#63856c',
            'bg'    => '#eef3ef',
            'scope' => 'Association member access — track crops and seasons, submit harvest reports, request cost quotations, review products, and view seasonal guidance.',
        ],
    ];

    $permissionMatrix = [
        ['Member account area',                 'View & manage all accounts, approvals, and store directory', 'Full', 'View own store only', '—'],
        ['Store catalog & reviews',             'List products and moderate farmer reviews',                 'Full', 'Manage own catalog', 'Review & rate'],
        ['Input formulas',                      'Crop/season application rates per hectare',                 'Full', 'Create & edit own', 'View'],
        ['Harvest reports',                     'Record and view seasonal yield and expenses',                'Full', '—', 'Submit own'],
        ['Cost quotations',                     'Farmer crop-cost estimates',                                 'Full', 'Compute for farmers', 'Request'],
        ['Analytics & expenses',                'Demand, harvest, and budget trends',                         'Full', 'View own store data', '—'],
        ['System settings & audit logs',        'Policies, roles & access, audit trail',                      'Full', '—', '—'],
    ];
  @endphp

  <div class="roles-page">
    <div class="roles-content">

      <div class="roles-head">
        <h1>Roles &amp; Access</h1>
        <p>Role scope definitions, the permission matrix, and currently assigned accounts.</p>
      </div>

      @include('partials.admin-tabs', [
        'tabs' => [
          ['key' => 'policies', 'label' => 'Policies',       'url' => route('admin.settings.index')],
          ['key' => 'logs',     'label' => 'Logs',           'url' => route('admin.audit.index')],
          ['key' => 'roles',    'label' => 'Roles & Access', 'url' => route('admin.settings.roles')],
        ],
        'activeTab' => 'roles',
      ])

      <!-- Role scope definition cards -->
      <div class="roles-grid">
        @foreach($roleScopes as $key => $rs)
          <div class="scope-card">
            <div class="scope-top">
              <span class="scope-badge" style="background:{{ $rs['bg'] }};color:{{ $rs['color'] }};">{{ strtoupper(str_replace('_', ' ', $key)) }}</span>
            </div>
            <h4 style="color:{{ $rs['color'] }};margin:0 0 8px 0;">{{ $rs['title'] }}</h4>
            <p class="scope-body">{{ $rs['scope'] }}</p>
          </div>
        @endforeach
      </div>

      <!-- Permission matrix -->
      <div class="roles-panel" style="margin-bottom:22px;">
        <div class="panel-head">
          <div>
            <h3>Permission Matrix</h3>
            <p>What each role can do across the association portal</p>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="roles-table matrix-table">
            <thead>
              <tr>
                <th>Module</th>
                <th>Capability</th>
                <th>Officer</th>
                <th>Store Owner</th>
                <th>Farmer</th>
              </tr>
            </thead>
            <tbody>
              @foreach($permissionMatrix as $row)
                <tr>
                  <td style="font-weight:700;color:#1e293b;white-space:nowrap;">{{ $row[0] }}</td>
                  <td style="font-size:12px;color:#64748b;">{{ $row[1] }}</td>
                  <td><span class="perm-cell full"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>{{ $row[2] }}</span></td>
                  <td><span class="perm-cell {{ $row[3] === '—' ? 'none' : 'part' }}">{{ $row[3] }}</span></td>
                  <td><span class="perm-cell {{ $row[4] === '—' ? 'none' : 'part' }}">{{ $row[4] }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="roles-stack">
        <div class="roles-panel">
          <div class="panel-head">
            <h3>Association Officers</h3>
            <p>Administrator accounts with full system access</p>
          </div>
          @if($officers->isEmpty())
            <div class="roles-empty">No administrator accounts yet.</div>
          @else
            <table class="roles-table">
              <thead>
                <tr>
                  <th>Officer</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Added</th>
                </tr>
              </thead>
              <tbody>
                @foreach($officers as $officer)
                  @php [$chip, $label] = $statusChip($officer->status); @endphp
                  <tr>
                    <td>
                      <div class="role-cell">
                        <div class="role-avatar" style="background:#86799f;">{{ strtoupper(substr($officer->name, 0, 1)) }}</div>
                        <div><b>{{ $officer->name }}</b></div>
                      </div>
                    </td>
                    <td style="font-size:12px;color:#64748b;">{{ $officer->email }}</td>
                    <td><span class="status-chip {{ $chip }}"><span class="dot"></span>{{ $label }}</span></td>
                    <td>{{ $officer->created_at?->format('M d, Y') ?: '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>

        <div class="roles-panel">
          <div class="panel-head">
            <h3>Store Owners</h3>
            <p>Accredited supply store operators</p>
          </div>
          @if($storeOwners->isEmpty())
            <div class="roles-empty">No store owner accounts yet.</div>
          @else
            <table class="roles-table">
              <thead>
                <tr>
                  <th>Owner</th>
                  <th>Store</th>
                  <th>Status</th>
                  <th>Approved</th>
                </tr>
              </thead>
              <tbody>
                @foreach($storeOwners as $owner)
                  @php [$chip, $label] = $statusChip($owner->status); @endphp
                  <tr>
                    <td>
                      <div class="role-cell">
                        <div class="role-avatar" style="background:#a57f43;">{{ strtoupper(substr($owner->name, 0, 1)) }}</div>
                        <div><b>{{ $owner->name }}</b><span>{{ $owner->email }}</span></div>
                      </div>
                    </td>
                    <td style="font-weight:600;color:#1e293b;">{{ $owner->store_name ?: '—' }}</td>
                    <td><span class="status-chip {{ $chip }}"><span class="dot"></span>{{ $label }}</span></td>
                    <td>{{ $owner->approved_at?->format('M d, Y') ?: '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>
      </div>

    </div>
  </div>
@endsection