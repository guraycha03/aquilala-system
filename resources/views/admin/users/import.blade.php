@extends('layouts.sidebar')

@section('title', 'Import Members')

@section('content')
  @php
    $import = session('admin.user_import');
    $error  = session('error');
  @endphp

  <style>
    :root {
      --primary: #15803d;
      --primary-hover: #166534;
      --primary-light: #f0fdf4;
      --primary-border: #bbf7d0;
      --slate-50: #f8fafc;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-400: #94a3b8;
      --slate-600: #475569;
      --slate-700: #334155;
      --slate-800: #1e293b;
      --slate-900: #0f172a;
      --radius-lg: 16px;
      --radius-md: 12px;
      --radius-sm: 8px;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.06), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
      --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
    }

    .import-page { 
      position: relative; 
      font-family: inherit;
    }
    .import-page::before,
    .import-page::after {
      content: ""; 
      position: fixed; 
      border-radius: 50%; 
      filter: blur(80px); 
      pointer-events: none; 
      z-index: 0;
    }
    .import-page::before { 
      width: 380px; 
      height: 380px; 
      background: rgba(16, 185, 129, 0.08); 
      top: -100px; 
      right: -100px; 
    }
    .import-page::after { 
      width: 320px; 
      height: 320px; 
      background: rgba(34, 197, 94, 0.06); 
      bottom: -80px; 
      left: -80px; 
    }
    .import-content { 
      position: relative; 
      z-index: 1; 
    }

    .top-navigation {
      margin-bottom: 16px;
    }

    .header-row { 
      margin-bottom: 24px; 
    }
    .header-row h1 { 
      font-size: 24px; 
      font-weight: 700; 
      color: var(--slate-900); 
      letter-spacing: -0.02em; 
      margin: 0 0 4px 0; 
    }
    .header-row p { 
      font-size: 14px; 
      color: var(--slate-600); 
      margin: 0; 
    }

    .btn-users {
      display: inline-flex; 
      align-items: center; 
      justify-content: center;
      gap: 8px; 
      text-decoration: none; 
      cursor: pointer;
      font-family: inherit; 
      line-height: 1.25;
      background: var(--primary); 
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #ffffff; 
      padding: 10px 18px; 
      border-radius: var(--radius-md); 
      font-size: 13.5px; 
      font-weight: 600;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(21, 128, 61, 0.15), var(--shadow-sm);
    }
    .btn-users:hover { 
      background: var(--primary-hover); 
      transform: translateY(-1px); 
      box-shadow: 0 4px 12px rgba(21, 128, 61, 0.25), var(--shadow-md); 
    }
    .btn-users:active { 
      transform: translateY(0); 
      box-shadow: var(--shadow-sm); 
    }
    .btn-users:focus-visible { 
      outline: 2px solid var(--primary); 
      outline-offset: 2px; 
    }
    .btn-users:disabled { 
      opacity: 0.55; 
      cursor: not-allowed; 
      transform: none; 
      background: var(--slate-200); 
      color: var(--slate-400); 
      border-color: var(--slate-200); 
      box-shadow: none; 
    }
    .btn-users svg { 
      width: 16px; 
      height: 16px; 
      stroke: currentColor; 
      fill: none; 
      stroke-width: 2; 
      stroke-linecap: round; 
      stroke-linejoin: round; 
    }
    .btn-users.ghost { 
      background: #ffffff; 
      color: var(--slate-700); 
      border: 1px solid var(--slate-200); 
      box-shadow: var(--shadow-sm); 
    }
    .btn-users.ghost:hover { 
      background: var(--slate-50); 
      border-color: var(--slate-300); 
      color: var(--slate-900); 
    }
    .btn-users.danger { 
      background: #ffffff; 
      color: #dc2626; 
      border-color: #fecaca; 
      box-shadow: var(--shadow-sm); 
    }
    .btn-users.danger:hover { 
      background: #fef2f2; 
      border-color: #fca5a5; 
      color: #b91c1c; 
    }

    .import-alert {
      margin-bottom: 20px; 
      padding: 14px 18px; 
      border-radius: var(--radius-md); 
      font-size: 13.5px; 
      font-weight: 500;
      background: #fef2f2; 
      border: 1px solid #fecaca; 
      color: #991b1b;
      display: flex; 
      align-items: center; 
      gap: 12px;
      box-shadow: var(--shadow-sm);
    }
    .import-alert svg { 
      width: 18px; 
      height: 18px; 
      stroke: currentColor; 
      fill: none; 
      stroke-width: 2; 
      flex-shrink: 0; 
    }

    .import-panel {
      background: rgba(255, 255, 255, 0.95); 
      backdrop-filter: blur(12px); 
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--slate-200); 
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 32px; 
      max-width: 860px;
    }
    .panel-title { 
      font-size: 17px; 
      font-weight: 700; 
      color: var(--slate-900); 
      margin: 0 0 6px 0; 
      letter-spacing: -0.01em;
    }
    .panel-desc { 
      font-size: 13.5px; 
      color: var(--slate-600); 
      margin: 0 0 22px 0; 
      line-height: 1.6; 
    }

    .drop-zone {
      border: 2px dashed var(--slate-300); 
      border-radius: var(--radius-md); 
      padding: 36px 24px; 
      text-align: center;
      background: var(--slate-50); 
      transition: all 0.2s ease-in-out; 
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .drop-zone:hover {
      border-color: var(--slate-400);
      background: #ffffff;
    }
    .drop-zone.dragover { 
      border-color: var(--primary); 
      background: var(--primary-light); 
    }
    .drop-zone svg { 
      width: 32px; 
      height: 32px; 
      stroke: var(--slate-400); 
      fill: none; 
      stroke-width: 1.75; 
      margin-bottom: 12px; 
      transition: transform 0.2s ease;
    }
    .drop-zone:hover svg {
      transform: translateY(-2px);
      stroke: var(--primary);
    }
    .drop-zone p { 
      font-size: 13.5px; 
      color: var(--slate-700); 
      margin: 0; 
    }
    .drop-zone p strong { 
      color: var(--primary); 
      font-weight: 600;
    }
    .drop-zone .dz-sub { 
      font-size: 12px; 
      color: var(--slate-400); 
      margin-top: 6px; 
    }
    .drop-zone input[type="file"] { display: none; }
    
    .file-selected { 
      display: none; 
      margin-top: 14px; 
      padding: 6px 12px;
      background: var(--primary-light);
      border: 1px solid var(--primary-border);
      border-radius: var(--radius-sm);
      font-size: 12.5px; 
      color: var(--primary); 
      font-weight: 600; 
    }
    .file-selected.show { display: inline-block; }

    .import-bullets { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
      gap: 16px; 
      margin-top: 24px; 
      padding-top: 24px;
      border-top: 1px solid var(--slate-200);
    }
    .bullet { 
      display: flex; 
      gap: 12px; 
      align-items: flex-start; 
      font-size: 12.5px; 
      color: var(--slate-600); 
      line-height: 1.5; 
    }
    .bullet svg { 
      width: 16px; 
      height: 16px; 
      stroke: var(--primary); 
      fill: none; 
      stroke-width: 2.5; 
      flex-shrink: 0; 
      margin-top: 2px; 
      background: var(--primary-light);
      padding: 3px;
      border-radius: 50%;
    }
    .bullet b { color: var(--slate-900); }

    .pill-row { 
      display: flex; 
      gap: 10px; 
      flex-wrap: wrap; 
      margin-bottom: 20px; 
    }
    .pill {
      display: inline-flex; 
      align-items: center; 
      gap: 8px; 
      padding: 6px 14px; 
      border-radius: 20px;
      font-size: 12.5px; 
      font-weight: 600; 
      background: var(--slate-100); 
      color: var(--slate-700);
    }
    .pill.green { 
      background: var(--primary-light); 
      color: var(--primary); 
      border: 1px solid var(--primary-border);
    }
    .pill.red { 
      background: #fef2f2; 
      color: #dc2626; 
      border: 1px solid #fecaca;
    }
    .pill svg { 
      width: 14px; 
      height: 14px; 
      stroke: currentColor; 
      fill: none; 
      stroke-width: 2.5; 
    }
    .pill .pill-num { 
      font-variant-numeric: tabular-nums; 
      font-size: 14px; 
      font-weight: 700;
    }

    .import-table-wrap { 
      overflow-x: auto; 
      border: 1px solid var(--slate-200); 
      border-radius: var(--radius-md); 
      max-height: 480px; 
      overflow-y: auto; 
      box-shadow: var(--shadow-sm);
    }
    .import-table { 
      width: 100%; 
      border-collapse: collapse; 
      text-align: left; 
    }
    .import-table thead { 
      position: sticky; 
      top: 0; 
      z-index: 2; 
    }
    .import-table thead tr { 
      background: var(--slate-50); 
      border-bottom: 1px solid var(--slate-200);
    }
    .import-table th { 
      padding: 12px 16px; 
      color: var(--slate-500); 
      font-size: 11px; 
      font-weight: 700; 
      text-transform: uppercase; 
      letter-spacing: 0.05em; 
    }
    .import-table td { 
      padding: 12px 16px; 
      color: var(--slate-700); 
      font-size: 13px; 
      border-bottom: 1px solid var(--slate-100); 
      vertical-align: middle; 
    }
    .import-table tbody tr {
      transition: background-color 0.15s ease;
    }
    .import-table tbody tr:hover {
      background-color: var(--slate-50);
    }
    .import-table tbody tr:last-child td { border-bottom: none; }
    .import-table tbody tr.row-error { background: #fff1f2; }
    .import-table tbody tr.row-error:hover { background: #ffe4e6; }
    
    .row-num { 
      color: var(--slate-400); 
      font-variant-numeric: tabular-nums; 
      font-weight: 500;
    }
    
    .role-chip {
      display: inline-block; 
      padding: 2px 8px; 
      border-radius: 6px; 
      font-size: 11px; 
      font-weight: 600;
      background: var(--primary-light); 
      color: var(--primary);
      border: 1px solid var(--primary-border);
    }
    .role-chip.store { 
      background: #fffbe3; 
      color: #b45309; 
      border-color: #fef08a;
    }
    .status-stack { 
      display: flex; 
      flex-direction: column; 
      gap: 4px; 
      align-items: flex-start; 
    }
    .ok-chip {
      display: inline-flex; 
      align-items: center; 
      gap: 4px; 
      padding: 2px 8px; 
      border-radius: 6px;
      font-size: 11px; 
      font-weight: 600; 
      background: var(--primary-light); 
      color: var(--primary);
    }
    .ok-chip svg { 
      width: 12px; 
      height: 12px; 
      stroke: currentColor; 
      fill: none; 
      stroke-width: 2.5; 
    }
    .err-chip {
      display: inline-flex; 
      align-items: center; 
      gap: 4px; 
      padding: 2px 8px; 
      border-radius: 6px;
      font-size: 11px; 
      font-weight: 600; 
      background: #fef2f2; 
      color: #dc2626;
    }
    .err-chip svg { 
      width: 12px; 
      height: 12px; 
      stroke: currentColor; 
      fill: none; 
      stroke-width: 2.5; 
    }
    .muted { 
      font-size: 12px; 
      color: var(--slate-400); 
    }

    .approve-check { 
      display: inline-flex; 
      align-items: center; 
      justify-content: center; 
      width: 20px; 
      height: 20px; 
      cursor: pointer; 
    }
    .approve-check input { 
      width: 16px; 
      height: 16px; 
      accent-color: var(--primary); 
      cursor: pointer; 
      border-radius: 4px;
    }
    .approve-check.disabled { opacity: 0.35; }
    .approve-check.disabled input { cursor: not-allowed; }

    .ocr-raw { 
      margin-bottom: 20px; 
      border: 1px solid var(--slate-200); 
      border-radius: var(--radius-md); 
      background: var(--slate-50); 
      overflow: hidden; 
    }
    .ocr-raw summary { 
      cursor: pointer; 
      padding: 12px 16px; 
      font-size: 13px; 
      font-weight: 600; 
      color: var(--slate-700); 
      list-style: none; 
      user-select: none; 
      transition: background 0.15s ease;
    }
    .ocr-raw summary:hover { background: var(--slate-100); }
    .ocr-raw summary::-webkit-details-marker { display: none; }
    .ocr-raw summary::before { 
      content: "› "; 
      color: var(--primary); 
      font-weight: 800; 
      display: inline-block;
      transition: transform 0.15s ease;
    }
    .ocr-raw[open] summary::before { 
      transform: rotate(90deg);
    }
    .ocr-raw pre { 
      margin: 0; 
      padding: 12px 16px; 
      font-size: 12px; 
      line-height: 1.6; 
      color: var(--slate-600); 
      white-space: pre-wrap; 
      word-break: break-word; 
      max-height: 220px; 
      overflow-y: auto; 
      border-top: 1px solid var(--slate-200);
      background: #ffffff;
    }

    .ocr-hint { 
      display: flex; 
      align-items: flex-start; 
      gap: 12px; 
      font-size: 13px; 
      color: #b45309; 
      background: #fffbe3;
      border: 1px solid #fef08a;
      padding: 12px 16px;
      border-radius: var(--radius-md);
      line-height: 1.5; 
      margin-bottom: 20px; 
    }
    .ocr-hint svg { 
      width: 18px; 
      height: 18px; 
      stroke: #d97706; 
      fill: none; 
      stroke-width: 2; 
      flex-shrink: 0; 
    }
  </style>

  <div class="import-page">
    <div class="import-content">

      <div class="top-navigation">
        <a href="{{ route('admin.users.index') }}" class="btn-users ghost">
          <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to Users
        </a>
      </div>

      <div class="header-row">
        <h1>Import Members</h1>
        <p>Bulk-create member accounts from CSV, Excel, or JSON files. Preview first, then confirm the import.</p>
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