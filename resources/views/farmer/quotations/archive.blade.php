@extends('layouts.sidebar')

@section('title', 'Expired Quotations')

@section('content')
<style>
  .archive-page { max-width: 980px; }

  .archive-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; flex-wrap: wrap; margin-bottom: 18px;
  }
  .archive-head h1 { font-size: 22px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.01em; }
  .archive-head p { font-size: 13px; color: #647263; margin: 0; line-height: 1.5; max-width: 520px; }

  .archive-back {
    display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
    background: #ffffff; border: 1px solid #E0E5DB; color: #344B38;
    font-size: 12.5px; font-weight: 700; padding: 9px 14px; border-radius: 10px;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .archive-back:hover { background: #F3F5F0; border-color: #C8D5C5; }
  .archive-back svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }

  .archive-card {
    background: #ffffff; border: 1px solid #E0E5DB; border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 18px rgba(15,23,42,0.05);
  }
  .archive-table { width: 100%; border-collapse: collapse; text-align: left; }
  .archive-table th {
    padding: 13px 20px; font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #94a3b8; background: #fafdfb; border-bottom: 1px solid #F0F2F1;
    white-space: nowrap;
  }
  .archive-table td { padding: 14px 20px; font-size: 13px; color: #334155; border-bottom: 1px solid #F0F2F1; vertical-align: middle; }
  .archive-table tr:last-child td { border-bottom: none; }
  .archive-ref { font-weight: 700; color: #143d23; font-family: 'SF Mono', ui-monospace, monospace; font-size: 12.5px; }
  .archive-muted { color: #879a8c; font-size: 12px; }
  .archive-expired {
    display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600;
    color: #87919c; background: #F4F6F5; border: 1px solid #E7EBE9; border-radius: 999px; padding: 3px 10px;
    white-space: nowrap;
  }
  .archive-view {
    display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
    background: #F3F5F0; border: 1px solid #E0E5DB; color: #344B38;
    font-size: 12px; font-weight: 700; padding: 7px 13px; border-radius: 9px;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .archive-view:hover { background: #EDF1E9; border-color: #C8D5C5; }
  .archive-view svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }

  .archive-empty { text-align: center; padding: 56px 24px; }
  .archive-empty-icon {
    width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 14px;
    background: #EDF1E9; color: #648268; display: flex; align-items: center; justify-content: center;
  }
  .archive-empty-icon svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
  .archive-empty h3 { font-size: 16px; font-weight: 700; color: #344B38; margin: 0 0 6px 0; }
  .archive-empty p { font-size: 13px; color: #647263; margin: 0; line-height: 1.6; max-width: 380px; margin-inline: auto; }

  [data-theme="dark"] .archive-card { background: #111827; border-color: #334155; }
  [data-theme="dark"] .archive-table th { background: rgba(15, 23, 42, 0.72); border-bottom-color: #334155; color: #9fb3a6; }
  [data-theme="dark"] .archive-table td { border-bottom-color: #334155; color: #e2e8f0; }
  [data-theme="dark"] .archive-back, [data-theme="dark"] .archive-view { background: #1f2937; border-color: #334155; color: #d6f5e3; }
  [data-theme="dark"] .archive-back:hover, [data-theme="dark"] .archive-view:hover { background: #16301f; border-color: #4ade80; }
  [data-theme="dark"] .archive-expired { background: #1f2937; border-color: #334155; color: #9fb3a6; }
  [data-theme="dark"] .archive-empty-icon { background: #16301f; color: #6ee7a0; }

  @media (max-width: 640px) {
    .archive-head { flex-direction: column-reverse; }
    .archive-table { min-width: 620px; }
  }
</style>

<div class="archive-page">
  <nav aria-label="Breadcrumb" class="archive-breadcrumb" style="margin-bottom:12px;">
    <a href="{{ url('/') }}" style="color:#5c6d61;text-decoration:none;">Home</a>
    <span style="color:#94a3b8;margin:0 6px;">/</span>
    <a href="{{ route('farmer.quotations.index') }}" style="color:#5c6d61;text-decoration:none;">My Cost Quotations</a>
    <span style="color:#94a3b8;margin:0 6px;">/</span>
    <span style="color:#1a2b1e;font-weight:700;">Expired Quotations</span>
  </nav>
  <div class="archive-head">
    <div>
      <h1>Expired quotations</h1>
      <p>
        These estimates have passed their validity window, so their prices are no longer current. They are
        kept as your record &mdash; open one to see what it came to.
        @if($archived->count() > 0)
          <br>Total quoted: <strong>₱{{ number_format((float) $archivedValue, 2) }}</strong>
        @endif
      </p>
    </div>
  </div>

  <div class="archive-card">
    @if($archived->count() > 0)
      <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
        <div class="app-scroll">
          <table class="archive-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Crop / Season</th>
                <th>Land Area</th>
                <th>Items</th>
                <th>Estimated Cost</th>
                <th>Expired On</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($archived as $quote)
                <tr>
                  <td><span class="archive-ref">{{ $quote->quotation_number }}</span></td>
                  <td>
                    <div style="text-transform: capitalize; font-weight: 500;">{{ $quote->crop_type }}</div>
                    <div class="archive-muted" style="text-transform: capitalize;">{{ $quote->season }} season</div>
                  </td>
                  <td><span style="font-weight: 600;">{{ number_format($quote->land_area, 2) }}</span> <span class="archive-muted">ha</span></td>
                  <td>{{ $quote->items->count() }}</td>
                  <td><span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($quote->estimated_total_cost, 2) }}</span></td>
                  <td>
                    <span class="archive-expired">
                      <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                      {{ $quote->valid_until?->format('M d, Y') }}
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <a href="{{ route('farmer.quotations.show', $quote) }}" class="archive-view">
                      View
                      <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
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
      <div class="archive-empty">
        <div class="archive-empty-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
        </div>
        <h3>Nothing expired yet</h3>
        <p>When an estimate's validity window closes, it is kept here instead of on your working page.</p>
      </div>
    @endif
  </div>
</div>
@endsection
