@extends('layouts.sidebar')

@section('title', $farmerCrop->crop->name . ' — Crop Batch')

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

    /* ── Success Alert ── */
    .sup-alert {
        display: flex; align-items: center; gap: 10px; background: #f0fdf4;
        border: 1px solid #bbf7d0; color: #166534; border-radius: 12px;
        padding: 12px 16px; font-size: 13.5px; font-weight: 600; margin-bottom: 18px;
    }

    /* ── Hero Band ── */
    .hero-band {
        background: rgb(230, 245, 195);
        border-radius: 20px; padding: 26px 30px; color: #143d23; position: relative; overflow: hidden; margin-bottom: 22px;
        box-shadow: 0 4px 16px rgba(20,61,35,0.10);
    }
    .hero-band::before {
        content: ""; position: absolute; top: -80px; right: -40px; width: 220px; height: 220px;
        border-radius: 50%; background: rgba(16,185,129,0.15);
    }
    .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
    .hero-title { font-size: 23px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .hero-sub { font-size: 13px; color: #40632d; margin: 6px 0 0 0; display: flex !important; align-items: center; gap: 8px; flex-wrap: wrap; }
    .hero-sub strong { color: #14532d; }
    .btn-hero {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        background: #ffffff; color: #14532d; padding: 10px 18px; border-radius: 10px;
        font-size: 13px; font-weight: 700; letter-spacing: 0.02em; text-decoration: none;
        border: 1px solid rgba(20,61,35,0.10); box-shadow: 0 2px 8px rgba(20,61,35,0.08);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; font-family: inherit;
    }
    .btn-hero:hover { background: #f0fdf4; color: #14532d; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(20,61,35,0.12); }
    .btn-hero:active { transform: translateY(0); }
    .btn-hero.danger { color: #b0403a; border-color: #e4c6c3; background: #fff; }
    .btn-hero.danger:hover { background: #faf2f1; color: #b0403a; }

    /* ── Stage Badge ── */
    .stage-badge {
        display: inline-flex; align-items: center; gap: 7px; padding: 5px 13px; border-radius: 20px;
        font-size: 12px; font-weight: 700; letter-spacing: 0.01em; white-space: nowrap;
    }
    .stage-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

    /* ── Stats Strip ── */
    .stats-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
    @media (max-width: 820px) { .stats-strip { grid-template-columns: repeat(2, 1fr); } }
    .stat-pill {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: all 0.2s ease;
    }
    .stat-pill:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
    .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 20px; height: 20px; }
    .stat-text { display: flex; flex-direction: column; min-width: 0; }
    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .stat-value { font-size: 20px; font-weight: 800; color: #1e293b; line-height: 1.2; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .stat-unit { font-size: 12px; font-weight: 500; color: #94a3b8; }

    /* ── Cards ── */
    .box-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); margin-bottom: 22px; overflow: hidden;
    }
    .box-head {
        padding: 16px 22px; border-bottom: 1px solid #f0f2f1;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .box-head h2 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px; }
    .box-head p { font-size: 12.5px; color: #94a3b8; margin: 3px 0 0 0; }
    .box-body { padding: 22px; }

    /* ── Growth Stepper ── */
    .stepper-wrap { display: flex; align-items: flex-start; justify-content: space-between; gap: 6px; padding: 4px 0 8px; }
    .stepper-step { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; position: relative; min-width: 0; }
    .stepper-step:not(:last-child)::after {
        content: ""; position: absolute; top: 14px; left: calc(50% + 14px); width: calc(100% - 28px);
        height: 3px; background: #eef2f1; border-radius: 99px;
    }
    .stepper-step.done:not(:last-child)::after { background: #16a34a; }
    .stepper-dot {
        width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: #fff; border: 2.5px solid #d7dedb; color: transparent; z-index: 1; font-size: 13px; font-weight: 800; transition: all 0.2s ease;
    }
    .stepper-step.done .stepper-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .stepper-step.current .stepper-dot { color: #fff; box-shadow: 0 0 0 4px rgba(22,163,74,0.12); }
    .stepper-label { font-size: 11px; font-weight: 600; color: #b0b8b3; text-align: center; text-transform: capitalize; }
    .stepper-step.done .stepper-label, .stepper-step.current .stepper-label { color: #374138; }
    .stepper-step.current .stepper-label { font-weight: 800; }

    /* ── Progress Bar ── */
    .progress-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .progress-title { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
    .progress-pct { font-size: 20px; font-weight: 800; }
    .progress-track { height: 10px; background: #eef2f1; border-radius: 99px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
    .progress-foot { display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #7d8d82; font-weight: 600; }

    /* ── Detail Grid ── */
    .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @media (max-width: 820px) { .detail-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 520px) { .detail-grid { grid-template-columns: 1fr; } }
    .detail-item {}
    .detail-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
    .detail-value { font-size: 14px; font-weight: 700; color: #1e293b; margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

    /* ── Harvest Summary ── */
    .harvest-summary { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    .hs-tile {
        flex: 1; min-width: 160px; border-radius: 12px; padding: 16px 18px;
        border: 1px solid #bbf7d0; background: #f0fdf4;
    }
    .hs-tile .hs-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a; }
    .hs-tile .hs-value { font-size: 19px; font-weight: 800; color: #166534; margin-top: 3px; }

    /* ── Notes ── */
    .notes-box { background: #fafdfb; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px 18px; font-size: 13.5px; color: #475569; line-height: 1.6; }

    /* ── Form ── */
    .form-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    @media (max-width: 560px) { .form-grid2 { grid-template-columns: 1fr; } }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #4b5c50; margin-bottom: 6px; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background: #fff; font-family: inherit; outline: none; transition: all 0.2s ease;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08); }
    .btn-submit {
        display: inline-flex; align-items: center; gap: 7px; background: #14532d; color: #fff;
        padding: 10px 22px; border-radius: 10px; font-size: 13px; font-weight: 700; border: 0;
        cursor: pointer; transition: all 0.15s; font-family: inherit;
    }
    .btn-submit:hover { background: #166534; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #64748b;
        padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
        border: 1px solid #e5e7eb; cursor: pointer; transition: all 0.15s; text-decoration: none; font-family: inherit;
    }
    .btn-cancel:hover { background: #f8faf9; color: #334155; }
</style>

<div class="crops-page">
<div class="crops-content">

@if(session('success'))
<div class="sup-alert">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- ── Breadcrumb ── --}}
<x-breadcrumb :items="[
    ['label' => 'My Crops', 'url' => route('farmer.crops.index')],
    ['label' => $farmerCrop->batch_name ?: $farmerCrop->crop->name],
]"/>

{{-- ── Hero Band ── --}}
<div class="hero-band">
    <div class="hero-inner">
        <div>
            <h1 class="hero-title">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                {{ $farmerCrop->crop->name }}
                @if($farmerCrop->batch_name)
                    <span style="font-size: 13px; font-weight: 700; color: #14532d; background: rgba(255,255,255,0.7); border: 1px solid rgba(20,61,35,0.12); padding: 3px 12px; border-radius: 14px;">{{ $farmerCrop->batch_name }}</span>
                @endif
            </h1>
            <div class="hero-sub">
                <span>{{ $farmerCrop->crop->category ?? 'Crop' }}</span>
                <span>&middot;</span>
                <span class="stage-badge" style="background: {{ $farmerCrop->growth_stage_color }}12; color: {{ $farmerCrop->growth_stage_color }};">
                    <span class="stage-dot"></span>
                    {{ $farmerCrop->growth_stage_label }}
                </span>
                @unless($farmerCrop->isConfirmed())
                    <span class="stage-badge" style="background: {{ $farmerCrop->confirmation_status_color }}14; color: {{ $farmerCrop->confirmation_status_color }}; border: 1px dashed {{ $farmerCrop->confirmation_status_color }};">
                        {{ $farmerCrop->confirmation_status_label }} Confirmation
                    </span>
                @endunless
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="btn-hero" href="{{ route('farmer.crops.index') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Back to My Crops
            </a>
            @if(!in_array($farmerCrop->growth_stage, ['harvested', 'failed']) && $farmerCrop->isConfirmed())
                <a class="btn-hero" href="#update-stage">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Update Stage
                </a>
            @endif
        </div>
    </div>
</div>

@unless($farmerCrop->isConfirmed())
<div style="max-width: 1100px; margin: 0 auto 4px; padding: 14px 18px; border-radius: 14px; display: flex; gap: 12px; align-items: flex-start;
     background: {{ $farmerCrop->isRejected() ? '#fef2f2' : '#fffbeb' }}; border: 1px solid {{ $farmerCrop->isRejected() ? '#fecaca' : '#fde68a' }};">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $farmerCrop->isRejected() ? '#dc2626' : '#d97706' }}" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;">
        @if($farmerCrop->isRejected())
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        @else
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        @endif
    </svg>
    <div style="font-size: 13px; line-height: 1.5; color: #78350f;">
        @if($farmerCrop->isRejected())
            <b style="color: #991b1b;">Planting rejected.</b>
            {{ $farmerCrop->admin_remarks ? $farmerCrop->admin_remarks : 'An admin rejected this planting request. You can remove this batch and submit a new one.' }}
        @else
            <b style="color: #92400e;">Awaiting admin confirmation.</b>
            Growth stage updates and harvest reporting unlock once an association admin confirms this planting.
            @if($farmerCrop->admin_remarks) <span style="display:block; margin-top:2px;">Admin note: {{ $farmerCrop->admin_remarks }}</span> @endif
        @endif
    </div>
</div>
@endunless

{{-- ── Stats Strip ── --}}
@php
$isFinished = in_array($farmerCrop->growth_stage, ['harvested', 'failed']);
$unit = $farmerCrop->land_area_unit === 'ha' ? 'ha' : $farmerCrop->land_area_unit;
@endphp
<div class="stats-strip">
    <div class="stat-pill">
        <div class="stat-icon" style="background: #f0fdf4; color: #16a34a;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Land Area</span>
            <span class="stat-value">{{ rtrim(rtrim(number_format($farmerCrop->land_area, 2, '.', ''), '0'), '.') }} <span class="stat-unit">{{ $unit }}</span></span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Planted</span>
            <span class="stat-value" style="font-size: 16px;">{{ $farmerCrop->planting_date->format('M d, Y') }}</span>
        </div>
    </div>
    <div class="stat-pill">
        <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Target Harvest</span>
            @if($farmerCrop->expected_harvest_date)
                <span class="stat-value" style="font-size: 16px;">{{ $farmerCrop->expected_harvest_date->format('M d, Y') }}</span>
            @else
                <span class="stat-value">—</span>
            @endif
        </div>
    </div>
    @if(!$isFinished)
    <div class="stat-pill">
        <div class="stat-icon" style="background: #f0fdf4; color: {{ $farmerCrop->growth_stage_color }};">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 9h4v4H7zM14 6h3v7h-3z"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Growth Progress</span>
            <span class="stat-value" style="color: {{ $farmerCrop->growth_stage_color }};">{{ $farmerCrop->progress_percent }}%</span>
        </div>
    </div>
    @elseif($farmerCrop->actual_yield)
    <div class="stat-pill">
        <div class="stat-icon" style="background: #faf5ff; color: #6d28d9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/><path d="M12 7l2 2 3-3"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Actual Yield</span>
            <span class="stat-value">{{ $farmerCrop->actual_yield }} <span class="stat-unit">{{ $farmerCrop->yield_unit ?? 'units' }}</span></span>
        </div>
    </div>
    @else
    <div class="stat-pill">
        <div class="stat-icon" style="background: #f1f5f9; color: #64748b;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div class="stat-text">
            <span class="stat-label">Est. Yield</span>
            <span class="stat-value">—</span>
        </div>
    </div>
    @endif
</div>

{{-- ── Growth Progress ── --}}
@if(!$isFinished)
@php
    $stageOrder = ['land_prep','seeding','vegetative','reproductive','ripening'];
    $stageIdx = array_search($farmerCrop->growth_stage, $stageOrder);
    $stageIdx = $stageIdx === false ? -1 : $stageIdx;
    $shortStages = ['land_prep' => 'Land Prep', 'seeding' => 'Seeding', 'vegetative' => 'Vegetative', 'reproductive' => 'Flowering', 'ripening' => 'Ripening'];
@endphp
<div class="box-card">
    <div class="box-head">
        <div>
            <h2>Growth Progress</h2>
            <p>Track where this batch currently stands in its growth cycle.</p>
        </div>
    </div>
    <div class="box-body" style="padding-bottom: 26px;">
        <div class="stepper-wrap">
            @foreach($stageOrder as $si => $sKey)
            <div class="stepper-step {{ $si < $stageIdx ? 'done' : ($si === $stageIdx ? 'current' : '') }}">
                <div class="stepper-dot" style="{{ $si === $stageIdx ? 'background: ' . $farmerCrop->growth_stage_color . '; border-color: ' . $farmerCrop->growth_stage_color . ';' : ($si < $stageIdx ? '' : '') }}">
                    @if($si < $stageIdx)
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $si + 1 }}
                    @endif
                </div>
                <span class="stepper-label">{{ $shortStages[$sKey] }}</span>
            </div>
            @endforeach
        </div>

        @if($farmerCrop->expected_harvest_date)
        <div style="margin-top: 18px;">
            <div class="progress-head">
                <span class="progress-title">Growth Progress</span>
                <span class="progress-pct" style="color: {{ $farmerCrop->growth_stage_color }};">{{ $farmerCrop->progress_percent }}%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: {{ $farmerCrop->progress_percent }}%; background: linear-gradient(90deg, {{ $farmerCrop->growth_stage_color }}, {{ $farmerCrop->growth_stage_color }}aa);"></div>
            </div>
            <div class="progress-foot">
                <span>Day {{ $farmerCrop->days_since_planting }} of {{ $farmerCrop->days_since_planting + $farmerCrop->days_until_harvest }}</span>
                <span>{{ $farmerCrop->days_until_harvest }}d remaining until target harvest</span>
            </div>
        </div>
        @else
        <p style="font-size: 13px; color: #94a3b8; margin: 14px 0 0;">Set an expected harvest date to see the growth progress timeline.</p>
        @endif
    </div>
</div>
@elseif($farmerCrop->growth_stage === 'harvested')
{{-- ── Harvest Summary ── --}}
<div class="box-card">
    <div class="box-head">
        <div>
            <h2>Harvest Summary</h2>
            <p>This crop batch has been completed.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            @if($farmerCrop->harvest_report_id)
                <a href="{{ route('farmer.harvests.index') }}" class="row-btn plain" title="View in Harvest Reports" style="padding: 6px 12px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    Synced to Harvest Reports
                </a>
            @endif
            <span class="stage-badge" style="background: #faf5ff; color: #6d28d9;"><span class="stage-dot"></span>Harvested</span>
        </div>
    </div>
    <div class="box-body">
        <div class="harvest-summary">
            @if($farmerCrop->actual_yield)
            <div class="hs-tile">
                <div class="hs-label">Actual Yield</div>
                <div class="hs-value">{{ $farmerCrop->actual_yield }} {{ $farmerCrop->yield_unit ?? 'units' }}</div>
            </div>
            @endif
            @if($farmerCrop->actual_harvest_date)
            <div class="hs-tile">
                <div class="hs-label">Harvest Date</div>
                <div class="hs-value" style="font-size: 16px;">{{ $farmerCrop->actual_harvest_date->format('M d, Y') }}</div>
            </div>
            @endif
            @if($farmerCrop->estimated_yield)
            <div class="hs-tile" style="background: #fffbeb; border-color: #fde68a;">
                <div class="hs-label" style="color: #d97706;">Estimated Yield</div>
                <div class="hs-value" style="color: #b45309;">{{ $farmerCrop->estimated_yield }} {{ $farmerCrop->yield_unit ?? 'units' }}</div>
            </div>
            @endif
            @if($farmerCrop->estimated_cost)
            <div class="hs-tile" style="background: #eff6ff; border-color: #bfdbfe;">
                <div class="hs-label" style="color: #2563eb;">Est. Cost</div>
                <div class="hs-value" style="color: #1d4ed8;">₱{{ number_format($farmerCrop->estimated_cost, 2) }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── Full Details ── --}}
<div class="box-card">
    <div class="box-head">
        <div>
            <h2>Batch Details</h2>
            <p>Complete record for this crop batch.</p>
        </div>
    </div>
    <div class="box-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Crop</div>
                <div class="detail-value">{{ $farmerCrop->crop->name }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Category</div>
                <div class="detail-value">{{ $farmerCrop->crop->category ?? 'Crop' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Batch Name</div>
                <div class="detail-value">{{ $farmerCrop->batch_name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Seed / Product Used</div>
                <div class="detail-value">{{ $farmerCrop->product?->display_name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Growth Stage</div>
                <div class="detail-value" style="color: {{ $farmerCrop->growth_stage_color }};">{{ $farmerCrop->growth_stage_label }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Season</div>
                <div class="detail-value">
                    @if($farmerCrop->season === 'wet')
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg> Wet Season
                    @else
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Dry Season
                    @endif
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Planting Date</div>
                <div class="detail-value">{{ $farmerCrop->planting_date->format('M d, Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Expected Harvest</div>
                <div class="detail-value">{{ $farmerCrop->expected_harvest_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Actual Harvest</div>
                <div class="detail-value">{{ $farmerCrop->actual_harvest_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Land Area</div>
                <div class="detail-value">{{ $farmerCrop->land_area }} {{ $unit }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Estimated Yield</div>
                <div class="detail-value">{{ $farmerCrop->estimated_yield ?? '—' }} {{ ($farmerCrop->estimated_yield && $farmerCrop->yield_unit) ? $farmerCrop->yield_unit : '' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Actual Yield</div>
                <div class="detail-value">{{ $farmerCrop->actual_yield ?? '—' }} {{ ($farmerCrop->actual_yield && $farmerCrop->yield_unit) ? $farmerCrop->yield_unit : '' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Estimated Cost</div>
                <div class="detail-value">{{ $farmerCrop->estimated_cost ? '₱' . number_format($farmerCrop->estimated_cost, 2) : '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Days Since Planting</div>
                <div class="detail-value">{{ $farmerCrop->days_since_planting }} days</div>
            </div>
        </div>

        @if($farmerCrop->notes)
        <div class="detail-label" style="margin-top: 24px; margin-bottom: 8px;">Notes</div>
        <div class="notes-box">{{ $farmerCrop->notes }}</div>
        @endif
    </div>
</div>

{{-- ── Update Growth Stage ── --}}
@if(!$isFinished && $farmerCrop->isConfirmed())
<div class="box-card" id="update-stage" style="scroll-margin-top: 20px;">
    <div class="box-head">
        <div>
            <h2>Update Growth Stage</h2>
            <p>Move this batch to the next stage, or record a completed harvest.</p>
        </div>
    </div>
    <div class="box-body">
        <form action="{{ route('farmer.crops.update', $farmerCrop) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-grid2">
                <div class="form-group">
                    <label>Growth Stage *</label>
                    <select name="growth_stage" id="updateStage" required onchange="toggleHarvest(this.value)">
                        <option value="land_prep" {{ $farmerCrop->growth_stage === 'land_prep' ? 'selected' : '' }}>Land Preparation</option>
                        <option value="seeding" {{ $farmerCrop->growth_stage === 'seeding' ? 'selected' : '' }}>Seeding / Sowing</option>
                        <option value="vegetative" {{ $farmerCrop->growth_stage === 'vegetative' ? 'selected' : '' }}>Vegetative Growth</option>
                        <option value="reproductive" {{ $farmerCrop->growth_stage === 'reproductive' ? 'selected' : '' }}>Flowering / Fruiting</option>
                        <option value="ripening" {{ $farmerCrop->growth_stage === 'ripening' ? 'selected' : '' }}>Ripening</option>
                        <option value="harvested" {{ $farmerCrop->growth_stage === 'harvested' ? 'selected' : '' }}>Harvested</option>
                        <option value="failed" {{ $farmerCrop->growth_stage === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated Yield</label>
                    <input type="number" name="estimated_yield" step="0.01" min="0" value="{{ $farmerCrop->estimated_yield ?? '' }}" placeholder="0">
                </div>
                <div class="form-group" id="harvestDateField" style="display: {{ $farmerCrop->growth_stage === 'harvested' ? 'block' : 'none' }};">
                    <label>Actual Harvest Date</label>
                    <input type="date" name="actual_harvest_date" value="{{ $farmerCrop->actual_harvest_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                </div>
                <div class="form-group" id="harvestYieldField" style="display: {{ $farmerCrop->growth_stage === 'harvested' ? 'block' : 'none' }};">
                    <label>Actual Yield</label>
                    <input type="number" name="actual_yield" step="0.01" min="0" value="{{ $farmerCrop->actual_yield ?? '' }}" placeholder="0">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3" placeholder="Add notes about this batch...">{{ $farmerCrop->notes }}</textarea>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 6px;">
                <button type="submit" class="btn-submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    Save Update
                </button>
                <a class="btn-cancel" href="{{ route('farmer.crops.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</div>
@elseif(!$farmerCrop->isConfirmed())
<div class="box-card">
    <div class="box-head">
        <div>
            <h2>Growth Stage</h2>
            <p>Stage updates are locked for this batch.</p>
        </div>
        <span class="stage-badge" style="background: {{ $farmerCrop->confirmation_status_color }}14; color: {{ $farmerCrop->confirmation_status_color }}; border: 1px dashed {{ $farmerCrop->confirmation_status_color }};">
            {{ $farmerCrop->confirmation_status_label }}
        </span>
    </div>
    <div class="box-body">
        <p style="margin: 0; font-size: 13.5px; color: #475569; line-height: 1.6;">
            This planting request has not been confirmed by an admin yet. Once confirmed, you can advance growth stages, record the harvest, and file a harvest report from this page.
        </p>
    </div>
</div>
@endif

{{-- ── Danger Zone ── --}}
<div class="box-card" style="border-color: #fecaca;">
    <div class="box-head">
        <div>
            <h2 style="color: #b91c1c;">Remove Batch</h2>
            <p>Permanently delete this crop batch and its tracking record.</p>
        </div>
    </div>
    <div class="box-body" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
        <p style="margin: 0; font-size: 13px; color: #7f1d1d; max-width: 460px;">This will remove {{ $farmerCrop->crop->name }}{{ $farmerCrop->batch_name ? ' (' . $farmerCrop->batch_name . ')' : '' }} from your crop batches. This action cannot be undone.</p>
        <form action="{{ route('farmer.crops.destroy', $farmerCrop) }}" method="POST" data-aqui-confirm="Remove this crop batch? This cannot be undone." data-aqui-danger data-aqui-confirm-label="Remove">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-hero danger">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Remove Batch
            </button>
        </form>
    </div>
</div>

</div>
</div>

<script>
    function toggleHarvest(s) {
        var show = s === 'harvested';
        var df = document.getElementById('harvestDateField');
        var yf = document.getElementById('harvestYieldField');
        if (df) df.style.display = show ? 'block' : 'none';
        if (yf) yf.style.display = show ? 'block' : 'none';
    }
</script>
@endsection