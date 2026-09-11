@extends('layouts.sidebar')

@section('title', 'Demand Intelligence')

@section('content')
<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }
  @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 560px) { .stats-grid { grid-template-columns: 1fr; } }

  .stat-card {
    position: relative;
    border-radius: 14px;
    padding: 22px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
  .stat-card::after {
    content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px;
    border-radius: 50%; opacity: 0.07; transform: translate(20px, -20px);
  }
  .stat-card.blue   { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; }
  .stat-card.blue::after  { background: #2563eb; }
  .stat-card.amber  { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; }
  .stat-card.amber::after { background: #d97706; }
  .stat-card.green  { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
  .stat-card.green::after { background: #16a34a; }
  .stat-card.violet { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1px solid #ddd6fe; }
  .stat-card.violet::after { background: #7c3aed; }

  .stat-card .stat-value { font-size: 32px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
  .stat-card.blue .stat-value  { color: #1e40af; }
  .stat-card.amber .stat-value { color: #92400e; }
  .stat-card.green .stat-value { color: #166534; }
  .stat-card.violet .stat-value { color: #5b21b6; }
  .stat-card .stat-label { font-size: 13px; color: #6b7c6e; margin-top: 4px; font-weight: 500; }
  .stat-sub { font-size: 12px; color: #6b7c6e; margin-top: 7px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
  .stat-sub b { color: #1a2b1e; font-weight: 800; }
  .stat-sub .trend-up { color: #15803d; }
  .stat-sub .trend-down { color: #b91c1c; }
  .stat-sub svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .ai-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #ffffff; border: none; cursor: pointer;
    padding: 9px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 700; font-family: inherit;
    box-shadow: 0 2px 8px rgba(109, 40, 217, 0.28);
    transition: all 0.2s ease;
  }
  .ai-btn:hover:not(:disabled) { box-shadow: 0 5px 14px rgba(109, 40, 217, 0.36); transform: translateY(-1px); }
  .ai-btn:active:not(:disabled) { transform: scale(0.97); }
  .ai-btn:disabled { opacity: 0.65; cursor: wait; }
  .ai-spin { width: 13px; height: 13px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #ffffff; border-radius: 50%; animation: aiSpin 0.7s linear infinite; }
  @keyframes aiSpin { to { transform: rotate(360deg); } }

  .ai-output {
    margin-top: 12px; padding-top: 12px; border-top: 1px dashed rgba(124,58,237,0.25);
    font-size: 13px; color: #4c1d95; max-height: 340px; overflow-y: auto;
    scrollbar-width: thin;
  }
  .ai-output p, .ai-output li { color: #4c1d95; }
  .ai-output .ai-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 12px 14px; font-size: 12.5px; line-height: 1.5; display: flex; gap: 8px; align-items: flex-start; }

  /* Floating AI loading indicator (outside the KPI card, no overlay) */
  .ai-floating {
    position: fixed; bottom: 26px; right: 26px; z-index: 1300;
    display: flex; align-items: center; gap: 10px;
    background: #ffffff; border: 1px solid #ede9fe; border-radius: 999px;
    padding: 12px 18px;
    box-shadow: 0 12px 32px rgba(109, 40, 217, 0.18);
    font-size: 13px; font-weight: 700; color: #5b21b6;
  }
  .ai-floating[hidden] { display: none; }
  .ai-floating .ai-spin { border-color: rgba(124,58,237,0.25); border-top-color: #7c3aed; }

  /* Floating result modal — centered, no dark overlay behind */
  .ai-modal {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    width: min(660px, calc(100vw - 32px)); max-height: 78vh; overflow-y: auto;
    background: #ffffff; border: 1px solid #ede9fe; border-radius: 18px;
    box-shadow: 0 28px 70px rgba(30, 15, 60, 0.32);
    z-index: 1400;
    opacity: 0; visibility: hidden;
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    scrollbar-width: thin;
  }
  .ai-modal.open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
  .ai-modal-head {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid #f0f2f1;
    position: sticky; top: 0; background: #fafafd; border-radius: 18px 18px 0 0; z-index: 1;
  }
  .ai-modal-head h3 { font-size: 15px; margin: 0; color: #1a2b1e; }
  .ai-modal-head p { font-size: 12px; color: #879a8c; margin: 2px 0 0 0; }
  .ai-modal-close {
    width: 30px; height: 30px; border-radius: 9px; border: none; cursor: pointer;
    background: #f0f2f1; color: #6b7c6e; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s ease; font-size: 16px; line-height: 1;
  }
  .ai-modal-close:hover { background: #fee2e2; color: #b91c1c; }
  .ai-modal-body { padding: 20px; font-size: 13.5px; color: #3f3f46; }

  .ai-kpis {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px;
  }
  .ai-kpi {
    background: linear-gradient(160deg, #faf5ff 0%, #ffffff 60%);
    border: 1px solid #ede9fe; border-radius: 12px; padding: 12px 14px;
  }
  .ai-kpi .k { font-size: 11px; font-weight: 600; color: #8b7ea8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
  .ai-kpi .v { font-size: 20px; font-weight: 800; color: #2b1a4e; line-height: 1.1; display: flex; align-items: center; gap: 6px; }
  .ai-kpi .v .delta-up { color: #16a34a; font-size: 12px; font-weight: 700; background: #f0fdf4; padding: 2px 7px; border-radius: 999px; }
  .ai-kpi .v .delta-down { color: #dc2626; font-size: 12px; font-weight: 700; background: #fef2f2; padding: 2px 7px; border-radius: 999px; }
  .ai-kpi .v .delta-flat { color: #94a3b8; font-size: 12px; font-weight: 700; background: #f1f5f9; padding: 2px 7px; border-radius: 999px; }
  .ai-kpi .s { font-size: 11px; color: #a5a0b8; margin-top: 3px; }

  .ai-card {
    background: #ffffff; border: 1px solid #f0f2f1; border-radius: 12px;
    padding: 14px 16px; margin-bottom: 12px;
  }
  .ai-card-head { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
  .ai-card-head .dot { width: 7px; height: 7px; border-radius: 50%; }
  .ai-card-head h4 { font-size: 13px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .ai-card-head .sub { font-size: 11px; color: #a5a0b8; margin-left: auto; }

  .ai-week {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: end;
  }
  .ai-week .bar-col { text-align: center; }
  .ai-week .bar-track { height: 74px; display: flex; align-items: flex-end; justify-content: center; border-radius: 8px 8px 0 0; background: #f8f5ff; position: relative; }
  .ai-week .bar-fill { width: 38px; border-radius: 8px 8px 0 0; transition: height .3s ease; }
  .ai-week .bar-fill.purple { background: linear-gradient(180deg, #8b5cf6, #7c3aed); box-shadow: 0 4px 12px rgba(124,58,237,0.3); }
  .ai-week .bar-fill.gray { background: linear-gradient(180deg, #d8d2e8, #c4bcd8); }
  .ai-week .bar-val { font-size: 18px; font-weight: 800; color: #2b1a4e; }
  .ai-week .bar-lbl { font-size: 11px; color: #a5a0b8; font-weight: 600; margin-top: 6px; }
  .ai-week .bar-sub { font-size: 10px; color: #c5bfd6; }

  .ai-product-row { padding: 9px 0; }
  .ai-product-row + .ai-product-row { border-top: 1px solid #f4f2f9; }
  .ai-product-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
  .ai-product-top .n { font-weight: 700; color: #1a2b1e; font-size: 13px; }
  .ai-product-top .m { font-size: 11px; color: #a5a0b8; font-weight: 600; }
  .ai-bar { height: 7px; border-radius: 999px; background: #f4f2f9; overflow: hidden; }
  .ai-bar i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #a78bfa, #7c3aed); }

  .ai-crops { display: flex; flex-wrap: wrap; gap: 8px; }
  .ai-crop {
    display: inline-flex; align-items: center; gap: 7px;
    background: #faf5ff; border: 1px solid #ede9fe; border-radius: 999px;
    padding: 5px 12px; font-size: 12px; font-weight: 600; color: #5b21b6;
  }
  .ai-crop .cnt { background: #7c3aed; color: #fff; border-radius: 999px; font-size: 10px; padding: 1px 7px; font-weight: 700; }

  .ai-narrative {
    background: linear-gradient(135deg, #faf5ff 0%, #f6efff 100%);
    border: 1px solid #ede9fe; border-radius: 12px; padding: 14px 16px;
  }
  .ai-narrative-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
  .ai-narrative-head h4 { font-size: 13px; font-weight: 700; color: #5b21b6; margin: 0; }
  .ai-narrative-body { font-size: 13px; color: #3b2a63; line-height: 1.6; }
  .ai-narrative-body p { margin: 6px 0 0 0; }
  .ai-narrative-body ul { margin: 8px 0 0 0; padding-left: 18px; }
  .ai-narrative-body li { margin-bottom: 6px; line-height: 1.5; }
  .ai-narrative-body strong { color: #7c3aed; }

  .panel {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }
  .panel-header { padding: 20px 24px; border-bottom: 1px solid #f0f2f1; background: #fafdfb; }
  .panel-body { padding: 20px 24px; }
  .panel-body-flush { padding: 0; }

  .demand-bar { height: 8px; background: #f0f2f1; border-radius: 4px; overflow: hidden; }
  .demand-bar-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, #16a34a, #22c55e);
    transition: width 0.6s ease;
  }

  .farmer-avatar {
    width: 36px; height: 36px;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; flex-shrink: 0;
  }
  .farmer-cell { display: flex; align-items: center; gap: 12px; }

  .table-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th {
    padding: 13px 16px; font-weight: 600; color: #6b7c6e;
    border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  tbody tr { transition: background 0.15s; }
  tbody tr:hover td { background: #f8faf9; }

  .action-btn {
    background: linear-gradient(135deg, #143d23 0%, #1a5230 100%);
    color: white; border: none;
    padding: 7px 16px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s; box-shadow: 0 2px 6px rgba(20,61,35,0.2);
  }
  .action-btn:hover { box-shadow: 0 4px 12px rgba(20,61,35,0.3); transform: translateY(-1px); }
  .action-btn:active { transform: scale(0.97); }

  .product-count-badge {
    background: #f0fdf4; color: #166534;
    padding: 3px 10px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    border: 1px solid #bbf7d0;
  }

  .empty-state {
    text-align: center; padding: 56px 24px;
    background: linear-gradient(180deg, #fafdfb 0%, #f5f7f6 100%);
  }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #bfdbfe;
    display: flex; align-items: center; justify-content: center;
  }
</style>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Demand Intelligence</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">See which farmers are exploring your products. This is demand visibility — no action required.</p>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

<div class="stats-grid">
  <div class="stat-card blue">
    <div>
      <div class="stat-value">{{ $totalQuotations }}</div>
      <div class="stat-label">Demand Signals</div>
      <div class="stat-sub">from <b>{{ $uniqueFarmers }}</b> interested farmers</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#2563eb" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
  </div>
  <div class="stat-card amber">
    <div>
      <div class="stat-value">{{ $recentQuotations }}</div>
      <div class="stat-label">Demand This Week</div>
      <div class="stat-sub">
        @if($weekTrend > 0)
          <svg viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
          <span class="trend-up">+{{ $weekTrend }}% vs last week</span>
        @elseif($weekTrend < 0)
          <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          <span class="trend-down">{{ $weekTrend }}% vs last week</span>
        @else
          <span style="color:#94a3b8;">{{ $priorQuotations }} last week</span>
        @endif
      </div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(217,119,6,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#d97706" stroke-width="2" fill="none"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
  </div>
  <div class="stat-card green">
    <div>
      <div class="stat-value">₱{{ number_format($ownDemandValue, 0) }}</div>
      <div class="stat-label">Your Products' Demand Value</div>
      <div class="stat-sub">across <b>{{ $demandProductCount }}</b> of your products</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="3" x2="8" y2="21"/><path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/></svg>
    </div>
  </div>
  <div class="stat-card violet" style="flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 12px;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
      <div>
        <div class="stat-value" style="font-size: 21px; line-height: 1.15;">AI Store Overview</div>
        <div class="stat-label">Plain-language read on your demand</div>
        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
          @if($weekTrend > 0)
            <span style="font-size: 12px; font-weight: 700; color: #16a34a; background: #f0fdf4; padding: 2px 8px; border-radius: 999px;">▲ +{{ $weekTrend }}%</span>
          @elseif($weekTrend < 0)
            <span style="font-size: 12px; font-weight: 700; color: #dc2626; background: #fef2f2; padding: 2px 8px; border-radius: 999px;">▼ {{ $weekTrend }}%</span>
          @else
            <span style="font-size: 12px; font-weight: 700; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 999px;">0%</span>
          @endif
          <span style="font-size: 11px; color: #8b7ea8; font-weight: 500;">{{ $recentQuotations }} this week · {{ $priorQuotations }} last week</span>
        </div>
      </div>
      <div style="width: 46px; height: 46px; border-radius: 14px; background: rgba(124,58,237,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg viewBox="0 0 24 24" width="22" height="22" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      </div>
    </div>
    <button type="button" class="ai-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
      <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>
      <span>Generate Overview</span>
    </button>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <div class="panel">
    <div class="panel-header">
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">Your Products in Demand</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Which products farmers are exploring</p>
    </div>
    <div class="panel-body">
      @if(count($productDemand) > 0)
        @php $maxCount = max(array_column($productDemand, 'count')); @endphp
        @foreach($productDemand as $productName => $data)
          <div style="{{ !$loop->last ? 'margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f2f1;' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $productName }}</span>
              <span style="font-size: 12px; color: #879a8c; font-weight: 600;">{{ $data['count'] }} quotation(s)</span>
            </div>
            <div class="demand-bar">
              <div class="demand-bar-fill" style="width: {{ ($data['count'] / $maxCount) * 100 }}%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px; color: #b0b8b3;">
              <span>{{ number_format($data['total_qty'], 1) }} units total</span>
              <span style="font-weight: 600; color: #4b5c50;">₱{{ number_format($data['total_value'], 0) }} value</span>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 40px 20px;">
          <div class="empty-icon" style="margin-bottom: 12px;">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          </div>
          <p style="margin: 0; font-size: 14px; color: #879a8c; line-height: 1.5;">No demand data yet. As farmers generate quotations using your products, you'll see trends here.</p>
        </div>
      @endif
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">Recent Farmer Activity</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Farmers who recently explored your products</p>
    </div>
    <div class="panel-body-flush">
      @if($totalQuotations > 0)
        @foreach($recentActivity as $q)
          <div style="padding: 14px 20px; {{ !$loop->last ? 'border-bottom: 1px solid #f5f7f6;' : '' }} display: flex; align-items: center; gap: 12px; transition: background 0.15s;" onmouseover="this.style.background='#fafdfb'" onmouseout="this.style.background='transparent'">
            <div class="farmer-avatar" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border: 1px solid #bfdbfe;">
              @php
                $name = $q->farmer ? $q->farmer->name : 'Unknown';
                $words = explode(' ', trim($name));
                echo count($words) >= 2 ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1) : mb_substr($name, 0, 2);
              @endphp
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $q->farmer?->name ?? 'Unknown Farmer' }}</div>
              <div style="font-size: 12px; color: #879a8c; text-transform: capitalize;">{{ $q->crop_type }} — {{ $q->land_area }} ha — {{ $q->season }} season</div>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 700; color: #143d23; font-size: 14px;">₱{{ number_format($q->estimated_total_cost, 0) }}</div>
              <div style="font-size: 11px; color: #b0b8b3;">{{ $q->created_at->diffForHumans() }}</div>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 48px 20px;">
          <div style="width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px; background: linear-gradient(135deg, #f5f5f5, #f0f0f0); border: 1px solid #e5e5e5; display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#9ca3af" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p style="font-size: 15px; font-weight: 600; color: #6b7c6e; margin: 0 0 4px 0;">No Activity Yet</p>
          <p style="font-size: 13px; color: #879a8c; margin: 0;">When farmers generate quotations using your products, they'll appear here.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<div class="table-card">
  <div style="padding: 20px 24px; border-bottom: 1px solid #f0f2f1; display: flex; justify-content: space-between; align-items: center; background: #fafdfb;">
    <div>
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">All Demand Signals</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 2px 0 0 0;">Every cost quotation that includes your products</p>
    </div>
    <span style="font-size: 13px; color: #879a8c; font-weight: 600; background: #f0f2f1; padding: 4px 12px; border-radius: 8px;">{{ $totalQuotations }} total</span>
  </div>
  <div data-paginated>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
      <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Farmer</th>
          <th>Crop / Season</th>
          <th>Land Area</th>
          <th>Your Products</th>
          <th>Est. Value</th>
          <th>Date</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
          @php
            $storeItems = $q->items->filter(fn ($item) => $item->product && $item->product->user_id === Auth::id());
          @endphp
          <tr>
            <td><span style="font-weight: 700; color: #143d23; font-family: 'SF Mono', ui-monospace, monospace; font-size: 13px;">{{ $q->quotation_number }}</span></td>
            <td>
              <div class="farmer-cell">
                <div class="farmer-avatar" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border: 1px solid #bfdbfe; width: 32px; height: 32px; border-radius: 8px; font-size: 12px;">
                  @php
                    $name = $q->farmer ? $q->farmer->name : 'Unknown';
                    $words = explode(' ', trim($name));
                    echo count($words) >= 2 ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1) : mb_substr($name, 0, 2);
                  @endphp
                </div>
                <span style="font-weight: 500;">{{ $q->farmer?->name ?? 'Unknown Farmer' }}</span>
              </div>
            </td>
            <td>
              <div style="text-transform: capitalize; font-weight: 500;">{{ $q->crop_type }}</div>
              <div style="font-size: 12px; color: #879a8c; text-transform: capitalize;">{{ $q->season }} season</div>
            </td>
            <td><span style="font-weight: 600;">{{ number_format($q->land_area, 1) }}</span> <span style="color: #879a8c; font-size: 12px;">ha</span></td>
            <td><span class="product-count-badge">{{ $storeItems->count() }} product(s)</span></td>
            <td>
              @if($q->estimated_total_cost > 0)
                <span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($q->estimated_total_cost, 2) }}</span>
              @else
                <span style="color: #d1d9d3;">—</span>
              @endif
            </td>
            <td style="font-size: 13px; color: #879a8c; white-space: nowrap;">{{ $q->created_at->format('M d, Y') }}</td>
            <td style="text-align: right;">
              <a href="{{ route('store.quotations.show', $q) }}" class="action-btn">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                View
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <p style="font-size: 16px; font-weight: 600; color: #6b7c6e; margin: 0 0 4px 0;">No Demand Signals Yet</p>
                <p style="font-size: 13px; color: #879a8c; margin: 0;">When farmers generate quotations using your products, they'll appear here.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="app-scrollbar">
      <div class="app-scrollbar-track"></div>
      <div class="app-scrollbar-thumb"></div>
    </div>
  </div>
  @if($quotations->hasPages())
    {{ $quotations->links('partials.pagination') }}
  @endif
  </div>
</div>

<div class="ai-floating" id="aiFloatingLoading" hidden>
  <span class="ai-spin"></span>
  <span>Generating your AI store overview&hellip;</span>
</div>

<div class="ai-modal" id="aiModal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" aria-hidden="true">
  <div class="ai-modal-head">
    <div>
      <h3 id="aiModalTitle">AI Store Overview</h3>
      <p>Plain-language read on your demand</p>
    </div>
    <button type="button" class="ai-modal-close" id="aiModalClose" aria-label="Close AI overview" title="Close">
      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="ai-modal-body" id="aiModalBody"></div>
</div>

<script>
  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return t
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
      .replace(/\u2014/g, '&mdash;');
  }
  function fmtMoney(n) {
    return '₱' + (Math.round(Number(n) || 0)).toLocaleString();
  }
  function renderKpis(m) {
    var tw = Number(m.this_week) || 0;
    var lw = Number(m.last_week) || 0;
    var delta = lw > 0 ? Math.round(((tw - lw) / lw) * 100) : (tw > 0 ? 100 : 0);
    var cls = delta > 0 ? 'delta-up' : (delta < 0 ? 'delta-down' : 'delta-flat');
    var txt = delta > 0 ? '▲ ' + delta + '%' : (delta < 0 ? '▼ ' + Math.abs(delta) + '%' : '0%');
    return '' +
      '<div class="ai-kpi"><div class="k">This Week</div><div class="v">' + tw + '</div><div class="s">demand signals</div></div>' +
      '<div class="ai-kpi"><div class="k">Last Week</div><div class="v">' + lw + '</div><div class="s">previous 7 days</div></div>' +
      '<div class="ai-kpi"><div class="k">Week over Week</div><div class="v"><span class="' + cls + '">' + txt + '</span></div><div class="s">' + (delta > 0 ? 'gaining momentum' : delta < 0 ? 'cooling off' : 'steady demand') + '</div></div>' +
      '<div class="ai-kpi"><div class="k">Unique Farmers</div><div class="v">' + (Number(m.unique_farmers) || 0) + '</div><div class="s">interested farmers</div></div>';
  }
  function renderWeekChart(m) {
    var tw = Number(m.this_week) || 0;
    var lw = Number(m.last_week) || 0;
    var max = Math.max(tw, lw, 1);
    var hTw = Math.max(6, Math.round((tw / max) * 72));
    var hLw = Math.max(6, Math.round((lw / max) * 72));
    return '<div class="ai-week">' +
      '<div class="bar-col"><div class="bar-val">' + tw + '</div>' +
      '<div class="bar-track"><div class="bar-fill purple" style="height:' + hTw + 'px;"></div></div>' +
      '<div class="bar-lbl">This Week</div><div class="bar-sub">last 7 days</div></div>' +
      '<div class="bar-col"><div class="bar-val">' + lw + '</div>' +
      '<div class="bar-track"><div class="bar-fill gray" style="height:' + hLw + 'px;"></div></div>' +
      '<div class="bar-lbl">Last Week</div><div class="bar-sub">prior 7 days</div></div></div>';
  }
  function renderProducts(m) {
    var list = m.top_products || [];
    if (!list.length) {
      return '<div class="ai-card"><div class="ai-card-head"><span class="dot" style="background:#16a34a;"></span><h4>Top Products</h4><span class="sub">by quotation count</span></div><p style="color:#a5a0b8;font-size:12px;margin:0;">No product demand yet.</p></div>';
    }
    var max = Math.max.apply(Math, list.map(function (p) { return p.count; }));
    var rows = list.map(function (p) {
      var w = max > 0 ? Math.max(4, Math.round((p.count / max) * 100)) : 0;
      return '<div class="ai-product-row"><div class="ai-product-top"><span class="n">' + escapeAiHtml(p.name) + '</span><span class="m">' + p.count + '× · ' + fmtMoney(p.value) + '</span></div><div class="ai-bar"><i style="width:' + w + '%;"></i></div></div>';
    }).join('');
    return '<div class="ai-card"><div class="ai-card-head"><span class="dot" style="background:#16a34a;"></span><h4>Top Products</h4><span class="sub">by quotation count</span></div>' + rows + '</div>';
  }
  function renderCrops(m) {
    var list = m.top_crops || [];
    if (!list.length) return '';
    var pills = list.map(function (c) {
      return '<span class="ai-crop">' + escapeAiHtml(c.crop) + ' <span class="cnt">' + c.count + '</span></span>';
    }).join('');
    return '<div class="ai-card"><div class="ai-card-head"><span class="dot" style="background:#f59e0b;"></span><h4>Top Crops</h4><span class="sub">quoted crops</span></div><div class="ai-crops">' + pills + '</div></div>';
  }
  function renderNarrative(raw) {
    var lines = escapeAiHtml(String(raw || '')).replace(/\r/g, '').split('\n');
    var html = '', bulletOpen = false;
    lines.forEach(function (line) {
      var t = line.trim();
      if (!t) return;
      if (/^[-*]\s+/.test(t)) {
        if (!bulletOpen) { html += '<ul>'; bulletOpen = true; }
        html += '<li>' + inlineAi(t.replace(/^[-*]\s+/, '')) + '</li>';
      } else {
        if (bulletOpen) { html += '</ul>'; bulletOpen = false; }
        html += '<p>' + inlineAi(t) + '</p>';
      }
    });
    if (bulletOpen) html += '</ul>';
    return html;
  }
  function openAiModal(data) {
    if (data && typeof data === 'object' && data.error) {
      document.getElementById('aiModalBody').innerHTML = '<div class="ai-error"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>' + escapeAiHtml(data.error) + '</span></div>';
      var modal = document.getElementById('aiModal');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      return;
    }
    var m = (data && data.metrics) || {};
    var hasMetrics = Object.keys(m).length > 0;
    var html = hasMetrics
      ? '<div class="ai-kpis">' + renderKpis(m) + '</div>' +
        '<div class="ai-card"><div class="ai-card-head"><span class="dot" style="background:#7c3aed;"></span><h4>Demand Volume</h4><span class="sub">signals received</span></div>' + renderWeekChart(m) + '</div>' +
        renderProducts(m) + renderCrops(m)
      : '';
    html += '<div class="ai-narrative"><div class="ai-narrative-head">' +
      '<svg viewBox="0 0 24 24" width="14" height="14" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>' +
      '<h4>AI Analyst Takeaway</h4></div><div class="ai-narrative-body">' + renderNarrative(data.overview) + '</div></div>';
    document.getElementById('aiModalBody').innerHTML = html;
    var modal = document.getElementById('aiModal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }
  function closeAiModal() {
    var modal = document.getElementById('aiModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }
  function showAiLoading() {
    var el = document.getElementById('aiFloatingLoading');
    el.hidden = false;
  }
  function hideAiLoading() {
    document.getElementById('aiFloatingLoading').hidden = true;
  }
  function setAiBtnState(btn, busy) {
    var label = btn.querySelector('span');
    btn.disabled = busy;
    label.textContent = busy ? 'Generating\u2026' : 'Regenerate Overview';
  }
  function generateAiOverview(btn) {
    setAiBtnState(btn, true);
    showAiLoading();

    fetch('{{ route('store.quotations.ai-overview') }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: '{}'
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      hideAiLoading();
      setAiBtnState(btn, false);
      if (res.ok && res.data && res.data.overview) {
        openAiModal(res.data);
      } else if (res.data && res.data.error) {
        openAiModal({ error: res.data.error });
      } else {
        openAiModal({ error: 'The AI service could not generate an overview right now. Please try again.' });
      }
    })
    .catch(function () {
      hideAiLoading();
      setAiBtnState(btn, false);
      openAiModal({ error: 'Connection failed. Please try again.' });
    });
  }
  document.getElementById('aiModalClose').addEventListener('click', closeAiModal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAiModal();
  });
</script>

@push('scripts')
  @include('partials.ajax-pagination')
@endpush
@endsection
