{{--
  The shared archive table for one importer's confirmed files.

  Both hosts pass the same four variables: the $uploads collection, the
  $kind ('members' or 'harvest'), and the route names used to $downloadRoute
  and $previewRoute those files. Keeping the markup in one place means the
  standalone Harvest Uploads page and the Members page's inline Uploads panel
  can never drift apart.

  The styles are scoped to .uploads-list so the panel can live on a page that
  already has its own table and button styles.
--}}
<div class="uploads-list">
  <style>
    .uploads-list {
      --upl-primary: #15803d;
      --upl-primary-light: #f0fdf4;
      --upl-primary-border: #bbf7d0;
      --upl-slate-50: #f8fafc;
      --upl-slate-200: #e2e8f0;
      --upl-slate-300: #cbd5e1;
      --upl-slate-500: #64748b;
      --upl-slate-600: #475569;
      --upl-slate-700: #334155;
      --upl-slate-800: #1e293b;
      --upl-radius-sm: 8px;
      font-family: inherit;
    }

    .uploads-list .file-name {
      display: block; font-weight: 600; color: var(--upl-slate-800);
      font-size: 13.5px; word-break: break-all;
    }
    .uploads-list .file-sub { display: block; font-size: 11.5px; color: var(--upl-slate-500); margin-top: 3px; }

    .uploads-list .src-badge {
      display: inline-block; margin-top: 5px; padding: 2px 9px;
      border-radius: 999px; font-size: 10.5px; font-weight: 700;
      letter-spacing: 0.04em; text-transform: uppercase;
      background: var(--upl-primary-light); color: var(--upl-primary); border: 1px solid var(--upl-primary-border);
    }
    .uploads-list .src-badge.ocr { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }

    .uploads-list .rows-main { font-size: 13px; color: var(--upl-slate-700); font-weight: 600; }

    .uploads-list .btn-dl {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: var(--upl-radius-sm);
      background: #ffffff; border: 1px solid var(--upl-slate-200);
      color: var(--upl-slate-700); font-size: 12.5px; font-weight: 600;
      text-decoration: none; white-space: nowrap; cursor: pointer;
      transition: all 0.15s ease;
    }
    .uploads-list .btn-dl:hover { background: var(--upl-slate-50); border-color: var(--upl-slate-300); color: var(--upl-slate-800); }
    .uploads-list .btn-dl svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .uploads-list .btn-dl.danger { color: #dc2626; border-color: #fecaca; background: #ffffff; }
    .uploads-list .btn-dl.danger:hover { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }

    /* The file cell itself opens the file: hover shows it is live. */
    .uploads-list .file-open { display: block; text-decoration: none; color: inherit; border-radius: 6px; padding: 2px 0; cursor: pointer; }
    .uploads-list .file-open .file-name { transition: color 0.15s ease; }
    .uploads-list .file-open:hover .file-name { color: var(--upl-primary); text-decoration: underline; text-underline-offset: 2px; }
    .uploads-list .file-open:focus-visible { outline: 2px solid var(--upl-primary); outline-offset: 3px; }
    [data-theme="dark"] .uploads-list .file-name { color: #dbe7df; }
    [data-theme="dark"] .uploads-list .file-open:hover .file-name { color: #6ee7a0; }

    .uploads-list .uploads-empty { text-align: center; padding: 46px 20px; color: var(--upl-slate-500); }
    .uploads-list .uploads-empty svg {
      width: 44px; height: 44px; stroke: currentColor; fill: none;
      stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; opacity: 0.7;
    }
    .uploads-list .uploads-empty p { font-size: 14px; margin: 14px 0 0 0; line-height: 1.6; }
  </style>

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
                    // Scans open in the page's viewer; a spreadsheet downloads,
                    // because opening one means handing it to the device's app.
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
                  {{-- Only the harvest importer can take records back; the file
                       itself stays archived so it can be re-imported. --}}
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
