@extends('layouts.sidebar')

@section('title', 'Import Harvest Records')

@section('content')
  @php
    $import  = session('admin.harvest_import');
    $error   = session('error');
    $success = session('success');
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
      --slate-500: #64748b;
      --slate-600: #475569;
      --slate-700: #334155;
      --slate-800: #1e293b;
      --slate-900: #0f172a;
      --radius-lg: 16px;
      --radius-md: 12px;
      --radius-sm: 8px;
      --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
      --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.08);
      --shadow-lg: 0 10px 30px rgba(15, 23, 42, 0.1);
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

    .top-navigation { margin-bottom: 16px; }

    .header-row { margin-bottom: 24px; }
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
    .btn-users:active { transform: translateY(0); box-shadow: var(--shadow-sm); }
    .btn-users:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
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
    .import-alert.ok {
      background: var(--primary-light);
      border-color: var(--primary-border);
      color: var(--primary);
    }

    .import-panel {
      position: relative;
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

    .help-btn {
      width: 22px;
      height: 22px;
      margin-left: 9px;
      padding: 0;
      border: 1px solid var(--slate-300);
      border-radius: 50%;
      background: var(--slate-100);
      color: var(--slate-500);
      font-size: 12px;
      font-weight: 700;
      line-height: 1;
      cursor: pointer;
      vertical-align: 2px;
      transition: all 0.15s ease;
    }
    .help-btn:hover,
    .help-btn[aria-expanded="true"] {
      background: var(--primary-light);
      border-color: var(--primary-border);
      color: var(--primary);
    }
    .help-btn:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }

    .help-pop {
      position: absolute;
      top: 58px;
      right: 32px;
      z-index: 70;
      width: min(540px, calc(100% - 64px));
      padding: 20px 22px;
      background: #ffffff;
      border: 1px solid var(--slate-200);
      border-radius: var(--radius-lg);
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
    }
    .help-pop[hidden] { display: none; }
    .help-pop-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 15px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--slate-200);
    }
    .help-pop-head h3 {
      margin: 0;
      font-size: 14px;
      font-weight: 700;
      color: var(--slate-900);
      letter-spacing: -0.01em;
    }
    .help-pop-head p {
      margin: 4px 0 0;
      font-size: 12.5px;
      color: var(--slate-500);
      line-height: 1.5;
    }
    .help-close {
      flex-shrink: 0;
      width: 26px;
      height: 26px;
      border: 0;
      border-radius: var(--radius-sm);
      background: transparent;
      color: var(--slate-400);
      font-size: 17px;
      line-height: 1;
      cursor: pointer;
    }
    .help-close:hover { background: var(--slate-100); color: var(--slate-700); }
    .help-pop .import-bullets {
      grid-template-columns: 1fr;
      gap: 13px;
      margin: 0;
      padding: 0;
      border-top: 0;
    }
    .help-pop .bullet { font-size: 13px; }

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

    .template-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 18px;
      padding-top: 18px;
      border-top: 1px solid var(--slate-200);
      font-size: 12.5px;
      color: var(--slate-500);
    }

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
      white-space: nowrap;
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

    /* ── Preview table: manual editing ────────────────────────────── */
    .ocr-table-bar {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    .ocr-table-bar .bar-note {
      font-size: 13px;
      color: var(--slate-600);
    }
    .ocr-table-bar .bar-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left: auto;
    }
    .ocr-table-bar .btn-users { padding: 7px 14px; font-size: 12.5px; }
    .ocr-table-bar [hidden] { display: none !important; }

    /* A cell shows its read-only text until the wrap enters edit mode, then
       the input replaces it. Both stay in the form so the values post. */
    .cell-static { display: block; }
    .cell-edit { display: none; }
    .import-table-wrap.editing .cell-static { display: none; }
    .import-table-wrap.editing .cell-edit { display: block; }

    .import-table input.ocr-cell,
    .import-table select.ocr-cell {
      width: 100%;
      min-width: 110px;
      padding: 7px 9px;
      border: 1px solid var(--slate-300);
      border-radius: var(--radius-sm);
      background: #ffffff;
      color: var(--slate-800);
      font-family: inherit;
      font-size: 13px;
      line-height: 1.4;
    }
    .import-table input.ocr-cell:focus,
    .import-table select.ocr-cell:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.18);
    }

    .row-num {
      color: var(--slate-400);
      font-variant-numeric: tabular-nums;
      font-weight: 500;
    }

    .num-cell {
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }

    .season-chip {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      text-transform: capitalize;
      background: var(--primary-light);
      color: var(--primary);
      border: 1px solid var(--primary-border);
    }
    .season-chip.dry {
      background: #fffbe3;
      color: #b45309;
      border-color: #fef08a;
    }

    .status-stack {
      display: flex;
      flex-direction: column;
      gap: 4px;
      align-items: flex-start;
      min-width: 220px;
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
      align-items: flex-start;
      gap: 4px;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      background: #fef2f2;
      color: #dc2626;
      line-height: 1.5;
      text-align: left;
    }
    .err-chip svg {
      width: 12px;
      height: 12px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2.5;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .muted {
      font-size: 12px;
      color: var(--slate-400);
    }

    .match-note {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 13px;
      color: #b45309;
      background: #fffbe3;
      border: 1px solid #fef08a;
      padding: 12px 16px;
      border-radius: var(--radius-md);
      line-height: 1.5;
      margin-bottom: 20px;
    }
    .match-note svg {
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
        <a href="{{ route('admin.harvests.reports') }}" class="btn-users ghost">
          <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to Harvest Reports
        </a>
      </div>

      <div class="header-row">
        <h1>Import Harvest Records</h1>
        <p>Load the association's historical harvest reports from a {{ \App\Support\ImportFormats::label() }} file. Preview first, then confirm the import.</p>
      </div>

      @if($error)
        <div class="import-alert">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $error }}
        </div>
      @endif

      @if($success)
        <div class="import-alert ok">
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          {{ $success }}
        </div>
      @endif

      @if(empty($import))
        {{-- ── PHASE 1: Upload ── --}}
        <div class="import-panel">
          <h2 class="panel-title">
            Upload a harvest file
            <button type="button" class="help-btn" id="fileHelpBtn" data-help-toggle
                    aria-expanded="false" aria-controls="fileHelpPop"
                    title="What file do I need?">?</button>
          </h2>
          <p class="panel-desc">
            The first row must be a header with column names. Every report is matched to a person on the
            <b>members list</b> by exact name — rows whose name is not on the list fail into the review table with a
            "did you mean" hint instead of being skipped silently. Reports are filed against the member record, not a
            system account; nothing is saved until you review the preview.
          </p>

          <form method="POST" action="{{ route('admin.harvests.import.preview') }}" enctype="multipart/form-data" id="importForm">
            @csrf
            <label class="drop-zone" for="fileInput" id="dropZone">
              <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <p><strong>Drag &amp; drop</strong> a file here, or <strong>browse</strong></p>
              <p class="dz-sub">Accepts {{ \App\Support\ImportFormats::accept() }} — up to 5 MB</p>
              <input type="file" name="file" id="fileInput" accept="{{ \App\Support\ImportFormats::accept() }}">
              <div class="file-selected" id="fileSelected"></div>
            </label>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
              <button type="submit" class="btn-users" id="previewBtn" disabled>
                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Preview Import
              </button>
            </div>
          </form>

          <div class="template-row">
            <span>Starting from scratch?</span>
            <a href="{{ route('admin.harvests.import.template', 'csv') }}" class="btn-users ghost">
              <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download CSV Template
            </a>
            <a href="{{ route('admin.harvests.import.template', 'xlsx') }}" class="btn-users ghost">
              <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download Excel Template
            </a>
          </div>

          <div class="help-pop" id="fileHelpPop" hidden role="dialog" aria-labelledby="fileHelpTitle">
            <div class="help-pop-head">
              <div>
                <h3 id="fileHelpTitle">What your file needs</h3>
                <p>Read this before you upload — nothing is imported until you have previewed it.</p>
              </div>
              <button type="button" class="help-close" data-help-close aria-label="Close">&times;</button>
            </div>

            <div class="import-bullets">
              <div class="bullet">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div><b>Formats</b><br>{{ \App\Support\ImportFormats::label() }} — delimiters are auto-detected (comma, semicolon or tab).</div>
              </div>
              <div class="bullet">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div><b>Required columns</b><br>Member&nbsp;Name, Crop, Season (Wet/Dry), Land&nbsp;Area&nbsp;(ha), Actual&nbsp;Yield&nbsp;(MT), Actual&nbsp;Expenses&nbsp;(PHP) and Harvest&nbsp;Date.</div>
              </div>
              <div class="bullet">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div><b>Names must match exactly</b><br>One typo fails the row — the preview tells you the closest name on the members list so you can fix it before importing.</div>
              </div>
              <div class="bullet">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div><b>Import the members first</b><br>Reports belong to members, not accounts. Upload <b>members.csv</b> from the Members page before this file.</div>
              </div>
            </div>
          </div>
        </div>
      @else
        {{-- ── PHASE 2: Preview ── --}}
        <div class="import-panel" style="max-width:none;">
          <h2 class="panel-title">Preview harvest reports</h2>
          <p class="panel-desc">
            File: <b>{{ $import['filename'] }}</b>. Rows with issues are <b>not</b> imported — fix them in your file
            and upload again. Ready rows are filed against the matching member when you confirm.
          </p>

          <div class="match-note">
            <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
              A failed name means the spelling on this row is not on the members list. Correct it in your file so it
              matches the member's registered name exactly, then upload again — or add the missing person to the
              members list first.
            </div>
          </div>

          <div class="pill-row">
            <div class="pill green">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="pill-num">{{ $import['valid'] }}</span> Ready to import
            </div>
            <div class="pill red">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span class="pill-num">{{ $import['errors'] }}</span> Rows to fix
            </div>
          </div>

          <div class="csv-preview-grid">
            <x-table-toolbar
                uid="csvPreview"
                work-table="csvPreviewTable"
                per-page="10"
                noun="rows"
                search-placeholder="Search member, crop, or date…"
            />

            {{-- The edit bar sits on top of the table and is available whether
                 or not any row failed — a typo is fixed here, not by
                 re-uploading the whole file. The cells post to their own
                 re-check form, so Enter in a cell re-checks too. --}}
            <div class="ocr-table-bar">
              <span class="bar-note" id="hvBarNote">
                Every row can be edited, not just the failing ones — click <b>Edit rows</b> to fix a cell in place.
              </span>
              <span class="bar-note" id="hvBarEditNote" hidden>
                Fix the cells you need, then <b>Save &amp; Re-check</b>. Discard edits throws them away.
              </span>
              <div class="bar-actions">
                <button type="button" class="btn-users ghost" id="hvEditBtn">
                  <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit rows
                </button>
                <button type="button" class="btn-users danger" id="hvCancelEditBtn" hidden>Discard edits</button>
                <button type="submit" class="btn-users" id="hvSaveBtn" form="hvRecheckForm" hidden>
                  <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  Save &amp; Re-check
                </button>
              </div>
            </div>

            <div class="import-table-wrap table-scroll" id="hvTableWrap">
              <form method="POST" action="{{ route('admin.harvests.import.revalidate') }}" id="hvRecheckForm">
                @csrf
                <input type="hidden" name="token" value="{{ $import['token'] }}">
                <table class="import-table" id="csvPreviewTable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Crop</th>
                    <th>Seed Variety</th>
                    <th>Season</th>
                    <th>Area (ha)</th>
                    <th>Yield (MT)</th>
                    <th>Expenses</th>
                    <th>Harvested</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($import['rows'] as $index => $row)
                    @php
                        $raw = is_array($row['_raw'] ?? null) ? $row['_raw'] : [];
                        // Inputs show what passed validation first; a failed
                        // cell falls back to what the file actually said so it
                        // can be fixed in place instead of retyped.
                        $editMember = $row['data']['member'] ?? ($raw['member'] ?? '');
                        $editCrop = $row['data']['crop'] ?? ($raw['crop'] ?? '');
                        $editVariety = $row['data']['variety'] ?? ($raw['variety'] ?? '');
                        $editSeason = $row['data']['season'] ?? ($raw['season'] ?? '');
                        $editArea = $row['data']['land_area'] ?? ($raw['land_area'] ?? '');
                        $editYield = $row['data']['yield'] ?? ($raw['yield'] ?? '');
                        $editExpenses = $row['data']['expenses'] ?? ($raw['expenses'] ?? '');
                        $editDate = $row['data']['harvest_date'] ?? ($raw['harvest_date'] ?? '');
                        // A season the file wrote that validation rejected still
                        // has to be offered in the dropdown — or choosing a
                        // valid one would silently overwrite what was written.
                        $seasonInvalid = $editSeason !== ''
                            && ! in_array(mb_strtolower($editSeason), ['wet', 'dry'], true);
                    @endphp
                    <tr class="{{ $row['issues'] ? 'row-error' : '' }}">
                      <td class="row-num">
                        {{ $row['_row'] }}
                        <input type="hidden" name="rows[{{ $index }}][__row]" value="{{ $row['_row'] }}">
                      </td>
                      <td>
                        <div class="cell-static">{{ $row['data']['member'] ?? '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][member]" value="{{ $editMember }}"
                                 placeholder="Member name" autocomplete="off">
                        </div>
                      </td>
                      <td>
                        <div class="cell-static">{{ $row['data']['crop'] ?? '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][crop]" value="{{ $editCrop }}"
                                 placeholder="Crop" autocomplete="off">
                        </div>
                      </td>
                      <td>
                        <div class="cell-static">{{ ($row['data']['variety'] ?? '') !== '' ? $row['data']['variety'] : '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][variety]" value="{{ $editVariety }}"
                                 placeholder="Seed variety (optional)" autocomplete="off">
                        </div>
                      </td>
                      <td>
                        <div class="cell-static">
                          @if(!empty($row['data']['season']))
                            <span class="season-chip {{ $row['data']['season'] }}">{{ $row['data']['season'] }}</span>
                          @else
                            —
                          @endif
                        </div>
                        <div class="cell-edit">
                          <select class="ocr-cell" data-hv-cell name="rows[{{ $index }}][season]">
                            @if($editSeason === '')
                              <option value="" selected>Choose season…</option>
                            @elseif($seasonInvalid)
                              <option value="{{ $editSeason }}" selected>{{ $editSeason }}</option>
                            @endif
                            <option value="Wet" @selected($editSeason === 'wet')>Wet</option>
                            <option value="Dry" @selected($editSeason === 'dry')>Dry</option>
                          </select>
                        </div>
                      </td>
                      <td class="num-cell">
                        <div class="cell-static">{{ $row['data']['land_area'] ?? '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][land_area]" value="{{ $editArea }}"
                                 placeholder="Hectares" autocomplete="off">
                        </div>
                      </td>
                      <td class="num-cell">
                        <div class="cell-static">{{ $row['data']['yield'] ?? '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][yield]" value="{{ $editYield }}"
                                 placeholder="MT" autocomplete="off">
                        </div>
                      </td>
                      <td class="num-cell">
                        <div class="cell-static">{{ isset($row['data']['expenses']) ? '₱' . number_format((float) $row['data']['expenses'], 2) : '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][expenses]" value="{{ $editExpenses }}"
                                 placeholder="₱ expenses" autocomplete="off">
                        </div>
                      </td>
                      <td class="num-cell">
                        <div class="cell-static">{{ $row['data']['harvest_date'] ?? '—' }}</div>
                        <div class="cell-edit">
                          <input type="text" class="ocr-cell" data-hv-cell
                                 name="rows[{{ $index }}][harvest_date]" value="{{ $editDate }}"
                                 placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
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
                    <tr><td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">No rows to show.</td></tr>
                  @endforelse
                </tbody>
              </table>
              </form>
            </div>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
            <form method="POST" action="{{ route('admin.harvests.import.confirm') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Importing…';">
              @csrf
              <input type="hidden" name="token" value="{{ $import['token'] }}">
              <button type="submit" class="btn-users" id="hvImportBtn" {{ $import['valid'] === 0 ? 'disabled' : '' }}>
                <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Import {{ $import['valid'] }} Report{{ $import['valid'] === 1 ? '' : 's' }}
              </button>
            </form>
            <form method="POST" action="{{ route('admin.harvests.import.cancel') }}">
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

      // ---- "?" help panels --------------------------------------------
      // No scrim on purpose, so the page behind stays sharp and usable —
      // clicks outside the panel (or Escape) simply close it again.
      var helpToggles = document.querySelectorAll('[data-help-toggle]');

      function helpPopFor(btn) {
        var id = btn && btn.getAttribute('aria-controls');
        return id ? document.getElementById(id) : null;
      }

      function closeHelp(btn) {
        var pop = helpPopFor(btn);
        if (!pop || pop.hidden) return;
        pop.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
      }

      function closeAllHelp() {
        Array.prototype.forEach.call(helpToggles, closeHelp);
      }

      Array.prototype.forEach.call(helpToggles, function (btn) {
        var pop = helpPopFor(btn);
        if (!pop) return;

        btn.addEventListener('click', function (event) {
          event.stopPropagation();
          var wasOpen = !pop.hidden;
          closeAllHelp();
          if (wasOpen) return;
          pop.hidden = false;
          btn.setAttribute('aria-expanded', 'true');
        });

        pop.addEventListener('click', function (event) {
          event.stopPropagation();
          if (event.target && event.target.closest && event.target.closest('[data-help-close]')) {
            closeHelp(btn);
            btn.focus();
          }
        });
      });

      document.addEventListener('click', closeAllHelp);

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var openButton = null;
        Array.prototype.forEach.call(helpToggles, function (btn) {
          var pop = helpPopFor(btn);
          if (pop && !pop.hidden) { closeHelp(btn); openButton = btn; }
        });
        if (openButton) openButton.focus();
      });
    })();

    // ---- Preview table: always-available editing ---------------------
    // Every row is editable whether or not it failed, the cells post to
    // their own re-check form, and importing is locked out until the
    // officer saves (or discards) what they typed.
    (function () {
      var hvForm = document.getElementById('hvRecheckForm');

      if (!hvForm) return;

      var hvWrap = document.getElementById('hvTableWrap');
      var hvEditBtn = document.getElementById('hvEditBtn');
      var hvCancelBtn = document.getElementById('hvCancelEditBtn');
      var hvSaveBtn = document.getElementById('hvSaveBtn');
      var hvImportBtn = document.getElementById('hvImportBtn');
      var hvBarNote = document.getElementById('hvBarNote');
      var hvBarEditNote = document.getElementById('hvBarEditNote');
      var hvSnapshot = null;
      // Import may already be locked server-side (nothing ready to import);
      // remember which is which so leaving edit mode restores the right state.
      var hvImportLocked = hvImportBtn ? hvImportBtn.disabled : false;

      var hvCells = function () {
        return Array.prototype.slice.call(hvForm.querySelectorAll('[data-hv-cell]'));
      };

      function setHvEditing(on) {
        hvWrap.classList.toggle('editing', on);
        hvEditBtn.hidden = on;
        hvCancelBtn.hidden = !on;
        hvSaveBtn.hidden = !on;
        hvBarNote.hidden = on;
        hvBarEditNote.hidden = !on;

        if (hvImportBtn) {
          // While editing, the only safe buttons are Save & Re-check and
          // Discard — importing from here would ignore what was typed.
          hvImportBtn.disabled = on || hvImportLocked;
        }

        if (on) {
          // Remember the table as it stands so Discard can put it back.
          hvSnapshot = hvCells().map(function (el) { return el.value; });
          var first = hvForm.querySelector('[data-hv-cell]');
          if (first) first.focus();
        }
      }

      hvEditBtn.addEventListener('click', function () { setHvEditing(true); });

      hvCancelBtn.addEventListener('click', function () {
        if (hvSnapshot) {
          hvCells().forEach(function (el, i) {
            if (hvSnapshot[i] !== undefined) el.value = hvSnapshot[i];
          });
          hvSnapshot = null;
        }
        setHvEditing(false);
      });

      // Save & Re-check submits this form through its form= reference; the
      // reload replaces the page, so editing mode simply ends with it.
    })();
  </script>
@endsection
