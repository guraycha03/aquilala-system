@extends('layouts.sidebar')

@section('title', 'Planting Requests')

@section('content')
<style>
  .pr-header { margin-bottom: 20px; }
  .pr-header h1 { font-size: 24px; font-weight: 700; color: #1e2922; margin: 0; }
  .pr-header p { font-size: 13px; color: #64748b; margin: 5px 0 0; line-height: 1.55; }

  /* Quiet text filter replaces the old KPI cards. Counts stay muted;
     only the active status carries the theme green. */
  .pr-filters { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 16px; }
  .pr-filter {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; border-radius: 999px;
    border: 1px solid #dbe5dd; background: #ffffff; color: #4b5c50;
    font-size: 12px; font-weight: 600; text-decoration: none;
  }
  .pr-filter:hover { border-color: #4A7A54; color: #2f5d3f; }
  .pr-filter.is-active { background: #4A7A54; border-color: #4A7A54; color: #ffffff; }
  .pr-filter-count { opacity: 0.7; font-weight: 500; }

  .pr-table-wrap { background: #ffffff; border: 1px solid #e5ebe7; border-radius: 12px; overflow: hidden; }

  .pr-crop { display: flex; align-items: center; gap: 10px; }
  .pr-crop-name { font-weight: 700; color: #1a2b1e; }
  .pr-crop-sub { font-size: 12px; color: #94a3b8; margin-top: 1px; }

  .pr-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 700; color: #4b5c50;
  }
  .pr-badge.pending, .pr-badge.confirmed { color: #2f5d3f; }
  .pr-badge.rejected { color: #6b7280; }
  .pr-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
  .pr-season { font-size: 12.5px; font-weight: 600; color: #64748b; }
  .pr-remarks { font-size: 12px; color: #64748b; margin-top: 4px; max-width: 260px; }

  .btn-act {
    appearance: none; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px; padding: 7px 12px; border-radius: 8px;
    border: 1px solid transparent; font-size: 12.5px; font-weight: 600;
    text-decoration: none; font-family: inherit;
  }
  .btn-act.ok { background: #4A7A54; border-color: #4A7A54; color: #ffffff; }
  .btn-act.ok:hover { background: #3c6644; }
  .btn-act.no { background: #ffffff; border-color: #dbe5dd; color: #4b5c50; }
  .btn-act.no:hover { background: #f4f8f5; }
  .btn-act.ghost { background: #ffffff; border-color: #cbd9cd; color: #2f5d3f; }
  .btn-act.ghost:hover { background: #f4f8f5; }

  .pr-empty { text-align: center; padding: 54px 20px; color: #64748b; }
  .pr-empty svg { margin-bottom: 10px; color: #4A7A54; }
  .pr-empty b { display: block; color: #334155; font-size: 15px; margin-bottom: 4px; }

  .pr-modal-overlay {
    position: fixed; inset: 0; z-index: 60; display: none;
    align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.35); padding: 24px;
  }
  .pr-modal-overlay.open { display: flex; }
  .pr-modal {
    background: #ffffff; border-radius: 12px; width: 100%; max-width: 440px;
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18); overflow: hidden;
  }
  .pr-modal-head { padding: 18px 20px 0; }
  .pr-modal-title { font-size: 16px; font-weight: 700; color: #1e2922; margin: 0 0 4px; }
  .pr-modal-sub { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.5; }
  .pr-modal-body { padding: 16px 20px 20px; }
  .pr-modal-body label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; }
  .pr-modal-body textarea {
    width: 100%; border: 1px solid #dbe5dd; border-radius: 9px; padding: 10px 12px;
    font-size: 13.5px; font-family: inherit; resize: vertical; min-height: 84px;
    box-sizing: border-box; color: #0f172a;
  }
  .pr-modal-body textarea:focus { outline: none; border-color: #4A7A54; }
  .pr-modal-foot { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }

  .pr-table tbody tr:target > td { background: #f4f8f5; }
  .pr-table tbody tr:target > td:first-child { box-shadow: inset 3px 0 0 #4A7A54; }

  @media (max-width: 760px) {
    .pr-table thead { display: none; }
    .pr-table, .pr-table tbody, .pr-table tr, .pr-table td { display: block; width: 100%; }
    .pr-table tr { border-bottom: 6px solid #f4f8f5; }
    .pr-table tbody td { border-bottom: none; padding: 6px 16px; }
    .pr-table tbody td:first-child { padding-top: 14px; }
    .pr-table tbody td:last-child { padding-bottom: 14px; }
  }
</style>

  <div class="pr-header">
    <div>
      <h1>Planting Requests</h1>
      <p>Confirm farmer crop batches before their growth tracking and harvest reporting unlock.</p>
    </div>
    <nav class="pr-filters" aria-label="Filter planting requests by status">
      @foreach([
          'pending' => ['label' => 'Pending', 'count' => $stats['pending']],
          'confirmed' => ['label' => 'Confirmed', 'count' => $stats['confirmed']],
          'rejected' => ['label' => 'Rejected', 'count' => $stats['rejected']],
          'all' => ['label' => 'All', 'count' => $stats['total']],
      ] as $statusKey => $filter)
        <a href="{{ route('admin.plantings.index', ['status' => $statusKey]) }}"
           class="pr-filter {{ $status === $statusKey ? 'is-active' : '' }}"
           @if($status === $statusKey) aria-current="page" @endif>
          {{ $filter['label'] }} <span class="pr-filter-count">{{ $filter['count'] }}</span>
        </a>
      @endforeach
    </nav>
  </div>{{-- Status summary — each card doubles as the filter for its status. --}}
<x-table-toolbar
    uid="plantings"
    work-table="plantingsTable"
    per-page="10"
    noun="plantings"
    search-placeholder="Search crop, batch, or farmer…"
    :sorts="[
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
        ['key' => 'crop', 'dir' => -1, 'type' => 'str', 'label' => 'Crop (Z–A)'],
        ['key' => 'planted', 'dir' => -1, 'type' => 'date', 'label' => 'Planted (newest)'],
        ['key' => 'planted', 'dir' => 1, 'type' => 'date', 'label' => 'Planted (oldest)'],
        ['key' => 'farmer', 'dir' => 1, 'type' => 'str', 'label' => 'Farmer (A–Z)'],
    ]"
  />

<div class="pr-table-wrap">
  @if($crops->count())
    <table id="plantingsTable" class="pr-table data-table">
      <thead>
        <tr>
          <th>Crop Batch</th>
          <th>Farmer</th>
          <th>Land Area</th>
          <th>Season</th>
          <th>Planted</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($crops as $crop)
          <tr id="planting-{{ $crop->id }}"
              data-crop="{{ strtolower($crop->crop?->name ?? '') }}"
              data-farmer="{{ strtolower($crop->user?->name ?? '') }}"
              data-planted="{{ $crop->planting_date?->format('Y-m-d') ?? '' }}">
            <td>
              <div class="pr-crop">
                <div>
                  <div class="pr-crop-name">{{ $crop->crop?->name ?? 'Unknown crop' }}</div>
                  <div class="pr-crop-sub">
                    {{ $crop->batch_name ?? 'Unnamed batch' }}
                    @if($crop->variety) &middot; {{ $crop->variety }} @endif
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-weight: 600; color: #334155;">{{ $crop->user?->name ?? '—' }}</div>
              <div class="pr-crop-sub">{{ $crop->user?->email }}</div>
            </td>
            <td>{{ rtrim(rtrim(number_format((float) $crop->land_area, 2, '.', ''), '0'), '.') }} {{ $crop->land_area_unit === 'ha' ? 'ha' : $crop->land_area_unit }}</td>
            <td><span class="pr-season">{{ ucfirst($crop->season ?? '—') }}</span></td>
            <td>{{ $crop->planting_date?->format('M d, Y') ?? '—' }}</td>
            <td>
              <span class="pr-badge {{ $crop->confirmation_status }}">
                <span class="pr-dot"></span>
                {{ $crop->confirmation_status_label }}
              </span>
              @if(!$crop->isConfirmed() && $crop->admin_remarks)
                <div class="pr-remarks">Note: {{ $crop->admin_remarks }}</div>
              @endif
              @if($crop->confirmedBy)
                <div class="pr-crop-sub">by {{ $crop->confirmedBy->name }}{{ $crop->confirmed_at ? ' · ' . $crop->confirmed_at->format('M d, Y') : '' }}</div>
              @endif
            </td>
            <td style="text-align: right; white-space: nowrap;">
              @if($crop->isPending())
                <form action="{{ route('admin.plantings.confirm', $crop) }}" method="POST" style="display: inline;">
                  @csrf
                  <button type="submit" class="btn-act ok" title="Confirm this planting">
                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm
                  </button>
                </form>
                <button type="button" class="btn-act no" title="Reject this planting"
                        onclick='openRejectModal({{ $crop->id }}, @json($crop->crop?->name ?? 'Unknown crop'), @json($crop->batch_name ?? ""))'>
                  <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  Reject
                </button>
              @elseif($crop->isRejected())
                <form action="{{ route('admin.plantings.confirm', $crop) }}" method="POST" style="display: inline;">
                  @csrf
                  <button type="submit" class="btn-act ghost" title="Overturn the rejection and confirm this planting">
                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm Anyway
                  </button>
                </form>
              @else
                <span class="pr-crop-sub" style="white-space: nowrap;">{{ $crop->confirmed_at ? $crop->confirmed_at->format('M d, Y') : 'Confirmed' }}</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <div class="pr-empty">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <b>{{ $status === 'pending' ? 'No planting requests awaiting review' : 'Nothing matches this filter' }}</b>
      {{ $status === 'pending' ? 'New farmer crop batches appear here for confirmation.' : 'Try another status filter above.' }}
    </div>
  @endif
</div>

{{-- Pagination is handled inside .pr-table-wrap by the shared table toolbar. --}}

{{-- ── Reject modal (optional remarks) ── --}}
<div class="pr-modal-overlay" id="rejectModal" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
  <div class="pr-modal">
    <div class="pr-modal-head">
      <p class="pr-modal-title" id="rejectTitle">Reject planting request</p>
      <p class="pr-modal-sub">Rejecting <b id="rejectBatchName">this batch</b> locks it for the farmer. They can remove it and submit a new request.</p>
    </div>
    <form method="POST" id="rejectForm" action="">
      @csrf
      <div class="pr-modal-body">
        <label for="rejectRemarks">Remarks (optional)</label>
        <textarea id="rejectRemarks" name="admin_remarks" maxlength="255" placeholder="e.g. Land area exceeds the farmer's declared plot — please split the batch."></textarea>
        <div class="pr-modal-foot">
          <button type="button" class="btn-act ghost" id="rejectCancel">Cancel</button>
          <button type="submit" class="btn-act no">
            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Reject Planting
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    function modal() { return document.getElementById('rejectModal'); }
    function form() { return document.getElementById('rejectForm'); }
    function remarks() { return document.getElementById('rejectRemarks'); }
    function batchName() { return document.getElementById('rejectBatchName'); }

    function closeRejectModal() {
      var box = modal();
      if (!box) return;
      box.classList.remove('open');
      document.body.style.overflow = '';
    }

    window.openRejectModal = function (id, cropName, batch) {
      var f = form();
      if (!f) return;
      f.action = @json(url('/admin/plantings')) + '/' + id + '/reject';
      batchName().textContent = cropName + (batch ? ' (' + batch + ')' : '');
      remarks().value = '';
      modal().classList.add('open');
      document.body.style.overflow = 'hidden';
      remarks().focus();
    };

    if (!window.__aqPlantingRejectBound) {
      window.__aqPlantingRejectBound = true;
      document.addEventListener('click', function (e) {
        var el = e.target;
        if (el && el.closest && el.closest('#rejectCancel')) {
          e.preventDefault();
          closeRejectModal();
          return;
        }
        if (el === modal()) closeRejectModal();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeRejectModal();
      });
    }
  })();
</script>
@endsection
