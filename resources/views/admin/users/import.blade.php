@extends('layouts.sidebar')

@section('title', 'Import Members')

@section('content')
  @php
    $import = session('admin.user_import');
    $error  = session('error');
  @endphp

  <style>
    .import-page { position: relative; }
    .import-page::before,
    .import-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .import-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.16); top: -80px; right: -80px; }
    .import-page::after { width: 280px; height: 280px; background: rgba(93,194,87,0.12); bottom: -60px; left: -60px; }
    .import-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }

    .btn-users {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: none;
      background: rgb(172, 242, 121); border: 1px solid rgba(20,61,35,0.12);
      color: #14532d; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08); cursor: pointer;
    }
    .btn-users:hover { background: rgb(160, 232, 108); transform: translateY(-1px); }
    .btn-users:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-users.ghost:hover { background: #f0fdf4; }
    .btn-users.danger { background: #fff; color: #b91c1c; border-color: rgba(185,28,28,0.25); box-shadow: none; }
    .btn-users.danger:hover { background: #fef2f2; }

    .import-alert {
      margin-bottom: 18px; padding: 13px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
      background: #fef2f2; border: 1px solid rgba(185,28,28,0.25); color: #b91c1c;
      display: flex; align-items: center; gap: 10px;
    }
    .import-alert svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.5; flex-shrink: 0; }

    .import-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px 28px; max-width: 860px;
    }
    .panel-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
    .panel-desc { font-size: 13px; color: #64748b; margin: 0 0 18px 0; line-height: 1.55; }

    .drop-zone {
      border: 2px dashed #cbd5e1; border-radius: 14px; padding: 30px 20px; text-align: center;
      background: #fbfdfc; transition: all 0.2s ease; cursor: pointer;
    }
    .drop-zone.dragover { border-color: #16a34a; background: #f0fdf4; }
    .drop-zone svg { width: 30px; height: 30px; stroke: #94a3b8; fill: none; stroke-width: 1.8; margin-bottom: 8px; }
    .drop-zone p { font-size: 13px; color: #475569; margin: 0; }
    .drop-zone p strong { color: #14532d; }
    .drop-zone .dz-sub { font-size: 12px; color: #94a3b8; margin-top: 4px; }
    .drop-zone input[type="file"] { display: none; }
    .file-selected { display: none; margin-top: 12px; font-size: 12.5px; color: #15803d; font-weight: 700; }
    .file-selected.show { display: block; }

    .import-bullets { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-top: 20px; }
    .bullet { display: flex; gap: 10px; align-items: flex-start; font-size: 12.5px; color: #475569; line-height: 1.5; }
    .bullet svg { width: 15px; height: 15px; stroke: #16a34a; fill: none; stroke-width: 2.5; flex-shrink: 0; margin-top: 1px; }
    .bullet b { color: #0f172a; }

    .pill-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .pill {
      display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 12px;
      font-size: 12.5px; font-weight: 700; background: #f1f5f9; color: #334155;
    }
    .pill.green { background: #dcfce7; color: #15803d; }
    .pill.red { background: #fee2e2; color: #b91c1c; }
    .pill svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.5; }
    .pill .pill-num { font-variant-numeric: tabular-nums; font-size: 15px; }

    .import-table-wrap { overflow-x: auto; border: 1px solid #eef1ef; border-radius: 12px; max-height: 480px; overflow-y: auto; }
    .import-table { width: 100%; border-collapse: collapse; text-align: left; }
    .import-table thead { position: sticky; top: 0; z-index: 2; }
    .import-table thead tr { background: #fafbfa; }
    .import-table th { padding: 12px 16px; color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .import-table td { padding: 12px 16px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .import-table tbody tr:last-child td { border-bottom: none; }
    .import-table tbody tr.row-error { background: #fffaf7; }
    .row-num { color: #94a3b8; font-variant-numeric: tabular-nums; }
    .role-chip {
      display: inline-block; padding: 3px 9px; border-radius: 7px; font-size: 10.5px; font-weight: 700;
      background: #dcfce7; color: #15803d;
    }
    .role-chip.store { background: #fef3c7; color: #d97706; }
    .status-stack { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
    .ok-chip {
      display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 7px;
      font-size: 10.5px; font-weight: 700; background: #f0fdf4; color: #16a34a;
    }
    .ok-chip svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 3; }
    .err-chip {
      display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 7px;
      font-size: 10.5px; font-weight: 700; background: #fee2e2; color: #b91c1c;
    }
    .err-chip svg { width: 11px; height: 11px; stroke: currentColor; fill: none; stroke-width: 3; }
    .muted { font-size: 12px; color: #94a3b8; }
  </style>

  <div class="import-page">
    <div class="import-content">

      <div class="header-row">
        <div>
          <h1>Import Members</h1>
          <p>Bulk-create member accounts from CSV, Excel, or JSON files. Preview first, then confirm the import.</p>
        </div>
        <div class="header-actions">
          <a href="{{ route('admin.users.index') }}" class="btn-users ghost">
            <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Users
          </a>
        </div>
      </div>

      @if($error)
        <div class="import-alert">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $error }}
        </div>
      @endif

      @if(empty($import))
        {{-- ── PHASE 1: Upload ── --}}
        <div class="import-panel">
          <h2 class="panel-title">Upload a member file</h2>
          <p class="panel-desc">
            The first row must be a header with column names. Only new accounts are created — rows whose email or
            phone already exists are flagged and skipped. Imported accounts start as <b>pending verification</b> and we
            can email each activation link afterwards from the Users page.
          </p>

          <form method="POST" action="{{ route('admin.users.import.preview') }}" enctype="multipart/form-data" id="importForm">
            @csrf
            <label class="drop-zone" for="fileInput" id="dropZone">
              <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <p><strong>Drag &amp; drop</strong> a file here, or <strong>browse</strong></p>
              <p class="dz-sub">Accepts .csv, .xls, .xlsx, .json — up to 5 MB</p>
              <input type="file" name="file" id="fileInput" accept=".csv,.xls,.xlsx,.json">
              <div class="file-selected" id="fileSelected"></div>
            </label>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
              <button type="submit" class="btn-users" id="previewBtn" disabled>
                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Preview Import
              </button>
            </div>
          </form>

          <div class="import-bullets">
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Formats</b><br>Comma / semicolon / tab CSV, Excel workbooks, and JSON arrays.</div>
            </div>
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Required columns</b><br>Either <b>Email</b> + <b>First&nbsp;Name</b> + <b>Last&nbsp;Name</b>, or a single <b>Full&nbsp;Name</b> column.</div>
            </div>
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Optional columns</b><br>Phone, Middle&nbsp;Initial, Sex, Birth&nbsp;Date, Civil&nbsp;Status, Barangay, City, Province, Address, Farm&nbsp;Location, Land&nbsp;Ownership, Years&nbsp;Farming, Assoc&nbsp;Member&nbsp;No, Store&nbsp;Name, Role.</div>
            </div>
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Role</b><br>Defaults to <b>Farmer</b>. Use <b>Store&nbsp;Owner</b> for store accounts (with a Store&nbsp;Name).</div>
            </div>
          </div>
        </div>
      @else
        {{-- ── PHASE 2: Preview ── --}}
        <div class="import-panel" style="max-width:none;">
          <h2 class="panel-title">Preview imported members</h2>
          <p class="panel-desc">
            File: <b>{{ $import['filename'] }}</b>. Rows with issues are skipped on import — the ready ones are inserted
            as pending-verification accounts.
          </p>

          <div class="pill-row">
            <div class="pill green">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="pill-num">{{ $import['valid'] }}</span> Ready to import
            </div>
            <div class="pill red">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span class="pill-num">{{ $import['errors'] }}</span> Rows with issues
            </div>
          </div>

          <div class="import-table-wrap">
            <table class="import-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($import['rows'] as $row)
                  <tr class="{{ $row['issues'] ? 'row-error' : '' }}">
                    <td class="row-num">{{ $row['_row'] }}</td>
                    <td>
                      {{ $row['data']['name'] ?? '—' }}
                      @if(!empty($row['data']['assoc_member_no']))
                        <div class="muted">Member {{ $row['data']['assoc_member_no'] }}</div>
                      @endif
                    </td>
                    <td>{{ $row['data']['email'] ?? '—' }}</td>
                    <td>{{ $row['data']['phone'] ?? '—' }}</td>
                    <td>
                      <span class="role-chip {{ ($row['data']['role'] ?? 'farmer') === 'store_owner' ? 'store' : '' }}">
                        {{ ($row['data']['role'] ?? 'farmer') === 'store_owner' ? 'Store Owner' : 'Farmer' }}
                      </span>
                    </td>
                    <td>
                      <div class="status-stack">
                        @if(empty($row['issues']))
                          <span class="ok-chip"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Ready</span>
                        @else
                          @foreach($row['issues'] as $issue)
                            <span class="err-chip"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>{{ $issue }}</span>
                          @endforeach
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">No rows to show.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
            <form method="POST" action="{{ route('admin.users.import.confirm') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Importing…';">
              @csrf
              <input type="hidden" name="token" value="{{ $import['token'] }}">
              <button type="submit" class="btn-users" {{ $import['valid'] === 0 ? 'disabled' : '' }}>
                <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Import {{ $import['valid'] }} Member{{ $import['valid'] === 1 ? '' : 's' }}
              </button>
            </form>
            <form method="POST" action="{{ route('admin.users.import.cancel') }}">
              @csrf
              <button type="submit" class="btn-users ghost">Start Over</button>
            </form>
          </div>
        </div>
      @endif

    </div>
  </div>

  <script>
    (function () {
      var zone = document.getElementById('dropZone');
      var input = document.getElementById('fileInput');
      var previewBtn = document.getElementById('previewBtn');
      var selected = document.getElementById('fileSelected');

      function showFile() {
        if (input.files.length > 0) {
          selected.textContent = 'Selected file: ' + input.files[0].name;
          selected.classList.add('show');
          zone.classList.remove('dragover');
          previewBtn.disabled = false;
        } else {
          selected.classList.remove('show');
          previewBtn.disabled = true;
        }
      }

      input.addEventListener('change', showFile);

      ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('dragover'); });
      });
      ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('dragover'); });
      });
      zone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length > 0) {
          input.files = e.dataTransfer.files;
          showFile();
        }
      });

      @if(!empty($import))
        document.getElementById('previewBtn') && (document.getElementById('previewBtn').disabled = false);
      @endif
    })();
  </script>
@endsection