@extends('layouts.sidebar')

@section('title', 'My Activity')

@section('content')
<style>
  .stats-row {
    display: flex; gap: 14px; margin-bottom: 28px; flex-wrap: wrap;
  }
  .stat-pill {
    display: inline-flex;
    align-items: center; gap: 14px;
    background: #ffffff;
    border: 1px solid #f0f2f1;
    border-radius: 14px;
    padding: 18px 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
    min-width: 200px;
  }
  .stat-pill:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
  .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .stat-pill .stat-val { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
  .stat-pill .stat-lbl { font-size: 12px; font-weight: 600; margin-top: 2px; }

  .stat-pill.green .stat-icon  { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #16a34a; }
  .stat-pill.green .stat-val  { color: #166534; }
  .stat-pill.green .stat-lbl  { color: #15803d; }

  .stat-pill.amber .stat-icon { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #d97706; }
  .stat-pill.amber .stat-val { color: #92400e; }
  .stat-pill.amber .stat-lbl { color: #b45309; }

  .stat-pill.red .stat-icon   { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #dc2626; }
  .stat-pill.red .stat-val   { color: #991b1b; }
  .stat-pill.red .stat-lbl   { color: #dc2626; }

  /* Timeline card */
  .timeline-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }

  .activity-item {
    display: flex;
    gap: 16px;
    padding: 18px 24px;
    border-bottom: 1px solid #f5f7f6;
    transition: background 0.15s;
  }
  .activity-item:last-child { border-bottom: none; }
  .activity-item:hover { background: #fafdfb; }

  .activity-icon {
    width: 40px; height: 40px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .empty-state {
    text-align: center; padding: 72px 24px;
    background: linear-gradient(180deg, #fafdfb 0%, #f5f7f6 100%);
  }
  .empty-icon {
    width: 72px; height: 72px; border-radius: 20px; margin: 0 auto 20px;
    background: linear-gradient(135deg, #f5f5f5, #f0f0f0);
    border: 1px solid #e5e5e5;
    display: flex; align-items: center; justify-content: center;
  }
</style>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">My Activity</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">Your complete history — cost quotations generated, crops planted, and more.</p>
</div>

@php
  $totalQuotations = $activities->where('type', 'quotation.created')->count();
  $totalCrops = $activities->whereIn('type', ['crop.planted', 'crop.updated', 'crop.harvested'])->count();
  $totalDeleted = $activities->where('type', 'quotation.deleted')->count();
@endphp

<div class="stats-row">
  <div class="stat-pill green">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalQuotations }}</div>
      <div class="stat-lbl">Quotations Generated</div>
    </div>
  </div>
  <div class="stat-pill amber">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalCrops }}</div>
      <div class="stat-lbl">Crop Activities</div>
    </div>
  </div>
  <div class="stat-pill red">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalDeleted }}</div>
      <div class="stat-lbl">Quotations Deleted</div>
    </div>
  </div>
</div>

<!-- Activity Timeline -->
<div class="timeline-card">
  @if($activities->count() > 0)
    @foreach($activities as $activity)
      <div class="activity-item">
        <div class="activity-icon" style="background: {{ $activity->bg }}; color: {{ $activity->color }};">
          {!! $activity->icon !!}
        </div>
        <div style="flex: 1; min-width: 0;">
          <div style="font-size: 14px; color: #1a2b1e; line-height: 1.5; font-weight: 500;">{{ $activity->description }}</div>
          <div style="font-size: 12px; color: #b0b8b3; margin-top: 3px; display: flex; align-items: center; gap: 6px;">
            <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ $activity->created_at->diffForHumans() }} &middot;
            {{ $activity->created_at->format('M d, Y \a\t g:i A') }}
          </div>
        </div>
      </div>
    @endforeach

    <div style="padding: 18px 24px; border-top: 1px solid #f0f2f1; text-align: center; background: #fafdfb;">
      {{ $activities->links() }}
    </div>
  @else
    <div class="empty-state">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" width="32" height="32" stroke="#b0b8b3" stroke-width="1.5" fill="none">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <p style="font-size: 18px; font-weight: 700; color: #1a2b1e; margin: 0 0 6px 0;">No Activity Yet</p>
      <p style="font-size: 14px; color: #879a8c; margin: 0; line-height: 1.5;">Start generating cost quotations or planting crops to see your activity here.</p>
    </div>
  @endif
</div>
@endsection
