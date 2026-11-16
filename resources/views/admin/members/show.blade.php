@extends('layouts.sidebar')

@section('title', 'Member Details')

@section('content')
  <style>
    .member-show { max-width: 980px; }
    .ms-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .ms-identity { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .ms-name { font-size: 24px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.2; }
    .ms-sub { font-size: 13px; color: #64748b; margin: 4px 0 0; }
    .ms-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .ms-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid #d8e3db; color: #334c3e; background: #fff; }
    .ms-btn.primary { background: #14532d; color: #f4faf5; border-color: rgba(255,255,255,0.1); }
    .ms-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .ms-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 820px) { .ms-grid { grid-template-columns: 1fr; } }
    .ms-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; }
    .ms-card h2 { font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6b7f71; margin: 0 0 14px; }
    .ms-dl { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; margin: 0; }
    .ms-dl.full { grid-template-columns: 1fr; }
    .ms-item { min-width: 0; }
    .ms-item dt { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin: 0 0 3px; }
    .ms-item dd { font-size: 14px; color: #1e293b; margin: 0; line-height: 1.45; word-break: break-word; }
    .ms-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .ms-badge.active { background: #eef6ef; color: #2f6b3a; }
    .ms-badge.inactive { background: #fdf2f2; color: #b91c1c; }
    .ms-badge.linked { background: #eef3ef; color: #3f7a4f; }
    .ms-badge.none { background: #fbf7ee; color: #8a6d34; }
    .ms-note { white-space: pre-wrap; }
    .ms-land { font-weight: 700; color: #14532d; }
  </style>

  <div class="member-show">
    <x-breadcrumb :items="[
      ['label' => 'Members', 'url' => route('admin.members.index')],
      ['label' => $member->fullName() ?: 'Member Details'],
    ]" />

    <div class="ms-head">
      <div class="ms-identity">
        <x-user-avatar :user="$member" :size="52" />
        <div>
          <h1 class="ms-name">{{ $member->fullName() ?: 'Member' }}</h1>
          <p class="ms-sub">{{ $member->assoc_member_no ? 'Member No. ' . $member->assoc_member_no : 'No member number recorded' }}</p>
        </div>
      </div>
      <div class="ms-actions">
        <a href="{{ route('admin.members.edit', $member) }}" class="ms-btn primary">
          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Member
        </a>
        <a href="{{ route('admin.members.index') }}" class="ms-btn">Back to Members</a>
      </div>
    </div>

    <div class="ms-grid">
      <section class="ms-card">
        <h2>Identity & Status</h2>
        <dl class="ms-dl">
          <div class="ms-item"><dt>Full Name</dt><dd>{{ $member->fullName() ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Member No.</dt><dd>{{ $member->assoc_member_no ?: '—' }}</dd></div>
          <div class="ms-item"><dt>First Name</dt><dd>{{ $member->first_name ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Middle Name</dt><dd>{{ $member->middle_initial ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Last Name</dt><dd>{{ $member->last_name ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Sex</dt><dd>{{ $member->sex ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Birth Date</dt><dd>{{ $member->birth_date ? $member->birth_date->format('M d, Y') : '—' }}</dd></div>
          <div class="ms-item"><dt>Civil Status</dt><dd>{{ $member->civil_status ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Member Status</dt><dd><span class="ms-badge {{ $member->status === 'inactive' ? 'inactive' : 'active' }}">{{ $member->status === 'inactive' ? 'Inactive' : 'Active' }}</span></dd></div>
          <div class="ms-item"><dt>System Account</dt><dd>
            @if($member->user)
              <span class="ms-badge linked">{{ $member->user->role === 'store_owner' ? 'Store Owner' : 'Farmer' }}</span>
              <div style="margin-top:6px;font-size:13px;color:#475569;">{{ $member->user->name }} · {{ $member->user->email }}</div>
            @else
              <span class="ms-badge none">No account</span>
            @endif
          </dd></div>
        </dl>
      </section>

      <section class="ms-card">
        <h2>Contact</h2>
        <dl class="ms-dl">
          <div class="ms-item" style="grid-column:1/-1;"><dt>Email Address</dt><dd>{{ $member->email ?: '—' }}</dd></div>
          <div class="ms-item" style="grid-column:1/-1;"><dt>Mobile Number</dt><dd>{{ $member->phone ?: '—' }}</dd></div>
          <div class="ms-item" style="grid-column:1/-1;"><dt>Address</dt><dd>{{ $member->addressLine() ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Street / House No.</dt><dd>{{ $member->address ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Barangay</dt><dd>{{ $member->barangay ?: '—' }}</dd></div>
          <div class="ms-item"><dt>City / Municipality</dt><dd>{{ $member->city ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Province</dt><dd>{{ $member->province ?: '—' }}</dd></div>
        </dl>
      </section>

      <section class="ms-card">
        <h2>Farming & Land</h2>
        <dl class="ms-dl">
          <div class="ms-item" style="grid-column:1/-1;"><dt>Total Land Area</dt><dd class="ms-land">
            @if($member->land_area !== null)
              {{ number_format((float) $member->land_area, 2) }} {{ $member->land_area_unit === 'sq_m' ? 'm²' : ($member->land_area_unit === 'sq_ft' ? 'ft²' : 'ha') }}
              <span style="font-weight:500;color:#64748b;font-size:12px;margin-left:6px;">(admin-set, synced to member account/capacity)</span>
            @else
              Not set yet
              <span style="font-weight:500;color:#64748b;font-size:12px;margin-left:6px;">(system uses default 1.00 ha fallback for safety until set)</span>
            @endif
          </dd></div>
          <div class="ms-item"><dt>Land Ownership</dt><dd>{{ $member->land_ownership ?: '—' }}</dd></div>
          <div class="ms-item"><dt>Years Farming</dt><dd>{{ $member->years_farming !== null ? $member->years_farming . ' year' . ($member->years_farming == 1 ? '' : 's') : '—' }}</dd></div>
          <div class="ms-item" style="grid-column:1/-1;"><dt>Farm Location</dt><dd>{{ $member->farm_location ?: '—' }}</dd></div>
        </dl>
      </section>

      <section class="ms-card">
        <h2>Record Details</h2>
        <dl class="ms-dl">
          <div class="ms-item"><dt>Record ID</dt><dd>#{{ $member->id }}</dd></div>
          <div class="ms-item"><dt>Created</dt><dd>{{ $member->created_at ? $member->created_at->format('M d, Y g:i A') : '—' }}</dd></div>
          <div class="ms-item"><dt>Last Updated</dt><dd>{{ $member->updated_at ? $member->updated_at->format('M d, Y g:i A') : '—' }}</dd></div>
          <div class="ms-item"><dt>Created By</dt><dd>{{ $member->createdBy?->name ?: '—' }}</dd></div>
          <div class="ms-item" style="grid-column:1/-1;"><dt>Notes (admin only)</dt><dd class="ms-note">{{ $member->notes ?: '—' }}</dd></div>
        </dl>
      </section>
    </div>
  </div>
@endsection