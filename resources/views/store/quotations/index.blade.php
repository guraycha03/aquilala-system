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
    min-height: 140px;
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
  .stat-card .stat-label { font-size: 12px; color: #6b7c6e; margin-top: 10px; font-weight: 500; }
  .stat-sub { font-size: 12px; color: #6b7c6e; margin-top: 7px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
  .stat-sub b { color: #1a2b1e; font-weight: 800; }
  .stat-sub .trend-up { color: #15803d; }
  .stat-sub .trend-down { color: #b91c1c; }
  .stat-sub svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .stat-card.violet.ai-overview { flex-direction: column; align-items: stretch; justify-content: space-between; }
  .stat-card.violet .ai-title { font-size: 20px; letter-spacing: -0.02em; }
  .ai-overview-head { position: relative; min-height: 42px; padding-right: 48px; }
  .ai-icon {
    position: absolute; top: 0; right: 0;
    width: 38px; height: 38px; border-radius: 11px;
    background: rgba(124,58,237,0.12);
    display: flex; align-items: center; justify-content: center;
  }
  .ai-icon svg { width: 19px; height: 19px; stroke: #7c3aed; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .ai-overview-btn { align-self: flex-start; padding: 8px 13px; font-size: 11.5px; }

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

  .ai-narrative {
    background: linear-gradient(135deg, #faf5ff 0%, #f6efff 100%);
    border: 1px solid #ede9fe; border-radius: 12px; padding: 16px 18px;
  }
  .ai-narrative-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
  .ai-narrative-head h4 { font-size: 15px; font-weight: 700; color: #5b21b6; margin: 0; }
  .ai-narrative-body { font-size: 14px; color: #3b2a63; line-height: 1.7; }
  .ai-narrative-body p { margin: 8px 0 0 0; }
  .ai-narrative-body ul, .ai-narrative-body ol { margin: 10px 0 0 18px; padding: 0; }
  .ai-narrative-body li { margin-bottom: 7px; line-height: 1.6; }
  .ai-narrative-body strong { color: #6d28d9; }
  .ai-narrative-body h1, .ai-narrative-body h2, .ai-narrative-body h3, .ai-narrative-body h4 {
    margin: 16px 0 6px; font-weight: 700; color: #4c1d95; line-height: 1.35;
  }
  .ai-narrative-body h1 { font-size: 16px; }
  .ai-narrative-body h2 { font-size: 15px; }
  .ai-narrative-body h3 { font-size: 14.5px; }
  .ai-narrative-body h4 { font-size: 14px; }
  .ai-narrative-body > *:first-child { margin-top: 0; }
  .ai-narrative-body table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
  .ai-narrative-body th { background: #f3e8ff; text-align: left; font-weight: 700; color: #4c1d95; }
  .ai-narrative-body th, .ai-narrative-body td { border: 1px solid #e9d5ff; padding: 7px 9px; }
  .ai-narrative-body blockquote { margin: 10px 0; padding: 9px 13px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; color: #78350f; }
  .ai-narrative-body .table-wrap { overflow-x: auto; max-width: 100%; }
  .ai-narrative-body .warn-icon-img { width: 16px; height: 16px; display: inline-block; margin-right: 6px; vertical-align: -3px; }

  .ai-modal-foot { margin-top: 16px; display: flex; justify-content: flex-end; }
  .ai-chat-link {
    display: inline-flex; align-items: center; gap: 8px;
    background: #7c3aed; color: #ffffff; border: none; border-radius: 10px;
    padding: 10px 16px; font-size: 13px; font-weight: 700; font-family: 'DM Sans', sans-serif;
    cursor: pointer; transition: background 0.2s ease;
  }
  .ai-chat-link:hover { background: #6d28d9; }
  .ai-chat-link svg { width: 15px; height: 15px; }

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

  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */

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
      <div class="stat-sub">From <b>{{ $uniqueFarmers }}</b> interested farmers</div>
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
      <div class="stat-sub">Across <b>{{ $demandProductCount }}</b> of your products</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center;">
      <x-peso-icon :size="24" color="#16a34a" />
    </div>
  </div>
  <div class="stat-card violet ai-overview">
    <div class="ai-overview-head">
      <div class="stat-value ai-title">AI Store Overview</div>
      <div class="ai-icon">
        <svg viewBox="0 0 24 24"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      </div>
    </div>
    <button type="button" class="ai-btn ai-overview-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
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

<x-table-toolbar
    uid="store-quotes"
    work-table="storeQuotesTable"
    per-page="10"
    noun="signals"
    search-placeholder="Search by reference, farmer, crop, or season…"
    :filters="[
        ['key' => 'crop', 'label' => 'All crops', 'options' => collect($quotations->pluck('crop_type')->unique()->values())->map(fn($c) => [strtolower($c), $c])->all()],
        ['key' => 'season', 'label' => 'All seasons', 'options' => collect($quotations->pluck('season')->unique()->values())->map(fn($s) => [strtolower($s), ucfirst($s)])->all()],
    ]"
    :sorts="[
        ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Date (newest)'],
        ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Date (oldest)'],
        ['key' => 'value', 'dir' => -1, 'type' => 'num', 'label' => 'Est. value (highest)'],
        ['key' => 'value', 'dir' => 1, 'type' => 'num', 'label' => 'Est. value (lowest)'],
        ['key' => 'farmer', 'dir' => 1, 'type' => 'str', 'label' => 'Farmer (A–Z)'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
    ]"
  />

<div class="table-card">
  <div style="padding: 20px 24px; border-bottom: 1px solid #f0f2f1; display: flex; justify-content: space-between; align-items: center; background: #fafdfb;">
    <div>
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">All Demand Signals</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 2px 0 0 0;">Every cost quotation that includes your products</p>
    </div>
    <span style="font-size: 13px; color: #879a8c; font-weight: 600; background: #f0f2f1; padding: 4px 12px; border-radius: 8px;">{{ $totalQuotations }} total</span>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
      <table id="storeQuotesTable" class="data-table">
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
          <tr data-search="{{ strtolower(($q->quotation_number ?? '') . ' ' . ($q->farmer?->name ?? '') . ' ' . ($q->crop_type ?? '') . ' ' . ($q->season ?? '')) }}"
            data-farmer="{{ strtolower($q->farmer?->name ?? '') }}"
            data-crop="{{ strtolower($q->crop_type ?? '') }}"
            data-season="{{ strtolower($q->season ?? '') }}"
            data-value="{{ (float) ($q->estimated_total_cost ?? 0) }}"
            data-date="{{ $q->created_at?->format('Y-m-d') ?? '' }}">
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
</div>

<div class="ai-floating" id="aiFloatingLoading" hidden>
  <span class="ai-spin"></span>
  <span>Generating your AI store overview&hellip;</span>
</div>

<div class="ai-modal" id="aiModal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" aria-hidden="true">
  <div class="ai-modal-head">
    <div>
      <h3 id="aiModalTitle">AI Store Overview</h3>
      <p>LLM-generated demand intelligence report</p>
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
  function renderAiMarkdown(raw) {
    raw = String(raw || '').replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
    var lines = escapeAiHtml(raw).replace(/\r/g, '').split('\n');
    var html = '', inUl = false, inOl = false, inBq = false;
    function closeUl() { if (inUl) { html += '</ul>'; inUl = false; } }
    function closeOl() { if (inOl) { html += '</ol>'; inOl = false; } }
    function closeBq() { if (inBq) { html += '</blockquote>'; inBq = false; } }
    function renderTable(buf) {
      var rows = buf.map(function (r) { return r.slice(1, -1).split('|').map(function (c) { return c.trim(); }); })
        .filter(function (cells) { return !cells.every(function (c) { return /^:?-+:?$/.test(c); }); });
      if (!rows.length) return;
      html += '<div class="table-wrap"><table>';
      var header = rows.shift();
      html += '<thead><tr>' + header.map(function (c) { return '<th>' + inlineAi(c) + '</th>'; }).join('') + '</tr></thead><tbody>';
      rows.forEach(function (cells) {
        html += '<tr>' + cells.map(function (c) { return '<td>' + inlineAi(c) + '</td>'; }).join('') + '</tr>';
      });
      html += '</tbody></table></div>';
    }
    for (var i = 0; i < lines.length; i++) {
      var trimmed = lines[i].trim();
      if (!trimmed) { closeUl(); closeOl(); closeBq(); continue; }
      if (trimmed.charAt(0) === '|' && trimmed.charAt(trimmed.length - 1) === '|') {
        closeUl(); closeOl();
        var buf = [];
        while (i < lines.length) {
          var t = lines[i].trim();
          if (t.charAt(0) === '|' && t.charAt(t.length - 1) === '|') { buf.push(t); i++; } else break;
        }
        i--;
        renderTable(buf);
        continue;
      }
      if (trimmed.indexOf('&gt;') === 0) {
        closeUl(); closeOl();
        var warnImg = '<img src="{{ asset('images/caution.png') }}" class="warn-icon-img" alt="Warning">';
        if (!inBq) { html += '<blockquote>'; inBq = true; }
        html += '<p>' + warnImg + inlineAi(trimmed.replace(/^&gt;\s?/, '').replace(/^[\s\u26A0\uFE0F\u2757\u2B21]+/u, '').trim()) + '</p>';
        continue;
      } else { closeBq(); }
      var hMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
      if (hMatch) {
        closeUl(); closeOl();
        var lvl = hMatch[1].length;
        html += '<h' + lvl + '>' + inlineAi(hMatch[2]) + '</h' + lvl + '>';
        continue;
      }
      if (/^[-*]\s+/.test(trimmed)) {
        if (!inUl) { closeOl(); html += '<ul>'; inUl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^[-*]\s+/, '')) + '</li>';
        continue;
      } else { closeUl(); }
      if (/^\d+[.)]\s+/.test(trimmed)) {
        if (!inOl) { closeUl(); html += '<ol>'; inOl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^\d+[.)]\s+/, '')) + '</li>';
        continue;
      } else { closeOl(); }
      html += '<p>' + inlineAi(trimmed) + '</p>';
    }
    closeUl(); closeOl(); closeBq();
    return html;
  }
  var lastAiOverview = '';
  function openAiChat() {
    try { sessionStorage.setItem('aiSeedOverview', lastAiOverview || ''); } catch (e) {}
    window.location.href = '{{ route('store.ai-chat.index') }}';
  }
  function openAiModal(data) {
    if (data && typeof data === 'object' && data.error) {
      document.getElementById('aiModalBody').innerHTML = '<div class="ai-error"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>' + escapeAiHtml(data.error) + '</span></div>';
      var modal = document.getElementById('aiModal');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      return;
    }
    lastAiOverview = String(data.overview || '');
    var html = '<div class="ai-narrative">' +
      '<div class="ai-narrative-head">' +
      '<svg viewBox="0 0 24 24" width="14" height="14" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>' +
      '<h4>AI Demand Intelligence Report</h4></div><div class="ai-narrative-body">' + renderAiMarkdown(lastAiOverview) + '</div></div>' +
      '<div class="ai-modal-foot">' +
      '<button type="button" class="ai-chat-link" onclick="openAiChat()">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
      '<span>Open in AI Chat</span></button></div>';
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

@endsection
