@extends('layouts.sidebar')

@section('title', 'Audit Trail Logs')

@section('content')
  @php
    // SAMPLE placeholder data — replace with real audit-log model later.
    $events = [
        ['ts' => '2026-08-16 09:42:11', 'actor' => 'Charisse G. Guray', 'role' => 'Admin', 'type' => 'approve', 'cat' => 'approval', 'msg' => 'Approved store application — Rice Mill', 'module' => 'Store Approvals', 'ip' => '192.168.1.24'],
        ['ts' => '2026-08-16 09:15:04', 'actor' => 'Juan Dela Cruz', 'role' => 'Farmer', 'type' => 'request', 'cat' => 'quotation', 'msg' => 'Requested quotation for 2.5 ha Rice', 'module' => 'Quotations', 'ip' => '192.168.1.108'],
        ['ts' => '2026-08-16 08:58:47', 'actor' => 'Maria Santos', 'role' => 'Store Owner', 'type' => 'dispatch', 'cat' => 'quotation', 'msg' => 'Dispatched cost estimate #Q-1042', 'module' => 'Quotations', 'ip' => '192.168.1.91'],
        ['ts' => '2026-08-15 17:30:55', 'actor' => 'Charisse G. Guray', 'role' => 'Admin', 'type' => 'formula', 'cat' => 'formula', 'msg' => 'Adjusted Urea rate override (Wet Season)', 'module' => 'Formula Overrides', 'ip' => '192.168.1.24'],
        ['ts' => '2026-08-15 16:12:30', 'actor' => 'Pedro Ramos', 'role' => 'Store Owner', 'type' => 'login', 'cat' => 'auth', 'msg' => 'Signed in to the platform', 'module' => 'Authentication', 'ip' => '192.168.1.76'],
        ['ts' => '2026-08-15 15:44:09', 'actor' => 'Liza Morales', 'role' => 'Farmer', 'type' => 'accept', 'cat' => 'quotation', 'msg' => 'Accepted quotation #Q-1039', 'module' => 'Quotations', 'ip' => '192.168.1.133'],
        ['ts' => '2026-08-15 14:20:18', 'actor' => 'Charisse G. Guray', 'role' => 'Admin', 'type' => 'reject', 'cat' => 'approval', 'msg' => 'Rejected store application — Agro Traders', 'module' => 'Store Approvals', 'ip' => '192.168.1.24'],
        ['ts' => '2026-08-15 13:05:42', 'actor' => 'Juan Dela Cruz', 'role' => 'Farmer', 'type' => 'logout', 'cat' => 'auth', 'msg' => 'Signed out of the platform', 'module' => 'Authentication', 'ip' => '192.168.1.108'],
        ['ts' => '2026-08-15 11:38:27', 'actor' => 'Rosa Villanueva', 'role' => 'Store Owner', 'type' => 'update', 'cat' => 'update', 'msg' => 'Updated product — Palay Seeds price', 'module' => 'Storefront', 'ip' => '192.168.1.57'],
        ['ts' => '2026-08-15 10:02:13', 'actor' => 'Admin System', 'role' => 'System', 'type' => 'maintain', 'cat' => 'system', 'msg' => 'Automated seasonal data refresh', 'module' => 'System', 'ip' => '127.0.0.1'],
    ];

    $typeMeta = [
        'login'    => ['Signed In', '#4f6f8f', '#eef2f6'],
        'logout'   => ['Signed Out', '#64748b', '#f1f5f9'],
        'approve'  => ['Approved', '#63856c', '#eef3ef'],
        'reject'   => ['Rejected', '#a86464', '#f6ecec'],
        'request'  => ['Request', '#62829b', '#eef2f6'],
        'dispatch' => ['Dispatched', '#a57f43', '#f6f0e6'],
        'accept'   => ['Accepted', '#63856c', '#eef3ef'],
        'formula'  => ['Override', '#86799f', '#f1eef6'],
        'update'   => ['Updated', '#a57f43', '#f6f0e6'],
        'maintain' => ['Maintenance', '#64748b', '#f1f5f9'],
    ];
  @endphp

  <style>
    .audit-page { position: relative; }
    .audit-page::before,
    .audit-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .audit-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.15); top: -80px; right: -80px; }
    .audit-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.12); bottom: -60px; left: -60px; }
    .audit-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
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

    .audit-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
      position: relative;
    }
    .audit-toolbar {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 16px 20px; border-bottom: 1px solid #eef1ef;
    }
    .audit-toolbar .search-box {
      display: flex; align-items: center; gap: 8px; flex: 1; min-width: 200px; max-width: 340px;
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px; padding: 8px 12px;
    }
    .audit-toolbar .search-box svg { width: 14px; height: 14px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .audit-toolbar .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #334155; }
    .audit-toolbar select {
      background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 10px;
      padding: 8px 12px; font-size: 13px; color: #334155; outline: none; cursor: pointer;
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

    /* Custom overlay scrollbar moved to shared layout (.app-scrollbar) */
    .pager { display: flex; gap: 6px; }
    .pager button {
      min-width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e6ebe8; background: #ffffff;
      font-size: 13px; color: #475569; cursor: pointer; transition: all 0.15s ease;
    }
    .pager button:hover { background: #f6f8f7; }
    .pager button.active { background: #143d23; border-color: #143d23; color: #ffffff; }

    .audit-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }

    @media (max-width: 768px) {
      .audit-table th:nth-child(4), .audit-table td:nth-child(4),
      .audit-table th:nth-child(5), .audit-table td:nth-child(5) { display: none; }
    }
  </style>

  <div class="audit-page">
    <div class="audit-content">
      <div class="header-row">
        <div>
          <h1>System Security Audit Trail</h1>
          <p>Immutable event log capturing authentication steps, approvals, formula overrides, and account modifications.</p>
        </div>
        <div class="header-actions">
          <button id="exportBtn" class="btn-audit ghost">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </button>
          <button id="clearFiltersBtn" class="btn-audit">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Summary KPIs -->
      <div class="audit-kpis">
        <div class="audit-kpi">
          <div class="ak-label">Total Events</div>
          <div class="ak-value">1,284</div>
          <div class="ak-sub">all-time records</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Today</div>
          <div class="ak-value">42</div>
          <div class="ak-sub">events logged today</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Authentications</div>
          <div class="ak-value">618</div>
          <div class="ak-sub">sign-ins &amp; sign-outs</div>
        </div>
        <div class="audit-kpi">
          <div class="ak-label">Modifications</div>
          <div class="ak-value">97</div>
          <div class="ak-sub">approvals &amp; overrides</div>
        </div>
      </div>

      <div class="audit-panel">
        <div class="audit-toolbar">
          <div class="search-box">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="searchInput" type="text" placeholder="Search actor, event, or module…">
          </div>
          <select id="typeFilter">
            <option value="all">All event types</option>
            <option value="auth">Authentications</option>
            <option value="approval">Store Approvals</option>
            <option value="quotation">Quotations</option>
            <option value="formula">Formula Overrides</option>
            <option value="update">Storefront Updates</option>
            <option value="system">System</option>
          </select>
          <select id="dateFilter">
            <option value="all">All dates</option>
            <option value="today">Today</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
          </select>
        </div>

        <div class="app-scroll-wrap" style="--sb-bottom: 18px;">
          <div class="app-scroll">
            <table class="audit-table">
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
              @foreach($events as $e)
                @php
                  $meta = $typeMeta[$e['type']] ?? ['Event', '#64748b', '#f1f5f9'];
                @endphp
                <tr data-cat="{{ $e['cat'] }}"
                    data-ts="{{ $e['ts'] }}"
                    data-search="{{ strtolower($e['actor'] . ' ' . $e['role'] . ' ' . $e['msg'] . ' ' . $e['module'] . ' ' . $meta[0]) }}">
                  <td class="audit-ts">{{ $e['ts'] }}</td>
                  <td>
                    <div class="audit-actor">
                      <div class="audit-avatar">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                      </div>
                      <div>
                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap;">{{ $e['actor'] }}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 1px;">{{ $e['role'] }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="audit-event">
                      <span class="audit-badge has-tip" data-tip="{{ $e['msg'] }}" data-sub="{{ $e['module'] }} · {{ $e['ip'] }}" data-inline-style="background:{{ $meta[2] }};color:{{ $meta[1] }};">{{ $meta[0] }}</span>
                    </div>
                  </td>
                  <td class="audit-module">{{ $e['module'] }}</td>
                  <td class="audit-ip">{{ $e['ip'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
          </div>

          <div class="app-scrollbar">
            <div class="app-scrollbar-track"></div>
            <div class="app-scrollbar-thumb"></div>
          </div>
        </div>

        <div class="audit-footer">
          <span class="rows-info" id="rowsInfo">Showing 10 of 10 records</span>
          <div class="pager">
            <button>&lsaquo;</button>
            <button class="active">1</button>
            <button>2</button>
            <button>3</button>
            <button>…</button>
            <button>129</button>
            <button>&rsaquo;</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var rows = Array.prototype.slice.call(document.querySelectorAll('#auditRows tr'));
      var total = rows.length;
      var searchInput = document.getElementById('searchInput');
      var typeFilter = document.getElementById('typeFilter');
      var dateFilter = document.getElementById('dateFilter');
      var rowsInfo = document.getElementById('rowsInfo');

      function visibleRows() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var cat = typeFilter.value;
        var range = dateFilter.value;
        var now = new Date();
        var cutoff7 = new Date(now); cutoff7.setDate(now.getDate() - 7);
        var cutoff30 = new Date(now); cutoff30.setDate(now.getDate() - 30);

        return rows.filter(function (row) {
          if (q && row.getAttribute('data-search').indexOf(q) === -1) return false;
          if (cat !== 'all' && row.getAttribute('data-cat') !== cat) return false;
          if (range !== 'all') {
            var ts = new Date(row.getAttribute('data-ts').replace(' ', 'T'));
            if (range === 'today' && ts.toDateString() !== now.toDateString()) return false;
            if (range === '7d' && ts < cutoff7) return false;
            if (range === '30d' && ts < cutoff30) return false;
          }
          return true;
        });
      }

      function applyFilters() {
        var shown = visibleRows();
        var before = new Map(rows.map(function (r) { return [r, r.getBoundingClientRect().top]; }));

        var wasShown = new Map(rows.map(function (r) { return [r, r.style.display !== 'none']; }));
        rows.forEach(function (row) { row.style.display = 'none'; });
        shown.forEach(function (row) { row.style.display = ''; });

        var after = new Map(rows.map(function (r) { return [r, r.getBoundingClientRect().top]; }));

        shown.forEach(function (row) {
          var wasHidden = !wasShown.get(row);
          if (wasHidden) {
            row.style.transition = 'none';
            row.style.opacity = '0';
            void row.offsetHeight;
            row.style.transition = 'opacity 0.35s ease';
            row.style.opacity = '1';
          }
          var delta = before.get(row) - after.get(row);
          if (Math.abs(delta) < 1) return;
          row.style.transition = 'none';
          row.style.transform = 'translateY(' + delta + 'px)';
          void row.offsetHeight;
          row.style.transition = 'transform 0.4s cubic-bezier(0.25,0.8,0.25,1)';
          row.style.transform = '';
        });

        rowsInfo.textContent = 'Showing ' + shown.length + ' of ' + total + ' records';

        clearFiltersBtn.disabled = !hasActiveFilters();
        requestAnimationFrame(updateScrollbar);
      }

      function hasActiveFilters() {
        return (searchInput.value || '').trim() !== '' ||
               typeFilter.value !== 'all' ||
               dateFilter.value !== 'all';
      }

      var searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 120);
      });
      typeFilter.addEventListener('change', applyFilters);
      dateFilter.addEventListener('change', applyFilters);

      var clearFiltersBtn = document.getElementById('clearFiltersBtn');
      clearFiltersBtn.addEventListener('click', function () {
        searchInput.value = '';
        typeFilter.value = 'all';
        dateFilter.value = 'all';
        applyFilters();
      });

      document.getElementById('exportBtn').addEventListener('click', function () {
        var shown = visibleRows();
        if (shown.length === 0) { alert('No records to export.'); return; }
        var lines = ['Timestamp,Actor,Role,Event,Module,IP Address'];
        shown.forEach(function (row) {
          var cells = row.querySelectorAll('td');
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
      });

      applyFilters();

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
