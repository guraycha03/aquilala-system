@extends('layouts.sidebar')

@section('title', 'Recent Activity')

@section('content')
<style>
  .activity-item {
    display: flex;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
  }
  .activity-item:last-child { border-bottom: none; }
  .activity-item:hover { background: #fafbfa; }
  .activity-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .activity-meta {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
  }
  .activity-desc {
    font-size: 14px;
    color: #1e2922;
    line-height: 1.5;
  }
  .user-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 600;
    color: #166534;
  }
  .user-chip .chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #16a34a;
  }
  /* Filter bar uses the shared .table-toolbar design (layouts.sidebar). */
</style>

<div class="header-panel" style="margin-bottom: 10px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Recent Activity</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">All user actions across the system — quotations, crops, products, and more.</p>
</div>

<!-- Filters (shared .table-toolbar design — auto-applies on change) -->
<form method="GET" class="table-toolbar">
  <select name="user_id" aria-label="User" onchange="this.form.submit()"
          style="height:42px;min-width:200px;padding:0 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:500;color:#1e293b;background:#ffffff;font-family:inherit;outline:none;cursor:pointer;transition:all .2s ease;box-sizing:border-box;">
    <option value="">All Users</option>
    @foreach($users as $u)
      <option value="{{ $u->id }}" {{ $userFilter == $u->id ? 'selected' : '' }}>
        {{ $u->name }} ({{ ucfirst(str_replace('_', ' ', $u->role)) }})
      </option>
    @endforeach
  </select>

  <select name="type" aria-label="Activity type" onchange="this.form.submit()"
          style="height:42px;min-width:200px;padding:0 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:500;color:#1e293b;background:#ffffff;font-family:inherit;outline:none;cursor:pointer;transition:all .2s ease;box-sizing:border-box;">
    <option value="">All Activity Types</option>
    @foreach($activityTypes as $type)
      <option value="{{ $type }}" {{ $typeFilter === $type ? 'selected' : '' }}>
        {{ str_replace('.', ' → ', ucfirst(str_replace('_', ' ', $type))) }}
      </option>
    @endforeach
  </select>

  @if($userFilter || $typeFilter)
    <a href="{{ route('admin.activity.index') }}" class="btn-clear">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      Clear
    </a>
  @endif
</form>

<!-- Activity Timeline -->
<div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
  <div data-paginated>
  @if($activities->count() > 0)
    @foreach($activities as $activity)
      <div class="activity-item">
        <div class="activity-icon" style="background: {{ $activity->bg }}; color: {{ $activity->color }};">
          {!! $activity->icon !!}
        </div>
        <div style="flex: 1; min-width: 0;">
          <div class="activity-desc">
            <span class="user-chip">
              <span class="chip-dot"></span>
              {{ $activity->actor_name }}
            </span>
            @if($activity->actor && $activity->user && $activity->actor->id !== $activity->user->id)
              <span class="user-chip" style="background:#f1f5f9;border-color:#e2e8f0;color:#475569;" title="Notification recipient">
                &rarr; {{ $activity->user->name }}
              </span>
            @endif
            &nbsp;{{ $activity->description }}
          </div>
          <div class="activity-meta">
            {{ $activity->created_at->diffForHumans() }} &middot;
            {{ $activity->created_at->format('M d, Y \a\t g:i A') }}
            &middot; {{ $activity->typeLabel }}
            @if($activity->ip_address)
              &middot; {{ $activity->ip_address }}
            @endif
          </div>
        </div>
      </div>
    @endforeach

    <!-- Pagination -->
    {{ $activities->withQueryString()->links('partials.pagination') }}
  @else
    <div style="text-align: center; padding: 64px 20px; color: #94a3b8;">
      <svg viewBox="0 0 24 24" width="40" height="40" stroke="#cbd5e1" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <p style="font-size: 15px; font-weight: 600; color: #64748b; margin: 0 0 4px 0;">No Activity Found</p>
      <p style="font-size: 13px; margin: 0;">
        @if($userFilter || $typeFilter)
          No activities match your current filters. Try adjusting the filters above.
        @else
          No user activities have been recorded yet.
        @endif
      </p>
    </div>
  @endif
  </div>
</div>
@endsection

@push('scripts')
  @include('partials.ajax-pagination')
@endpush
