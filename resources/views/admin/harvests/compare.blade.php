@extends('layouts.sidebar')

@section('title', 'Estimate vs Actual Harvest')

@section('content')

  <div class="header-panel" style="margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0;">Estimate vs Actual Harvest</h1>
      <p style="font-size: 13px; color: #64748b; margin: 5px 0 0 0; max-width: 680px; line-height: 1.55;">
        Association-wide yield estimates recorded at planting against the actual harvest farmers reported. Filter by year, season, crop or farmer.
      </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="{{ route('admin.harvests.reports') }}" class="btn-act ghost" style="appearance:none; display:inline-flex; align-items:center; gap:6px; padding: 9px 15px; border-radius: 9px; font-size: 12.5px; font-weight: 700; text-decoration:none; background:#f8fafc; color:#475569; border:1.5px solid #e2e8f0;">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        All Harvest Reports
      </a>
      <a href="{{ route('admin.harvests.ai') }}" style="display:inline-flex; align-items:center; gap:6px; padding: 9px 15px; border-radius: 9px; font-size: 12.5px; font-weight: 700; text-decoration:none; background:#f5f3ff; color:#6d28d9; border:1.5px solid #ddd6fe;">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
        AI Insights
      </a>
    </div>
  </div>

  @include('partials.harvest-compare', [
    'compareRoute' => 'admin.harvests.compare',
    'isAdmin' => true,
  ])
@endsection
