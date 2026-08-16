@extends('layouts.sidebar')

@section('title', 'My Quotations')

@section('content')
<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px;
  }
  .stat-card .stat-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
  .stat-card .stat-value { font-size: 24px; font-weight: 800; color: #1e2922; margin-top: 4px; }
  .stat-card .stat-value.green { color: #16a34a; }
  .stat-card .stat-value.amber { color: #d97706; }
  .stat-card .stat-value.blue { color: #2563eb; }
  .stat-card .stat-value.red { color: #dc2626; }

  .quote-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .quote-table th {
    background: #fafbfa; padding: 12px 14px; font-weight: 600;
    color: #4a574e; border-bottom: 1px solid #e2e8f0; text-align: left;
    font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em;
  }
  .quote-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #3d4a41; }
  .quote-table tr:hover td { background: #f8fafc; }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
  }
  .status-badge.pending { background: #fef3c7; color: #d97706; }
  .status-badge.computed { background: #e0f2fe; color: #0284c7; }
  .status-badge.dispatched { background: #f3e8ff; color: #7c3aed; }
  .status-badge.accepted { background: #f0fdf4; color: #16a34a; }
  .status-badge.rejected { background: #fef2f2; color: #dc2626; }

  .item-count-badge {
    background: #f1f5f9; padding: 2px 8px; border-radius: 6px;
    font-size: 12px; color: #475569; font-weight: 600;
  }
  .action-link {
    color: #143d23; font-weight: 600; font-size: 13px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    padding: 6px 12px; border-radius: 6px; transition: background 0.2s;
  }
  .action-link:hover { background: #f0fdf4; text-decoration: underline; }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">My Quotations</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">
    Track and manage your product quotation requests across all supply stores.
  </p>
</div>

@if(session('success'))
  <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    <span style="font-size: 14px; color: #166534; font-weight: 500;">{{ session('success') }}</span>
  </div>
@endif

@php
  $totalQuotes = $quotations->count();
  $pendingCount = $quotations->where('status', 'pending')->count();
  $dispatchedCount = $quotations->where('status', 'dispatched')->count();
  $acceptedCount = $quotations->where('status', 'accepted')->count();
  $rejectedCount = $quotations->where('status', 'rejected')->count();
  $recentQuotes = $quotations->take(5);
@endphp

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Requests</div>
    <div class="stat-value blue">{{ $totalQuotes }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pending Response</div>
    <div class="stat-value amber">{{ $pendingCount }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Awaiting Review</div>
    <div class="stat-value blue">{{ $dispatchedCount }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Accepted</div>
    <div class="stat-value green">{{ $acceptedCount }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Rejected</div>
    <div class="stat-value red">{{ $rejectedCount }}</div>
  </div>
</div>

<!-- Quotations List -->
<div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
  <div style="padding: 18px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Quotation Requests</h2>
      <p style="font-size: 13px; color: #64748b; margin: 2px 0 0 0;">All your product quotation requests and their current status</p>
    </div>
    <a href="{{ route('farmer.quotations.request') }}" style="background: #143d23; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Quotation Request
    </a>
  </div>

  @if($quotations->count() > 0)
    <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table class="quote-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Store</th>
            <th>Items</th>
            <th>Total Estimate</th>
            <th>Date Requested</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotations as $quote)
            <tr>
              <td style="font-weight: 700; color: #143d23; font-family: monospace;">{{ $quote->quotation_number }}</td>
              <td>
                @if($quote->storeOwner)
                  <div style="font-weight: 600;">{{ $quote->storeOwner->name }}</div>
                  <div style="font-size: 11px; color: #94a3b8;">{{ $quote->storeOwner->email }}</div>
                @else
                  <span style="color: #94a3b8;">Waiting for store assignment</span>
                @endif
              </td>
              <td>
                <span class="item-count-badge">{{ $quote->items->count() }} product(s)</span>
              </td>
              <td style="font-weight: 700; color: #1e2922;">
                ₱{{ number_format($quote->estimated_total_cost, 2) }}
              </td>
              <td style="color: #64748b; font-size: 13px;">{{ $quote->created_at->format('M d, Y') }}</td>
              <td>
                <span class="status-badge {{ $quote->status }}">
                  <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                  {{ $quote->status_label }}
                </span>
              </td>
              <td>
                <a href="{{ route('farmer.quotations.show', $quote) }}" class="action-link">
                  View Details
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
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
    <div style="text-align: center; padding: 60px 20px;">
      <svg viewBox="0 0 24 24" width="48" height="48" stroke="#d1d9d3" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
      </svg>
      <h3 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0 0 4px 0;">No Quotation Requests Yet</h3>
      <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">You haven't submitted any quotation requests. Browse store products and request a quote!</p>
      <a href="{{ route('farmer.quotations.request') }}" style="background: #143d23; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        Request a Quotation
      </a>
    </div>
  @endif
</div>
@endsection

