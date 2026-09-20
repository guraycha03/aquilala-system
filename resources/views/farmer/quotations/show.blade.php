@extends('layouts.sidebar')

@section('title', 'Cost Quotation ' . $quotation->quotation_number)

@section('content')
@php
  $total     = (float) $quotation->estimated_total_cost;
  $landArea  = (float) $quotation->land_area;
  $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;
  $daysLeft  = $quotation->valid_until ? (int) max(0, now()->diffInDays($quotation->valid_until, false)) : null;
  $effStatus = $quotation->effective_status;
  $storeNames = $quotation->items->pluck('product.storeOwner.name')->filter()->unique();
  $discreteUnits = ['sack','sacks','bag','bags','piece','pieces','unit','units','carton','cartons','box','boxes','pack','packs','bottle','bottles','sachet','sachets','kit','kits','length','lengths','set','sets'];
@endphp
<style>
  .page-header-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdf4 100%);
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
  }

  .detail-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
    margin-bottom: 20px;
  }
  .detail-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f2f1;
    background: #fafdfb;
  }
  .detail-card-body { padding: 24px; }

  .info-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em; color: #879a8c; margin-bottom: 4px;
  }
  .info-value { font-weight: 700; font-size: 15px; color: #1a2b1e; }

  .status-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 16px; border-radius: 24px;
    font-size: 13px; font-weight: 700;
    letter-spacing: 0.01em;
  }
  .status-pill.computed { background: #eff6ff; color: #1d4ed8; }
  .status-pill.viewed   { background: #f5f3ff; color: #6d28d9; }
  .status-pill.expired  { background: #f5f5f5; color: #9ca3af; }
  .status-pill.pending  { background: #fffbeb; color: #b45309; }
  .status-pill.accepted { background: #f0fdf4; color: #15803d; }
  .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

  .items-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .items-table th {
    padding: 13px 16px; font-weight: 600;
    color: #6b7c6e; border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  .items-table td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  .items-table tbody tr { transition: background 0.15s; }
  .items-table tbody tr:hover td { background: #f8faf9; }

  .calc-line {
    font-size: 12px; color: #4b5c50;
    margin-top: 6px; padding: 8px 10px;
    background: #f8faf9; border: 1px dashed #d7dfda; border-radius: 8px;
    line-height: 1.5; font-family: 'SF Mono', ui-monospace, monospace;
  }

  .delete-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 10px; color: #dc2626; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.15s;
  }
  .delete-btn:hover { background: #fee2e2; border-color: #fca5a5; transform: translateY(-1px); }
  .delete-btn:active { transform: scale(0.98); }

  .total-badge {
    display: inline-flex; align-items: baseline; gap: 6px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 10px 18px;
  }
  .total-badge .total-label { font-size: 12px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 0.04em; }
  .total-badge .total-value { font-size: 24px; font-weight: 800; color: #166534; letter-spacing: -0.02em; }

  .store-chip {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 10px;
    border: 1px solid #f0f2f1;
    transition: all 0.15s;
  }
  .store-chip:hover { background: #f8faf9; border-color: #e5e7eb; }

  .info-banner {
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex; align-items: flex-start; gap: 14px;
    margin-top: 4px;
  }

  /* ── Process timeline ── */
  .process-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; }
  .process-step { position: relative; padding: 6px 18px 0 0; }
  .process-step .step-dot {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; flex-shrink: 0;
  }
  .process-step .step-connector {
    position: absolute; top: 21px; left: 44px; right: 6px; height: 2px;
    background: #e8ecea;
  }
  .process-step.done .step-connector { background: #86efac; }
  .process-step.done .step-dot { background: #16a34a; color: #fff; box-shadow: 0 3px 8px rgba(22,163,74,0.25); }
  .process-step.active .step-dot { background: #6d28d9; color: #fff; box-shadow: 0 3px 8px rgba(109,40,217,0.28); animation: stepPulse 1.6s ease infinite; }
  .process-step.todo .step-dot { background: #f8faf9; color: #94a3b8; border: 1px solid #e5e7eb; }
  .process-step.danger .step-dot { background: #f5f5f5; color: #9ca3af; border: 1px solid #e5e7eb; }
  @keyframes stepPulse { 0%,100% { box-shadow: 0 3px 8px rgba(109,40,217,0.28); } 50% { box-shadow: 0 3px 16px rgba(109,40,217,0.45); } }
  .process-step .step-title { font-size: 13px; font-weight: 700; color: #1a2b1e; margin-top: 8px; }
  .process-step .step-sub { font-size: 11.5px; color: #879a8c; margin-top: 3px; line-height: 1.45; }
  @media (max-width: 900px) {
    .process-steps { grid-template-columns: 1fr 1fr; gap: 16px 0; }
    .process-step .step-connector { display: none; }
  }

  /* ── AI Overview ── */
  .ai-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #ffffff; border: none; cursor: pointer;
    padding: 10px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    box-shadow: 0 2px 8px rgba(109, 40, 217, 0.28);
    transition: all 0.2s ease;
  }
  .ai-btn:hover:not(:disabled) { box-shadow: 0 5px 14px rgba(109, 40, 217, 0.36); transform: translateY(-1px); }
  .ai-btn:active:not(:disabled) { transform: scale(0.97); }
  .ai-btn:disabled { opacity: 0.65; cursor: wait; }
  .ai-spin { width: 13px; height: 13px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #ffffff; border-radius: 50%; animation: aiSpin 0.7s linear infinite; }
  @keyframes aiSpin { to { transform: rotate(360deg); } }

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

  .ai-modal {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    width: min(660px, calc(100vw - 32px)); max-height: 80vh; overflow-y: auto;
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
  .ai-source-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px;
  }
  .ai-source-badge.ai { background: #ede9fe; color: #6d28d9; }
  .ai-source-badge.fallback { background: #f0fdf4; color: #15803d; }

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
  .ai-narrative-body .table-wrap { overflow-x: auto; max-width: 100%; }
  .ai-error {
    background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
    border-radius: 10px; padding: 12px 14px; font-size: 12.5px; line-height: 1.5;
    display: flex; gap: 8px; align-items: flex-start;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em;">
        Cost Quotation {{ $quotation->quotation_number }}
      </h1>
      <p class="keep-sub" style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
        Generated on {{ $quotation->created_at->format('F d, Y \a\t g:i A') }}
        @if($effStatus === 'viewed' && $quotation->viewed_at)
          <span style="color: #7c3aed; font-weight: 600;">&middot; Viewed by a store {{ $quotation->viewed_at->format('F d, Y \a\t g:i A') }}</span>
        @elseif($effStatus !== 'expired')
          <span style="color: #879a8c;">&middot; No store has viewed it yet</span>
        @endif
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <span class="status-pill {{ $effStatus }}">
        <span class="dot"></span>
        {{ $quotation->status_label }}
      </span>
      <form action="{{ route('farmer.quotations.destroy', $quotation) }}" method="POST"
            data-aqui-confirm="Are you sure you want to delete this cost quotation? This cannot be undone."
            data-aqui-danger data-aqui-confirm-label="Delete"
            style="margin: 0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

<!-- Quotation Process -->
<div class="detail-card">
  <div class="detail-card-header">
    <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Quotation Process</h2>
    <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">What has happened with this estimate so far</p>
  </div>
  <div class="detail-card-body">
    <div class="process-steps">
      @php
        $stepGenerated = true;
        $stepViewed = $effStatus === 'viewed';
        $stepExpired = $effStatus === 'expired';
      @endphp
      <div class="process-step done">
        <div class="step-dot">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="#fff" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="step-connector"></div>
        <div class="step-title">Generated</div>
        <div class="step-sub">{{ $quotation->created_at->format('M d, Y · g:i A') }}</div>
      </div>

      <div class="process-step {{ $stepGenerated ? 'done' : 'todo' }}">
        <div class="step-dot">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="step-connector"></div>
        <div class="step-title">Shared as Demand Intel</div>
        <div class="step-sub">Visible to stores selling your products</div>
      </div>

      <div class="process-step {{ $stepViewed ? 'done' : ($stepExpired ? 'danger' : 'active') }}">
        <div class="step-dot">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="step-connector"></div>
        <div class="step-title">Viewed by Store</div>
        <div class="step-sub">
          @if($stepViewed)
            {{ $quotation->viewed_at ? $quotation->viewed_at->format('M d, Y · g:i A') : 'Yes' }}
          @elseif($stepExpired)
            Expired before it was viewed
          @else
            Waiting for a store to look
          @endif
        </div>
      </div>

      <div class="process-step {{ $stepExpired ? 'done' : 'todo' }}">
        <div class="step-dot" style="{{ $stepExpired ? 'background:#94a3b8;color:#fff;box-shadow:none;' : '' }}">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="step-title">Expires</div>
        <div class="step-sub">
          @if($quotation->valid_until)
            @if($stepExpired)
              Expired {{ $quotation->valid_until->format('M d, Y') }}
            @else
              Valid until {{ $quotation->valid_until->format('M d, Y') }}
              @if(is_numeric($daysLeft)) · {{ $daysLeft }} day(s) left @endif
            @endif
          @else
            No expiration set
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Farming Plan Details -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Farming Plan Details</h2>
    </div>
    <div class="detail-card-body">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
          <div class="info-label">Crop Type</div>
          <div class="info-value" style="text-transform: capitalize;">{{ $quotation->crop_type }}</div>
        </div>
        <div>
          <div class="info-label">Season</div>
          <div class="info-value" style="display: flex; align-items: center; gap: 8px;">
            @if($quotation->season === 'wet')
              <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #38bdf8, #0284c7); border-radius: 50%;"></span> Wet Season
            @else
              <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #fbbf24, #d97706); border-radius: 50%;"></span> Dry Season
            @endif
          </div>
        </div>
        <div>
          <div class="info-label">Land Area</div>
          <div class="info-value">{{ number_format($landArea, 2) }} <span style="font-weight: 500; font-size: 13px; color: #879a8c;">hectares</span></div>
        </div>
        <div>
          <div class="info-label">Generated On</div>
          <div class="info-value" style="font-weight: 600;">{{ $quotation->created_at->format('M d, Y') }}</div>
        </div>
        <div>
          <div class="info-label">Cost per Hectare</div>
          <div class="info-value">₱{{ number_format($perHectare, 2) }}<span style="font-weight: 500; font-size: 13px; color: #879a8c;"> /ha</span></div>
        </div>
        <div>
          <div class="info-label">Line Items</div>
          <div class="info-value">{{ $quotation->items->count() }} product(s)</div>
        </div>
      </div>
      @if($quotation->notes)
        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f2f1;">
          <div class="info-label">Notes</div>
          <p style="margin: 4px 0 0 0; font-size: 14px; color: #374138; line-height: 1.5;">{{ $quotation->notes }}</p>
        </div>
      @endif
      @if($quotation->store_notes)
        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f2f1;">
          <div class="info-label">Store Response</div>
          <p style="margin: 4px 0 0 0; font-size: 14px; color: #374138; line-height: 1.5;">{{ $quotation->store_notes }}</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Stores Involved -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Supply Stores</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Where your inputs come from</p>
    </div>
    <div class="detail-card-body">
      @php
        $storeIds = $quotation->items->pluck('product.storeOwner')->filter()->unique('id');
      @endphp
      @if($storeIds->count() > 0)
        @foreach($storeIds as $store)
          <div class="store-chip" style="{{ !$loop->last ? 'margin-bottom: 10px;' : '' }}">
            <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; border: 1px solid #bfdbfe;">
              @php $w = explode(' ', trim($store->name)); echo count($w) >= 2 ? mb_substr($w[0],0,1).mb_substr(end($w),0,1) : mb_substr($store->name,0,2); @endphp
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $store->name }}</div>
              <div style="font-size: 12px; color: #879a8c;">{{ $quotation->items->where('product.user_id', $store->id)->count() }} product(s) · ₱{{ number_format($quotation->items->where('product.user_id', $store->id)->sum('subtotal'), 2) }}</div>
            </div>
          </div>
        @endforeach
      @else
        <p style="color: #94a3b8; font-size: 14px; margin: 0;">Products from the general catalog were used.</p>
      @endif

      <div style="margin-top: 20px; padding: 14px 16px; background: #fafdfb; border: 1px solid #e8ecea; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
          <span style="font-size: 13px; color: #6b7c6e; display: flex; align-items: center; gap: 6px;">
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            @if($quotation->valid_until)
              Valid until <strong style="color: #1a2b1e;">{{ $quotation->valid_until->format('F d, Y') }}</strong>
              @if($effStatus === 'expired')
                <span style="color: #dc2626; font-weight: 600; background: #fef2f2; padding: 2px 8px; border-radius: 6px; font-size: 11px;">Expired</span>
              @elseif(is_numeric($daysLeft))
                <span style="color: #15803d; font-weight: 600; background: #f0fdf4; padding: 2px 8px; border-radius: 6px; font-size: 11px;">{{ $daysLeft }} day(s) left</span>
              @endif
            @else
              No expiration set
            @endif
          </span>
          <span style="color: #b0b8b3; font-size: 12px;">Based on current product prices</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Cost Breakdown -->
<div class="detail-card">
  <div class="detail-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Cost Breakdown</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Auto-calculated via Decision Support formulas — rate per hectare &times; land area</p>
    </div>
    @if($total > 0)
      <div class="total-badge">
        <div class="total-label">Total</div>
        <div class="total-value">₱{{ number_format($total, 2) }}</div>
      </div>
    @endif
  </div>
  <div style="padding: 0;">
    @if($quotation->items->count() > 0)
      <table class="items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Store</th>
            <th>Calculation</th>
            <th>Price per Unit</th>
            <th>Quantity</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotation->items as $item)
            @php
              $formula = $formulas->get($item->product_id);
              $unit    = strtolower(trim($formula?->unit ?? $item->product?->stock_unit ?? 'unit'));
              $rate    = $formula ? (float) $formula->rate_per_hectare : null;
              if ($rate === null && $landArea > 0) {
                  $rate = round((float) $item->calculated_quantity / $landArea, 2);
              }
              $baseQty = $rate !== null ? round($rate * $landArea, 2) : null;
              $qty     = (float) $item->calculated_quantity;
              $rounded = in_array($unit, $discreteUnits) && is_numeric($baseQty) && ceil($baseQty) != $baseQty;
              $storeOwner = $item->product?->storeOwner;
            @endphp
            <tr>
              <td>
                <div style="font-weight: 600; color: #1a2b1e;">{{ $item->product_name }}</div>
                @if($item->product)
                  <div style="font-size: 11px; color: #b0b8b3; font-family: 'SF Mono', ui-monospace, monospace;">SKU: {{ $item->product->sku }}</div>
                  <div style="font-size: 11px; color: #b0b8b3; text-transform: capitalize;">{{ $item->product->category?->name ?? '' }}</div>
                @endif
              </td>
              <td style="font-size: 13px; color: #6b7c6e;">
                @if($storeOwner)
                  {{ $storeOwner->name }}
                @else
                  <span style="color: #d1d9d3;">—</span>
                @endif
              </td>
              <td style="max-width: 260px;">
                @if($rate !== null && $baseQty !== null)
                  <div class="calc-line">
                    {{ rtrim(rtrim(number_format($rate, 3), '0'), '.') }} {{ $unit }}/ha &times; {{ number_format($landArea, 2) }} ha
                    @if($rounded)
                      = {{ number_format($baseQty, 2) }} {{ $unit }} &rarr; rounded up to <strong>{{ number_format($qty, 0) }} {{ $unit }}</strong>
                    @else
                      &asymp; <strong>{{ rtrim(rtrim(number_format($baseQty, 2), '0'), '.') }} {{ $unit }}</strong>
                    @endif
                    <br>&times; ₱{{ number_format($item->price_at_generation, 2) }}/{{ $unit }} = <strong>₱{{ number_format($item->subtotal, 2) }}</strong>
                  </div>
                @else
                  <span style="font-size: 12px; color: #b0b8b3;">No formula rate — quantity estimated</span>
                @endif
              </td>
              <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
              <td><span style="font-weight: 600;">{{ number_format($qty, 2) }}</span> <span style="font-size: 11px; color: #b0b8b3;">{{ $unit }}</span></td>
              <td style="text-align: right; font-weight: 700; color: #1a2b1e;">₱{{ number_format($item->subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" style="text-align: right; padding: 16px; font-weight: 700; color: #6b7c6e; border-bottom: none; background: #fafdfb;">Estimated Total</td>
            <td style="text-align: right; padding: 16px; font-weight: 800; color: #143d23; font-size: 18px; border-bottom: none; background: #fafdfb;">
              ₱{{ number_format($total, 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    @else
      <div style="text-align: center; padding: 48px 24px;">
        <svg viewBox="0 0 24 24" width="40" height="40" stroke="#d1d9d3" stroke-width="1.5" fill="none" style="margin-bottom: 12px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="color: #879a8c; font-size: 14px; margin: 0;">No products matched the formulas for this crop and season.</p>
      </div>
    @endif
  </div>
</div>

<!-- AI Overview -->
<div class="detail-card">
  <div class="detail-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">AI Overview</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">A plain-language read of this quotation</p>
    </div>
    <button type="button" class="ai-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
      <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      <span>Generate AI Overview</span>
    </button>
  </div>
  <div style="padding: 20px 24px;">
    <div style="display: flex; gap: 12px; align-items: flex-start;">
      <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #f5f3ff, #ede9fe); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #ddd6fe;">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="#6d28d9" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
      </div>
      <div>
        <p style="margin: 0; font-size: 13.5px; color: #4b5c50; line-height: 1.6;">
          Get a quick explanation of what this quotation means — the totals, what's driving the cost, and a practical next step.
          If the AI service is unavailable, a smart summary built straight from your quotation data will be shown instead.
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Info Banner -->
<div class="info-banner">
  <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
  </div>
  <div>
    <div style="font-weight: 700; font-size: 14px; color: #166534; margin-bottom: 4px;">This is a cost quotation, not a purchase order</div>
    <p style="margin: 0; font-size: 13px; color: #15803d; line-height: 1.6;">
      This cost breakdown was auto-generated using current product prices and standard farming formulas.
      Store owners can see this as demand intelligence. Contact the store directly to finalize any purchase.
    </p>
  </div>
</div>

<div class="ai-floating" id="aiFloatingLoading" hidden>
  <span class="ai-spin"></span>
  <span>Preparing your AI overview&hellip;</span>
</div>

<div class="ai-modal" id="aiModal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" aria-hidden="true">
  <div class="ai-modal-head">
    <div>
      <h3 id="aiModalTitle">AI Overview</h3>
      <p>LLM-generated readability overview</p>
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
    var html = '', inUl = false, inOl = false;
    function closeUl() { if (inUl) { html += '</ul>'; inUl = false; } }
    function closeOl() { if (inOl) { html += '</ol>'; inOl = false; } }
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
      if (!trimmed) { closeUl(); closeOl(); continue; }
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
    closeUl(); closeOl();
    return html;
  }

  function openAiModal(data) {
    var body = document.getElementById('aiModalBody');
    if (data && typeof data === 'object' && data.error) {
      body.innerHTML = '<div class="ai-error"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>' + escapeAiHtml(data.error) + '</span></div>';
    } else {
      var source = (data && data.source === 'ai')
        ? '<span class="ai-source-badge ai"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>AI-assisted</span>'
        : '<span class="ai-source-badge fallback"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>Smart summary (AI offline)</span>';
      body.innerHTML = '<div style="margin-bottom: 12px;">' + source + '</div>' +
        '<div class="ai-narrative">' +
        '<div class="ai-narrative-head">' +
        '<svg viewBox="0 0 24 24" width="14" height="14" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>' +
        '<h4>' + (data.source === 'ai' ? 'AI Readability Overview' : 'Smart Summary — Quotation Data') + '</h4></div>' +
        '<div class="ai-narrative-body">' + renderAiMarkdown(data.overview || '') + '</div></div>';
    }
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
    document.getElementById('aiFloatingLoading').hidden = false;
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

    fetch('{{ route('farmer.quotations.ai-overview', $quotation) }}', {
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
        openAiModal({ error: 'The AI overview could not be generated right now. Please try again.' });
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