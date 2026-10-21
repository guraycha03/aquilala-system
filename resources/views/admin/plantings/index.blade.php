@extends('layouts.sidebar')

@section('title', 'Planting Requests')

@section('content')
<style>
  .stat-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px;
    padding: 18px 22px; display: flex; align-items: center; justify-content: space-between;
    transition: all 0.2s ease;
  }
  .stat-card.clickable:hover { border-color: #cbd5e1; box-shadow: 0 4px 14px rgba(15, 23, 42, .06); transform: translateY(-1px); }
  .stat-card.active-filter { border-color: #4A7A54; box-shadow: 0 0 0 2px rgba(22, 163, 74, .15); }
  .stat-value { font-size: 26px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

  /* Filter pills removed — status filtering lives on the KPI cards above,
     exactly like the Users page. Table base style = shared .data-table rules. */
  .pr-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }

  .pr-crop { display: flex; align-items: center; gap: 10px; }
  .pr-crop-ic {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    background: #edf1e9; color: #648268;
  }
  .pr-crop-name { font-weight: 700; color: #1a2b1e; }
  .pr-crop-sub { font-size: 12px; color: #94a3b8; margin-top: 1px; }

  .pr-badge {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px;
    border-radius: 20px; font-size: 11.5px; font-weight: 700; white-space: nowrap;
  }
  .pr-badge.pending  { background: #edf1e9; color: #648268; border: 1px dashed #f59e0b; }
  .pr-badge.confirmed { background: #edf1e9; color: #648268; }
  .pr-badge.rejected { background: #fef2f2; color: #dc2626; }

  .pr-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

  .btn-act {
    appearance: none; border: none; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px; padding: 7px 13px; border-radius: 9px;
    font-size: 12.5px; font-weight: 700; text-decoration: none;
    transition: all .15s ease;
  }
  .btn-act.ok { background: #4A7A54; color: #fff; box-shadow: 0 3px 8px rgba(22, 163, 74, .25); }
  .btn-act.ok:hover { background: #15803d; transform: translateY(-1px); }
  .btn-act.no { background: #fff; color: #b91c1c; border: 1.5px solid #fecaca; }
  .btn-act.no:hover { background: #fef2f2; }
  .btn-act.ghost { background: #edf1e9; color: #648268; border: 1.5px solid #e2e8f0; }
  .btn-act.ghost:hover { background: #f1f5f9; }

  .pr-remarks { font-size: 12px; color: #92400e; margin-top: 4px; max-width: 260px; }

  .pr-empty { text-align: center; padding: 54px 20px; color: #94a3b8; }
  .pr-empty svg { margin-bottom: 10px; }
  .pr-empty b { display: block; color: #475569; font-size: 15px; margin-bottom: 4px; }

  .pr-modal-overlay {
    position: fixed; inset: 0; z-index: 60; display: none;
    align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.45); padding: 24px;
  }
  .pr-modal-overlay.open { display: flex; }
  .pr-modal {
    background: #fff; border-radius: 14px; width: 100%; max-width: 460px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25); overflow: hidden;
    animation: prIn .18s ease-out;
  }
  @keyframes prIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
  .pr-modal-head { padding: 18px 20px 0; }
  .pr-modal-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
  .pr-modal-sub { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.5; }
  .pr-modal-body { padding: 16px 20px 20px; }
  .pr-modal-body label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; }
  .pr-modal-body textarea {
    width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;
    font-size: 13.5px; font-family: inherit; resize: vertical; min-height: 84px;
    box-sizing: border-box; color: #0f172a;
  }
  .pr-modal-body textarea:focus { outline: none; border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(22, 163, 74, .12); }
  .pr-modal-foot { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }

  @media (max-width: 760px) {
    .pr-table thead { display: none; }
    .pr-table, .pr-table tbody, .pr-table tr, .pr-table td { display: block; width: 100%; }
    .pr-table tr { border-bottom: 6px solid #f8fafc; }
    .pr-table tbody td { border-bottom: none; padding: 6px 16px; }
    .pr-table tbody td:first-child { padding-top: 14px; }
    .pr-table tbody td:last-child { padding-bottom: 14px; }
  }
</style>

<div class="header-panel" style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0;">Planting Requests</h1>
    <p style="font-size: 13px; color: #64748b; margin: 5px 0 0 0;">Confirm farmer crop batches before their growth tracking and harvest reporting unlock.</p>
  </div>
  <a href="{{ route('admin.operations.catalog') }}" class="btn-act ghost">
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
    Back to Crops &amp; Inputs
  </a>
</div>


{{-- Status summary — each card doubles as the filter for its status. --}}
<div class="kpi-strip">
  <a href="{{ route('admin.plantings.index', ['status' => 'pending']) }}"
     class="stat-card clickable {{ $status === 'pending' ? 'active-filter' : '' }}" style="text-decoration: none;">
    <div>
      <div class="stat-value">{{ $stats['pending'] }}</div>
      <div class="stat-label">Pending Confirmation</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#d97706" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
  </a>
  <a href="{{ route('admin.plantings.index', ['status' => 'confirmed']) }}"
     class="stat-card clickable {{ $status === 'confirmed' ? 'active-filter' : '' }}" style="text-decoration: none;">
    <div>
      <div class="stat-value">{{ $stats['confirmed'] }}</div>
      <div class="stat-label">Confirmed</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#4A7A54" stroke-width="1.5" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  </a>
  <a href="{{ route('admin.plantings.index', ['status' => 'rejected']) }}"
     class="stat-card clickable {{ $status === 'rejected' ? 'active-filter' : '' }}" style="text-decoration: none;">
    <div>
      <div class="stat-value">{{ $stats['rejected'] }}</div>
      <div class="stat-label">Rejected</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#dc2626" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
  </a>
  <a href="{{ route('admin.plantings.index', ['status' => 'all']) }}"
     class="stat-card clickable {{ $status === 'all' ? 'active-filter' : '' }}" style="text-decoration: none;">
    <div>
      <div class="stat-value">{{ $stats['total'] }}</div>
      <div class="stat-label">All Plantings</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </a>
</div>

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
          <tr data-crop="{{ strtolower($crop->crop?->name ?? '') }}"
              data-farmer="{{ strtolower($crop->user?->name ?? '') }}"
              data-planted="{{ $crop->planting_date?->format('Y-m-d') ?? '' }}">
            <td>
              <div class="pr-crop">
                <span class="pr-crop-ic">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                </span>
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
            <td><span style="display:inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; background: {{ $crop->season === 'wet' ? '#eff6ff; color: #1d4ed8' : '#fffbeb; color: #b45309' }};">{{ ucfirst($crop->season ?? '—') }}</span></td>
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
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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
  var rejectModal = document.getElementById('rejectModal');
  var rejectForm = document.getElementById('rejectForm');
  var rejectRemarks = document.getElementById('rejectRemarks');
  var rejectBatchName = document.getElementById('rejectBatchName');

  function openRejectModal(id, cropName, batchName) {
    rejectForm.action = @json(url('/admin/plantings')) + '/' + id + '/reject';
    rejectBatchName.textContent = cropName + (batchName ? ' (' + batchName + ')' : '');
    rejectRemarks.value = '';
    rejectModal.classList.add('open');
    document.body.style.overflow = 'hidden';
    rejectRemarks.focus();
  }

  function closeRejectModal() {
    rejectModal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.getElementById('rejectCancel').addEventListener('click', closeRejectModal);
  rejectModal.addEventListener('click', function (e) { if (e.target === rejectModal) closeRejectModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeRejectModal(); });
</script>
@endsection
