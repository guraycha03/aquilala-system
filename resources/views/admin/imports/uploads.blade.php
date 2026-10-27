@extends('layouts.sidebar')

@section('title', $title)

@section('content')
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

    .import-page { position: relative; font-family: inherit; }
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
      width: 380px; height: 380px;
      background: rgba(16, 185, 129, 0.08);
      top: -100px; right: -100px;
    }
    .import-page::after {
      width: 320px; height: 320px;
      background: rgba(34, 197, 94, 0.06);
      bottom: -80px; left: -80px;
    }
    .import-content { position: relative; z-index: 1; }

    .header-row { margin-bottom: 24px; }
    .header-row h1 {
      font-size: 24px; font-weight: 700; color: var(--slate-900);
      letter-spacing: -0.02em; margin: 0 0 4px 0;
    }
    .header-row p { font-size: 14px; color: var(--slate-600); margin: 0; max-width: 760px; }

    .btn-users {
      display: inline-flex; align-items: center; justify-content: center;
      gap: 8px; text-decoration: none; cursor: pointer; font-family: inherit;
      line-height: 1.25; background: var(--primary); border: 1px solid rgba(255, 255, 255, 0.2);
      color: #ffffff; padding: 10px 18px; border-radius: var(--radius-md);
      font-size: 13.5px; font-weight: 600;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(21, 128, 61, 0.15), var(--shadow-sm);
    }
    .btn-users:hover {
      background: var(--primary-hover); transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(21, 128, 61, 0.25), var(--shadow-md);
    }
    .btn-users:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
    .btn-users svg {
      width: 16px; height: 16px; stroke: currentColor; fill: none;
      stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    }
    .btn-users.ghost {
      background: #ffffff; color: var(--slate-700);
      border: 1px solid var(--slate-200); box-shadow: var(--shadow-sm);
    }
    .btn-users.ghost:hover {
      background: var(--slate-50); border-color: var(--slate-300); color: var(--slate-900);
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
      max-width: 1100px;
    }

    .file-name {
      display: block; font-weight: 600; color: var(--slate-800);
      font-size: 13.5px; word-break: break-all;
    }
    .file-sub { display: block; font-size: 11.5px; color: var(--slate-500); margin-top: 3px; }

    .src-badge {
      display: inline-block; margin-top: 5px; padding: 2px 9px;
      border-radius: 999px; font-size: 10.5px; font-weight: 700;
      letter-spacing: 0.04em; text-transform: uppercase;
      background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border);
    }
    .src-badge.ocr { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }

    .rows-main { font-size: 13px; color: var(--slate-700); font-weight: 600; }

    .btn-dl {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: var(--radius-sm);
      background: #ffffff; border: 1px solid var(--slate-200);
      color: var(--slate-700); font-size: 12.5px; font-weight: 600;
      text-decoration: none; white-space: nowrap; cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-dl:hover { background: var(--slate-50); border-color: var(--slate-300); color: var(--slate-900); }
    .btn-dl svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Removing records off a wrong file — a destructive action, so it reads
       in red but stays visually the same quiet pill as Download. */
    .btn-dl.danger { color: #dc2626; border-color: #fecaca; background: #ffffff; }
    .btn-dl.danger:hover { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }

    /* Flash messages — removal results land here after the redirect back. */
    .page-flash { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 600; margin: 0 0 14px; border: 1px solid; }
    .page-flash--success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
    .page-flash--error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

    /* The file column is how a file opens: hover shows it's live. The name
       stays the loudest thing in the cell — no extra view button. */
    .file-open { display: block; text-decoration: none; color: inherit; border-radius: 6px; padding: 2px 0; cursor: pointer; }
    .file-open .file-name { transition: color 0.15s ease; }
    .file-open:hover .file-name { color: var(--primary); text-decoration: underline; text-underline-offset: 2px; }
    .file-open:focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }
    /* The panel goes dark in dark mode, so the name has to leave navy behind. */
    [data-theme="dark"] .file-name { color: #dbe7df; }
    [data-theme="dark"] .file-open:hover .file-name { color: #6ee7a0; }

    /* Scans open over the page instead of downloading. Backdrop click, the ×,
       or Esc all close it. */
    .up-viewer { position: fixed; inset: 0; z-index: 210; background: rgba(5, 12, 9, 0.9); display: flex; align-items: center; justify-content: center; padding: 24px; }
    .up-viewer[hidden] { display: none; }
    .upv-close { position: absolute; top: 16px; right: 18px; width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.3); background: rgba(15, 23, 42, 0.7); color: #ffffff; font-size: 24px; line-height: 1; cursor: pointer; transition: background 0.15s ease; }
    .upv-close:hover { background: rgba(15, 23, 42, 0.95); }
    .upv-frame { margin: 0; max-width: min(1100px, 94vw); display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .upv-frame img { max-width: 100%; max-height: 80vh; border-radius: 10px; background: #ffffff; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.55); object-fit: contain; }
    .upv-caption { margin: 0; color: #d7e3da; font-size: 13px; font-weight: 600; text-align: center; word-break: break-all; }

    .uploads-empty { text-align: center; padding: 56px 20px; color: var(--slate-400); }
    .uploads-empty svg {
      width: 44px; height: 44px; stroke: currentColor; fill: none;
      stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; opacity: 0.7;
    }
    .uploads-empty p { font-size: 14px; margin: 14px 0 0 0; line-height: 1.6; }
  </style>

  <x-breadcrumb :items="[
    ['label' => $backLabel, 'url' => route($backRoute)],
    ['label' => $title],
  ]"/>

  @if (session('success'))
    <div class="page-flash page-flash--success" role="status">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="page-flash page-flash--error" role="alert">{{ session('error') }}</div>
  @endif

  <div class="import-page">
    <div class="import-content">

      <div class="header-row">
        <h1>{{ $title }}</h1>
        {{-- The layout hides a plain <p> after an <h1>; this one explains the
             archive, so it opts in like the other pages do. --}}
        <p class="keep-sub">{{ $subtitle }}</p>
      </div>

      <div class="import-panel">
        @if($uploads->isNotEmpty())
          <x-table-toolbar
              uid="uploads-{{ $kind }}"
              work-table="uploadsTable"
              per-page="10"
              noun="files"
              search-placeholder="Search by file name or uploader…"
              :sorts="[
                  ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Uploaded (newest)'],
                  ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Uploaded (oldest)'],
                  ['key' => 'name', 'dir' => 1, 'type' => 'str', 'label' => 'File name (A–Z)'],
                  ['key' => 'name', 'dir' => -1, 'type' => 'str', 'label' => 'File name (Z–A)'],
              ]"
          />

          <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
            <div class="app-scroll">
              <table id="uploadsTable" class="data-table">
                <thead>
                  <tr>
                    <th>File</th>
                    <th>Uploaded</th>
                    <th>By</th>
                    <th>Rows</th>
                    <th>Size</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($uploads as $upload)
                    @php
                      $search = strtolower($upload->original_name.' '.($upload->user?->name ?? ''));
                    @endphp
                    <tr data-search="{{ $search }}"
                        data-name="{{ strtolower($upload->original_name) }}"
                        data-date="{{ $upload->created_at->format('Y-m-d') }}">
                      <td>
                        @php
                          // The file cell itself is the open affordance — no
                          // separate view button. Scans open inside the page's
                          // viewer; a spreadsheet downloads, because opening
                          // one means handing it to the device's own app.
                          $isImage = $upload->isImage();
                          $openUrl = $upload->isDownloadable()
                              ? route($isImage ? $previewRoute : $downloadRoute, $upload)
                              : null;
                        @endphp
                        @if($openUrl)
                          <a class="file-open" href="{{ $openUrl }}"
                             @if($isImage) data-up-preview @endif
                             title="{{ $isImage ? 'View '.$upload->original_name.' in the viewer' : 'Download '.$upload->original_name.' to open it on this device' }}">
                        @endif
                        <span class="file-name">{{ $upload->original_name }}</span>
                        <span class="src-badge {{ $upload->source === \App\Models\ImportUpload::SOURCE_OCR ? 'ocr' : '' }}">
                          {{ $upload->sourceLabel() }}
                        </span>
                        @if($openUrl)
                          </a>
                        @endif
                      </td>
                      <td>
                        {{ $upload->created_at->format('M j, Y') }}
                        <span class="file-sub">{{ $upload->created_at->format('g:i A') }}</span>
                      </td>
                      <td>{{ $upload->user?->name ?? 'Removed account' }}</td>
                      <td>
                        <div class="rows-main">
                          {{ number_format($upload->rows_imported) }} of {{ number_format($upload->rows_total) }} imported
                        </div>
                        @if($upload->rows_skipped > 0)
                          <span class="file-sub">
                            {{ number_format($upload->rows_skipped) }} duplicate{{ $upload->rows_skipped > 1 ? 's' : '' }} skipped
                          </span>
                        @endif
                        @if($kind === \App\Models\ImportUpload::KIND_HARVEST && (int) $upload->records_present !== (int) $upload->rows_imported)
                          <span class="file-sub">
                            {{ number_format((int) $upload->records_present) }} of {{ number_format($upload->rows_imported) }} still on the list
                          </span>
                        @endif
                      </td>
                      <td>{{ $upload->sizeLabel() }}</td>
                      <td style="text-align: right; white-space: nowrap;">
                        @if($upload->isDownloadable())
                          <a class="btn-dl"
                             href="{{ route($downloadRoute, $upload) }}"
                             title="Download {{ $upload->original_name }}">
                            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                          </a>
                        @else
                          <span class="file-sub" title="The file itself is no longer on disk">File unavailable</span>
                        @endif
                        {{-- Wrong file? Take back exactly what it put on the
                             Harvest Reports list, in one confirmed click — the
                             file itself stays archived for a re-import. Hidden
                             once nothing from this file is still on the list. --}}
                        @if($kind === \App\Models\ImportUpload::KIND_HARVEST && (int) ($upload->records_present ?? 0) > 0)
                          <form method="POST"
                                action="{{ route('admin.harvests.uploads.remove-records', $upload) }}"
                                style="display: inline-block; margin-left: 8px;">
                            @csrf
                            <button type="submit" class="btn-dl danger"
                                    data-aqui-tone="danger"
                                    data-aqui-title="Remove {{ number_format((int) $upload->records_present) }} record{{ (int) $upload->records_present === 1 ? '' : 's' }}?"
                                    data-aqui-confirm-label="Remove records"
                                    data-aqui-confirm="These {{ number_format((int) $upload->records_present) }} records were imported from {{ $upload->original_name }} and will be removed from Harvest Reports. The file stays here in Uploads, so you can re-import it any time. Linked planting batches become available to report again.">
                              Remove records
                            </button>
                          </form>
                        @endif
                      </td>
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
        @else
          <div class="uploads-empty">
            <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            <p>No files have been archived yet.<br>
               Each import keeps a copy of the exact file it was built from — they will appear here once one is confirmed.</p>
          </div>
        @endif
      </div>

    </div>
  </div>

  {{-- Viewing a scan: the image renders over the page. Spreadsheets never
       land here — their file cell is a plain download link. --}}
  <div class="up-viewer" id="upViewer" hidden>
    <button type="button" class="upv-close" id="upvClose" aria-label="Close the image viewer">&times;</button>
    <figure class="upv-frame">
      <img id="upvImg" alt="">
      <figcaption class="upv-caption" id="upvCaption"></figcaption>
    </figure>
  </div>

  <script>
    (function () {
      // Bind once for the session: the handlers live on document, so a Turbo
      // body swap brings fresh markup without stacking duplicate listeners.
      if (window.__upViewerReady) return;
      window.__upViewerReady = true;

      function viewer() { return document.getElementById('upViewer'); }
      function close() { var v = viewer(); if (v) v.hidden = true; }

      document.addEventListener('click', function (e) {
        var opener = e.target && e.target.closest ? e.target.closest('a[data-up-preview]') : null;
        if (opener) {
          e.preventDefault();
          var v = viewer(); if (!v) return;
          document.getElementById('upvImg').src = opener.getAttribute('href');
          var name = opener.querySelector('.file-name');
          document.getElementById('upvCaption').textContent = name ? name.textContent.trim() : '';
          v.hidden = false;
          return;
        }
        var box = viewer();
        if (box && !box.hidden) {
          var t = e.target;
          if (t === box || (t && t.closest && t.closest('.upv-close'))) close();
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
      });
    })();
  </script>
@endsection
