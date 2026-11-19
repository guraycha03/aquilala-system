@props([
    'allCrops' => [],
    'crops' => [],
    'stats' => ['land_area' => 0, 'used_land_area' => 0, 'remaining_land' => 0],
    'user' => null,
    'allocPct' => 0,
    'unitLabel' => 'ha',
    'cropDefaults' => [],
    'seedProducts' => [],
    'productYields' => [],
    'productCosts' => [],
    'cropProductSlugs' => [],
    'cropProductKeywords' => [],
    'cropVarieties' => [],
    'cropInfo' => [],
])

<!-- Load AlpineJS -->


<!-- Load Flatpickr CSS & JS for fully custom datepicker popups -->
<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<script src="{{ asset('js/flatpickr.min.js') }}"></script>

<style>
    /* Full in-page "screen": covers the My Crops page without leaving it. */
    .ncb-overlay {
        position: fixed; top: 0; left: 260px; right: 0; bottom: 0;
        display: none; align-items: stretch; justify-content: stretch;
        padding: 0; overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none;
        background: #f8faf9;
        z-index: 900;
    }
    @media (max-width: 768px) { .ncb-overlay { left: 0; } }
    .ncb-overlay::-webkit-scrollbar { display: none; width: 0; }
    .ncb-overlay.active { display: block; }
    .ncb-modal {
        background: #f8faf9; border-radius: 0; width: 100%; max-width: none !important;
        margin: 0; min-height: 100vh;
        box-shadow: none;
        max-height: none;
    }
    .ncb-header {
        position: sticky; top: 0; z-index: 10;
        background: #f8faf9;
        box-shadow: 0 1px 0 rgba(0,0,0,0.06);
    }
    .ncb-header h3 { color: #1e293b; }
    .ncb-body { max-height: none; background: #f8faf9; }
    .ncb-footer { position: sticky; bottom: 0; z-index: 10; background: #f8faf9; box-shadow: 0 -1px 0 rgba(0,0,0,0.06); }
    .ncb-header { padding: 20px 24px 16px; border-bottom: none; display: flex; justify-content: space-between; align-items: center; background: #f8faf9; border-radius: 0; z-index: 9999; }
    .ncb-overlay { z-index: 9999; }
    .ncb-header h3 { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .ncb-close { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; border: none; background: #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.15s ease; }
    .ncb-close:hover { background: #e2e8f0; color: #1e293b; }
    .ncb-body { padding: 32px 48px; background: #f8faf9; overflow: visible; max-height: none; scrollbar-width: none; -ms-overflow-style: none; }
    @media (max-width: 768px) { .ncb-body { padding: 20px 16px; } }
    .ncb-body::-webkit-scrollbar { display: none; width: 0; }
    .ncb-footer { position: relative; z-index: 1; padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; background: #fff; border-radius: 0 0 18px 18px; }
    .ncb-section-label { font-size: 16px; font-weight: 800; color: #1e293b; margin: 12px 0 18px; padding-bottom: 0; border-bottom: none; text-transform: none; letter-spacing: 0; }
    .ncb-section-label:first-child { margin-top: 0; }
    .ncb-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 20px; }
    .ncb-group { margin-bottom: 22px; position: relative; }
    .ncb-row { margin-bottom: 8px; }
    .ncb-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
    .ncb-required { color: #dc2626; font-weight: 800; margin-left: 2px; }

    .ncb-group input, .ncb-group textarea {
        width: 100%; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit;
    }
    .ncb-group input:focus, .ncb-group textarea:focus { outline: none; border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,0.08); }
    .ncb-group textarea { resize: vertical; min-height: 64px; }
    .ncb-group input.over-cap { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.08); }
    .ncb-group input.warn-cap { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.08); }

    /* Custom Select Trigger Rules */
    .ncb-select-trigger {
        width: 100%; min-height: 41px; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center; user-select: none;
    }
    .ncb-select-trigger.placeholder { color: #94a3b8; }
    .ncb-select-trigger:hover { border-color: #cbd5e1; }
    .ncb-select-trigger.active { border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,0.08); outline: none; }
    .ncb-select-arrow { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
    .ncb-select-arrow.rotate { transform: rotate(180deg); }

    .ncb-select-menu {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
        max-height: 200px; overflow-y: auto; z-index: 600; padding: 4px;
        scrollbar-width: thin;
    }
    /* The shared sidebar layout sets a global `.ncb-select-menu { display: none }` for the
       table-toolbar filters. That rule would keep these Alpine `x-show` menus hidden even when
       Alpine removes the inline `display` on open, so force the modal's menus to render as block.
       `.ncb-group` is only used inside this modal, so the table-toolbar (`.ncb-group-inline`) is unaffected. */
    .ncb-group > .ncb-select-menu { display: block; }
    .ncb-select-option {
        padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
    }
    .ncb-select-option:hover { background: #edf1e9; color: #648268; }
    .ncb-select-option.selected { background: #edf1e9; color: #648268; font-weight: 600; }

    /* Yield Unit search-as-you-type combobox */
    .ncb-unit-wrap { position: relative; }
    .ncb-unit-wrap input { padding-right: 34px; }
    .ncb-unit-arrow {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        color: #64748b; cursor: pointer; transition: transform 0.2s ease; pointer-events: none;
    }
    .ncb-unit-menu {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
        max-height: 200px; overflow-y: auto; z-index: 600; padding: 4px;
        display: none; scrollbar-width: thin;
    }
    .ncb-unit-option {
        padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
    }
    .ncb-unit-option:hover { background: #edf1e9; color: #648268; }
    .ncb-unit-option .ncb-unit-alias { font-size: 10.5px; color: #94a3b8; font-weight: 600; }
    .ncb-unit-empty { padding: 10px 12px; font-size: 12.5px; color: #94a3b8; font-style: italic; }

    /* ── CUSTOM FLATPICKR DATEPICKER POPUP STYLES ── */
.ncb-date-wrapper { position: relative; width: 100%; }
.ncb-date-icon { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); color: #4A7A54; pointer-events: none; }

.flatpickr-calendar {
    background: #ffffff !important;
    border-radius: 14px !important;
    border: 1.5px solid #e2e8f0 !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12) !important;
    font-family: inherit !important;
    padding: 12px 14px !important;
    width: 315px !important;
    box-sizing: border-box !important;
    z-index: 9999 !important;
}
.flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after { display: none !important; }

/* Keep flatpickr's native grid alignment intact */
.flatpickr-innerContainer, .flatpickr-rContainer {
    width: 100% !important;
}
.flatpickr-days {
    width: 100% !important;
}
.dayContainer {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
}

/* Header & Navigation Styling */
.flatpickr-months {
    display: flex !important;
    align-items: center !important;
    margin-bottom: 8px !important;
}
.flatpickr-months .flatpickr-month {
    background: #ffffff !important;
    color: #1e293b !important;
    fill: #1e293b !important;
    height: 34px !important;
}
.flatpickr-current-month {
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 700 !important; color: #1e293b !important; }
.flatpickr-current-month input.cur-year { font-weight: 700 !important; color: #1e293b !important; }
.flatpickr-months .flatpickr-prev-month:hover svg, .flatpickr-months .flatpickr-next-month:hover svg { fill: #4A7A54 !important; }

/* Weekdays & Days spacing */
span.flatpickr-weekday {
    color: #64748b !important;
    font-weight: 700 !important;
    font-size: 11px !important;
}
.flatpickr-day {
    border-radius: 8px !important;
    color: #334155 !important;
    font-weight: 500 !important;
    height: 36px !important;
    line-height: 36px !important;
    margin-top: 2px !important;
}
.flatpickr-day:hover { background: #f1f5f9 !important; border-color: transparent !important; color: #4A7A54 !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: #4A7A54 !important; border-color: #4A7A54 !important; color: #ffffff !important; font-weight: 700 !important; }
.flatpickr-day.today { border-color: #4A7A54 !important; font-weight: 700 !important; color: #4A7A54 !important; }
.flatpickr-day.today.selected { color: #ffffff !important; }


    .ncb-btn-cancel { appearance: none; -webkit-appearance: none; padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; transition: all 0.15s ease; }
    .ncb-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
    .ncb-btn-submit { appearance: none; -webkit-appearance: none; padding: 10px 22px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none; background: #4A7A54; color: #fff; cursor: pointer; box-shadow: 0 4px 12px rgba(74,122,84,0.25); transition: all 0.2s ease; }
    .ncb-btn-submit:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(74,122,84,0.3); }

    .ncb-cap-wrap { margin-bottom: 14px; }
    .ncb-cap-bar-track { width: 100%; height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden; position: relative; }
    .ncb-cap-bar-fill { height: 100%; border-radius: 5px; transition: width 0.4s ease, background 0.3s ease; }
    .ncb-cap-bar-fill.ok { background: linear-gradient(90deg, #4A7A54, #7FA886); }
    .ncb-cap-bar-fill.warn { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .ncb-cap-bar-fill.over { background: linear-gradient(90deg, #dc2626, #ef4444); }
    .ncb-cap-text { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; font-size: 11px; color: #64748b; font-weight: 600; }
    .ncb-cap-remaining { color: #4A7A54; font-weight: 700; }
    .ncb-cap-remaining.warn { color: #d97706; }
    .ncb-cap-remaining.over { color: #dc2626; }
    .ncb-cap-warning { display: none; margin-top: 8px; margin-bottom: 14px; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; line-height: 1.4; }
    .ncb-cap-warning.warn { display: flex; align-items: center; gap: 6px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .ncb-cap-warning.over { display: flex; align-items: center; gap: 6px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .ncb-yield-hint { display: block; margin-top: 8px; margin-bottom: 8px; font-size: 13px; line-height: 1.55; color: #4A7A54; font-weight: 600; min-height: 16px; }
    .ncb-yield-hint:empty { min-height: 0; }
    .ncb-note { display: block; margin-top: 8px; margin-bottom: 8px; font-size: 12.5px; color: #64748b; line-height: 1.5; }

    .ncb-cost-card {
        display: flex; align-items: flex-start; gap: 9px;
        margin-top: 12px; margin-bottom: 12px;
        padding: 11px 13px;
        background: #f0fdf4; border: 1px solid #bbe7c6; border-radius: 10px;
        font-size: 13px; line-height: 1.55; color: #166534; font-weight: 600;
        box-sizing: border-box;
    }
    .ncb-cost-card .ncb-cost-icon { flex-shrink: 0; color: #0284c7; margin-top: 2px; }
    .ncb-cost-card .ncb-cost-text { flex: 1; min-width: 0; }
    .ncb-source-link { display: inline-flex; align-items: center; gap: 6px; width: fit-content; max-width: 100%; margin-top: 8px; color: inherit; font-weight: 600; text-decoration: none; line-height: 1.5; }
    .ncb-source-link:hover { opacity: 0.8; }
    .ncb-source-link svg { flex-shrink: 0; }

    /* ── MINIMAL & SPACIOUS CROP GROWTH GUIDE ── */
    .ncb-growth-card {
        margin: 8px 0 24px;
        background: #ffffff;
        border: 1px solid #dde6df;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(30, 41, 59, 0.03);
        box-sizing: border-box;
    }
    .ncb-growth-header {
        display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
        padding: 18px 22px;
        background: #f7faf8;
        border-bottom: 1px solid #e6eee8;
    }
    .ncb-growth-title-wrap { display: flex; align-items: center; gap: 12px; min-width: 0; flex-wrap: wrap; }
    .ncb-growth-emblem {
        width: 36px; height: 36px; border-radius: 10px;
        background: #eef4ef; color: #4A7A54; border: 1px solid #d5e3d8;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ncb-growth-eyebrow {
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;
        color: #5b7b62; line-height: 1.4; margin-bottom: 3px;
    }
    .ncb-growth-title {
        font-size: 15.5px; font-weight: 700; color: #1e293b; line-height: 1.45;
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .ncb-growth-variety-tag {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 7px;
        background: #ffffff; border: 1px solid #d6e3d9;
        font-size: 12px; font-weight: 600; color: #365e3e; line-height: 1.4;
    }
    .ncb-growth-hint-pill {
        font-size: 11.5px; font-weight: 500; color: #64748b;
        background: #ffffff; border: 1px solid #e2e8f0;
        padding: 5px 11px; border-radius: 7px; line-height: 1.4;
    }

    .ncb-growth-body {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    /* Interactive 3-Stage Stepper (no movement on hover) */
    .ncb-stepper {
        position: relative;
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    }
    .ncb-stepper-track {
        position: absolute; top: 23px; left: 16%; right: 16%; height: 2px;
        background: #e2e8f0; border-radius: 2px; z-index: 0; pointer-events: none;
    }
    .ncb-stepper-fill {
        height: 100%; width: 0%; background: #4A7A54;
        border-radius: 2px; transition: width 0.2s ease;
    }
    .ncb-step-btn {
        position: relative; z-index: 1;
        appearance: none; -webkit-appearance: none;
        background: transparent; border: 1px solid transparent; border-radius: 10px;
        padding: 10px 12px; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px;
        font-family: inherit; transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .ncb-step-btn:hover { background: #f8faf9; border-color: #e2e8f0; }
    .ncb-step-btn.is-active { background: #f4f9f5; border-color: #cde0d1; }
    .ncb-step-dot {
        width: 26px; height: 26px; border-radius: 8px;
        background: #ffffff; border: 1.5px solid #cbd5e1; color: #64748b;
        font-size: 12px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .ncb-step-btn.is-done .ncb-step-dot {
        background: #eef5ef; border-color: #9dc0a4; color: #4A7A54;
    }
    .ncb-step-btn.is-active .ncb-step-dot {
        background: #4A7A54; border-color: #4A7A54; color: #ffffff;
    }
    .ncb-step-name {
        font-size: 13px; font-weight: 700; color: #475569; line-height: 1.4;
    }
    .ncb-step-btn.is-active .ncb-step-name { color: #1e293b; }
    .ncb-step-mini {
        font-size: 11.5px; font-weight: 500; color: #64748b; line-height: 1.45;
        max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .ncb-step-btn.is-active .ncb-step-mini { color: #4A7A54; }

    /* Active Stage Spotlight Box */
    .ncb-stage-panel {
        display: none;
        background: #f9fbf9;
        border: 1px solid #e5ede7;
        border-radius: 12px;
        padding: 18px 20px;
    }
    .ncb-stage-panel.is-active { display: block; }
    .ncb-stage-panel-head {
        display: flex; align-items: center; gap: 8px;
        font-size: 13.5px; font-weight: 700; color: #2f5738;
        line-height: 1.5; margin-bottom: 12px;
    }
    .ncb-stage-list {
        display: grid; gap: 11px;
    }
    .ncb-stage-row {
        display: flex; align-items: flex-start; gap: 10px;
        font-size: 13.5px; line-height: 1.65; color: #475569;
    }
    .ncb-stage-bullet {
        width: 5px; height: 5px; border-radius: 2px;
        background: #648f6d; margin-top: 9px; flex-shrink: 0;
    }
    .ncb-stage-key { font-weight: 600; color: #1e293b; margin-right: 5px; }

    /* 4-Tile Quick Specs Grid (spacious & static on hover) */
    .ncb-specs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
        gap: 12px;
    }
    .ncb-spec-tile {
        text-align: left; font-family: inherit;
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 11px;
        padding: 14px 16px; cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
        display: flex; flex-direction: column; gap: 6px;
    }
    .ncb-spec-tile:hover { border-color: #b7ceb9; background: #fcfefd; }
    .ncb-spec-tile.is-highlighted {
        border-color: #7ca384; background: #f5faf6;
    }
    .ncb-spec-label {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
        color: #64748b; line-height: 1.4;
    }
    .ncb-spec-tile.is-highlighted .ncb-spec-label { color: #4A7A54; }
    .ncb-spec-val {
        font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.5;
    }
    .ncb-spec-sub {
        font-size: 12px; font-weight: 400; color: #64748b; line-height: 1.5;
    }

    .ncb-growth-footer {
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        padding: 13px 22px;
        border-top: 1px solid #edf3ee;
        background: #fbfdfb;
        font-size: 12.5px; color: #5b7b62;
    }
    .ncb-growth-footer .ncb-source-link { margin-top: 0; }
    @media (max-width: 640px) {
        .ncb-growth-header, .ncb-growth-body, .ncb-growth-footer { padding-left: 16px; padding-right: 16px; }
        .ncb-step-mini { display: none; }
        .ncb-specs-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<!-- ── NEW CROP BATCH MODAL ── -->
<div class="ncb-overlay" id="addModal">
    <div class="ncb-modal">
        <div class="ncb-header">
            <h3>New Crop Planting</h3>
            <button type="button" class="ncb-close" onclick="closeModal('addModal')" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="cropPlantingForm" action="{{ route('farmer.crops.store') }}" method="POST">
            @csrf
            <div class="ncb-body">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 4px 0 40px;">
                    <div class="ncb-section-label" style="margin: 0;">Crop Information</div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="ncb-btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                        <button type="submit" class="ncb-btn-submit">Add Planting</button>
                    </div>
                </div>
                <div class="ncb-row">

                    @php
                        $cropLibrary = collect(config('crop_library.crops', []))
                            ->keyBy(fn ($c) => strtolower($c['name']));
                        $cropOptions = collect($allCrops)
                            ->map(function ($crop) use ($cropLibrary) {
                            $entry = $cropLibrary->get(strtolower($crop->name));
                            if (! $entry) {
                                return null;
                            }

                            $aliases = array_values(array_filter($entry['aliases'] ?? []));
                            $searchTerms = array_values(array_filter(array_merge(
                                $aliases,
                                array_column($entry['varieties'] ?? [], 'name')
                            )));
                            $terms = array_values(array_filter(array_unique(array_merge(
                                [$entry['name'] ?? $crop->name],
                                $searchTerms,
                                [$entry['description'] ?? '', $entry['scientific_name'] ?? '']
                            ))));
                            $local = implode(', ', $aliases);
                            return [
                                'id' => $crop->id,
                                'name' => $entry['name'] ?? $crop->name,
                                'aliases' => $aliases,
                                'local' => $local,
                                'terms' => $terms,
                            ];
                        })->filter()->values()->all();
                    @endphp
                    <!-- Crop Type Select (searchable, alias-aware, and typo tolerant) -->
                    <script>
                        window.cropSearchDropdown = function () {
                            return {
                                open: false,
                                value: '',
                                query: '',
                                options: @json($cropOptions),

                                norm: function (s) {
                                    var value = (s || '').toString().toLowerCase();
                                    if (value.normalize) value = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                                    return value.replace(/[^a-z0-9]+/g, ' ').trim();
                                },

                                lev: function (a, b) {
                                    if (a.length === 0) return b.length;
                                    if (b.length === 0) return a.length;
                                    var matrix = [];
                                    var i;
                                    var j;
                                    for (i = 0; i <= b.length; i++) matrix[i] = [i];
                                    for (j = 0; j <= a.length; j++) matrix[0][j] = j;
                                    for (i = 1; i <= b.length; i++) {
                                        for (j = 1; j <= a.length; j++) {
                                            matrix[i][j] = b.charAt(i - 1) === a.charAt(j - 1)
                                                ? matrix[i - 1][j - 1]
                                                : Math.min(matrix[i - 1][j - 1] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j] + 1);
                                        }
                                    }
                                    return matrix[b.length][a.length];
                                },

                                scoreWord: function (term, word) {
                                    var t = this.norm(term).replace(/ /g, '');
                                    var w = this.norm(word).replace(/ /g, '');
                                    if (!t || !w) return 999;
                                    if (t === w) return 0;
                                    if (w.indexOf(t) === 0) return 1;
                                    if (w.indexOf(t) !== -1) return 2;
                                    if (t.indexOf(w) === 0) return 3;
                                    var distance = this.lev(t, w);
                                    var limit = t.length <= 4 ? 1 : (t.length <= 7 ? 2 : 3);
                                    return distance <= limit ? 4 + distance : 999;
                                },

                                scoreMatch: function (terms, query) {
                                    var words = this.norm(query).split(' ').filter(Boolean);
                                    if (!words.length) return 0;
                                    var best = 999;
                                    (terms || []).forEach(function (term) {
                                        var termWords = this.norm(term).split(' ').filter(Boolean);
                                        var score = words.reduce(function (total, word) {
                                            var wordScore = termWords.reduce(function (lowest, termWord) {
                                                return Math.min(lowest, this.scoreWord(termWord, word));
                                            }.bind(this), 999);
                                            return wordScore === 999 ? 999 : total + wordScore;
                                        }.bind(this), 0);
                                        best = Math.min(best, score);
                                    }.bind(this));
                                    return best;
                                },

                                matched: function () {
                                    var query = this.query.trim();
                                    var scored = this.options.map(function (crop) {
                                        return { crop: crop, score: this.scoreMatch(crop.terms, query) };
                                    }.bind(this));
                                    return scored
                                        .filter(function (result) { return result.score < 999; })
                                        .sort(function (a, b) { return a.score - b.score; });
                                },

                                showNoMatch: function () {
                                    return this.query.trim() !== '' && this.matched().length === 0;
                                }
                            };
                        };
                    </script>
                    <div class="ncb-group" x-data="cropSearchDropdown()">
                        <label>Crop Type <span class="ncb-required" aria-hidden="true">*</span></label>
                        <input type="hidden" name="crop_id" :value="value" required>
                        <input x-model="query"
                               @focus="open = true"
                               @input="open = true; value = '';"
                               @click.outside="open = false"
                               type="text"
                               placeholder="Type a crop name (e.g. palay, mais, saging, pakwan)"
                               autocomplete="off"
                               spellcheck="false"
                               aria-label="Search crop type"
                               style="width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:15px; background:#fff;" />
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none; margin-top: 4px;">
                            <template x-for="match in matched()" :key="match.crop.id">
                                <div class="ncb-select-option"
                                     @click="value = match.crop.id; query = match.crop.name; open = false; window.pickCrop(match.crop.id)"
                                     style="padding: 10px 12px; font-size: 14px;">
                                    <div style="font-weight: 700; color: #1e293b;" x-text="match.crop.name"></div>
                                    <span x-show="match.crop.local" style="font-size: 11.5px; color: #64748b; display: block; margin-top: 2px;">Also known as: <span x-text="match.crop.local"></span></span>
                                </div>
                            </template>
                            <template x-if="showNoMatch()">
                                <div class="ncb-select-option" style="color:#94a3b8; cursor:default; padding: 10px 12px; font-size: 13px;">
                                    No crop matches "<span x-text="query"></span>". Try a local name (e.g. palay, mais, saging, niyog, talong, kalabasa).
                                </div>
                            </template>
                            <template x-if="query.trim() === ''">
                                <div class="ncb-select-option" style="color:#94a3b8; cursor:default; padding: 10px 12px; font-size: 13px;">Start typing a crop name…</div>
                            </template>
                        </div>
                    </div>

                    <div class="ncb-group">
                        <label>Planting Name</label>
                        <input type="text" name="batch_name" id="batchName" placeholder="e.g. North Field A" autocomplete="off">
                        <span class="ncb-note" id="batchNameHint" style="margin-bottom: 0;">Auto-generated. Editable.</span>
                    </div>
                </div>

                <!-- Variety step (required; defaults to Local / General variety) -->
                <div class="ncb-group" id="varietyGroup">
                    <label>Variety <span class="ncb-required" aria-hidden="true">*</span></label>
                    <input type="hidden" name="variety" id="varietyValue" value="{{ \App\Services\CropLibraryService::GENERAL_VARIETY }}" required>
                    <input type="hidden" name="variety_key" id="varietyKeyValue" value="{{ \App\Services\CropLibraryService::GENERAL_VARIETY_KEY }}">
                    <div class="ncb-select-trigger placeholder" id="varietyTrigger" tabindex="0">
                        <span id="varietyLabel">{{ \App\Services\CropLibraryService::GENERAL_VARIETY }}</span>
                        <svg class="ncb-select-arrow" id="varietyArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="ncb-select-menu" id="varietyMenu" style="display: none;"></div>
                    <span class="ncb-note" id="varietyNote">Choose a crop first.</span>
                </div>

                <!-- Crop agronomy info panel (fills once a crop is picked) -->
                <div class="ncb-growth-card" id="cropInfoCard" style="display: none;">
                    <div id="cropInfoText" class="ncb-growth-inner"></div>
                </div>

                <!-- Optional Field: Specific Product -->
                <div class="ncb-group" x-data="{ open: false, selProd: '', label: 'None — crop type only (e.g. free seeds distributed)' }">
                    <label>Specific Product (Optional)</label>
                    <input type="hidden" name="product_id" id="cropProductId" :value="selProd">
                    <div class="ncb-select-trigger" :class="{ 'active': open, 'placeholder': !selProd }" @click="open = !open" @click.outside="open = false" tabindex="0">
                        <span x-text="label">None — crop type only (e.g. free seeds distributed)</span>
                        <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="ncb-select-menu" id="prodList" x-show="open" x-cloak style="display: none;">
                        <div class="ncb-select-option" id="prodNone" :class="{ 'selected': selProd === '' }" 
                             @click="selProd = ''; label = 'None — crop type only (e.g. free seeds distributed)'; open = false; window.pickProduct(0)">None — crop type only (e.g. free seeds distributed)</div>
                        @forelse($seedProducts as $product)
                            <div class="ncb-select-option" data-pid="{{ $product['id'] }}" data-name="{{ mb_strtolower($product['name']) }}" data-catslug="{{ $product['category_slug'] }}" data-cropids="{{ implode(',', $product['crop_ids']) }}"
                                 :class="{ 'selected': selProd === '{{ $product['id'] }}' }"
                                 @click="selProd = '{{ $product['id'] }}'; label = '{{ addslashes($product['display_name']) }}'; open = false; window.pickProduct({{ $product['id'] }})">
                                {{ $product['display_name'] }}
                            </div>
                        @empty
                            <div class="ncb-select-option" style="color: #94a3b8; cursor: default;">No seed/planting products available in the stores yet.</div>
                        @endforelse
                        <div class="ncb-select-option" id="prodNoMatch" style="color: #94a3b8; cursor: default; display: none;">No applicable store products for this crop yet — leave None or pick another crop.</div>
                    </div>
                    <span class="ncb-note">Optional. Filtered by crop.</span>
                </div>

                <div class="ncb-section-label">Land & Season</div>
                @if(($stats['land_area'] ?? 0) > 0)
                <div class="ncb-cap-wrap">
                    <div class="ncb-cap-bar-track">
                        <div class="ncb-cap-bar-fill {{ $allocPct > 90 ? 'over' : ($allocPct > 70 ? 'warn' : 'ok') }}" id="capBarFill" style="width: {{ $allocPct }}%;"></div>
                    </div>
                    <div class="ncb-cap-text">
                        <span>{{ number_format($stats['used_land_area'] ?? 0, 1) }} {{ $unitLabel }} used</span>
                        <span class="ncb-cap-remaining" id="capRemaining">{{ number_format($stats['remaining_land'] ?? 0, 1) }} {{ $unitLabel }} remaining</span>
                    </div>
                    @if(($stats['land_area_is_default'] ?? false))
                    <div style="margin-top:6px;font-size:11px;color:#64748b;">Using default 1 ha until admin sets your member land size.</div>
                    @endif
                </div>
                <div class="ncb-cap-warning" id="capWarning"></div>
                @endif
                <div class="ncb-row">
                    <div class="ncb-group">
                        <label>Land Area <span class="ncb-required" aria-hidden="true">*</span></label>
                        <input type="number" name="land_area" id="addLandArea" step="0.01" min="0.01" value="" placeholder="e.g. 1.00" required oninput="checkLandCapacity(this.value); window.recalcYield()">
                    </div>

                    <!-- Area Unit Select -->
                    @php
                        $defaultUnit = method_exists($user, 'effectiveLandAreaUnit') ? $user->effectiveLandAreaUnit() : ($user->default_land_area_unit ?? 'ha');
                        $unitLabels = ['ha' => 'Hectares (ha)', 'sq_m' => 'Square Meters (m²)', 'sq_ft' => 'Square Feet (ft²)'];
                    @endphp
                    <div class="ncb-group" x-data="{ open: false, value: '{{ $defaultUnit }}', label: '{{ $unitLabels[$defaultUnit] ?? 'Hectares (ha)' }}' }">
                        <label>Area Unit <span class="ncb-required" aria-hidden="true">*</span></label>
                        <input type="hidden" name="land_area_unit" id="landAreaUnit" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">{{ $unitLabels[$defaultUnit] ?? 'Hectares (ha)' }}</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            <div class="ncb-select-option" :class="{ 'selected': value === 'ha' }" @click="value = 'ha'; label = 'Hectares (ha)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Hectares (ha)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_m' }" @click="value = 'sq_m'; label = 'Square Meters (m²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Square Meters (m²)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_ft' }" @click="value = 'sq_ft'; label = 'Square Feet (ft²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Square Feet (ft²)</div>
                        </div>
                    </div>
                </div>

                <div class="ncb-row">
                    <!-- Season Select — defaults to the season the calendar
                         says it is now; window labels come from SeasonHelper
                         so admin season edits show up here too. -->
                    @php
                        $seasonNow = \App\Helpers\SeasonHelper::getCurrentSeason();
                        $wetLabel = 'Wet Season (' . \App\Helpers\SeasonHelper::rangeLabel('wet', true) . ')';
                        $dryLabel = 'Dry Season (' . \App\Helpers\SeasonHelper::rangeLabel('dry', true) . ')';
                    @endphp
                    <div class="ncb-group" x-data="{ open: false, value: '{{ $seasonNow }}', label: '{{ $seasonNow === 'wet' ? $wetLabel : $dryLabel }}' }">
                        <label>Season <span class="ncb-required" aria-hidden="true">*</span></label>
                        <input type="hidden" name="season" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">{{ $seasonNow === 'wet' ? $wetLabel : $dryLabel }}</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            <div class="ncb-select-option" :class="{ 'selected': value === 'wet' }" @click="value = 'wet'; label = '{{ $wetLabel }}'; open = false">{{ $wetLabel }}</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'dry' }" @click="value = 'dry'; label = '{{ $dryLabel }}'; open = false">{{ $dryLabel }}</div>
                        </div>
                    </div>

                    <!-- Custom Styled Planting Date -->
                    <div class="ncb-group">
                        <label>Planting Date <span class="ncb-required" aria-hidden="true">*</span></label>
                        <div class="ncb-date-wrapper">
                            <input type="text" class="custom-datepicker" id="plantingDate" name="planting_date" value="{{ now()->format('Y-m-d') }}" required placeholder="Select date">
                            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="ncb-section-label">Planning (Optional)</div>
                <div class="ncb-row">
                    <!-- Custom Styled Expected Harvest Date -->
                    <div class="ncb-group">
                        <label>Expected Harvest Date</label>
                        <div class="ncb-date-wrapper">
                            <input type="text" class="custom-datepicker" id="estHarvest" name="expected_harvest_date" placeholder="Select date">
                            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span class="ncb-yield-hint" id="harvestHint"></span>
                    </div>
                    <div class="ncb-group">
                        <label>Estimated Cost (₱)</label>
                        <input type="number" name="estimated_cost" id="estCost" step="0.01" min="0" placeholder="Auto-fills when a product is selected">
                    </div>
                </div>
                <div class="ncb-cost-card">
                    <img class="ncb-cost-icon" src="{{ asset('images/information.png') }}" alt="info" width="16" height="16">
                    <span class="ncb-cost-text" id="costHint"></span>
                </div>
                <div class="ncb-row">
                    <div class="ncb-group">
                        <label>Estimated Yield</label>
                        <input type="number" name="estimated_yield" id="estYield" step="0.01" min="0" placeholder="Auto-fills when a crop is selected">
                        <span class="ncb-yield-hint" id="yieldHint"></span>
                    </div>
                    <div class="ncb-group">
                        <label>Yield Unit <span style="font-weight:500;color:#94a3b8;">(search as you type)</span></label>
                        <div class="ncb-unit-wrap">
                            <input type="text" name="yield_unit" id="estUnit" placeholder="e.g. kg, tons, sacks, nuts" autocomplete="off" spellcheck="false">
                            <svg class="ncb-unit-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <div class="ncb-unit-menu" id="unitMenu"></div>
                        </div>
                    </div>
                </div>
                <div class="ncb-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any additional notes about this crop batch..."></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof flatpickr !== "undefined") {
            // Planting date: no past dates allowed (planning forward)
            flatpickr("#plantingDate", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disableMobile: "true",
                onChange: function (selectedDates, dateStr) {
                    window.recalcHarvest();
                }
            });
            // Expected harvest date: auto-fills, min date = planting date
            flatpickr("#estHarvest", {
                dateFormat: "Y-m-d",
                minDate: function () { return document.getElementById('plantingDate').value || "today"; },
                disableMobile: "true"
            });
        }
        if (_varietyMenu && _varietyMenu.childElementCount === 0) {
            fillVarietyOptions(_PICKED_CROP || '');
        }
    });

    var _capTotal = parseFloat("{{ $stats['land_area'] ?? 0 }}") || 0;
    var _capUsed = parseFloat("{{ $stats['used_land_area'] ?? 0 }}") || 0;
    var _capUnit = {!! json_encode($unitLabel ?? 'ha') !!};

    function checkLandCapacity(val) {
        const input = document.getElementById('addLandArea');
        const bar = document.getElementById('capBarFill');
        const rem = document.getElementById('capRemaining');
        const warn = document.getElementById('capWarning');
        if (!input || !bar || _capTotal <= 0) return;
        const entered = parseFloat(val) || 0;
        const newUsed = Math.round((_capUsed + entered) * 100) / 100;
        const newPct = Math.min(100, Math.round((newUsed / _capTotal) * 100));
        const newRem = Math.round(Math.max(0, _capTotal - newUsed) * 100) / 100;
        bar.style.width = newPct + '%';
        input.classList.remove('over-cap', 'warn-cap');
        bar.classList.remove('ok', 'warn', 'over');
        rem.classList.remove('warn', 'over');
        warn.className = 'ncb-cap-warning';
        if (newUsed > _capTotal) {
            bar.classList.add('over');
            input.classList.add('over-cap');
            rem.classList.add('over');
            rem.textContent = 'Exceeded by ' + Math.abs(_capTotal - newUsed).toFixed(2) + ' ' + _capUnit;
            warn.className = 'ncb-cap-warning over';
            warn.innerHTML = '<i data-lucide="triangle-alert" width="14" height="14" style="flex-shrink:0"></i><span>Land area exceeds your total farm size! You cannot plant more than ' + _capTotal + ' ' + _capUnit + '.</span>';
            if (window.lucide) lucide.createIcons();
        } else if (newPct >= 80) {
            bar.classList.add('warn');
            input.classList.add('warn-cap');
            rem.classList.add('warn');
            rem.textContent = newRem.toFixed(1) + ' ' + _capUnit + ' remaining';
            warn.className = 'ncb-cap-warning warn';
            warn.innerHTML = '<i data-lucide="triangle-alert" width="14" height="14" style="flex-shrink:0"></i><span>Running low on land — only ' + newRem.toFixed(1) + ' ' + _capUnit + ' will remain.</span>';
            if (window.lucide) lucide.createIcons();
        } else {
            bar.classList.add('ok');
            rem.textContent = newRem.toFixed(1) + ' ' + _capUnit + ' remaining';
        }
    }

    /* ── Yield auto-estimation ── */
    var _CROP_DEFAULTS = @json($cropDefaults);
    var _PRODUCT_YIELDS = @json($productYields);
    var _PRODUCT_COSTS = @json($productCosts);
    var _CROP_PRODUCT_SLUGS = @json($cropProductSlugs);
    var _CROP_PRODUCT_KEYWORDS = @json($cropProductKeywords);
    var _CROP_VARIETIES = @json($cropVarieties);
    var _CROP_INFO = @json($cropInfo);
    var _GENERAL_VARIETY = @json(\App\Services\CropLibraryService::GENERAL_VARIETY);
    var _GENERAL_VARIETY_KEY = @json(\App\Services\CropLibraryService::GENERAL_VARIETY_KEY);
    var _PICKED_CROP = '';
    var _PICKED_PRODUCT = '';
    var _PICKED_VARIETY_KEY = _GENERAL_VARIETY_KEY;
    var _harvestAuto = false;

    /* ── Variety step: options come from the crop library per crop ── */
    var _varietyMenu = document.getElementById('varietyMenu');
    var _varietyTrigger = document.getElementById('varietyTrigger');
    var _varietyValue = document.getElementById('varietyValue');
    var _varietyKeyValue = document.getElementById('varietyKeyValue');
    var _varietyLabel = document.getElementById('varietyLabel');
    var _varietyNote = document.getElementById('varietyNote');
    var _varietyArrow = document.getElementById('varietyArrow');
    var _varietyOptions = [];

    function closeVarietyMenu() {
        if (!_varietyMenu) return;
        _varietyMenu.style.display = 'none';
        _varietyTrigger && _varietyTrigger.classList.remove('active');
        _varietyArrow && _varietyArrow.classList.remove('rotate');
    }

    function selectVariety(opt) {
        if (!opt) return;
        _PICKED_VARIETY_KEY = opt.key;
        if (_varietyValue) _varietyValue.value = opt.name;
        if (_varietyKeyValue) _varietyKeyValue.value = opt.key;
        if (_varietyLabel) {
            _varietyLabel.textContent = opt.name;
            _varietyTrigger.classList.toggle('placeholder', opt.key === _GENERAL_VARIETY_KEY);
        }
        if (_varietyNote) {
            _varietyNote.textContent = opt.note || 'Preselected default — keep it when you are using local or free-distributed planting material.';
        }
        if (_varietyMenu) {
            Array.prototype.forEach.call(_varietyMenu.querySelectorAll('.ncb-select-option'), function (el) {
                el.classList.toggle('selected', el.getAttribute('data-key') === opt.key);
            });
        }
        window.recalcYield && window.recalcYield();
        window.recalcHarvest && window.recalcHarvest();
        showCropInfo(_PICKED_CROP);
    }

    function fillVarietyOptions(cropId) {
        if (!_varietyMenu) return;
        _varietyOptions = _CROP_VARIETIES[cropId] || [{ key: _GENERAL_VARIETY_KEY, name: _GENERAL_VARIETY, note: null }];
        _varietyMenu.innerHTML = '';
        _varietyOptions.forEach(function (opt) {
            var div = document.createElement('div');
            div.className = 'ncb-select-option';
            div.setAttribute('data-key', opt.key);
            var name = document.createElement('span');
            name.textContent = opt.name;
            div.appendChild(name);
            if (opt.note) {
                var note = document.createElement('div');
                note.style.cssText = 'font-size:11px;color:#94a3b8;line-height:1.4;margin-top:2px;';
                note.textContent = opt.note;
                div.appendChild(note);
            }
            div.addEventListener('click', function () {
                selectVariety(opt);
                closeVarietyMenu();
            });
            _varietyMenu.appendChild(div);
        });
        /* Always default to Local / General variety. */
        selectVariety(_varietyOptions[0]);
        _PICKED_VARIETY_KEY = _varietyOptions[0] ? _varietyOptions[0].key : _GENERAL_VARIETY_KEY;
    }

    if (_varietyTrigger) {
        _varietyTrigger.addEventListener('click', function () {
            var open = _varietyMenu.style.display !== 'block';
            _varietyMenu.style.display = open ? 'block' : 'none';
            _varietyTrigger.classList.toggle('active', open);
            _varietyArrow.classList.toggle('rotate', open);
        });
        document.addEventListener('click', function (e) {
            var group = document.getElementById('varietyGroup');
            if (group && !group.contains(e.target)) closeVarietyMenu();
        });
    }

    /* The picked variety, or null for the general default. */
    function _pickedVariety() {
        if (!_PICKED_CROP || !_PICKED_VARIETY_KEY || _PICKED_VARIETY_KEY === _GENERAL_VARIETY_KEY) return null;
        var list = _CROP_VARIETIES[_PICKED_CROP] || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].key === _PICKED_VARIETY_KEY) return list[i];
        }
        return null;
    }

    /* ── Crop agronomy info panel (breed-specific when a variety is picked) ── */
    function _esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    /* Citation line: link icon + "Source: …", on its own line, opening the
       official reference in a new tab. Text and URL are escaped. */
    function _sourceLinkHtml(text, url) {
        if (!text) return '';
        var icon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><path d="M8 12h8"/></svg>';
        if (!url) return '<span class="ncb-source-link">' + icon + 'Source: ' + _esc(text) + '</span>';
        return '<a class="ncb-source-link" href="' + _esc(url) + '" target="_blank" rel="noopener noreferrer">' +
            icon + 'Source: ' + _esc(text) + '</a>';
    }
    var _activeGrowthStage = 0;
    function _setGrowthStage(idx) {
        var container = document.getElementById('cropInfoText');
        if (!container) return;
        _activeGrowthStage = idx;
        var steps = container.querySelectorAll('.ncb-step-btn');
        var panels = container.querySelectorAll('.ncb-stage-panel');
        var tiles = container.querySelectorAll('.ncb-spec-tile');
        var fill = container.querySelector('.ncb-stepper-fill');
        Array.prototype.forEach.call(steps, function (btn, i) {
            btn.classList.toggle('is-active', i === idx);
            btn.classList.toggle('is-done', i < idx);
            btn.setAttribute('aria-selected', i === idx ? 'true' : 'false');
        });
        Array.prototype.forEach.call(panels, function (panel, i) {
            panel.classList.toggle('is-active', i === idx);
        });
        Array.prototype.forEach.call(tiles, function (tile) {
            tile.classList.toggle('is-highlighted', Number(tile.getAttribute('data-jump-stage')) === idx);
        });
        if (fill) {
            fill.style.width = (idx === 0 ? '0%' : (idx === 1 ? '50%' : '100%'));
        }
    }
    function showCropInfo(cropId) {
        var card = document.getElementById('cropInfoCard');
        var text = document.getElementById('cropInfoText');
        if (!card || !text) return;
        var info = _CROP_INFO[cropId];
        if (!info) { card.style.display = 'none'; text.innerHTML = ''; return; }

        var variety = (String(cropId) === String(_PICKED_CROP)) ? _pickedVariety() : null;
        var defaults = _CROP_DEFAULTS[cropId] || {};
        var yieldUnit = info.yield_unit || 'kg';

        var maturityVal = (variety && variety.maturity_days)
            ? (variety.maturity_days + ' days')
            : (info.days_to_harvest || (defaults.maturity_days ? defaults.maturity_days + ' days' : 'Varies'));
        var maturitySub = (variety && variety.maturity_days && info.days_to_harvest)
            ? ('Window: ' + info.days_to_harvest)
            : '';

        var yieldVal = (variety && variety.yield_kg_ha)
            ? ('~' + Number(variety.yield_kg_ha).toLocaleString() + ' kg/ha')
            : (info.yield_per_ha ? ('~' + Number(info.yield_per_ha).toLocaleString() + ' ' + yieldUnit + '/ha') : 'Custom');
        var yieldSub = (variety && variety.yield_kg_ha && info.yield_per_ha)
            ? ('Crop avg: ' + Number(info.yield_per_ha).toLocaleString() + ' ' + yieldUnit + '/ha')
            : '';

        var spacingVal = info.spacing || 'Standard';
        var plantingVal = info.planting_window || 'Year-round / Local';

        /* Build crisp bullet items per stage */
        var stage1Items = [];
        if (info.planting_window) {
            stage1Items.push({ k: 'Planting period:', v: info.planting_window });
        }
        if (variety && variety.note) {
            stage1Items.push({ k: 'Planting material:', v: variety.note });
        }
        if (!stage1Items.length) {
            stage1Items.push({ k: 'Setup:', v: 'Prepare field beds and follow local seasonal timing for ' + info.name + '.' });
        }

        var stage2Items = [];
        if (info.spacing) {
            stage2Items.push({ k: 'Recommended spacing:', v: 'Allow ' + info.spacing + ' between plants/rows.' });
        }
        if (variety) {
            stage2Items.push({
                k: 'Selected variety:',
                v: variety.name + (variety.note ? ' — ' + variety.note : ' (' + info.name + ')')
            });
        }
        if (!stage2Items.length) {
            stage2Items.push({ k: 'Field care:', v: 'Maintain even moisture, weed control, and healthy spacing for ' + info.name + '.' });
        }

        var stage3Items = [];
        if (variety && variety.maturity_days) {
            stage3Items.push({ k: 'Time to harvest:', v: 'About ' + variety.maturity_days + ' days from planting.' });
        }
        if (info.days_to_harvest) {
            stage3Items.push({ k: 'Harvest window:', v: info.days_to_harvest });
        }
        if (variety && variety.yield_kg_ha) {
            stage3Items.push({ k: variety.name + ' yield:', v: 'Around ' + Number(variety.yield_kg_ha).toLocaleString() + ' kg/ha' });
        }
        if (info.yield_per_ha) {
            stage3Items.push({ k: 'Typical crop yield:', v: 'About ' + Number(info.yield_per_ha).toLocaleString() + ' ' + yieldUnit + '/ha' });
        }
        if (!stage3Items.length) {
            stage3Items.push({ k: 'Harvest:', v: 'Harvest when crop reaches full visual maturity.' });
        }

        function renderStageRows(items) {
            return '<div class="ncb-stage-list">' + items.map(function (it) {
                return '<div class="ncb-stage-row"><span class="ncb-stage-bullet"></span><div><span class="ncb-stage-key">' +
                    _esc(it.k) + '</span><span>' + _esc(it.v) + '</span></div></div>';
            }).join('') + '</div>';
        }

        var sproutIcon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></svg>';
        var calIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
        var rulerIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>';
        var clockIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
        var trendIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>';

        var varietyBadge = variety
            ? '<span class="ncb-growth-variety-tag">' + _esc(variety.name) + '</span>'
            : '<span class="ncb-growth-variety-tag">' + _esc(_GENERAL_VARIETY) + '</span>';

        var html = '' +
            '<div class="ncb-growth-header">' +
                '<div class="ncb-growth-title-wrap">' +
                    '<div class="ncb-growth-emblem">' + sproutIcon + '</div>' +
                    '<div>' +
                        '<div class="ncb-growth-eyebrow">Crop Growth Guide</div>' +
                        '<div class="ncb-growth-title">' + _esc(info.name) + ' ' + varietyBadge + '</div>' +
                    '</div>' +
                '</div>' +
                '<span class="ncb-growth-hint-pill">Click a stage to explore</span>' +
            '</div>' +
            '<div class="ncb-growth-body">' +
                '<div class="ncb-stepper" role="tablist" aria-label="Crop growth stages">' +
                    '<div class="ncb-stepper-track"><div class="ncb-stepper-fill"></div></div>' +
                    '<button type="button" class="ncb-step-btn" data-stage-idx="0" role="tab" aria-selected="true">' +
                        '<span class="ncb-step-dot">1</span>' +
                        '<span class="ncb-step-name">Planting</span>' +
                        '<span class="ncb-step-mini">' + _esc(info.planting_window ? info.planting_window : 'Start stage') + '</span>' +
                    '</button>' +
                    '<button type="button" class="ncb-step-btn" data-stage-idx="1" role="tab" aria-selected="false">' +
                        '<span class="ncb-step-dot">2</span>' +
                        '<span class="ncb-step-name">Growth &amp; Spacing</span>' +
                        '<span class="ncb-step-mini">' + _esc(info.spacing ? info.spacing : 'Field setup') + '</span>' +
                    '</button>' +
                    '<button type="button" class="ncb-step-btn" data-stage-idx="2" role="tab" aria-selected="false">' +
                        '<span class="ncb-step-dot">3</span>' +
                        '<span class="ncb-step-name">Harvest &amp; Yield</span>' +
                        '<span class="ncb-step-mini">' + _esc(maturityVal) + '</span>' +
                    '</button>' +
                '</div>' +
                '<div class="ncb-stage-panel" data-stage-panel="0">' +
                    '<div class="ncb-stage-panel-head">' + calIcon + '<span>Stage 1 · Planting &amp; Establishment</span></div>' +
                    renderStageRows(stage1Items) +
                '</div>' +
                '<div class="ncb-stage-panel" data-stage-panel="1">' +
                    '<div class="ncb-stage-panel-head">' + rulerIcon + '<span>Stage 2 · Spacing &amp; Field Care</span></div>' +
                    renderStageRows(stage2Items) +
                '</div>' +
                '<div class="ncb-stage-panel" data-stage-panel="2">' +
                    '<div class="ncb-stage-panel-head">' + trendIcon + '<span>Stage 3 · Maturity &amp; Harvest</span></div>' +
                    renderStageRows(stage3Items) +
                '</div>' +
                '<div class="ncb-specs-grid">' +
                    '<button type="button" class="ncb-spec-tile" data-jump-stage="0">' +
                        '<span class="ncb-spec-label">' + calIcon + ' Planting Period</span>' +
                        '<span class="ncb-spec-val">' + _esc(plantingVal) + '</span>' +
                    '</button>' +
                    '<button type="button" class="ncb-spec-tile" data-jump-stage="1">' +
                        '<span class="ncb-spec-label">' + rulerIcon + ' Spacing</span>' +
                        '<span class="ncb-spec-val">' + _esc(spacingVal) + '</span>' +
                    '</button>' +
                    '<button type="button" class="ncb-spec-tile" data-jump-stage="2">' +
                        '<span class="ncb-spec-label">' + clockIcon + ' Maturity</span>' +
                        '<span class="ncb-spec-val">' + _esc(maturityVal) + '</span>' +
                        (maturitySub ? '<span class="ncb-spec-sub">' + _esc(maturitySub) + '</span>' : '') +
                    '</button>' +
                    '<button type="button" class="ncb-spec-tile" data-jump-stage="2">' +
                        '<span class="ncb-spec-label">' + trendIcon + ' Expected Yield</span>' +
                        '<span class="ncb-spec-val">' + _esc(yieldVal) + '</span>' +
                        (yieldSub ? '<span class="ncb-spec-sub">' + _esc(yieldSub) + '</span>' : '') +
                    '</button>' +
                '</div>' +
            '</div>' +
            (info.source ? '<div class="ncb-growth-footer">' + _sourceLinkHtml(info.source, info.source_url) + '</div>' : '');

        text.innerHTML = html;

        Array.prototype.forEach.call(text.querySelectorAll('[data-stage-idx]'), function (btn) {
            btn.addEventListener('click', function () {
                _setGrowthStage(Number(btn.getAttribute('data-stage-idx')));
            });
        });
        Array.prototype.forEach.call(text.querySelectorAll('[data-jump-stage]'), function (tile) {
            tile.addEventListener('click', function () {
                _setGrowthStage(Number(tile.getAttribute('data-jump-stage')));
            });
        });

        _setGrowthStage(_activeGrowthStage);
        card.style.display = 'block';
    }

    /* ── Auto batch name: {CROP}-FIELD{N}-{DDMMYY} (e.g. TOMATO-FIELD1-160926) ── */
    var _BATCH_COUNTS = @json(collect($crops)->reject(fn ($c) => ! $c->crop)->groupBy(fn ($c) => $c->crop->name)->map->count() ?? []);
    var _batchNameEl = document.getElementById('batchName');
    var _batchAuto = true;
    function _batchSlug(name) { return (name || '').replace(/[^a-zA-Z0-9]+/g, '').toUpperCase(); }
    function _batchDmy(ymd) {
        var p = (ymd || '').split('-');
        return p.length === 3 ? ((p[2] || '') + (p[1] || '') + (p[0] || '').slice(-2)) : '';
    }
    window.autoBatchName = function () {
        if (!_batchNameEl || !_batchAuto) return;
        var crop = _CROP_DEFAULTS[_PICKED_CROP];
        if (!crop || !crop.name) return;
        var plantEl = document.getElementById('plantingDate');
        var d = _batchDmy(plantEl ? plantEl.value : '');
        if (!d) return;
        var field = (_BATCH_COUNTS[crop.name] || 0) + 1;
        _batchNameEl.value = _batchSlug(crop.name) + '-FIELD' + field + '-' + d;
    };
    if (_batchNameEl) {
        _batchNameEl.addEventListener('input', function () {
            if (!_batchNameEl.value.trim()) { _batchAuto = true; window.autoBatchName(); }
            else { _batchAuto = false; }
        });
    }

    /* Mass units convertible to kilograms (used when the user switches the
       Yield Unit — the estimate is re-converted so the amount stays equal). */
    var _KG_PER_UNIT = { kg: 1, tons: 1000, grams: 0.001, quintal: 100, sacks: 50, cavans: 50 };
    var _lastKgEst = null;

    /* ── Expected Harvest Date auto-estimation ── */
    var _HARVEST_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    function _harvestFmt(d) { return _HARVEST_MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear(); }
    function _harvestYmd(d) { return d.getFullYear() + '-' + (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate(); }

    window.recalcHarvest = function () {
        var el = document.getElementById('estHarvest');
        var hintEl = document.getElementById('harvestHint');
        if (!el || !hintEl) return;

        var crop = _CROP_DEFAULTS[_PICKED_CROP];
        if (!_PICKED_CROP || !crop) {
            hintEl.textContent = 'Optional — choose a crop type and we\'ll estimate the harvest date for you.';
            return;
        }

        var mdays = parseFloat(crop.maturity_days) || 0;
        var who = crop.name;
        var basis = crop.harvest_days ? ' — typical window ' + crop.harvest_days : '';
        var source = crop.source || 'Sorsogon crop library';

        /* Product > Variety > Crop, same precedence as the yield estimate. */
        var variety = _pickedVariety();
        if (variety && variety.maturity_days) {
            mdays = parseFloat(variety.maturity_days) || mdays;
            who = variety.name + ' (' + crop.name + ')';
            basis = ' — breed-specific maturity';
        }
        if (_PICKED_PRODUCT && _PRODUCT_YIELDS[_PICKED_PRODUCT] && _PRODUCT_YIELDS[_PICKED_PRODUCT].maturity_days) {
            var pv = _PRODUCT_YIELDS[_PICKED_PRODUCT];
            mdays = parseFloat(pv.maturity_days) || 0;
            who = pv.variety + ' ' + crop.name;
            basis = ' — maturity of the selected seed product';
        }

        var plantEl = document.getElementById('plantingDate');
        var date = new Date((plantEl ? plantEl.value : '') + 'T00:00:00');
        if (!mdays || isNaN(date.getTime())) {
            hintEl.textContent = mdays
                ? 'Enter a planting date above and we\'ll estimate when harvest comes.'
                : 'No maturity data for this crop yet — enter the date yourself.';
            return;
        }

        date.setDate(date.getDate() + mdays);
        var suggested = _harvestFmt(date);

        if (!el.value || _harvestAuto) {
            el.value = _harvestYmd(date);
            _harvestAuto = true;
            hintEl.innerHTML = _esc('Auto-set: ' + who + ' takes ' + mdays + ' days from planting to harvest' +
                basis + ' → ' + suggested + '.') + _sourceLinkHtml(source, crop.source_url);
        } else {
            hintEl.textContent = 'You set your own harvest date. ' + who + ' takes ' + mdays +
                ' days from planting → suggested ' + suggested + '.';
        }
    };

    window.pickCrop = function (id) {
        _PICKED_CROP = String(id);
        fillVarietyOptions(_PICKED_CROP);
        showCropInfo(_PICKED_CROP);
        filterProducts();
        window.recalcYield();
        window.recalcHarvest();
        window.autoBatchName();
    };

    window.pickProduct = function (id) {
        _PICKED_PRODUCT = id ? String(id) : '';
        window.recalcYield();
        window.recalcHarvest();
    };

    /* Filter the Specific Product dropdown so only seeds genuinely meant for the
       selected crop show. A product is applicable when:
         - it is directly linked to the crop (product_applications.crop_id), or
         - its name contains one of the crop's curated keywords, or
         - the crop declares no keywords AND its category is an allowed fallback. */
    function filterProducts() {
        var cropId = _PICKED_CROP;
        var allowed = _CROP_PRODUCT_SLUGS[cropId] || [];
        var keywords = _CROP_PRODUCT_KEYWORDS[cropId] || [];
        var opts = document.querySelectorAll('#prodList .ncb-select-option[data-pid]');
        var anyVisible = false;
        for (var i = 0; i < opts.length; i++) {
            var opt = opts[i];
            var cat = opt.getAttribute('data-catslug') || '';
            var name = opt.getAttribute('data-name') || '';
            var cropIds = (opt.getAttribute('data-cropids') || '')
                .split(',').filter(function (x) { return x.trim() !== ''; });

            var linked = cropIds.indexOf(cropId) !== -1;
            var keywordHit = keywords.length > 0 && keywords.indexOf('') === -1 &&
                keywords.some(function (kw) { return name.indexOf(kw) !== -1; });
            var catFallback = keywords.length === 0 && allowed.indexOf(cat) !== -1;

            var show = linked || keywordHit || catFallback;
            opt.style.display = show ? 'block' : 'none';
            if (show) anyVisible = true;
        }
        var emptyEl = document.getElementById('prodNoMatch');
        if (emptyEl) emptyEl.style.display = (cropId && !anyVisible) ? 'block' : 'none';
        if (_PICKED_PRODUCT) {
            var selOpt = document.querySelector('#prodList .ncb-select-option[data-pid="' + _PICKED_PRODUCT + '"]');
            if (selOpt && selOpt.style.display === 'none') {
                var noneBtn = document.getElementById('prodNone');
                if (noneBtn) noneBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
            }
        }
    }

    window.recalcYield = function () {
        var yieldEl = document.getElementById('estYield');
        var unitEl = document.getElementById('estUnit');
        var hintEl = document.getElementById('yieldHint');
        if (!yieldEl || !unitEl || !hintEl) return;

        window.recalcCost();

        var areaInput = document.getElementById('addLandArea');
        var unitInput = document.getElementById('landAreaUnit');
        var area = parseFloat(areaInput ? areaInput.value : 0) || 0;
        var unit = unitInput ? unitInput.value : 'ha';
        var crop = _CROP_DEFAULTS[_PICKED_CROP];

        if (!crop || !_PICKED_CROP) {
            hintEl.textContent = _PICKED_CROP
                ? 'No built-in yield rate for this crop yet — enter the estimate manually, or leave blank (optional).'
                : '';
            return;
        }

        var base = parseFloat(crop.default_yield_per_ha) || 0;
        var yieldUnit = crop.yield_unit || 'kg';
        var srcDesc = 'the typical Sorsogon yield for ' + crop.name;

        /* Product > Variety > Crop */
        var variety = _pickedVariety();
        if (variety && variety.yield_kg_ha) {
            base = parseFloat(variety.yield_kg_ha) || base;
            srcDesc = 'the ' + variety.name + ' variety yield';
        }

        if (_PICKED_PRODUCT && _PRODUCT_YIELDS[_PICKED_PRODUCT]) {
            var pv = _PRODUCT_YIELDS[_PICKED_PRODUCT];
            base = parseFloat(pv.default_yield_per_ha) || base;
            yieldUnit = pv.yield_unit || yieldUnit;
            srcDesc = 'the ' + pv.variety + ' seed-product yield';
        }

        if (!base) {
            hintEl.textContent = 'No built-in yield rate for this crop yet — enter the estimate manually, or leave blank (optional).';
            return;
        }

        var ha = unit === 'ha' ? area
            : (unit === 'sq_m' ? area / 10000 : area / 107639.104);
        var kgEst = base * ha;
        _lastKgEst = kgEst;

        var curUnit = (unitEl.value || '').trim().toLowerCase();
        var est, outUnit;
        if (!curUnit) {
            outUnit = _KG_PER_UNIT[yieldUnit] !== undefined ? yieldUnit : 'kg';
            est = kgEst / (_KG_PER_UNIT[outUnit] || 1);
            if (outUnit === 'kg' && est >= 1000) { est = est / 1000; outUnit = 'tons'; }
        } else if (_KG_PER_UNIT[curUnit] !== undefined) {
            outUnit = curUnit;
            est = kgEst / _KG_PER_UNIT[curUnit];
        } else {
            hintEl.textContent = 'Your estimate is about ' + Number(kgEst).toLocaleString() + ' kg — ' +
                'pick a standard unit above or just type your own.';
            return;
        }
        est = (est < 10) ? Math.round(est * 100) / 100 : Math.round(est);

        yieldEl.value = est;
        unitEl.value = outUnit;

        var areaLabel = unit === 'ha' ? area + ' ha' : (unit === 'sq_m' ? area + ' m²' : area + ' ft²');
        hintEl.innerHTML = _esc('Auto-estimated ~' + Number(est).toLocaleString() + ' ' + outUnit + ' using ' +
            srcDesc + ' of ' + Number(base).toLocaleString() + ' ' + yieldUnit + '/ha × ' + areaLabel + '.') +
            _sourceLinkHtml(crop.source || 'Sorsogon crop library', crop.source_url);
    };

    /* ── Estimated cost auto-calculation ── */
    window.recalcCost = function () {
        var costEl = document.getElementById('estCost');
        var hintEl = document.getElementById('costHint');
        if (!costEl || !hintEl) return;

        var info = _PRODUCT_COSTS[_PICKED_PRODUCT];
        if (!_PICKED_PRODUCT || !info) {
            hintEl.textContent = _PICKED_PRODUCT
                ? 'We don\'t have a planting rate for this product yet — no problem, just type the cost you expect.'
                : 'Optional: Choose a seed product above and we\'ll estimate the total cost for your land area automatically.';
            return;
        }

        var areaInput = document.getElementById('addLandArea');
        var unitInput = document.getElementById('landAreaUnit');
        var area = parseFloat(areaInput ? areaInput.value : 0) || 0;
        var unit = unitInput ? unitInput.value : 'ha';
        if (!area) return;

        var ha = unit === 'ha' ? area : (unit === 'sq_m' ? area / 10000 : area / 107639.104);
        var rateUnit = info.rate_unit || 'kg/ha';
        var factor = 1;
        if (info.package_unit === 'g' && rateUnit.indexOf('kg') === 0) factor = 1000;
        else if (info.package_unit === 'kg' && rateUnit.indexOf('g') === 0) factor = 0.001;
        var pkgSize = parseFloat(info.package_size) || 1;
        var price = parseFloat(info.price) || 0;
        var rateMin = parseFloat(info.rate_min) || 0;
        var rateMax = parseFloat(info.rate_max) || rateMin;
        var label = (info.package_label || 'package').toLowerCase();

        function packsFor(rate) { return rate > 0 ? Math.ceil((ha * rate * factor) / pkgSize) : 1; }

        var packs = packsFor(rateMax);
        var cost = Math.round(packs * price * 100) / 100;
        costEl.value = cost.toFixed(2);

        var fmt = function (n) { return Number(n).toLocaleString('en-US'); };
        var plural = function (n, s) { return n === 1 ? s : (s.slice(-1) === 's' ? s : s + 's'); };
        var needUnits = Math.round(ha * rateMax * factor);

        if (rateMax > 0) {
            var rateLabel = rateMax === rateMin
                ? fmt(rateMax) + ' ' + rateUnit
                : fmt(rateMin) + '–' + fmt(rateMax) + ' ' + rateUnit;
            var perWord = (info.price_unit || '').replace(/^per\s+/i, '') ||
                (info.package_unit === 'piece' ? 'piece' : label);
            var nounWord = plural(packs, info.package_unit === 'piece' ? 'unit' : label);

            hintEl.textContent = 'Your seeds for this batch will cost about ₱' + fmt(cost) + '. ' +
                'For ' + fmt(ha) + ' hectar' + (ha === 1 ? 'e' : 'es') + ' at the usual planting rate of ' + rateLabel +
                ', you\'ll need around ' + fmt(needUnits) + ' ' + plural(needUnits, info.package_unit) +
                ' — about ' + packs + ' ' + nounWord + ' at ₱' + fmt(price) + ' per ' + perWord + '.';
        } else {
            hintEl.textContent = 'We don\'t have a planting rate for this product yet — this is just the price of one ' +
                label + ': ₱' + fmt(price) + '. Enter the real cost you expect below.';
        }
    };

    /* ── Yield Unit search-as-you-type combobox ── */
    var _UNIT_OPTIONS = [
        ['kg', 'kilograms'],
        ['tons', 'tonnes / metric tons'],
        ['sacks', 'sacks (rice ≈ 50 kg)'],
        ['cavans', 'cavan (palay ≈ 50 kg)'],
        ['quintal', '100 kg'],
        ['pieces', 'count'],
        ['nuts', 'coconut / pili nuts'],
        ['bunches', 'bananas'],
        ['heads', 'cabbage / lettuce heads'],
        ['bundles', 'bounded leafy greens'],
        ['crates', 'packed produce'],
        ['bales', 'abaca / hay'],
        ['stalks', 'corn plants'],
        ['trees', 'perennial trees'],
        ['liters', 'liquid measure'],
        ['grams', 'small weights'],
    ];
    var _estUnitEl = document.getElementById('estUnit');
    var _unitMenuEl = document.getElementById('unitMenu');

    function renderUnitMenu() {
        var f = (_estUnitEl.value || '').trim().toLowerCase();
        var items = _UNIT_OPTIONS.filter(function (u) { return !f || u[0].indexOf(f) !== -1 || u[1].indexOf(f) !== -1; });
        if (!items.length) {
            _unitMenuEl.style.display = 'block';
            _unitMenuEl.innerHTML = '<div class="ncb-unit-empty">No matching units — just type your own.</div>';
            return;
        }
        _unitMenuEl.innerHTML = items.map(function (u) {
            return '<div class="ncb-unit-option" data-unit="' + u[0] + '">' +
                '<span>' + u[0] + '</span><span class="ncb-unit-alias">' + u[1] + '</span></div>';
        }).join('');
        _unitMenuEl.style.display = 'block';
    }

    function closeUnitMenu() { _unitMenuEl.style.display = 'none'; }

    if (_estUnitEl && _unitMenuEl) {
        _unitMenuEl.addEventListener('mousedown', function (e) {
            var opt = e.target.closest('.ncb-unit-option');
            if (!opt) return;
            e.preventDefault();
            _estUnitEl.value = opt.getAttribute('data-unit');
            closeUnitMenu();
            window.recalcYield && window.recalcYield();
        });
        _estUnitEl.addEventListener('focus', function () { renderUnitMenu(); });
        _estUnitEl.addEventListener('input', function () { renderUnitMenu(); });
        _estUnitEl.addEventListener('change', function () { window.recalcYield && window.recalcYield(); });
        _estUnitEl.addEventListener('blur', function () { setTimeout(closeUnitMenu, 150); });
        _estUnitEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeUnitMenu(); _estUnitEl.blur(); }
            if (e.key === 'Enter') {
                var sel = _unitMenuEl.querySelector('.ncb-unit-option');
                if (sel) { _estUnitEl.value = sel.getAttribute('data-unit'); }
                closeUnitMenu();
                window.recalcYield && window.recalcYield();
            }
        });
    }

    var _plantingEl = document.getElementById('plantingDate');
    var _harvestEl = document.getElementById('estHarvest');
    if (_plantingEl) {
        _plantingEl.addEventListener('change', function () {
            window.recalcHarvest && window.recalcHarvest();
            window.autoBatchName && window.autoBatchName();
        });
    }
    if (_harvestEl) {
        _harvestEl.addEventListener('change', function () {
            _harvestAuto = false;
            if (!_harvestEl.value) { window.recalcHarvest && window.recalcHarvest(); }
        });
    }

    window.recalcCost && window.recalcCost();
    window.recalcHarvest && window.recalcHarvest();

    /* ── Add Crop Planting via AJAX — no full page reload ── */
    var _addForm = document.getElementById('cropPlantingForm');
    if (_addForm) {
        _addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = _addForm.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.innerHTML = 'Adding…';
            btn.disabled = true;

            var formData = new FormData(_addForm);

            fetch(_addForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function (response) {
                // Controller redirects on both success and validation error
                closeModal('addModal');
                if (typeof Turbo !== 'undefined') {
                    Turbo.visit(response.url || '/farmer/crops', { action: 'replace' });
                } else {
                    window.location.href = response.url || '/farmer/crops';
                }
            })
            .catch(function (err) {
                console.error('Add crop error:', err);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }
</script>