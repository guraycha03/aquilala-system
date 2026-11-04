@extends('layouts.sidebar')

@section('title', 'My Crops')

@section('content')
<style>
    /* ── Page Shell ── */
    .crops-page { position: relative; }
    .crops-page::before, .crops-page::after {
        content: ""; position: fixed; border-radius: 50%; filter: blur(80px);
        pointer-events: none; z-index: 0;
    }
    .crops-page::before { width: 340px; height: 340px; background: rgba(74,122,84,0.10); top: -80px; right: -60px; }
    .crops-page::after { width: 280px; height: 280px; background: rgba(143,184,154,0.08); bottom: -60px; left: -40px; }
    .crops-content { position: relative; z-index: 1; }

    /* ── Hero Banner ── */
    .hero-band {
        background: rgb(248, 243, 232);
        border-radius: 20px; padding: 28px 32px; color: #143d23; position: relative; overflow: hidden; margin-bottom: 24px;
        box-shadow: 0 4px 16px rgba(20,61,35,0.10);
    }
    .hero-band::before {
        content: ""; position: absolute; top: -80px; right: -40px; width: 220px; height: 220px;
        border-radius: 50%; background: rgba(74, 150, 90, 0.24);
    }
    .hero-band::after {
        content: ""; position: absolute; bottom: -70px; left: 20%; width: 180px; height: 180px;
        border-radius: 50%; background: rgba(115, 185, 125, 0.22);
    }
    .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
    .hero-title { font-size: 24px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; }
    .hero-sub { font-size: 13px; color: #40632d; margin: 5px 0 0 0; }
    .hero-sub strong { color: #14532d; }
    .btn-hero {
        display: inline-flex; align-items: center; justify-content: center;
        background: #ffffff; color: #14532d; padding: 10px 22px; border-radius: 10px;
        font-size: 13px; font-weight: 700; letter-spacing: 0.02em; text-decoration: none;
        border: 1px solid rgba(20,61,35,0.10); box-shadow: 0 2px 8px rgba(20,61,35,0.08);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
    }
    .btn-hero:hover { 
        background: #f0fdf4; color: #14532d; transform: translateY(-2px); 
        box-shadow: 0 6px 20px rgba(20,61,35,0.12);
    }
    .btn-hero:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(20,61,35,0.08); }

    /* ── Stats Strip ── */
    .stats-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
    .stat-pill {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: all 0.2s ease;
    }
    .stat-pill:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
    .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 20px; height: 20px; }
    .stat-text { display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .stat-value { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2; margin-top: 2px; }
    .stat-unit { font-size: 12px; font-weight: 500; color: #94a3b8; }

    /* ── Section Header ── */
    .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .section-title { font-size: 16px; font-weight: 700; color: #1e293b; }
    .filter-group { display: flex; gap: 6px; }
    .filter-btn {
        padding: 6px 14px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: #edf1e9; color: #648268; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.15s ease;
    }
    .filter-btn:hover { border-color: #cbd5e1; color: #334155; }
    .filter-btn.active { background: #14532d; color: #fff; border-color: #14532d; }
    .batch-count { display: inline-flex; align-items: center; background: #14532d; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; margin-left: 8px; vertical-align: middle; }

    /* ── Crop Batches Table ── */
    .crop-table-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow-x: auto;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); margin-bottom: 24px;
    }
    /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
    .crop-table { min-width: 880px; }
    .crop-table tbody tr { cursor: pointer; }
    .crop-name-cell { display: flex; align-items: center; gap: 10px; }
    .crop-name-ic { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .crop-name-ic svg { width: 18px; height: 18px; }
    .crop-name { font-weight: 700; color: #1e293b; font-size: 14px; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
    .crop-batch-chip { font-size: 11px; font-weight: 600; color: #3f6b3e; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 12px; white-space: nowrap; }
    .crop-sub { font-size: 12px; color: #94a3b8; margin-top: 3px; }
    .stage-badge-mini { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .stage-badge-mini.confirm-badge { margin-left: 6px; }
    .confirm-badge.pending { border: 1px dashed #f59e0b; }
    .stage-dot-mini { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .season-badge-mini { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; white-space: nowrap; text-transform: capitalize; }
    .season-badge-mini.wet { background: #edf1e9; color: #648268; }
    .season-badge-mini.dry { background: #edf1e9; color: #648268; }
    .yield-cell b { font-variant-numeric: tabular-nums; }
    .yield-tons { font-size: 11.5px; color: #94a3b8; font-weight: 600; margin-top: 1px; }
    .row-progress { width: 110px; height: 6px; background: #eef2f1; border-radius: 99px; overflow: hidden; }
    .row-progress > div { height: 100%; border-radius: 99px; }
    .row-progress-label { font-size: 12px; font-weight: 700; color: #1e293b; margin-top: 5px; }
    .act-yield { font-size: 12px; font-weight: 700; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 8px; white-space: nowrap; }
    .row-actions { display: inline-flex; align-items: center; gap: 6px; }
    .row-btn {
        display: inline-flex; align-items: center; gap: 5px; border-radius: 8px; padding: 7px 10px;
        font-size: 12px; font-weight: 600; border: 1px solid transparent; cursor: pointer;
        transition: all 0.15s; background: none; font-family: inherit;
    }
    .row-btn.plain { color: #64748b; border-color: #e5e7eb; }
    .row-btn.plain:hover { color: #14532d; border-color: #bbf7d0; background: #f0fdf4; }
    .row-btn.red { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
    .row-btn.red:hover { background: #fee2e2; }
    .row-btn:disabled, .row-btn.disabled {
        opacity: 0.45; cursor: not-allowed; color: #94a3b8;
        border-color: #e5e7eb; background: #f8faf9; pointer-events: none;
    }
    @media (max-width: 900px) { .hide-sm { display: none; } }

    .tb-hint { font-size: 12px; color: #94a3b8; font-weight: 500; }

    /* ── Crop Cards ── */
    .crops-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .crop-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        overflow: hidden; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .crop-card:hover { box-shadow: 0 12px 36px rgba(0,0,0,0.08); transform: translateY(-3px); }
    .card-top-accent { height: 4px; width: 100%; }
    .card-header { padding: 16px 20px 0; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .card-crop-name { font-size: 17px; font-weight: 800; color: #1e293b; margin: 0; letter-spacing: -0.01em; }
    .card-meta { display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap; }
    .card-category { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
    .card-batch { font-size: 11px; color: #94a3b8; padding: 2px 8px; background: #f1f5f9; border-radius: 6px; font-weight: 500; }
    .stage-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
    .stage-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .card-body { padding: 14px 20px; }
    .card-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
    .detail-item { display: flex; flex-direction: column; }
    .detail-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .detail-value { font-size: 13px; font-weight: 600; color: #334151; margin-top: 2px; }

    /* ── Growth Stage Stepper ── */
    .stage-stepper { display: flex; align-items: center; gap: 0; margin-top: 14px; padding: 10px 14px; background: #f8fafc; border-radius: 10px; }
    .stepper-step { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; text-align: center; }
    .stepper-step::after { content: ""; position: absolute; top: 8px; left: 50%; width: 100%; height: 2px; background: #e2e8f0; z-index: 0; }
    .stepper-step:last-child::after { display: none; }
    .stepper-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #d1d5db; background: #fff; z-index: 1; position: relative; transition: all 0.3s ease; }
    .stepper-dot.done { background: #4A7A54; border-color: #4A7A54; }
    .stepper-dot.current { background: #fff; border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,0.2); }
    .stepper-label { font-size: 9px; font-weight: 600; color: #94a3b8; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
    .stepper-label.active-label { color: #4A7A54; font-weight: 700; }

    /* ── Progress ── */
    .progress-section { margin-top: 12px; }
    .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .progress-title { font-size: 11px; font-weight: 600; color: #64748b; }
    .progress-pct { font-size: 12px; font-weight: 800; }
    .progress-track { width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }
    .progress-footer { display: flex; justify-content: space-between; margin-top: 5px; }
    .progress-footer span { font-size: 10px; color: #94a3b8; font-weight: 500; }

    .card-footer { padding: 10px 20px; background: #fafbfc; border-top: 1px solid #f1f5f9; display: flex; gap: 8px; align-items: center; }

    /* ── Buttons ── */
    .btn-sm { padding: 6px 13px; font-size: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; border: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.15s ease; text-decoration: none; }
    .btn-sm-green { background: #edf1e9; color: #648268; }
    .btn-sm-green:hover { background: #bbf7d0; }
    .btn-sm-red { background: #fee2e2; color: #991b1b; }
    .btn-sm-red:hover { background: #fecaca; }

    /* ── Empty State ── */
    .empty-wrap { padding: 32px 0; text-align: center; }
    .empty-card { background: #fff; border: 1px dashed #cbd5e1; border-radius: 20px; padding: 60px 32px; max-width: 520px; margin: 0 auto; box-shadow: 0 8px 32px -8px rgba(0,0,0,0.04); }
    .empty-icon-wrap { width: auto; height: auto; background: none; border-radius: 0; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #6f8f73; box-shadow: none; padding: 0; }
    .empty-icon-wrap svg, .empty-icon-wrap i { width: 44px; height: 44px; }
    .empty-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
    .empty-desc { color: #64748b; font-size: 13px; line-height: 1.6; max-width: 360px; margin: 0 auto 24px; }
    .empty-tips { display: flex; justify-content: center; gap: 24px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    .empty-tip { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 500; }
    .empty-tip svg { color: #4A7A54; flex-shrink: 0; }

    /* ── Modal Overlay Fixed Target ── */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15,41,24,0.18); z-index: 99999; overflow-y: auto; padding: 40px 0;
        backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; align-items: center; justify-content: center; }
    .modal-overlay.active .modal { animation: modalIn 0.18s ease-out; }
    .modal {
        background: #fff; border-radius: 18px; width: 95%; max-width: 540px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 24px 80px rgba(0,0,0,0.25); margin: auto; position: relative; z-index: 100000;
    }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
    .modal-header { padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .modal-close { width: 34px; height: 34px; border-radius: 10px; border: none; background: #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.15s ease; }
    .modal-close:hover { background: #e2e8f0; color: #1e293b; }
    .modal-body { padding: 20px 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }

    .form-section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #4A7A54; margin: 16px 0 10px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9; }
    .form-section-label:first-child { margin-top: 0; }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,0.08); }
    .form-group textarea { resize: vertical; min-height: 64px; }

    .btn-cancel { padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px; border: 1px solid #e2e8f0; background: #edf1e9; color: #648268; cursor: pointer; transition: all 0.15s ease; }
    .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-submit { padding: 10px 22px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none; background: #4A7A54; color: #fff; cursor: pointer; box-shadow: 0 4px 12px rgba(74,122,84,0.25); transition: all 0.2s ease; }
    .btn-submit:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(74,122,84,0.3); }

    /* ── Toast ── */
    .toast {
        position: fixed; top: 24px; right: 24px; z-index: 200000;
        background: #166534; color: #fff; padding: 14px 22px; border-radius: 12px;
        font-size: 13px; font-weight: 600; box-shadow: 0 10px 32px rgba(22,101,52,0.3);
        display: flex; align-items: center; gap: 10px;
        animation: toastIn 0.35s cubic-bezier(0.4,0,0.2,1), toastOut 0.3s ease 2.5s forwards;
    }
    @keyframes toastIn { from { opacity: 0; transform: translateX(60px); } }
    @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .stats-strip { grid-template-columns: 1fr 1fr; }
        .crops-grid { grid-template-columns: 1fr; }
        .hero-inner { flex-direction: column; align-items: flex-start; }
        .card-details { grid-template-columns: 1fr; }
        .stepper-label { font-size: 8px; }
    }

    /* ── Modern Farm Plot Dashboard ── */
    .land-plot-card { background: #FFFFFF; border-radius: 24px; padding: 24px; border: 1px solid #E2E8F0; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); margin-bottom: 24px; }
    .land-plot-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
    .land-plot-title { font-size: 16px; font-weight: 800; color: #1E293B; margin: 0; letter-spacing: -0.01em; }
    .land-plot-sub { font-size: 12px; color: #64748B; margin-top: 2px; }
    .land-plot-svg-wrap { position: relative; border-radius: 18px; background: #F8FAF9; border: 1px solid #E2E8F0; padding: 16px; overflow: hidden; }
    .plot-map-controls { position: absolute; top: 28px; right: 28px; display: flex; flex-direction: column; gap: 6px; z-index: 5; }
    .plot-map-btn { width: 32px; height: 32px; border-radius: 50%; background: #FFFFFF; border: 1px solid #E2E8F0; color: #475569; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.06); cursor: pointer; transition: all 0.15s ease; }
    .plot-map-btn:hover { background: #edf1e9; color: #648268; }
    .plot-polygon { cursor: pointer; transform-box: fill-box; transform-origin: center; transition: opacity 0.15s ease; }
    .plot-polygon:hover { opacity: 0.85; }
    .plot-tooltip { position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%); background: #0F172A; color: #FFFFFF; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; pointer-events: none; opacity: 0; transition: opacity 0.2s ease; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .land-legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #F1F5F9; }
    .land-legend-pill { display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: #F8FAF9; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 12px; font-weight: 600; color: #334155; }
    .land-legend-dot { width: 10px; height: 10px; border-radius: 50%; }

    /* ── Farm Plot Fullscreen ── */
    .farm-fullscreen-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999; background: rgba(15,41,24,0.18); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .farm-fullscreen-overlay.active { display: flex; }
    .farm-fullscreen-box { background: #fff; border-radius: 20px; width: 95vw; height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,0.3); animation: modalIn 0.18s ease-out; }
    .farm-fs-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
    .farm-fs-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; }
    .farm-fs-header-actions { display: flex; gap: 8px; align-items: center; }
    .farm-fs-close { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.15s ease; }
    .farm-fs-close:hover { background: #f1f5f9; color: #1e293b; }
    .farm-fs-body { flex: 1; overflow: auto; position: relative; background: #f8faf9; cursor: grab; }
    .farm-fs-body.grabbing { cursor: grabbing; }
    .farm-fs-body .farm-fs-svg { min-width: 100%; min-height: 100%; display: block; transition: transform 0.15s ease; transform-origin: center center; }
    .farm-fs-zoom-controls { position: absolute; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 4px; z-index: 10; }
    .farm-fs-zoom-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 18px; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.15s ease; }
    .farm-fs-zoom-btn:hover { background: #edf1e9; color: #648268; }
    .farm-fs-zoom-label { text-align: center; font-size: 11px; font-weight: 700; color: #64748b; background: #fff; border-radius: 8px; padding: 3px 0; border: 1px solid #e2e8f0; min-width: 38px; }

</style>

@if(session('success'))
<div class="toast">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="crops-page">
<div class="crops-content">

<!-- ── Hero ── -->
<section class="hero-band">
    <div class="hero-inner">
        <div>
            <h1 class="hero-title">My Crops</h1>
            <p class="hero-sub">Track planting cycles, monitor <strong>growth stages</strong>, and plan your harvests across seasons.</p>
            <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 12px; color: #40632d;">Your land:</span>
                @if($user->default_land_area)
                <button onclick="openLandModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(20,61,35,0.06); border: 1px solid rgba(20,61,35,0.12); border-radius: 8px; color: #14532d; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s ease;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    {{ $user->default_land_area }} {{ $user->default_land_area_unit === 'ha' ? 'ha' : ($user->default_land_area_unit === 'sq_m' ? 'm²' : 'ft²') }}
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                </button>
                @else
                <button onclick="openLandModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(20,61,35,0.04); border: 1px dashed rgba(20,61,35,0.25); border-radius: 8px; color: rgba(20,61,35,0.7); font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Set default land area
                </button>
                @endif
            </div>
        </div>
        <button class="btn-hero" onclick="openAddModal()">
            Add Crop Planting
        </button>
    </div>
</section>

<!-- ── Stats ── -->
<div class="stats-strip kpi-strip">
    <div class="stat-pill">
        <div class="stat-icon" style="background: #e6f0e7; color: #4A7A54;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Active Plantings</span>
            <span class="stat-value">{{ $stats['active_count'] }}</span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #e6f0e7; color: #4A7A54;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Your Farm Size</span>
            <span class="stat-value">{{ $stats['land_area'] > 0 ? number_format($stats['land_area'], 1) : '--' }} <span class="stat-unit">{{ $stats['land_area_unit'] === 'ha' ? 'ha' : ($stats['land_area_unit'] === 'sq_m' ? 'm²' : 'ft²') }}</span></span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #e6f0e7; color: #4A7A54;">
            <i data-lucide="group"></i>
        </div>
        <div class="stat-text">
            <span class="stat-label">All Plantings</span>
            <span class="stat-value">{{ $stats['total_count'] }}</span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #e6f0e7; color: #4A7A54;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/>
                <path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/>
                <path d="m2 16 6 6"/>
                <circle cx="17" cy="4" r="2"/>
                <path d="M15.6 2.8a2 2 0 1 0 2.8 2.8"/>
            </svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Estimated Cost</span>
            <span class="stat-value"><span style="font-weight: 500; margin-right: 6px;">₱</span>{{ number_format($stats['estimated_cost'], 0) }}</span>
        </div>
    </div>
</div>

{{-- ── Farm Plot Visualization ── --}}
@php
    $totalArea = (float) ($user->default_land_area ?? 0);
    $plotActive = $crops->filter(fn($c) => !in_array($c->growth_stage, ['harvested', 'failed']));
    $usedArea = (float) $plotActive->sum('land_area');
    $availableArea = max(0, $totalArea - $usedArea);
    $allocPct = $totalArea > 0 ? min(100, round(($usedArea / $totalArea) * 100)) : 0;
    $unitLabel = match($stats['land_area_unit'] ?? 'ha') { 'sq_m' => 'm²', 'sq_ft' => 'ft²', default => 'ha' };

    $cropColors = [
        // Grains
        'Rice' => 'rgb(245, 238, 142)', 'Corn' => '#FFE082', 'Wheat' => '#EFE0C8',
        'Mung Bean' => '#C8E6C9', 'Soybean' => '#DCE8B9', 'Peanut' => '#E4D5BB',
        'Cassava' => '#DCC9B8',
        // Fruits
        'Banana' => '#FFEE58', 'Mango' => '#FFCC80', 'Papaya' => '#FFB74D',
        'Pineapple' => '#FFD54F', 'Jackfruit' => '#E6EE9C', 'Watermelon' => '#FFCDD2',
        'Calamansi' => '#C5E1A5', 'Orange' => '#FFC107', 'Lemon' => '#FFF9C4',
        // Vegetables
        'Tomato' => '#FF8A80', 'Eggplant' => '#D1C4E9', 'Okra' => '#9CCC65',
        'String Bean' => '#A5D6A7', 'Squash' => '#FFB74D', 'Ampalaya' => '#A5D6A7',
        'Pepper' => '#FF7043', 'Cabbage' => '#C8E6C9', 'Sayote' => '#DCEDC8',
        'Luffa' => '#BCE08A', 'Cucumber' => '#81C784', 'Lettuce' => '#DCEDC8',
        'Malunggay' => '#A5D6A7', 'Kangkong' => '#C8E6C9', 'Carrot' => '#FFB74D',
        'Onion' => '#E8D0DC', 'Garlic' => '#F5F5F5',
        // Roots & Tubers
        'Sweet Potato' => '#FFE0B2', 'Taro' => '#E1BEE7', 'Potato' => '#F5E6CA',
        'Ginger' => '#F0E6C9', 'Turmeric' => '#FFD54F', 'Lemongrass' => '#DCEDC8',
        // Trees & Perennials
        'Coconut' => 'rgb(218, 190, 131)', 'Pili' => '#CDB79E', 'Cacao' => '#dda879',
        'Coffee' => '#A97C50', 'Rubber' => '#E3E7EA', 'Abaca' => '#E2D5BC',
        'Sugarcane' => '#DCEDC8', 'Tobacco' => '#C5A98E', 'Cotton' => '#FAFAFA',
        'default' => '#C8E6C9',
    ];

    $getCropColor = function ($name) use ($cropColors) {
        $name = trim((string) $name);
        if ($name === '') return '#C8E6C9';
        foreach ($cropColors as $key => $hex) {
            if (strcasecmp($key, $name) === 0) return $hex;
        }
        return '#C8E6C9';
    };

    $plotEntries = [];
    foreach ($plotActive as $batch) {
        $cropName = $batch->crop->name ?? 'Crop';
        $plotEntries[] = [
            'name' => $cropName,
            'area' => (float) $batch->land_area,
            'stage' => $batch->growth_stage,
            'batch' => $batch->batch_name ?? '',
            'color' => $getCropColor($cropName),
            'stroke' => 'rgba(0, 0, 0, 0.12)',
        ];
    }
    if ($availableArea > 0.01 && $totalArea > 0) {
        $plotEntries[] = [
            'name' => 'Unallocated', 'area' => $availableArea,
            'stage' => 'available', 'batch' => '',
            'color' => '#F1F5F9', 'stroke' => 'rgba(0, 0, 0, 0.12)',
        ];
    }

    $svgW = 800; $svgH = 260;
@endphp

@if($totalArea > 0)
<div class="land-plot-card">
    <div class="land-plot-head">
        <div>
            <h3 class="land-plot-title">Field Overview</h3>
            <p class="land-plot-sub">{{ number_format($usedArea, 1) }} {{ $unitLabel }} planted of {{ number_format($totalArea, 1) }} {{ $unitLabel }} total</p>
        </div>
        <div style="background: #F1F5F9; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #1E293B;">
            {{ $allocPct }}% Allocated
        </div>
    </div>

    <div class="land-plot-svg-wrap">
        <div class="plot-map-controls">
            <button class="plot-map-btn" title="Toggle Legend" onclick="togglePlotLegend()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </button>
        </div>

        <div class="plot-tooltip" id="plotTooltip"></div>

        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: auto; display: block;">
        @php $currX = 0; @endphp
        @foreach($plotEntries as $pe)
            @php
                $pct = $totalArea > 0 ? ($pe['area'] / $totalArea) : 0;
                $width = $pct * $svgW;
                $x1 = $currX;
                $x2 = $currX + $width;
                $currX += $width;

                $pad = 3;
                $px1 = $x1 + $pad;
                $px2 = $x2 - $pad;
                $py1 = $pad;
                $py2 = $svgH - $pad;
                $rectW = max(0, $px2 - $px1);

                $fontSizeName = $rectW > 100 ? 13 : ($rectW > 40 ? 10 : 8);
                $fontSizeArea = $rectW > 100 ? 10 : ($rectW > 40 ? 9 : 7);
            @endphp

            @if($rectW > 0)
            <g class="plot-polygon"
               data-name="{{ $pe['name'] }}"
               data-area="{{ $pe['area'] }}"
               data-stage="{{ $pe['stage'] }}"
               data-batch="{{ $pe['batch'] }}"
               data-unit="{{ $unitLabel }}">
                <rect x="{{ $px1 }}" y="{{ $py1 }}" width="{{ $rectW }}" height="{{ $py2 - $py1 }}"
                      rx="6" ry="6" fill="{{ $pe['color'] }}" stroke="{{ $pe['stroke'] }}" stroke-width="1"/>

                @if($rectW > 15)
                <text x="{{ $px1 + ($rectW / 2) }}" y="{{ $svgH / 2 }}"
                      text-anchor="middle" dominant-baseline="middle"
                      fill="#2C3E50" font-family="system-ui, -apple-system, sans-serif">
                    <tspan x="{{ $px1 + ($rectW / 2) }}" dy="-6" font-size="{{ $fontSizeName }}" font-weight="700">
                        {{ $rectW < 35 ? \Illuminate\Support\Str::limit($pe['name'], 3, '') : $pe['name'] }}
                    </tspan>
                    <tspan x="{{ $px1 + ($rectW / 2) }}" dy="16" font-size="{{ $fontSizeArea }}" font-weight="600">
                        {{ number_format($pe['area'], 1) }}{{ $unitLabel }}
                    </tspan>
                </text>
                @endif
            </g>
            @endif
        @endforeach
        </svg>
    </div>

    <div class="land-legend">
        @foreach($plotEntries as $pe)
        <div class="land-legend-pill">
            <span class="land-legend-dot" style="background: {{ $pe['color'] }}; border: 1px solid rgba(0,0,0,0.08);"></span>
            <span>{{ $pe['name'] }}{{ $pe['batch'] ? ' — ' . $pe['batch'] : '' }}</span>
            <span style="color: #94A3B8; font-weight: 400;">({{ number_format($pe['area'], 1) }} {{ $unitLabel }})</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- ── Filter Bar ── -->
@if($crops->count() > 0)
<div class="section-head">
    <h2 class="section-title">
        Crop Plantings
        <span class="batch-count" id="tbCount">{{ $crops->count() }}</span>
    </h2>
</div>

<x-table-toolbar
    uid="crops"
    work-table="cropsTable"
    count="tbCount"
    per-page="10"
    noun="crops"
    search-placeholder="Search crop, planting, category…"
    :filters="[
        ['key' => 'status', 'label' => 'All Statuses', 'options' => [['active', 'Active'], ['harvested', 'Harvested']]],
        ['key' => 'crop', 'label' => 'All Crops', 'options' => $crops->groupBy(fn($c) => $c->crop->name)->keys()->map(fn($n) => [strtolower($n), $n])->values()->all()],
        ['key' => 'stageIndex', 'label' => 'All Stages', 'options' => [
            ['0', 'Land Preparation'], ['1', 'Seeding / Sowing'], ['2', 'Vegetative Growth'],
            ['3', 'Flowering / Fruiting'], ['4', 'Ripening'], ['5', 'Harvested'], ['6', 'Failed'],
        ]],
        ['key' => 'season', 'label' => 'All Seasons', 'options' => [['wet', 'Wet'], ['dry', 'Dry']]],
    ]"
    :sorts="[
        ['key' => 'planted', 'dir' => -1, 'type' => 'date', 'label' => 'Newest planted'],
        ['key' => 'planted', 'dir' => 1, 'type' => 'date', 'label' => 'Oldest planted'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
        ['key' => 'crop', 'dir' => -1, 'type' => 'str', 'label' => 'Crop (Z–A)'],
        ['key' => 'area', 'dir' => -1, 'type' => 'num', 'label' => 'Land area (largest)'],
        ['key' => 'area', 'dir' => 1, 'type' => 'num', 'label' => 'Land area (smallest)'],
        ['key' => 'stageIndex', 'dir' => 1, 'type' => 'num', 'label' => 'Stage (early → harvested)'],
        ['key' => 'progress', 'dir' => -1, 'type' => 'num', 'label' => 'Progress (highest)'],
    ]"
/>
@endif

<!-- ── Crop Batches Table ── -->
@if($crops->count() > 0)
<div class="crop-table-card">
    <table class="crop-table data-table" id="cropsTable">
        <thead>
            <tr>
                <th>Crop Planting</th>
                <th>Stage</th>
                <th>Land Area</th>
                <th>Season</th>
                <th>Planted</th>
                <th>Target Harvest</th>
                <th>Progress</th>
                <th class="hide-sm">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($crops as $fc)
            @php
                $isFinished = in_array($fc->growth_stage, ['harvested', 'failed']);
                $stageIndexMap = ['land_prep' => 0, 'seeding' => 1, 'vegetative' => 2, 'reproductive' => 3, 'ripening' => 4, 'harvested' => 5, 'failed' => 6];
            @endphp
            <tr data-status="{{ $isFinished ? 'harvested' : 'active' }}"
                data-crop="{{ strtolower($fc->crop->name ?? '') }}"
                data-search="{{ strtolower(implode(' ', array_filter([$fc->crop->name ?? '', $fc->batch_name ?? '', $fc->crop->category ?? '', $fc->growth_stage_label, $fc->season ?? '', (string) ($fc->actual_yield ?? '')]))) }}"
                data-stage-index="{{ $stageIndexMap[$fc->growth_stage] ?? 0 }}"
                data-season="{{ $fc->season }}"
                data-area="{{ $fc->land_area }}"
                data-planted="{{ $fc->planting_date->format('Y-m-d') }}"
                data-harvest="{{ $fc->expected_harvest_date ? $fc->expected_harvest_date->format('Y-m-d') : '' }}"
                data-progress="{{ $isFinished ? ($fc->growth_stage === 'harvested' ? 100 : -1) : $fc->progress_percent }}"
                onclick="location.href='{{ route('farmer.crops.show', $fc) }}'">
                <td>
                    <div class="crop-name-cell">
                        <span class="crop-name-ic" style="background: {{ $fc->growth_stage_color }}14; color: #4A7A54;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                        </span>
                        <div>
                            <div class="crop-name">
                                {{ $fc->crop->name }}
                                @if($fc->batch_name)<span class="crop-batch-chip">{{ $fc->batch_name }}</span>@endif
                            </div>
                            <div class="crop-sub">{{ $fc->crop->category ?? 'Crop' }} &middot; {{ $fc->days_since_planting }}d since planting</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="stage-badge-mini" style="background: {{ $fc->growth_stage_color }}12; color: {{ $fc->growth_stage_color }};">
                        <span class="stage-dot-mini"></span>
                        {{ $fc->growth_stage_label }}
                    </span>
                    @unless($fc->isConfirmed())
                        <span class="stage-badge-mini confirm-badge {{ $fc->confirmation_status }}" style="background: {{ $fc->confirmation_status_color }}12; color: {{ $fc->confirmation_status_color }};">
                            {{ $fc->confirmation_status_label }}
                        </span>
                    @endunless
                </td>
                <td class="yield-cell">
                    <b>{{ rtrim(rtrim(number_format($fc->land_area, 2, '.', ''), '0'), '.') }}</b>
                    <span class="yield-tons">{{ $fc->land_area_unit === 'ha' ? 'ha' : $fc->land_area_unit }}</span>
                </td>
                <td>
                    <span class="season-badge-mini {{ $fc->season }}">{{ ucfirst($fc->season) }}</span>
                </td>
                <td>{{ $fc->planting_date->format('M d, Y') }}</td>
                <td>
                    @if($fc->expected_harvest_date)
                        <div>{{ $fc->expected_harvest_date->format('M d, Y') }}</div>
                        @if(!$isFinished)<div class="yield-tons">{{ $fc->days_until_harvest }}d left</div>@endif
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if(!$isFinished)
                        <div class="row-progress"><div style="width: {{ $fc->progress_percent }}%; background: linear-gradient(90deg, {{ $fc->growth_stage_color }}, {{ $fc->growth_stage_color }}aa);"></div></div>
                        <div class="row-progress-label">{{ $fc->progress_percent }}%</div>
                    @elseif($fc->actual_yield)
                        <span class="act-yield">{{ $fc->actual_yield }} {{ $fc->yield_unit ?? 'units' }}</span>
                    @else
                        <span class="yield-tons">—</span>
                    @endif
                </td>
                <td class="hide-sm">
                    <div class="row-actions">
                        @if(!$isFinished)
                            @if(!$fc->isConfirmed())
                                <button class="row-btn disabled" disabled title="Growth updates unlock once an admin confirms this planting.">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Pending
                                </button>
                            @else
                                <button class="row-btn plain" title="Update growth stage or record the harvest" onclick="event.stopPropagation(); openUpdateModal({{ $fc->id }}, '{{ $fc->growth_stage }}')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Update
                                </button>
                            @endif
                        @else
                            <button class="row-btn disabled" disabled title="Updates are not applicable to completed plantings.">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Update
                            </button>
                        @endif
                        <button class="row-btn red" title="Remove this crop planting" onclick="event.stopPropagation(); deleteCrop({{ $fc->id }})">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Remove
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<!-- ── Empty State ── -->
<div class="empty-wrap">
    <div class="empty-card">
        <div class="empty-icon-wrap">
            <i data-lucide="grid-2x2-x"></i>
        </div>
        <h3 class="empty-title">No Crop Plantings Yet</h3>
        <p class="empty-desc">Start by logging your first crop planting to track growth stages and plan harvests for the season.</p>
        <button class="btn-hero" onclick="openAddModal()" style="margin: 0 auto;">
            Add First Crop Planting
        </button>
        <div class="empty-tips">
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Add Crop Plantings</span>
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Track Growth Stages</span>
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Plan Harvests</span>        </div>
    </div>
</div>
@endif

<x-new-crop-batch-modal
    :all-crops="$allCrops"
    :crops="$crops"
    :stats="$stats"
    :user="$user"
    :alloc-pct="$allocPct"
    :unit-label="$unitLabel"
    :crop-defaults="$cropDefaults"
    :seed-products="$seedProducts"
    :product-yields="$productYields"
    :product-costs="$productCosts"
    :crop-product-slugs="$cropProductSlugs"
    :crop-product-keywords="$cropProductKeywords"
    :crop-varieties="$cropVarieties"
    :crop-info="$cropInfo"
/>

</div>
</div>

{{-- MODALS AND OVERLAYS PLACED OUTSIDE .crops-page TO AVOID CSS STACKING/TRANSFORM CONFLICTS --}}

<!-- ── Farm Plot Fullscreen Overlay ── -->
<div class="farm-fullscreen-overlay" id="farmFullscreen">
    <div class="farm-fullscreen-box">
        <div class="farm-fs-header">
            <h3>Farm Plot — Full View</h3>
            <div class="farm-fs-header-actions">
                <button class="farm-fs-close" onclick="farmFsZoom(0.1)" title="Zoom In">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <button class="farm-fs-close" onclick="farmFsZoom(-0.1)" title="Zoom Out">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <button class="farm-fs-close" onclick="farmFsReset()" title="Reset View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
                <button class="farm-fs-close" onclick="closeFarmFs()" title="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <div class="farm-fs-body" id="farmFsBody">
            <div class="farm-fs-zoom-controls">
                <button class="farm-fs-zoom-btn" onclick="farmFsZoom(0.15)">+</button>
                <div class="farm-fs-zoom-label" id="farmFsZoomLabel">100%</div>
                <button class="farm-fs-zoom-btn" onclick="farmFsZoom(-0.15)">−</button>
                <button class="farm-fs-zoom-btn" onclick="farmFsReset()" style="margin-top:4px; font-size:13px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                </button>
            </div>
            <div id="farmFsSvgWrap"></div>
        </div>
    </div>
</div>

<!-- ── UPDATE MODAL ── -->
<div class="modal-overlay" id="updateModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Growth Stage</h3>
            <button class="modal-close" onclick="closeModal('updateModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="updateForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>Growth Stage *</label>
                    <select name="growth_stage" id="updateStage" required>
                        <option value="land_prep">Land Preparation</option>
                        <option value="seeding">Seeding / Sowing</option>
                        <option value="vegetative">Vegetative Growth</option>
                        <option value="reproductive">Flowering / Fruiting</option>
                        <option value="ripening">Ripening</option>
                        <option value="harvested">Harvested</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div id="harvestFields" style="display:none;">
                    <div class="form-section-label">Harvest Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Actual Harvest Date</label>
                            <input type="date" name="actual_harvest_date" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label>Actual Yield</label>
                            <input type="number" name="actual_yield" step="0.01" min="0" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Update notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('updateModal')">Cancel</button>
                <button type="submit" class="btn-submit">Save Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ── DEFAULT LAND MODAL ── -->
<div class="modal-overlay" id="landModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Default Land Area</h3>
            <button class="modal-close" onclick="closeModal('landModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('farmer.crops.default-land') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <p style="font-size: 13px; color: #64748b; margin: 0 0 16px; line-height: 1.5;">This is used to pre-fill the land area when adding new crop plantings. You can still change it per planting.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Land Area *</label>
                        <input type="number" name="default_land_area" step="0.01" min="0.01" value="{{ $user->default_land_area ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="default_land_area_unit" required>
                            <option value="ha" {{ ($user->default_land_area_unit ?? 'ha') === 'ha' ? 'selected' : '' }}>Hectares (ha)</option>
                            <option value="sq_m" {{ ($user->default_land_area_unit ?? '') === 'sq_m' ? 'selected' : '' }}>Square Meters (m²)</option>
                            <option value="sq_ft" {{ ($user->default_land_area_unit ?? '') === 'sq_ft' ? 'selected' : '' }}>Square Feet (ft²)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('landModal')">Cancel</button>
                <button type="submit" class="btn-submit">Save Default</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>

<script>
    /* ── Modals Handler ── */
    var _overlayOpenedAt = {};
    function _lockPageScroll() {
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
    }
    function _releasePageScroll() {
        const anyActive = document.querySelector('.modal-overlay.active, .ncb-overlay.active');
        if (!anyActive) {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
    }
    window._openOverlay = function(el) {
        if (!el) return;
        _overlayOpenedAt[el.id] = Date.now();
        el.classList.add('active');
        _lockPageScroll();
    };
    window.openAddModal = function() {
        const modal = document.getElementById('addModal');
        if (!modal) return;
        window._openOverlay(modal);
        setTimeout(function() {
            const input = document.getElementById('addLandArea');
            if (input && typeof checkLandCapacity === 'function') {
                checkLandCapacity(input.value);
            }
        }, 50);
    };

    window.openLandModal = function() { const m = document.getElementById('landModal'); if (m) window._openOverlay(m); };
    window.closeModal = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('active');
            _releasePageScroll();
        }
    };
    window.openUpdateModal = function(id, stage) {
        const form = document.getElementById('updateForm');
        if (form) form.action = '{{ url("/farmer/crops") }}/' + id;
        const updateStageEl = document.getElementById('updateStage');
        if (updateStageEl) updateStageEl.value = stage;
        try { toggleHarvest(stage); } catch(e) {}
        const um = document.getElementById('updateModal'); if (um) window._openOverlay(um);
    };
    window.deleteCrop = function(id) {
        if (!window.AquiNotice) { return; }
        AquiNotice.confirm({
            message: 'Remove this crop planting? This cannot be undone.',
            danger: true,
            confirmLabel: 'Remove'
        }).then(function (ok) {
            if (!ok) return;
            const f = document.getElementById('deleteForm');
            if (f) { f.action = '{{ url("/farmer/crops") }}/' + id; f.submit(); }
        });
    };

    document.querySelectorAll('.modal-overlay, .ncb-overlay').forEach(o => {
        o.addEventListener('click', e => {
            if (e.target === o && Date.now() - (_overlayOpenedAt[o.id] || 0) > 350) {
                window.closeModal(o.id);
            }
        });
    });

    /* ── Farm Plot Tooltips ── */
    const unitLabels = { ha: 'ha', sq_m: 'm\u00B2', sq_ft: 'ft\u00B2' };
    const stageLabels = {
        land_prep: 'Land Preparation', seeding: 'Seeding', vegetative: 'Vegetative',
        reproductive: 'Reproductive', ripening: 'Ripening', harvested: 'Harvested', failed: 'Failed',
        available: 'Unallocated'
    };
    function showPlotTip(el) {
        const tip = document.getElementById('plotTooltip');
        if (!tip) return;
        const name = el.dataset.name;
        const area = parseFloat(el.dataset.area);
        const stage = el.dataset.stage;
        const batch = el.dataset.batch;
        const unit = unitLabels[el.dataset.unit] || 'ha';
        tip.innerHTML = '<strong>' + name + '</strong>' + (batch ? ' — ' + batch : '')
            + ' &middot; ' + area + ' ' + unit
            + ' &middot; ' + stageLabels[stage];
        tip.style.opacity = '1';
    }
    function hidePlotTip() { var t = document.getElementById('plotTooltip'); if (t) t.style.opacity = '0'; }

    function attachPlotHandlers(){
        document.querySelectorAll('.plot-polygon').forEach(function(el){
            el.addEventListener('mouseenter', function(){ showPlotTip(el); });
            el.addEventListener('mouseleave', function(){ hidePlotTip(); });
            el.addEventListener('click', function(){ openFarmFs(); });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attachPlotHandlers);
    else attachPlotHandlers();

    /* ── Plot Legend Toggle ── */
    function togglePlotLegend() {
        var legend = document.querySelector('.land-legend');
        if (!legend) return;
        legend.style.display = legend.style.display === 'none' ? 'flex' : 'none';
    }

    /* ── Farm Plot Fullscreen ── */
    var _fsScale = 1;
    function openFarmFs() {
        var overlay = document.getElementById('farmFullscreen');
        var wrap = document.getElementById('farmFsSvgWrap');
        var svgSrc = document.querySelector('.land-plot-svg-wrap svg');
        if (!overlay || !wrap || !svgSrc) return;
        
        wrap.replaceChildren(); // Fully clears prior elements before appending
        var clone = svgSrc.cloneNode(true);
        clone.style.width = '100%';
        clone.style.height = 'auto';
        clone.style.minHeight = '400px';
        clone.style.display = 'block';
        wrap.appendChild(clone);
        
        _fsScale = 1;
        _applyFsTransform();
        document.getElementById('farmFsZoomLabel').textContent = '100%';
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeFarmFs() {
        var overlay = document.getElementById('farmFullscreen');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    function farmFsZoom(delta) {
        _fsScale = Math.max(0.3, Math.min(4, _fsScale + delta));
        _applyFsTransform();
        document.getElementById('farmFsZoomLabel').textContent = Math.round(_fsScale * 100) + '%';
    }
    function farmFsReset() {
        _fsScale = 1;
        _applyFsTransform();
        document.getElementById('farmFsZoomLabel').textContent = '100%';
        var body = document.getElementById('farmFsBody');
        if (body) { body.scrollLeft = 0; body.scrollTop = 0; }
    }
    function _applyFsTransform() {
        var wrap = document.getElementById('farmFsSvgWrap');
        if (wrap) wrap.style.transform = 'scale(' + _fsScale + ')';
    }

    /* ── Update Stage Modal ── */
    const _updateStageEl = document.getElementById('updateStage');
    if (_updateStageEl) _updateStageEl.addEventListener('change', function() { toggleHarvest(this.value); });
    function toggleHarvest(s) {
        const hf = document.getElementById('harvestFields');
        if (!hf) return;
        hf.style.display = s === 'harvested' ? 'block' : 'none';
    }
</script>
@endsection

