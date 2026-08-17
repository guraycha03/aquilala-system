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
    .crops-page::before { width: 340px; height: 340px; background: rgba(22,163,74,0.10); top: -80px; right: -60px; }
    .crops-page::after { width: 280px; height: 280px; background: rgba(132,204,22,0.08); bottom: -60px; left: -40px; }
    .crops-content { position: relative; z-index: 1; }

    /* ── Hero Banner ── */
    .hero-band {
        background: linear-gradient(135deg, #0f2918 0%, #14532d 50%, #166534 100%);
        border-radius: 20px; padding: 28px 32px; color: #fff; position: relative;
        overflow: hidden; margin-bottom: 24px;
        box-shadow: 0 12px 40px rgba(15,41,24,0.22);
    }
    .hero-band::before {
        content: ""; position: absolute; top: -80px; right: -40px; width: 220px; height: 220px;
        border-radius: 50%; background: rgba(255,255,255,0.05);
    }
    .hero-band::after {
        content: ""; position: absolute; bottom: -70px; left: 20%; width: 180px; height: 180px;
        border-radius: 50%; background: rgba(132,204,22,0.06);
    }
    .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
    .hero-title { font-size: 24px; font-weight: 800; margin: 0; letter-spacing: -0.01em; }
    .hero-sub { font-size: 13px; color: rgba(255,255,255,0.72); margin: 5px 0 0 0; }
    .hero-sub strong { color: #fff; }
    .btn-hero {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.20);
        color: #fff; padding: 10px 20px; border-radius: 11px; font-size: 13px; font-weight: 700;
        text-decoration: none; transition: all 0.2s ease; backdrop-filter: blur(6px);
        cursor: pointer;
    }
    .btn-hero:hover { background: rgba(255,255,255,0.24); transform: translateY(-1px); }
    .btn-hero svg { width: 15px; height: 15px; }

    /* ── Stats Strip ── */
    .stats-strip {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px;
    }
    .stat-pill {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: all 0.2s ease;
    }
    .stat-pill:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
    .stat-icon {
        width: 42px; height: 42px; border-radius: 12px; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0;
    }
    .stat-icon svg { width: 20px; height: 20px; }
    .stat-text { display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .stat-value { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2; margin-top: 2px; }
    .stat-unit { font-size: 12px; font-weight: 500; color: #94a3b8; }

    /* ── Section Header ── */
    .section-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 16px;
    }
    .section-title { font-size: 16px; font-weight: 700; color: #1e293b; }
    .filter-group { display: flex; gap: 6px; }
    .filter-btn {
        padding: 6px 14px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: #fff; color: #64748b; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.15s ease;
    }
    .filter-btn:hover { border-color: #cbd5e1; color: #334155; }
    .filter-btn.active { background: #14532d; color: #fff; border-color: #14532d; }

    /* ── Crop Cards ── */
    .crops-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 16px; margin-bottom: 24px;
    }
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
    .card-category {
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
        color: #64748b;
    }
    .card-batch {
        font-size: 11px; color: #94a3b8; padding: 2px 8px; background: #f1f5f9;
        border-radius: 6px; font-weight: 500;
    }

    .stage-badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px;
        border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;
        flex-shrink: 0;
    }
    .stage-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .card-body { padding: 14px 20px; }

    .card-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
    .detail-item { display: flex; flex-direction: column; }
    .detail-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .detail-value { font-size: 13px; font-weight: 600; color: #334151; margin-top: 2px; }

    /* ── Growth Stage Stepper ── */
    .stage-stepper {
        display: flex; align-items: center; gap: 0; margin-top: 14px;
        padding: 10px 14px; background: #f8fafc; border-radius: 10px;
    }
    .stepper-step {
        flex: 1; display: flex; flex-direction: column; align-items: center;
        position: relative; text-align: center;
    }
    .stepper-step::after {
        content: ""; position: absolute; top: 8px; left: 50%; width: 100%; height: 2px;
        background: #e2e8f0; z-index: 0;
    }
    .stepper-step:last-child::after { display: none; }
    .stepper-dot {
        width: 16px; height: 16px; border-radius: 50%; border: 2px solid #d1d5db;
        background: #fff; z-index: 1; position: relative; transition: all 0.3s ease;
    }
    .stepper-dot.done { background: #16a34a; border-color: #16a34a; }
    .stepper-dot.current {
        background: #fff; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.2);
    }
    .stepper-label {
        font-size: 9px; font-weight: 600; color: #94a3b8; margin-top: 6px;
        text-transform: uppercase; letter-spacing: 0.03em;
    }
    .stepper-label.active-label { color: #16a34a; font-weight: 700; }

    /* ── Progress ── */
    .progress-section { margin-top: 12px; }
    .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .progress-title { font-size: 11px; font-weight: 600; color: #64748b; }
    .progress-pct { font-size: 12px; font-weight: 800; }
    .progress-track { width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }
    .progress-footer { display: flex; justify-content: space-between; margin-top: 5px; }
    .progress-footer span { font-size: 10px; color: #94a3b8; font-weight: 500; }

    .card-footer {
        padding: 10px 20px; background: #fafbfc; border-top: 1px solid #f1f5f9;
        display: flex; gap: 8px; align-items: center;
    }

    /* ── Buttons ── */
    .btn-sm {
        padding: 6px 13px; font-size: 12px; border-radius: 8px; cursor: pointer;
        font-weight: 600; border: none; display: inline-flex; align-items: center; gap: 5px;
        transition: all 0.15s ease; text-decoration: none;
    }
    .btn-sm-green { background: #dcfce7; color: #166534; }
    .btn-sm-green:hover { background: #bbf7d0; }
    .btn-sm-red { background: #fee2e2; color: #991b1b; }
    .btn-sm-red:hover { background: #fecaca; }

    /* ── Empty State ── */
    .empty-wrap { padding: 32px 0; text-align: center; }
    .empty-card {
        background: #fff; border: 1px dashed #cbd5e1; border-radius: 20px;
        padding: 60px 32px; max-width: 520px; margin: 0 auto;
        box-shadow: 0 8px 32px -8px rgba(0,0,0,0.04);
    }
    .empty-icon-wrap {
        width: 88px; height: 88px; background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px; color: #16a34a;
        box-shadow: 0 8px 24px -4px rgba(22,163,74,0.15);
    }
    .empty-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
    .empty-desc { color: #64748b; font-size: 13px; line-height: 1.6; max-width: 360px; margin: 0 auto 24px; }
    .empty-tips { display: flex; justify-content: center; gap: 24px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    .empty-tip { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 500; }
    .empty-tip svg { color: #16a34a; flex-shrink: 0; }

    /* ── Modal ── */
    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(15,41,24,0.55);
        z-index: 1000; align-items: center; justify-content: center;
        backdrop-filter: blur(6px);
    }
    .modal-overlay.active { display: flex; }
    .modal {
        background: #fff; border-radius: 18px; width: 95%; max-width: 540px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 24px 80px rgba(0,0,0,0.25);
        animation: modalIn 0.25s cubic-bezier(0.4,0,0.2,1);
    }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.96) translateY(12px); } }
    .modal-header {
        padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h3 { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .modal-close {
        width: 34px; height: 34px; border-radius: 10px; border: none; background: #f1f5f9;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: #64748b; transition: all 0.15s ease;
    }
    .modal-close:hover { background: #e2e8f0; color: #1e293b; }
    .modal-body { padding: 20px 24px; }
    .modal-footer {
        padding: 16px 24px; border-top: 1px solid #f1f5f9;
        display: flex; justify-content: flex-end; gap: 10px;
    }

    .form-section-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
        color: #16a34a; margin: 16px 0 10px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;
    }
    .form-section-label:first-child { margin-top: 0; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08);
    }
    .form-group textarea { resize: vertical; min-height: 64px; }

    .btn-cancel {
        padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px;
        border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-submit {
        padding: 10px 22px; font-size: 13px; font-weight: 700; border-radius: 10px;
        border: none; background: #16a34a; color: #fff; cursor: pointer;
        box-shadow: 0 4px 12px rgba(22,163,74,0.25); transition: all 0.2s ease;
    }
    .btn-submit:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22,163,74,0.3); }

    /* ── Toast ── */
    .toast {
        position: fixed; top: 24px; right: 24px; z-index: 2000;
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
                <span style="font-size: 12px; color: rgba(255,255,255,0.6);">Your land:</span>
                @if($user->default_land_area)
                <button onclick="openLandModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18); border-radius: 8px; color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s ease; backdrop-filter: blur(4px);">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    {{ $user->default_land_area }} {{ $user->default_land_area_unit === 'ha' ? 'ha' : ($user->default_land_area_unit === 'sq_m' ? 'm²' : 'ft²') }}
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                </button>
                @else
                <button onclick="openLandModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(255,255,255,0.08); border: 1px dashed rgba(255,255,255,0.25); border-radius: 8px; color: rgba(255,255,255,0.7); font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Set default land area
                </button>
                @endif
            </div>
        </div>
        <button class="btn-hero" onclick="openAddModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Crop Batch
        </button>
    </div>
</section>

<!-- ── Stats ── -->
<div class="stats-strip">
    <div class="stat-pill">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Active Batches</span>
            <span class="stat-value">{{ $stats['active_count'] }}</span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #dbeafe; color: #2563eb;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Your Farm Size</span>
            <span class="stat-value">{{ $stats['land_area'] > 0 ? number_format($stats['land_area'], 1) : '--' }} <span class="stat-unit">{{ $stats['land_area_unit'] === 'ha' ? 'ha' : ($stats['land_area_unit'] === 'sq_m' ? 'm²' : 'ft²') }}</span></span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8l4.553-2.276A1 1 0 0 1 22 6.618v7.764a1 1 0 0 1-1.447.894L16 16"/><path d="M1 14v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">All Batches</span>
            <span class="stat-value">{{ $stats['total_count'] }}</span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #fce7f3; color: #db2777;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Estimated Cost</span>
            <span class="stat-value">₱{{ number_format($stats['estimated_cost'], 0) }}</span>
        </div>
    </div>
</div>

<!-- ── Filter Bar ── -->
@if($crops->count() > 0)
<div class="section-head">
    <h2 class="section-title">Crop Batches</h2>
    <div class="filter-group">
        <button class="filter-btn active" onclick="filterCrops('all', this)">All</button>
        <button class="filter-btn" onclick="filterCrops('active', this)">Active</button>
        <button class="filter-btn" onclick="filterCrops('harvested', this)">Harvested</button>
    </div>
</div>
@endif

<!-- ── Crop Cards ── -->
@if($crops->count() > 0)
@php
    $stageOrder = ['land_prep','seeding','vegetative','reproductive','ripening','harvested'];
@endphp
<div class="crops-grid">
    @foreach($crops as $fc)
    @php
        $isFinished = in_array($fc->growth_stage, ['harvested', 'failed']);
        $stageIdx = array_search($fc->growth_stage, $stageOrder) ?? 0;
    @endphp
    <div class="crop-card" data-status="{{ $isFinished ? 'harvested' : 'active' }}">
        <div class="card-top-accent" style="background: linear-gradient(90deg, {{ $fc->growth_stage_color }}, {{ $fc->growth_stage_color }}88);"></div>

        <div class="card-header">
            <div>
                <h3 class="card-crop-name">{{ $fc->crop->name }}</h3>
                <div class="card-meta">
                    <span class="card-category">{{ $fc->crop->category ?? 'Crop' }}</span>
                    @if($fc->batch_name)
                        <span class="card-batch">{{ $fc->batch_name }}</span>
                    @endif
                </div>
            </div>
            <span class="stage-badge" style="background: {{ $fc->growth_stage_color }}12; color: {{ $fc->growth_stage_color }};">
                <span class="stage-dot"></span>
                {{ $fc->growth_stage_label }}
            </span>
        </div>

        <div class="card-body">
            <div class="card-details">
                <div class="detail-item">
                    <span class="detail-label">Land Area</span>
                    <span class="detail-value">{{ $fc->land_area }} {{ $fc->land_area_unit === 'ha' ? 'ha' : $fc->land_area_unit }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Season</span>
                    <span class="detail-value">{{ $fc->season === 'wet' ? '🌧 Wet' : '☀ Dry' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Planted</span>
                    <span class="detail-value">{{ $fc->planting_date->format('M d, Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Days Active</span>
                    <span class="detail-value">{{ $fc->days_since_planting }}d</span>
                </div>
                @if($fc->expected_harvest_date)
                <div class="detail-item">
                    <span class="detail-label">Target Harvest</span>
                    <span class="detail-value">{{ $fc->expected_harvest_date->format('M d, Y') }}</span>
                </div>
                @endif
                @if($fc->estimated_cost)
                <div class="detail-item">
                    <span class="detail-label">Est. Cost</span>
                    <span class="detail-value">₱{{ number_format($fc->estimated_cost, 0) }}</span>
                </div>
                @endif
            </div>

            {{-- Growth Stage Stepper --}}
            @if(!$isFinished)
            <div class="stage-stepper">
                @foreach($stageOrder as $si => $sKey)
                @if($sKey !== 'harvested')
                <div class="stepper-step">
                    <div class="stepper-dot {{ $si < $stageIdx ? 'done' : ($si === $stageIdx ? 'current' : '') }}"></div>
                    <span class="stepper-label {{ $si <= $stageIdx ? 'active-label' : '' }}">
                        {{ match($sKey) { 'land_prep' => 'Prep', 'seeding' => 'Seed', 'vegetative' => 'Veg', 'reproductive' => 'Flwr', 'ripening' => 'Ripe', default => '' } }}
                    </span>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            {{-- Progress Bar --}}
            @if($fc->expected_harvest_date && !$isFinished)
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-title">Growth Progress</span>
                    <span class="progress-pct" style="color: {{ $fc->growth_stage_color }};">{{ $fc->progress_percent }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ $fc->progress_percent }}%; background: linear-gradient(90deg, {{ $fc->growth_stage_color }}, {{ $fc->growth_stage_color }}aa);"></div>
                </div>
                <div class="progress-footer">
                    <span>Day {{ $fc->days_since_planting }} of {{ $fc->days_since_planting + $fc->days_until_harvest }}</span>
                    <span>{{ $fc->days_until_harvest }}d remaining</span>
                </div>
            </div>
            @endif

            @if($isFinished && $fc->actual_yield)
            <div style="margin-top: 12px; padding: 10px 14px; background: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0;">
                <div style="font-size: 11px; font-weight: 600; color: #16a34a; text-transform: uppercase; letter-spacing: 0.05em;">Actual Harvest</div>
                <div style="font-size: 15px; font-weight: 800; color: #166534; margin-top: 2px;">{{ $fc->actual_yield }} {{ $fc->yield_unit ?? 'units' }}</div>
            </div>
            @endif
        </div>

        <div class="card-footer">
            @if(!$isFinished)
                <button class="btn-sm btn-sm-green" onclick="openUpdateModal({{ $fc->id }}, '{{ $fc->growth_stage }}')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    Update Stage
                </button>
            @endif
            <button class="btn-sm btn-sm-red" onclick="deleteCrop({{ $fc->id }})" style="margin-left: auto;">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Remove
            </button>
        </div>
    </div>
    @endforeach
</div>
@else
<!-- ── Empty State ── -->
<div class="empty-wrap">
    <div class="empty-card">
        <div class="empty-icon-wrap">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/>
            </svg>
        </div>
        <h3 class="empty-title">No Crop Batches Yet</h3>
        <p class="empty-desc">Start by logging your first crop batch to track planting, monitor growth stages, and plan harvests for the season.</p>
        <button class="btn-hero" onclick="openAddModal()" style="margin: 0 auto; background: #16a34a; border-color: #16a34a; color: #fff;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add First Crop Batch
        </button>
        <div class="empty-tips">
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Track Growth Stages</span>
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Estimate Costs</span>
            <span class="empty-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Plan Harvests</span>
        </div>
    </div>
</div>
@endif

</div>
</div>

<!-- ── ADD MODAL ── -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>New Crop Batch</h3>
            <button class="modal-close" onclick="closeModal('addModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('farmer.crops.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-section-label">Crop Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Crop Type *</label>
                        <select name="crop_id" required>
                            <option value="">Select crop...</option>
                            @foreach($allCrops as $crop)
                                <option value="{{ $crop->id }}">{{ $crop->name }} — {{ $crop->category ?? 'General' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Batch Name</label>
                        <input type="text" name="batch_name" placeholder="e.g. North Field A">
                    </div>
                </div>

                <div class="form-section-label">Land & Season</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Land Area *</label>
                        <input type="number" name="land_area" step="0.01" min="0.01" value="{{ $user->default_land_area ?? '1' }}" required>
                    </div>
                    <div class="form-group">
                        <label>Area Unit *</label>
                        <select name="land_area_unit" id="landAreaUnit" required>
                            <option value="ha" {{ ($user->default_land_area_unit ?? 'ha') === 'ha' ? 'selected' : '' }}>Hectares (ha)</option>
                            <option value="sq_m" {{ ($user->default_land_area_unit ?? '') === 'sq_m' ? 'selected' : '' }}>Square Meters (m²)</option>
                            <option value="sq_ft" {{ ($user->default_land_area_unit ?? '') === 'sq_ft' ? 'selected' : '' }}>Square Feet (ft²)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Season *</label>
                        <select name="season" required>
                            <option value="wet">🌧 Wet Season (Jun–Oct)</option>
                            <option value="dry">☀ Dry Season (Nov–May)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Planting Date *</label>
                        <input type="date" name="planting_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="form-section-label">Planning (Optional)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expected Harvest Date</label>
                        <input type="date" name="expected_harvest_date">
                    </div>
                    <div class="form-group">
                        <label>Estimated Cost (₱)</label>
                        <input type="number" name="estimated_cost" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Estimated Yield</label>
                        <input type="number" name="estimated_yield" step="0.01" min="0" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Yield Unit</label>
                        <input type="text" name="yield_unit" placeholder="e.g. sacks, kg, tons">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any additional notes about this crop batch..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-submit">Add Crop Batch</button>
            </div>
        </form>
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
                <p style="font-size: 13px; color: #64748b; margin: 0 0 16px; line-height: 1.5;">This is used to pre-fill the land area when adding new crop batches. You can still change it per batch.</p>
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
    function openAddModal() { document.getElementById('addModal').classList.add('active'); }
    function openLandModal() { document.getElementById('landModal').classList.add('active'); }

    function openUpdateModal(id, stage) {
        const form = document.getElementById('updateForm');
        form.action = '{{ url("/farmer/crops") }}/' + id;
        document.getElementById('updateStage').value = stage;
        toggleHarvest(stage);
        document.getElementById('updateModal').classList.add('active');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function deleteCrop(id) {
        if (confirm('Remove this crop batch? This cannot be undone.')) {
            const f = document.getElementById('deleteForm');
            f.action = '{{ url("/farmer/crops") }}/' + id;
            f.submit();
        }
    }

    function filterCrops(type, btn) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.crop-card').forEach(card => {
            card.style.display = (type === 'all' || card.dataset.status === type) ? '' : 'none';
        });
    }

    document.getElementById('updateStage').addEventListener('change', function() {
        toggleHarvest(this.value);
    });

    function toggleHarvest(s) {
        document.getElementById('harvestFields').style.display = s === 'harvested' ? 'block' : 'none';
    }

    document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
    });
</script>
@endsection
