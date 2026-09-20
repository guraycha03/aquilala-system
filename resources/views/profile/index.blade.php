@extends('layouts.sidebar')

@section('title', 'My Profile')

@section('content')
@php
  $me = auth()->user();
  $words = preg_split('/\s+/', trim($me->name ?? ''));
  $initials = count($words) >= 2
      ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1)
      : mb_substr($me->name ?? '?', 0, 2);
@endphp

<style>
  .profile-wrap { max-width: 860px; }

  .profile-hero {
    display: flex; align-items: center; gap: 20px;
    background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55);
    border-radius: 18px; box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    padding: 24px;
  }

  .profile-hero-avatar {
    width: 72px; height: 72px; flex-shrink: 0;
    background: #1b4d2e; color: #c8e6c9; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 26px; text-transform: uppercase; letter-spacing: 0.04em;
  }

  .profile-hero-info { min-width: 0; }
  .profile-hero-info h1 { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0; letter-spacing: -0.01em; }
  .profile-hero-role { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 700; text-transform: capitalize; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 3px 12px; border-radius: 999px; letter-spacing: 0.05em; }
  .profile-hero-meta { margin-top: 10px; font-size: 12px; color: #64748b; }

  .profile-card-panel {
    margin-top: 18px;
    background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55);
    border-radius: 18px; box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    padding: 22px;
  }

  .profile-section-title {
    font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
    color: #94a3b8; margin: 0 0 14px;
  }

  .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; }
  .profile-card-panel + .profile-card-panel .profile-grid { margin-top: 0; }

  .p-item { padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #eef1f4; }
  .p-item.wide { grid-column: 1 / -1; }
  .p-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 4px; }
  .p-value { font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.45; word-break: break-word; }
  .p-value.muted { color: #94a3b8; font-weight: 500; }

  .p-status { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; letter-spacing: 0.03em; }
  .p-status.active { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
  .p-status.pending { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
  .p-status.disabled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

  .field-row { grid-column: 1 / -1; display: flex; gap: 14px; justify-content: flex-end; align-items: center; }

  @media (max-width: 640px) {
    .profile-hero { flex-direction: column; text-align: center; }
    .profile-grid { grid-template-columns: 1fr; }
    .p-item.wide { grid-column: 1 / -1; }
  }
</style>

<div class="profile-wrap">
  <div class="profile-hero">
    <div class="profile-hero-avatar">{{ $initials }}</div>
    <div class="profile-hero-info">
      <h1>{{ $me->name }}</h1>
      <span class="profile-hero-role">{{ str_replace('_', ' ', $me->role) }}</span>
      <div class="profile-hero-meta">
        Joined {{ optional($me->approved_at ?? $me->created_at)->format('M d, Y') ?? '—' }}
        &middot; {{ $me->email }}
      </div>
    </div>
  </div>

  <div class="profile-card-panel">
    <div class="profile-section-title">Account</div>
    <div class="profile-grid">
      <div class="p-item"><div class="p-label">Full Name</div><div class="p-value">{{ $me->name }}</div></div>
      <div class="p-item"><div class="p-label">Role</div><div class="p-value">{{ ucwords(str_replace('_', ' ', $me->role)) }}</div></div>
      <div class="p-item"><div class="p-label">Status</div>
        <div class="p-value"><span class="p-status {{ $me->status }}">{{ str_replace('_', ' ', $me->status) }}</span></div>
      </div>
      <div class="p-item"><div class="p-label">Email</div><div class="p-value">{{ $me->email ?: '—' }}</div></div>
      <div class="p-item"><div class="p-label">Phone</div><div class="p-value">{{ $me->phone ?: '—' }}</div></div>
      @if($me->sex)
      <div class="p-item"><div class="p-label">Sex</div><div class="p-value">{{ ucwords($me->sex) }}</div></div>
      @endif
      @if($me->birth_date)
      <div class="p-item"><div class="p-label">Birth Date</div><div class="p-value">{{ $me->birth_date->format('M d, Y') }}</div></div>
      @endif
      @if($me->civil_status)
      <div class="p-item"><div class="p-label">Civil Status</div><div class="p-value">{{ ucwords($me->civil_status) }}</div></div>
      @endif
    </div>
  </div>

  @if($me->role === 'store_owner')
    <div class="profile-card-panel">
      <div class="profile-section-title">Store</div>
      <div class="profile-grid">
        <div class="p-item wide"><div class="p-label">Store Name</div><div class="p-value">{{ $me->store_name ?: '—' }}</div></div>
      </div>
    </div>
  @elseif($me->role === 'farmer')
    <div class="profile-card-panel">
      <div class="profile-section-title">Farming Profile</div>
      <div class="profile-grid">
        @if($me->assoc_member_no)
        <div class="p-item"><div class="p-label">Association Member No.</div><div class="p-value">{{ $me->assoc_member_no }}</div></div>
        @endif
        @if($me->farm_location)
        <div class="p-item wide"><div class="p-label">Farm Location</div><div class="p-value">{{ $me->farm_location }}</div></div>
        @endif
        @if($me->land_ownership)
        <div class="p-item"><div class="p-label">Land Ownership</div><div class="p-value">{{ ucwords($me->land_ownership) }}</div></div>
        @endif
        @if($me->years_farming)
        <div class="p-item"><div class="p-label">Years Farming</div><div class="p-value">{{ $me->years_farming }} {{ (int) $me->years_farming === 1 ? 'year' : 'years' }}</div></div>
        @endif
        @if($me->default_land_area)
        <div class="p-item"><div class="p-label">Default Land Area</div><div class="p-value">{{ $me->default_land_area }} {{ $me->default_land_area_unit ?: '' }}</div></div>
        @endif
      </div>
    </div>
  @elseif($me->role === 'admin')
    <div class="profile-card-panel">
      <div class="profile-section-title">Organization</div>
      <div class="profile-grid">
        <div class="p-item wide"><div class="p-label">Organization</div><div class="p-value">Aquilala Irrigators Association, Inc.</div></div>
        <div class="p-item wide"><div class="p-label">Station</div><div class="p-value">San Francisco RIS &middot; Brgy. Aquino, Bulan, Sorsogon</div></div>
        <div class="p-item"><div class="p-label">SEC Registration</div><div class="p-value">CN201228664</div></div>
        <div class="p-item"><div class="p-label">IA President</div><div class="p-value">Ana V. Lacsa</div></div>
      </div>
    </div>
  @endif

  <div class="profile-card-panel">
    <div class="profile-section-title">Address</div>
    <div class="profile-grid">
      @php $addr = trim(implode(', ', array_filter([$me->barangay, $me->city, $me->province]))); @endphp
      <div class="p-item wide"><div class="p-label">Address</div><div class="p-value">{{ $addr ?: '—' }}</div></div>
      @if($me->barangay)
      <div class="p-item"><div class="p-label">Barangay / District</div><div class="p-value">{{ $me->barangay }}</div></div>
      @endif
      @if($me->city)
      <div class="p-item"><div class="p-label">City / Municipality</div><div class="p-value">{{ $me->city }}</div></div>
      @endif
      @if($me->province)
      <div class="p-item"><div class="p-label">Province</div><div class="p-value">{{ $me->province }}</div></div>
      @endif
    </div>
  </div>
</div>
@endsection