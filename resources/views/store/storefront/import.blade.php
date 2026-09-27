@extends('layouts.sidebar')

@section('title', 'Import Products')

@section('content')
<style>
    .imp-wrap { max-width: 1180px; margin: 0 auto; }
    .imp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .imp-head h1 { font-size: 22px; font-weight: 700; color: #1e2922; margin: 0 0 4px; }
    .imp-head p { margin: 0; font-size: 14px; color: #6b7c72; }

    .btn-primary, .btn-secondary, .btn-ai, .btn-danger-ghost {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600;
        text-decoration: none; border: 1px solid transparent; cursor: pointer;
        font-family: inherit; transition: background .15s ease, border-color .15s ease;
    }
    .btn-primary svg, .btn-secondary svg, .btn-ai svg, .btn-danger-ghost svg {
        width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2;
        stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0;
    }
    .btn-primary { background: #1f7a5c; color: #fff; }
    .btn-primary:hover { background: #186349; }
    .btn-primary:disabled { background: #9bb5ab; cursor: not-allowed; }
    .btn-secondary { background: #fff; color: #1e2922; border-color: #cdd8d2; }
    .btn-secondary:hover { background: #f5f8f6; }
    .btn-ai { background: #4c1d95; color: #fff; }
    .btn-ai:hover { background: #3b1676; }
    .btn-danger-ghost { background: transparent; color: #b91c1c; border-color: #f0c9c9; }
    .btn-danger-ghost:hover { background: #fef2f2; }

    .panel { background: #fff; border: 1px solid #e3eae6; border-radius: 12px; padding: 22px; margin-bottom: 18px; }
    .panel h2 { font-size: 16px; font-weight: 700; color: #1e2922; margin: 0 0 6px; }
    .panel .sub { font-size: 13px; color: #6b7c72; margin: 0 0 16px; }

    /* Drop zone — a <label> is inline by default, which makes the dashed border
       shrink-wrap its content instead of filling the panel. Flex column forces a
       full-width box with the icon, text and chip stacked and centred. */
    .dropzone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 100%;
        box-sizing: border-box;
        border: 2px dashed #c6d4cd;
        border-radius: 12px;
        background: #fbfdfc;
        padding: 40px 24px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .dropzone:hover, .dropzone.drag {
        border-color: #1f7a5c;
        background: #f2f9f6;
        box-shadow: 0 0 0 4px rgba(31, 122, 92, .08);
    }
    .dropzone.has-file { border-style: solid; border-color: #1f7a5c; background: #f2f9f6; }
    .dropzone .dz-icon {
        display: flex; align-items: center; justify-content: center;
        width: 56px; height: 56px; margin-bottom: 12px;
        border-radius: 50%;
        background: #e7f2ed;
        color: #1f7a5c;
        transition: transform .15s ease, background .15s ease;
    }
    .dropzone .dz-icon svg { width: 26px; height: 26px; stroke-width: 1.75; }
    .dropzone:hover .dz-icon, .dropzone.drag .dz-icon { transform: translateY(-2px); background: #d8ebe3; }
    .dropzone.has-file .dz-icon { background: #d8ebe3; }
    .dropzone .dz-title { font-size: 15px; font-weight: 600; color: #1e2922; }
    .dropzone .dz-title strong { color: #1f7a5c; }
    .dropzone .dz-hint { font-size: 12.5px; color: #7d8f86; }
    .dropzone .dz-file {
        display: none;
        margin-top: 12px;
        padding: 6px 12px;
        background: #e7f6ee;
        border: 1px solid #b9e2ca;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #14663f;
        max-width: 100%;
        word-break: break-all;
    }
    .dropzone .dz-file.show { display: inline-block; }
    .dropzone input { display: none; }

    .btn-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 18px; }

    .note-list { margin: 0; padding-left: 20px; font-size: 13px; color: #44564c; line-height: 1.75; }
    .note-list strong { color: #1e2922; }

    /* Requirement cards, same grid pattern as the admin register import page. */
    .req-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 14px;
        margin-top: 18px;
        padding-top: 20px;
        border-top: 1px solid #eef2f0;
    }
    .req-card {
        display: flex;
        gap: 10px;
        padding: 12px 14px;
        background: #f7faf9;
        border: 1px solid #e6ede9;
        border-radius: 10px;
    }
    .req-card svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; stroke: #1f7a5c; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .req-card.warn svg { stroke: #b45309; }
    .req-card b { display: block; color: #1e2922; font-size: 12.5px; margin-bottom: 2px; }
    .req-card span { color: #5c6f65; font-size: 12.5px; line-height: 1.55; }
    .req-card code { background: #e7f2ed; color: #14663f; padding: 1px 5px; border-radius: 4px; font-size: 11.5px; }

    .chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .chip-ok { background: #e7f6ee; color: #14663f; }
    .chip-bad { background: #fdecec; color: #b91c1c; }
    .chip-warn { background: #fff5e5; color: #92400e; }
    .chip-mute { background: #eef2f0; color: #55665d; }
    .pill-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }

    .col-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 12px; color: #6b7c72; margin-top: 10px; }
    .col-legend span { display: inline-flex; align-items: center; gap: 5px; }
    .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    .dot-req { background: #1f7a5c; }
    .dot-fert { background: #b45309; }
    .dot-reg { background: #4c1d95; }
    .dot-opt { background: #b6c4bd; }

    /* Review table */
    .table-scroll { overflow-x: auto; border: 1px solid #e3eae6; border-radius: 10px; background: #fff; }
    table.review { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.review th, table.review td { padding: 8px 10px; border-bottom: 1px solid #eef2f0; text-align: left; vertical-align: top; }
    table.review thead th { background: #eaf1ed; font-size: 11.5px; font-weight: 700; color: #26332c; text-transform: uppercase; letter-spacing: .03em; position: sticky; top: 0; z-index: 2; white-space: nowrap; }
    table.review tbody tr.is-bad { background: #fff8f8; }
    /* Skipped rows used to be dimmed with opacity, which faded the error text
       too — the merchant could not read why a row was rejected. Muted surfaces
       still mark the row as skipped while keeping every glyph readable. */
    table.review tbody tr.is-off { background: #f6f8f7; }
    table.review tbody tr.is-off > td:first-child { box-shadow: inset 3px 0 0 #c3cfc9; }
    table.review tbody tr.is-off input.cell, table.review tbody tr.is-off select.cell { background: #eef2f0; border-color: #d3ddd8; color: #4a5b52; }
    table.review tbody tr.is-off .err { color: #c0392b; }
    table.review td.rowno { font-weight: 700; color: #4a5b52; white-space: nowrap; }
    table.review .skip-tag { display: block; margin-top: 3px; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #7d8f86; }
    table.review input[type="text"], table.review input[type="number"], table.review input[type="date"], table.review select {
        width: 100%; min-width: 110px; padding: 7px 9px; border: 1px solid #b9c7c0; border-radius: 6px;
        font-size: 13px; font-family: inherit; background: #fff; color: #16211b;
    }
    table.review input.cell-bad { border-color: #d64545; border-width: 2px; background: #fff5f5; }
    table.review input:focus, table.review select:focus { outline: 2px solid #1f7a5c66; border-color: #1f7a5c; }
    table.review .err { display: block; color: #b91c1c; font-size: 12px; font-weight: 600; margin-top: 4px; line-height: 1.45; }
    table.review .note { display: block; color: #92400e; font-size: 12px; margin-top: 4px; line-height: 1.45; }

    .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; font-size: 13px; color: #44564c; }
    .filter-bar label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }

    .empty-state { text-align: center; padding: 44px 20px; color: #6b7c72; }
    .empty-state h3 { font-size: 16px; color: #1e2922; margin: 0 0 6px; }

    .alert-success { background: #e7f6ee; border: 1px solid #b9e2ca; color: #14663f; padding: 12px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    .alert-error { background: #fdecec; border: 1px solid #f3c6c6; color: #b91c1c; padding: 12px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
    .alert-error ul { margin: 6px 0 0; padding-left: 20px; }

    details.howto { margin-top: 12px; }
    details.howto summary { cursor: pointer; font-size: 13px; font-weight: 600; color: #1f7a5c; }
    details.howto[open] summary { margin-bottom: 10px; }

    .foot-note { font-size: 12px; color: #8a9a92; margin-top: 12px; }
    @media (max-width: 720px) {
        .imp-head { flex-direction: column; }
        .btn-row .btn-primary, .btn-row .btn-secondary, .btn-row .btn-ai, .btn-row .btn-danger-ghost { width: 100%; justify-content: center; }
    }
</style>

<div class="imp-wrap">
    <x-breadcrumb :items="[['label' => 'My Store', 'url' => route('store.storefront.index')], ['label' => 'Import Products']]" />

    <div class="imp-head">
        <div>
            <h1>Import Products</h1>
            <p>Add many products at once from a CSV or Excel file.</p>
        </div>
        <a href="{{ route('store.storefront.index') }}" class="btn-secondary">Back to Storefront</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (empty($staged['rows']))
        {{-- ============================ PHASE 1: UPLOAD ============================ --}}
        <form method="POST" action="{{ route('store.products.import.preview') }}" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <div class="panel">
                <h2>1. Choose your file</h2>
                <p class="sub">CSV, XLS or XLSX. Up to {{ number_format($maxRows) }} products per file, {{ intdiv($maxFileKb, 1024) }} MB maximum.</p>

                <label class="dropzone" id="dropzone" for="importFile">
                    <span class="dz-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <path d="M17 8l-5-5-5 5"/>
                            <path d="M12 3v12"/>
                        </svg>
                    </span>
                    <div class="dz-title"><strong>Drag &amp; drop</strong> your file here, or <strong>browse</strong></div>
                    <div class="dz-hint" id="dzHint">Accepts {{ strtoupper(implode(', ', $allowedExtensions)) }} &mdash; up to {{ intdiv($maxFileKb, 1024) }} MB</div>
                    <div class="dz-file" id="dzFile"></div>
                    <input type="file" name="file" id="importFile" accept="{{ $accept }}" required>
                </label>

                <div class="btn-row">
                    <button type="submit" class="btn-primary" id="previewBtn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Read File &amp; Validate
                    </button>
                    <a href="{{ route('store.products.import.template', 'csv') }}" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download CSV Template
                    </a>
                    <a href="{{ route('store.products.import.template', 'xlsx') }}" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download Excel Template
                    </a>
                </div>

                <div class="foot-note">Not sure which format? Excel Template gives you a formatted sheet with instructions on a second tab.</div>
            </div>
        </form>

        <div class="panel">
            <h2>2. What the file needs</h2>
            <p class="sub">The first row must contain headings. Column names are matched loosely, so "Product Name", "product_name" and "Name" all work.</p>

            <div class="req-grid">
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>Required columns</b><span>Name, SKU, price, stock quantity, reorder threshold, recommended season and status.</span></div>
                </div>
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>Category</b><span>Use either the Category Name or the Category ID column &mdash; one is enough. A group name like "Fertilizers" lands on its first sub-category.</span></div>
                </div>
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>Size and unit</b><span>Put the weight or volume in Package Size and Package Unit, not inside the product name.</span></div>
                </div>
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>NPK shortcut</b><span>A single <code>14-14-14</code> heading is split into nitrogen, phosphorus and potassium for you.</span></div>
                </div>
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>Allowed values</b><span>Season: wet, dry or both. Status: active, inactive, out_of_stock or discontinued.</span></div>
                </div>
                <div class="req-card">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <div><b>Unique SKUs</b><span>Each SKU must be unique across the whole system and cannot contain spaces.</span></div>
                </div>
                <div class="req-card warn">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div><b>No photos</b><span>A spreadsheet cannot carry images, so add photos to each product after importing.</span></div>
                </div>
            </div>

            <div class="col-legend">
                <span><i class="dot dot-req"></i> Required</span>
                <span><i class="dot dot-opt"></i> Optional</span>
                <span><i class="dot dot-fert"></i> Fertilizer only</span>
                <span><i class="dot dot-reg"></i> Chemicals &amp; certified seed</span>
            </div>

            <details class="howto">
                <summary>Show every column the importer understands</summary>
                <div class="table-scroll">
                    <table class="review">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Meaning</th>
                                <th>Group</th>
                                <th>Required</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($columns as $field => $meta)
                                <tr>
                                    <td><code>{{ $field }}</code></td>
                                    <td>{{ $meta['label'] }}</td>
                                    <td>{{ $meta['group'] }}</td>
                                    <td>
                                        @if ($meta['required'])
                                            <span class="chip chip-ok">Required</span>
                                        @else
                                            <span class="chip chip-mute">Optional</span>
                                        @endif
                                    </td>
                                    <td>{{ $meta['example'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    @else
        {{-- ============================ PHASE 2: REVIEW ============================ --}}
        @php
            $rows = $staged['rows'];
            $validCount = count(array_filter($rows, fn ($r) => empty($r['errors'])));
            $badCount = count($rows) - $validCount;
            $ticked = count(array_filter($rows, fn ($r) => ! empty($r['include'])));
        @endphp

        <div class="panel">
            <h2>Review before importing</h2>
            <p class="sub">
                Read from <strong>{{ $staged['filename'] }}</strong>.
                Every row has been checked against the same rules the Add Product form uses.
                You can edit any cell, untick rows to skip them, then re-check or import.
            </p>

            <div class="pill-row">
                <span class="chip chip-mute">{{ count($rows) }} row(s) read</span>
                <span class="chip chip-ok">{{ $validCount }} ready</span>
                @if ($badCount > 0)
                    <span class="chip chip-bad">{{ $badCount }} need attention</span>
                @endif
                <span class="chip chip-warn">{{ $ticked }} ticked for import</span>
            </div>

            @if (! empty($staged['unmapped']))
                <div class="alert-error" style="background:#fff8ec;border-color:#f0dcb4;color:#92400e">
                    <strong>Columns we ignored:</strong>
                    {{ implode(', ', $staged['unmapped']) }}.
                    These are not product fields, so their values were not imported. Rename them to a supported heading if they hold data you need.
                </div>
            @endif

            @if (! empty($staged['ai_touched']))
                <div class="alert-error" style="background:#f5f0ff;border-color:#d9c9f5;color:#4c1d95">
                    <strong>AI suggestions were applied.</strong> The model rewrote some cells to try to clear the errors.
                    Check the values below before importing &mdash; AI output is a suggestion, not a fact.
                </div>
            @endif

            {{-- novalidate on purpose: the server re-validates every row against
                 ProductFields anyway, and browser validation would block the
                 submit button on exactly the blank cells the merchant needs to
                 see flagged. --}}
            <form method="POST" action="{{ route('store.products.import.confirm') }}" id="reviewForm" novalidate>
                @csrf

                {{-- Filled by the script below for every column group the
                     merchant has collapsed, so hidden values are still saved. --}}
                <div id="hiddenMirrors" style="display:none"></div>

                <div class="filter-bar">
                    <label><input type="checkbox" id="showOnlyErrors"> Show only rows with problems</label>
                    <label><input type="checkbox" id="tickAll"> Tick every row</label>
                    <label><input type="checkbox" id="untickBad"> Untick rows with problems</label>
                    @foreach (\App\Support\ProductFields::toggleGroups() as $key => $label)
                        <label class="group-toggle" data-group-toggle="{{ $key }}">
                            <input type="checkbox" class="group-box" @checked($key === 'core')> {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="table-scroll">
                    <table class="review" id="reviewTable">
                        <thead>
                            <tr>
                                <th>Import</th>
                                <th>Row</th>
                                @foreach (\App\Support\ProductFields::COLUMNS as $field => $meta)
                                    {{-- The body loop skips category_id (the category is
                                         edited by name), so it must be skipped here
                                         too or every header shifts one column right. --}}
                                    @continue($field === 'category_id')
                                    <th data-group="{{ \App\Support\ProductFields::groupKey($meta['group']) }}">{{ $meta['label'] }}</th>
                                @endforeach
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                                @php
                                    $hasErrors = ! empty($row['errors']);
                                    $hasFert = $hasReg = false;
                                    foreach ($row['data'] as $key => $value) {
                                        if ($value === null || $value === '') continue;
                                        $g = \App\Support\ProductFields::groupKey(\App\Support\ProductFields::COLUMNS[$key]['group'] ?? 'Basic Information');
                                        if ($g === 'fert') $hasFert = true;
                                        if ($g === 'reg') $hasReg = true;
                                    }
                                @endphp
                                <tr data-row-index="{{ $index }}"
                                    data-valid="{{ $hasErrors ? '0' : '1' }}"
                                    data-has-fert="{{ $hasFert ? '1' : '0' }}"
                                    data-has-reg="{{ $hasReg ? '1' : '0' }}"
                                    class="{{ $hasErrors ? 'is-bad' : '' }} {{ empty($row['include']) ? 'is-off' : '' }}">
                                    <td>
                                        <input type="hidden" name="rows[{{ $index }}][__include]" value="0">
                                        <input type="checkbox" name="rows[{{ $index }}][__include]" value="1" class="row-tick" @checked(! empty($row['include']))>
                                        <input type="hidden" name="rows[{{ $index }}][__row]" value="{{ $row['row'] }}">
                                    </td>
                                    <td class="rowno">
                                        {{ $row['row'] }}
                                        @if (empty($row['include']))
                                            <span class="skip-tag">Skipped</span>
                                        @endif
                                    </td>

                                    @foreach (\App\Support\ProductFields::COLUMNS as $field => $meta)
                                        @php
                                            $groupKey = \App\Support\ProductFields::groupKey($meta['group']);
                                            $errorKey = $field === 'category' ? 'category_id' : $field;
                                            $hasError = isset($row['errors'][$errorKey]);
                                            // The category is edited by name so the
                                            // server re-resolves it; the id is never
                                            // round-tripped from the form.
                                            if ($field === 'category_id') continue;
                                            $value = $field === 'category'
                                                ? ($row['category_label'] ?? '')
                                                : (is_scalar($row['data'][$field] ?? null) ? $row['data'][$field] : '');
                                            $type = \App\Support\ProductFields::inputType($field);
                                            $name = 'rows[' . $index . '][' . $field . ']';
                                        @endphp
                                        <td data-group="{{ $groupKey }}" @if ($field === 'description') style="min-width:220px" @endif>
                                            @if ($type === 'select')
                                                <select name="{{ $name }}" class="cell @if ($hasError) cell-bad @endif">
                                                    @foreach (\App\Support\ProductFields::optionsFor($field) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text"
                                                       name="{{ $name }}"
                                                       class="cell @if ($hasError) cell-bad @endif"
                                                       value="{{ $value }}"
                                                       @if ($field === 'category') list="importCategoryList" @endif
                                                       @if ($field === 'package_unit') list="importUnitList" @endif
                                                       placeholder="{{ $type === 'date' ? 'YYYY-MM-DD' : $meta['label'] }}">
                                            @endif
                                            @if ($hasError)
                                                <span class="err">{{ $row['errors'][$errorKey] }}</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="issues" style="min-width:240px">
                                        @foreach ($row['errors'] as $field => $message)
                                            <span class="err"><strong>{{ ucwords(str_replace('_', ' ', $field)) }}:</strong> {{ $message }}</span>
                                        @endforeach
                                        @foreach ($row['notes'] as $note)
                                            <span class="note">{{ $note }}</span>
                                        @endforeach
                                        @if (empty($row['errors']) && empty($row['notes']))
                                            <span class="chip chip-ok">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-primary" @disabled($validCount === 0)>
                        Import {{ $validCount }} Valid Row(s)
                    </button>

                    {{-- Same form, different destination: the browser posts the
                         visible cells to whichever endpoint the button names. --}}
                    <button type="submit" class="btn-secondary" formaction="{{ route('store.products.import.revalidate') }}">
                        Re-check Table
                    </button>

                    @if ($aiAvailable)
                        <button type="submit" class="btn-ai" formaction="{{ route('store.products.import.ai-fix') }}" @disabled($badCount === 0 || $badCount > $aiMaxRows)>
                            Let AI Suggest Fixes ({{ $badCount }})
                        </button>
                        @if ($badCount > $aiMaxRows)
                            <span class="foot-note" style="margin:0">
                                The AI reviews at most {{ $aiMaxRows }} rows at a time. Fix or untick some rows first.
                            </span>
                        @endif
                    @endif

                    <button type="submit" class="btn-danger-ghost" formaction="{{ route('store.products.import.cancel') }}"
                            data-aqui-confirm="Discard this import? Nothing will be saved."
                            data-aqui-danger data-aqui-confirm-label="Discard">
                        Discard Import
                    </button>
                </div>
            </form>

            <div class="foot-note">
                Imported products start with no photos. Open each product afterwards to upload up to {{ \App\Models\Product::MAX_IMAGES }} images.
            </div>

            <datalist id="importCategoryList">
                @foreach ($categories as $category)
                    <option value="{{ $category->name }}"></option>
                @endforeach
            </datalist>
            <datalist id="importUnitList">
                @foreach (\App\Models\Product::sizeUnitSuggestions() as $unit)
                    <option value="{{ $unit }}"></option>
                @endforeach
            </datalist>
        </div>
    @endif
</div>

<script>
(function () {
    var dropzone = document.getElementById('dropzone');
    var input = document.getElementById('importFile');
    var button = document.getElementById('previewBtn');
    var hint = document.getElementById('dzHint');
    var fileLabel = document.getElementById('dzFile');

    if (dropzone && input) {
        var describe = function () {
            var file = input.files && input.files[0];
            if (!file) {
                dropzone.classList.remove('has-file');
                fileLabel.textContent = '';
                fileLabel.classList.remove('show');
                if (button) button.disabled = true;
                return;
            }
            var mb = (file.size / 1048576).toFixed(2);
            dropzone.classList.add('has-file');
            fileLabel.textContent = file.name + ' (' + mb + ' MB)';
            fileLabel.classList.add('show');
            if (button) button.disabled = false;
        };

        input.addEventListener('change', describe);

        ['dragenter', 'dragover'].forEach(function (name) {
            dropzone.addEventListener(name, function (event) {
                event.preventDefault();
                dropzone.classList.add('drag');
            });
        });

        ['dragleave', 'drop'].forEach(function (name) {
            dropzone.addEventListener(name, function (event) {
                event.preventDefault();
                dropzone.classList.remove('drag');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (event.dataTransfer && event.dataTransfer.files.length) {
                input.files = event.dataTransfer.files;
                describe();
            }
        });

        if (hint && button) {
            // The button starts disabled so an empty form cannot be submitted.
            describe();
        }
    }

    // ---- review table helpers -------------------------------------------
    var table = document.getElementById('reviewTable');

    if (table) {
        var body = table.tBodies[0];
        var onlyErrors = document.getElementById('showOnlyErrors');
        var tickAll = document.getElementById('tickAll');
        var untickBad = document.getElementById('untickBad');

        var rowsOf = function () {
            return Array.prototype.slice.call(body.querySelectorAll('tr'));
        };

        // Ticking/unticking a row dims nothing, so the row number carries an
        // explicit "Skipped" tag instead. Keep the two in step.
        var setRowState = function (row, ticked) {
            row.classList.toggle('is-off', !ticked);
            var cell = row.querySelector('td.rowno');
            if (!cell) return;
            var tag = cell.querySelector('.skip-tag');
            if (ticked && tag) {
                tag.remove();
            } else if (!ticked && !tag) {
                var span = document.createElement('span');
                span.className = 'skip-tag';
                span.textContent = 'Skipped';
                cell.appendChild(span);
            }
        };

        if (onlyErrors) {
            onlyErrors.addEventListener('change', function () {
                rowsOf().forEach(function (row) {
                    row.style.display = onlyErrors.checked && row.dataset.valid === '1' ? 'none' : '';
                });
            });
        }

        if (tickAll) {
            tickAll.addEventListener('change', function () {
                rowsOf().forEach(function (row) {
                    var tick = row.querySelector('.row-tick');
                    if (tick) tick.checked = tickAll.checked;
                    setRowState(row, tickAll.checked);
                });
            });
        }

        if (untickBad) {
            untickBad.addEventListener('change', function () {
                rowsOf().forEach(function (row) {
                    if (row.dataset.valid !== '0') return;
                    var tick = row.querySelector('.row-tick');
                    if (tick) tick.checked = !untickBad.checked;
                    setRowState(row, !untickBad.checked);
                });
            });
        }

        // Un-ticking a row marks it as skipped so it is obvious what will be
        // left out without fading the text the merchant still needs to read.
        body.addEventListener('change', function (event) {
            if (event.target.classList.contains('row-tick')) {
                setRowState(event.target.closest('tr'), event.target.checked);
            }
        });

        // Mark a cell as edited so a fixed row no longer looks broken before
        // the server has had a chance to re-validate it.
        body.addEventListener('input', function (event) {
            if (event.target.classList.contains('cell')) {
                event.target.classList.remove('cell-bad');
                var span = event.target.parentElement.querySelector('.err');
                if (span) span.remove();
            }
        });

        // -- column groups ----------------------------------------------------
        // Every supported field is editable, but 30+ columns makes the table
        // unusable, so the optional groups collapse.
        //
        // Collapsing uses display:none, which drops the input from the POST
        // body. So each hidden cell gets a mirrored hidden input carrying the
        // same name and current value: a column the merchant cannot see must
        // still keep its value when the row is saved.
        var groupBoxes = Array.prototype.slice.call(document.querySelectorAll('.group-box'));
        var groupCells = Array.prototype.slice.call(table.querySelectorAll('[data-group]'));
        var mirrors = document.getElementById('hiddenMirrors');

        var syncMirrors = function () {
            if (!mirrors) return;
            mirrors.innerHTML = '';

            groupCells.forEach(function (cell) {
                if (cell.style.display !== 'none') return;

                var field = cell.querySelector('[name]');
                if (!field || !field.name) return;

                var mirror = document.createElement('input');
                mirror.type = 'hidden';
                mirror.name = field.name;
                mirror.value = field.value;
                mirrors.appendChild(mirror);
            });
        };

        var applyGroups = function () {
            var on = {};

            groupBoxes.forEach(function (box) {
                var label = box.closest('[data-group-toggle]');
                if (label) on[label.getAttribute('data-group-toggle')] = box.checked;
            });

            groupCells.forEach(function (cell) {
                cell.style.display = on[cell.getAttribute('data-group')] === false ? 'none' : '';
            });

            syncMirrors();
        };

        groupBoxes.forEach(function (box) {
            box.addEventListener('change', applyGroups);
        });

        if (groupBoxes.length) {
            // Open a collapsed group by default when the file actually used it,
            // so NPK or registration data is never silently off-screen.
            groupBoxes.forEach(function (box) {
                var label = box.closest('[data-group-toggle]');
                if (!label) return;

                var group = label.getAttribute('data-group-toggle');
                var used = Array.prototype.slice.call(body.querySelectorAll('tr')).some(function (row) {
                    return group === 'fert' ? row.dataset.hasFert === '1' : row.dataset.hasReg === '1';
                });

                if (used) box.checked = true;
            });

            applyGroups();
        }

        // Keep the mirrors in step with live edits, and rebuild them right before
        // the POST in case a cell was edited without a change event firing.
        var form = document.getElementById('reviewForm');
        if (form) {
            form.addEventListener('input', syncMirrors);
            form.addEventListener('change', syncMirrors);
            form.addEventListener('submit', syncMirrors);
        }
    }
})();
</script>
@endsection
