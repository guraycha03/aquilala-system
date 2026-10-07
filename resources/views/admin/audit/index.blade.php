@extends('layouts.sidebar')

@section('title', 'Audit Logs')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'general',   'label' => 'General',              'url' => route('admin.settings.index')],
      ['key' => 'seasons',   'label' => 'Seasons',              'url' => route('admin.settings.seasons')],
      ['key' => 'interface', 'label' => 'Interface',            'url' => route('admin.settings.interface')],
      ['key' => 'weather',   'label' => 'Weather Log',          'url' => route('admin.settings.weather')],
      ['key' => 'logs',      'label' => 'Audit Log',            'url' => route('admin.audit.index')],
    ],
    'activeTab' => 'logs',
  ])

  <style>
    .audit-page { position: relative; }
    .audit-page::before,
    .audit-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .audit-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.15); top: -80px; right: -80px; }
    .audit-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.12); bottom: -60px; left: -60px; }
    .audit-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 10px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-audit {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
      background: rgb(172, 242, 121); border: 1px solid rgba(20,61,35,0.12);
      color: #14532d; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08);
    }
    .btn-audit:hover { background: rgb(160, 232, 108); transform: translateY(-1px); }
    .btn-audit svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-audit.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-audit.ghost:hover { background: #f0fdf4; }
    .btn-audit:disabled {
      background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8;
      cursor: not-allowed; box-shadow: none; transform: none;
    }

    .audit-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .audit-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .audit-kpi .ak-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; margin-bottom: 6px; }
    .audit-kpi .ak-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .audit-kpi .ak-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    .audit-filters {
      display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
      margin-bottom: 16px;
    }
    .audit-filters input[type="search"],
    .audit-filters select {
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px;
      padding: 9px 12px; font-size: 13px; color: #334155; outline: none;
    }
    .audit-filters input[type="search"] { min-width: 220px; flex: 1; max-width: 340px; }
    .audit-filters input[type="search"]:focus,
    .audit-filters select:focus { border-color: #143d23; box-shadow: 0 0 0 2px #dcfce7; }
    .audit-filters button,
    .audit-filters a {
      font-size: 13px; padding: 9px 14px; border-radius: 10px; text-decoration: none;
      border: 1px solid #e6ebe8; background: #ffffff; color: #374151; cursor: pointer;
    }
    .audit-filters button { background: #143d23; border-color: #143d23; color: #ffffff; font-weight: 600; }
    .audit-filters button:hover { background: #1b5230; }
    .audit-filters a.clear { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .audit-filters a.clear:hover { background: #fee2e2; }

    .audit-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }

    .audit-table { width: 100%; border-collapse: collapse; text-align: left; }
    .audit-table thead tr { background: #fafbfa; }
    .audit-table th { padding: 13px 20px; color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .audit-table td { padding: 14px 20px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .audit-table tbody tr { transition: background 0.15s ease; }
    .audit-table tbody tr:hover { background: #fafbfa; }
    .audit-table tbody tr:last-child td { border-bottom: none; }
    .audit-ts { font-size: 12px; color: #64748b; font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .audit-actor { display: flex; align-items: center; gap: 10px; }
    .audit-avatar {
      width: 32px; height: 32px; border-radius: 9px; background: rgb(172, 242, 121); color: #14532d;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .audit-avatar svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .audit-role { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; background: #f1f5f9; color: #64748b; margin-left: 6px; }
    .audit-event { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .audit-badge { display: inline-block; padding: 4px 12px; border-radius: 7px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; cursor: help; }

    /* Event hover tooltip */
    .audit-tooltip {
      position: fixed; z-index: 9999; background: #ffffff; color: #334155;
      padding: 10px 14px; border-radius: 10px; font-size: 12px; line-height: 1.5;
      border: 1px solid #e6ebe8; box-shadow: 0 8px 24px rgba(15,23,42,0.14); max-width: 280px;
      opacity: 0; pointer-events: none; transition: opacity 0.15s ease;
    }
    .audit-tooltip b { display: block; font-weight: 700; color: #1e293b; margin-bottom: 3px; }
    .audit-tooltip span { display: block; color: #94a3b8; font-size: 11px; }
    .audit-module { font-size: 12px; color: #94a3b8; white-space: nowrap; }
    .audit-ip { font-size: 12px; color: #64748b; font-family: ui-monospace, monospace; white-space: nowrap; }

    .audit-footer {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 14px 20px; border-top: 1px solid #eef1ef;
    }
    .audit-footer .rows-info { font-size: 12px; color: #94a3b8; }

    .audit-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
    .audit-empty h3 { margin: 0 0 6px; color: #64748b; font-size: 15px; }

    @media (max-width: 768px) {
      .audit-table th:nth-child(4), .audit-table td:nth-child(4),
      .audit-table th:nth-child(5), .audit-table td:nth-child(5) { display: none; }
    }
  </style>

  <div class="audit-page">
    <div class="audit-content">
      <div class="header-row">
        <div>
          <h1>System Audit Logs</h1>
          <p>Every recorded action across the system — sign-ins, account changes, product CRUD, quotations and moderation — straight from the activity trail.</p>
        </div>
        <div class="header-actions">
          <button id="exportBtn" class="btn-audit ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </button>
        </div>
      </div>

      <!-- Summary KPIs (computed from activity_logs) -->
      <div class="audit-kpis">
        <div class="audit-kpi">
          <div class="ak-label">Total Events</div>
          <div class="ak-value">{{ number_format($kpis['total']) }}</div>
          <div class="ak-sub">All-time records</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Today</div>
          <div class="ak-value">{{ number_format($kpis['today']) }}</div>
          <div class="ak-sub">Events logged today</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Authentications</div>
          <div class="ak-value">{{ number_format($kpis['authentications']) }}</div>
          <div class="ak-sub">Sign-ins &amp; sign-outs</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Modifications</div>
          <div class="ak-value">{{ number_format($kpis['changes']) }}</div>
          <div class="ak-sub">Creates, updates &amp; deletes</div>
        </div>
      </div>

      <!-- Server-side filters (search, category, date range, user) -->
      <form method="GET" class="audit-filters">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search description, event, or user…">
        <select name="cat">
          <option value="">All event types</option>
          @foreach($categories as $key => $label)
            <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select name="period">
          <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All dates</option>
          <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
          <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 days</option>
          <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 days</option>
          <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This month</option>
        </select>
        <select name="user_id">
          <option value="">All users</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
              {{ $u->name }} ({{ ucfirst(str_replace('_', ' ', $u->role)) }})
            </option>
          @endforeach
        </select>
        <button type="submit">Apply</button>
        @if($search !== '' || $category !== '' || $period !== 'all' || $userId !== '')
          <a href="{{ route('admin.audit.index') }}" class="clear">Clear</a>
        @endif
      </form>

      <div class="audit-panel">
        <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
          <div class="app-scroll">
            <table id="auditTable" class="audit-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Actor</th>
                <th>Event</th>
                <th>Module</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody id="auditRows">
              @forelse($activities as $activity)
                @php
                  $actor = $activity->actor ?? $activity->user;
                  $actorName = $activity->actor_name;
                  $role = $actor?->role ? ucfirst(str_replace('_', ' ', $actor->role)) : 'System';
                  // Notification-style rows file themselves under the recipient;
                  // show who actually acted when that differs from the recipient.
                  $recipient = ($activity->actor && $activity->user && $activity->actor->id !== $activity->user->id)
                      ? $activity->user?->name
                      : null;
                @endphp
                <tr data-cat="{{ $activity->category }}"
                    data-ts="{{ $activity->created_at->format('Y-m-d H:i:s') }}"
                    data-epoch="{{ $activity->created_at->getTimestamp() }}"
                    data-actor="{{ strtolower($actorName) }}"
                    data-module="{{ strtolower($activity->category_label) }}"
                    data-search="{{ strtolower($actorName . ' ' . $role . ' ' . $activity->description . ' ' . $activity->category_label . ' ' . $activity->type) }}">
                  <td class="audit-ts">{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                  <td>
                    <div class="audit-actor">
                      <div class="audit-avatar">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                      </div>
                      <div>
                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap;">{{ $actorName }}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 1px;">{{ $role }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="audit-event">
                      <span class="audit-badge has-tip"
                            data-tip="{{ $activity->description }}"
                            data-sub="{{ $activity->type }}{{ $recipient ? ' · for ' . $recipient : '' }}{{ $activity->ip_address ? ' · ' . $activity->ip_address : '' }}"
                            style="background: {{ $activity->bg }}; color: {{ $activity->color }};">
                        {{ ucwords(str_replace(['.', '_'], ' ', (string) $activity->type)) }}
                      </span>
                    </div>
                  </td>
                  <td class="audit-module">{{ $activity->category_label }}</td>
                  <td class="audit-ip">{{ $activity->ip_address ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5">
                    <div class="audit-empty">
                      <h3>No audit records</h3>
                      <p>No activity matches the current filters.
                        @if($search !== '' || $category !== '' || $period !== 'all' || $userId !== '')
                          Try clearing the filters above.
                        @endif
                      </p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
          </div>

          <div class="app-scrollbar">
            <div class="app-scrollbar-track"></div>
            <div class="app-scrollbar-thumb"></div>
          </div>
        </div>

        @if($activities->hasPages())
          <div class="audit-footer">
            <span class="rows-info">
              Showing {{ number_format($activities->firstItem()) }}–{{ number_format($activities->lastItem()) }}
              of {{ number_format($activities->total()) }} records
            </span>
            {{ $activities->withQueryString()->links('partials.pagination') }}
          </div>
        @elseif($activities->total() > 0)
          <div class="audit-footer">
            <span class="rows-info">Showing all {{ number_format($activities->total()) }} records</span>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div id="auditToast" style="position:fixed;bottom:24px;left:50%;transform:translate(-50%,16px);background:#143d23;color:#eaf7e3;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(20,61,35,0.28);opacity:0;pointer-events:none;transition:all .25s ease;z-index:9999;"></div>

  <script>
    (function () {
      var toast = document.getElementById('auditToast');
      var toastTimer = null;
      function showToast(msg) {
        toast.textContent = msg;
        toast.style.opacity = '1';
        toast.style.transform = 'translate(-50%, 0)';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
          toast.style.opacity = '0';
          toast.style.transform = 'translate(-50%, 16px)';
        }, 2600);
      }
      var rows = Array.prototype.slice.call(document.querySelectorAll('#auditRows tr'));

      document.getElementById('exportBtn').addEventListener('click', function () {
        var shown = rows.filter(function (r) { return r.style.display !== 'none'; });
        if (shown.length === 0) { showToast('No records to export.'); return; }
        var lines = ['Timestamp,Actor,Role,Event,Module,IP Address'];
        shown.forEach(function (row) {
          var cells = row.querySelectorAll('td');
          if (cells.length < 5) { return; }
          var actor = cells[1].innerText.replace(/\n/g, ' ');
          var badge = cells[2].querySelector('.audit-badge');
          var event = badge ? badge.dataset.tip : cells[2].innerText.replace(/\n/g, ' ');
          var csv = [cells[0].innerText, actor, event, cells[3].innerText, cells[4].innerText]
            .map(function (v) { return '"' + v.replace(/"/g, '""') + '"'; })
            .join(',');
          lines.push(csv);
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'audit-trail.csv';
        a.click();
        URL.revokeObjectURL(url);
        showToast('Exported ' + (lines.length - 1) + ' record(s) from this page.');
      });

      // Event hover tooltip
      var tip = document.createElement('div');
      tip.className = 'audit-tooltip';
      document.body.appendChild(tip);

      function positionTip(anchor) {
        var rect = anchor.getBoundingClientRect();
        var tipW = tip.offsetWidth;
        var x = rect.left + rect.width / 2 - tipW / 2;
        x = Math.max(8, Math.min(window.innerWidth - tipW - 8, x));
        var y = rect.top - tip.offsetHeight - 12;
        if (y < 8) y = rect.bottom + 12;
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
      }

      document.querySelectorAll('#auditRows .has-tip').forEach(function (el) {
        el.addEventListener('mouseenter', function () {
          tip.innerHTML = '<b>' + el.dataset.tip + '</b><span>' + el.dataset.sub + '</span>';
          tip.style.opacity = '1';
          positionTip(el);
        });
        el.addEventListener('mousemove', function () { positionTip(el); });
        el.addEventListener('mouseleave', function () { tip.style.opacity = '0'; });
      });
    })();
  </script>
@endsection
