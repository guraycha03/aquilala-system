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
    .btn-users.danger { background: #ffffff; color: #b0403a; border-color: #e6ccc9; box-shadow: 0 1px 2px rgba(176,64,58,0.05); }
    .btn-users.danger:hover { background: #faf2f1; border-color: #d8a49f; color: #963530; }

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

    .approve-check { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; cursor: pointer; }
    .approve-check input { width: 17px; height: 17px; accent-color: #16a34a; cursor: pointer; }
    .approve-check.disabled { opacity: 0.4; }
    .approve-check.disabled input { cursor: not-allowed; }

    .ocr-raw { margin-bottom: 18px; border: 1px solid #e6ebe8; border-radius: 12px; background: #fafbfa; overflow: hidden; }
    .ocr-raw summary { cursor: pointer; padding: 12px 16px; font-size: 12.5px; font-weight: 700; color: #334155; list-style: none; user-select: none; }
    .ocr-raw summary::-webkit-details-marker { display: none; }
    .ocr-raw summary::before { content: "› "; color: #16a34a; font-weight: 800; }
    .ocr-raw[open] summary::before { content: "⌄ "; }
    .ocr-raw pre { margin: 0; padding: 0 16px 14px; font-size: 12px; line-height: 1.6; color: #475569; white-space: pre-wrap; word-break: break-word; max-height: 220px; overflow-y: auto; }

    .ocr-hint { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #b45309; line-height: 1.5; margin-bottom: 16px; }
    .ocr-hint svg { width: 15px; height: 15px; stroke: #d97706; fill: none; stroke-width: 2.5; flex-shrink: 0; margin-top: 1px; }
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

        {{-- ── OCR: scanned document upload (optional enhancement) ── --}}
        <div class="import-panel" style="margin-top:22px;">
          <h2 class="panel-title">Import from Scanned Document</h2>
          <p class="panel-desc">
            Upload a photo or scan of a typed member list (JPG or PNG). Tesseract OCR reads the text, we show the
            records it recognizes, and <b>you approve each row before anything is created</b>. OCR never creates
            accounts automatically — when in doubt, use the spreadsheet import above instead.
          </p>

          <form method="POST" action="{{ route('admin.users.import.ocr') }}" enctype="multipart/form-data" id="ocrImportForm">
            @csrf
            <label class="drop-zone" for="ocrFileInput" id="ocrDropZone">
              <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <p><strong>Drag &amp; drop</strong> a scanned document here, or <strong>browse</strong></p>
              <p class="dz-sub">Accepts .jpg, .jpeg, .png — up to 5 MB</p>
              <input type="file" name="file" id="ocrFileInput" accept=".jpg,.jpeg,.png">
              <div class="file-selected" id="ocrFileSelected"></div>
            </label>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
              <button type="submit" class="btn-users ghost" id="ocrPreviewBtn" disabled>
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10"/><circle cx="12" cy="12" r="3"/></svg>
                Scan &amp; Preview
              </button>
            </div>
          </form>

          <div class="import-bullets">
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Never automatic</b><br>Every OCR record stays in review until an administrator approves it.</div>
            </div>
            <div class="bullet">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <div><b>Best effort</b><br>Names, phones and e-mails OCR can read get filled in. Rows without an e-mail are flagged for review.</div>
            </div>
          </div>
        </div>
      @else
        {{-- ── PHASE 2: Preview ── --}}
        @if(($import['source'] ?? 'file') === 'ocr')
          <div class="import-panel" style="max-width:none;">
            <h2 class="panel-title">Review OCR results</h2>
            <p class="panel-desc">
              Scanned document: <b>{{ $import['filename'] }}</b>. The rows below are the member records OCR could
              recognize from the document. <b>Nothing is imported automatically</b> — tick the box next to each record
              to approve it, then click the import button. Unchecked and flagged rows are skipped.
            </p>

            <div class="ocr-hint">
              <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <span>OCR can misread names and numbers. Review each row before approving, and open the raw OCR text below to check what the scanner actually detected.</span>
            </div>

            <div class="pill-row">
              <div class="pill green">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <span class="pill-num">{{ $import['valid'] }}</span> Ready to import
              </div>
              <div class="pill red">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="pill-num">{{ $import['errors'] }}</span> Need review
              </div>
            </div>

            <details class="ocr-raw">
              <summary>View raw OCR text</summary>
              <pre>{{ $import['ocr_text'] }}</pre>
            </details>

            <div class="import-table-wrap">
              <table class="import-table">
                <thead>
                  <tr>
                    <th>Approve</th>
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
                      <td>
                        <label class="approve-check {{ $row['issues'] ? 'disabled' : '' }}">
                          <input type="checkbox" name="approve[]" value="{{ $row['_row'] }}" data-ocr-row
                                 form="ocrConfirmForm" {{ $row['issues'] ? 'disabled' : '' }}>
                        </label>
                      </td>
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
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">No rows to show.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;align-items:center;">
              <div style="flex:1;min-width:200px;">
                <div class="muted">Tick the rows you want to add — unchecked and flagged rows are skipped.</div>
              </div>
              <form method="POST" action="{{ route('admin.users.import.ocr.confirm') }}" id="ocrConfirmForm" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Importing…';">
                @csrf
                <input type="hidden" name="token" value="{{ $import['token'] }}">
                <button type="submit" class="btn-users" id="ocrImportBtn" disabled>
                  <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                  Import Selected Members
                </button>
              </form>
              <form method="POST" action="{{ route('admin.users.import.cancel') }}">
                @csrf
                <button type="submit" class="btn-users ghost">Start Over</button>
              </form>
            </div>
          </div>
        @else
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
      @endif

    </div>
  </div>

  <script>
    (function () {
      function wireZone(zoneId, inputId, btnId, selectedId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        var btn = document.getElementById(btnId);
        var selected = document.getElementById(selectedId);
        if (!zone || !input || !btn || !selected) return;

        function showFile() {
          if (input.files.length > 0) {
            selected.textContent = 'Selected file: ' + input.files[0].name;
            selected.classList.add('show');
            zone.classList.remove('dragover');
            btn.disabled = false;
          } else {
            selected.classList.remove('show');
            btn.disabled = true;
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
      }

      wireZone('dropZone', 'fileInput', 'previewBtn', 'fileSelected');
      wireZone('ocrDropZone', 'ocrFileInput', 'ocrPreviewBtn', 'ocrFileSelected');

      var ocrRows = document.querySelectorAll('[data-ocr-row]');
      if (ocrRows.length > 0) {
        var ocrImportBtn = document.getElementById('ocrImportBtn');

        function refreshOcrButton() {
          if (!ocrImportBtn) return;
          var anyChecked = Array.prototype.some.call(ocrRows, function (cb) { return cb.checked; });
          ocrImportBtn.disabled = !anyChecked;
        }

        ocrRows.forEach(function (cb) { cb.addEventListener('change', refreshOcrButton); });
        refreshOcrButton();
      }
    })();
  </script>
@endsection