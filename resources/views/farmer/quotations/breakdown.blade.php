@extends('layouts.sidebar')

@section('title', 'Itemized Breakdown — ' . $quotation->quotation_number)

@section('content')
@php
  $total     = (float) $quotation->estimated_total_cost;
  $landArea  = (float) $quotation->land_area;
  $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;
  $effStatus = $quotation->effective_status;
  $daysLeft  = $quotation->valid_until ? (int) max(0, now()->diffInDays($quotation->valid_until, false)) : null;
  $discreteUnits = ['sack','sacks','bag','bags','piece','pieces','unit','units','carton','cartons','box','boxes','pack','packs','bottle','bottles','sachet','sachets','kit','kits','length','lengths','set','sets'];
@endphp
<style>
  .page-header-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #e0f7ea 100%);
    border: 1px solid #bbf7d0;
    border-radius: 18px;
    padding: 24px 28px;
    margin-bottom: 20px;
  }

  .btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    background: #ffffff; color: #166534; text-decoration: none;
    padding: 9px 16px; border-radius: 11px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    border: 1px solid #bbf7d0; transition: all 0.2s ease; cursor: pointer;
  }
  .btn-back:hover { background: #f0fdf4; }
  .btn-back svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2.4; fill: none; stroke-linecap: round; stroke-linejoin: round; }

  .status-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 16px; border-radius: 24px;
    font-size: 13px; font-weight: 700; letter-spacing: 0.01em;
  }
  .status-pill.computed { background: #eff6ff; color: #1d4ed8; }
  .status-pill.viewed   { background: #f5f3ff; color: #6d28d9; }
  .status-pill.expired  { background: #f5f5f5; color: #9ca3af; }
  .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

  .breakdown-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }
  .breakdown-head {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f2f1;
    background: #fafdfb;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
  }

  .items-table { width: 100%; border-collapse: collapse; font-size: 16px; }
  .items-table th {
    padding: 13px 16px; font-weight: 600;
    color: #6b7c6e; border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 13px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  .items-table td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  .items-table tbody tr { transition: background 0.15s; }
  .items-table tbody tr:hover td { background: #f8faf9; }

  .calc-line {
    font-size: 16px; color: #3d4f42;
    margin-top: 6px; padding: 12px 14px;
    background: #f8faf9; border: 1px dashed #c8d3cc; border-radius: 9px;
    line-height: 1.55; font-family: 'SF Mono', ui-monospace, monospace;
  }

  .down-total {
    display: flex; flex-direction: column; align-items: flex-start; gap: 6px;
    margin: 24px 0 0 0; padding: 18px 22px; width: 100%; box-sizing: border-box;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0; border-radius: 14px;
  }
  .down-total-label {
    font-size: 13px; font-weight: 700; color: #166534; text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  .down-total-value {
    font-size: 28px; font-weight: 800; color: #143d23; letter-spacing: -0.02em;
  }
  .down-total-caption {
    font-size: 14.5px; color: #6b7c6e; line-height: 1.5;
  }

  @media (max-width: 780px) {
    .app-scroll-wrap { overflow: visible; }
    .items-table, .items-table tbody, .items-table tr, .items-table td { display: block; width: 100%; }
    .items-table thead { display: none; }
    .items-table tbody tr {
      border: 1px solid #eef1ef; border-radius: 14px; margin: 0 0 14px 0;
      background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .items-table tbody tr:hover td { background: transparent; }
    .items-table td {
      display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
      padding: 12px 16px; border-bottom: 1px dashed #eef1ef;
    }
    .items-table td:last-child { border-bottom: none; }
    .items-table td::before {
      content: attr(data-label);
      font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase;
      letter-spacing: 0.05em; flex-shrink: 0; min-width: 78px; padding-top: 4px;
    }
    .items-table td:first-child::before { content: none; }
    .items-table td:first-child { flex-direction: column; align-items: flex-start; gap: 3px; }
    .items-table td.calc-cell { flex-direction: column; align-items: stretch; gap: 8px; }
    .items-table td.calc-cell::before { align-self: flex-start; }
    .items-table td[data-label="Price per Unit"],
    .items-table td[data-label="Quantity"] { align-items: baseline; }
    .items-table td[data-label="Subtotal"] { align-items: center; background: #fafdfb; border-bottom: none; }
  }

  .info-banner {
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0; border-radius: 14px;
    padding: 18px 22px; display: flex; align-items: flex-start; gap: 14px;
    margin-top: 20px;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => $quotation->quotation_number, 'url' => route('farmer.quotations.show', $quotation)],
  ['label' => 'Itemized Breakdown'],
]"/>

<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
        <span class="status-pill {{ $effStatus }}"><span class="dot"></span>{{ $quotation->status_label }}</span>
        <span style="font-size: 12px; color: #879a8c;">
          @if($quotation->valid_until)
            @if($effStatus === 'expired')
              Expired {{ $quotation->valid_until->format('M d, Y') }}
            @elseif(is_numeric($daysLeft))
              Valid until {{ $quotation->valid_until->format('M d, Y') }} &middot; {{ $daysLeft }} day(s) left
            @else
              Valid until {{ $quotation->valid_until->format('M d, Y') }}
            @endif
          @else
            No expiration set
          @endif
        </span>
      </div>
      <h1 style="font-size: 22px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em; font-family: 'SF Mono', ui-monospace, monospace;">
        Itemized Breakdown — {{ $quotation->quotation_number }}
      </h1>
      <p style="color: #51605a; font-size: 13.5px; margin: 0; line-height: 1.5;">
        <strong style="text-transform: capitalize; color: #143d23;">{{ $quotation->crop_type }}</strong>
        &middot; {{ ucfirst($quotation->season) }} Season &middot; {{ number_format($landArea, 2) }} ha
        &middot; generated {{ $quotation->created_at->format('M d, Y \a\t g:i A') }}
      </p>
    </div>
    <a href="{{ route('farmer.quotations.show', $quotation) }}" class="btn-back">
      <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Summary
    </a>
  </div>
</div>

<div class="breakdown-card">
  <div class="breakdown-head">
    <div>
      <h2 style="font-size: 17px; font-weight: 700; color: #1a2b1e; margin: 0;">Line-by-Line Cost Breakdown</h2>
      <p style="font-size: 14.5px; color: #6b7c6e; margin: 4px 0 0 0;">Rate per hectare &times; land area, priced at the store&rsquo;s current rate</p>
    </div>
  </div>

  @if($quotation->items->count() > 0)
    <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
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
                    <div style="font-size: 13px; color: #94a3b8; font-family: 'SF Mono', ui-monospace, monospace;">SKU: {{ $item->product->sku }}</div>
                    <div style="font-size: 13px; color: #94a3b8; text-transform: capitalize;">{{ $item->product->category_display_name }}</div>
                  @endif
                </td>
                <td data-label="Store" style="font-size: 15px; color: #6b7c6e;">
                  @if($storeOwner)
                    {{ $storeOwner->name }}
                  @else
                    <span style="color: #d1d9d3;">—</span>
                  @endif
                </td>
                <td data-label="Calculation" class="calc-cell" style="max-width: 360px;">
                  @if($rate !== null && $baseQty !== null)
                    <div class="calc-line">
                      {{ rtrim(rtrim(number_format($rate, 3), '0'), '.') }} {{ $unit }}/ha &times; {{ number_format($landArea, 2) }} ha
                      @if($rounded)
                        = {{ number_format($baseQty, 2) }} {{ $unit }} &rarr; rounded up to <strong>{{ number_format($qty, 0) }} {{ $unit }}</strong>
                      @else
                        &asymp; <strong>{{ rtrim(rtrim(number_format($baseQty, 2), '0'), '.') }} {{ $unit }}</strong>
                      @endif
                      <br>&times; ₱{{ number_format($item->price_at_generation, 2) }}/{{ $unit }} = <strong>₱{{ number_format($item->subtotal, 2) }}</strong> — {{ $unit }} at the store&rsquo;s current rate
                    </div>
                  @else
                    <span style="font-size: 15px; color: #94a3b8;">No formula rate — quantity estimated</span>
                  @endif
                </td>
                <td data-label="Price per Unit" style="font-size: 16px;">₱{{ number_format($item->price_at_generation, 2) }}</td>
                <td data-label="Quantity"><span style="font-weight: 600; font-size: 16px;">{{ number_format($qty, 2) }}</span> <span style="font-size: 13px; color: #94a3b8;">{{ $unit }}</span></td>
                <td data-label="Subtotal" style="text-align: right; font-weight: 700; color: #1a2b1e;">₱{{ number_format($item->subtotal, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="down-total">
        <div class="down-total-label">Estimated Total</div>
        <div class="down-total-value">₱{{ number_format($total, 2) }}</div>
        <div class="down-total-caption">Rate per hectare &times; land area, priced at the store&rsquo;s current rate</div>
      </div>
      <div class="app-scrollbar">
        <div class="app-scrollbar-track"></div>
        <div class="app-scrollbar-thumb"></div>
      </div>
    </div>
  @else
    <div style="text-align: center; padding: 48px 24px;">
      <svg viewBox="0 0 24 24" width="40" height="40" stroke="#d1d9d3" stroke-width="1.5" fill="none" style="margin-bottom: 12px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p style="color: #879a8c; font-size: 14px; margin: 0;">No products matched the formulas for this crop and season.</p>
    </div>
  @endif
</div>

<div class="info-banner">
  <img src="{{ asset('images/information.png') }}" width="36" height="36" alt="Information" style="flex-shrink: 0; margin-top: 2px; object-fit: contain; display: block;">
  <div>
    <div style="font-weight: 700; font-size: 14px; color: #166534; margin-bottom: 4px;">This is a cost quotation, not a purchase order</div>
    <p style="margin: 0; font-size: 13px; color: #15803d; line-height: 1.6;">
      Prices reflect store prices at the time of generation. Contact the store directly to confirm availability and totals.
      Store owners can see this quotation as demand intelligence.
    </p>
  </div>
</div>
@endsection