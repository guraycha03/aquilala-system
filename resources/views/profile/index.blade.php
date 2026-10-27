@extends('layouts.sidebar')

@section('title', 'My Profile')

@section('content')
@php
  $me = auth()->user();
@endphp

<style>
  .profile-wrap { max-width: 860px; }

  .profile-hero {
    display: flex; align-items: center; gap: 20px;
    background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55);
    border-radius: 18px; box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    padding: 24px;
    /* The picture's menu opens out of this card, so this card has to sit
       above the ones below it (the blur already makes it its own layer). */
    position: relative; z-index: 30;
  }

  /* The default avatar is a soft, light tile — not a dark green badge. */
  .profile-hero-avatar {
    width: 72px; height: 72px; flex-shrink: 0;
    background: #EDF1E9; color: #4A7A54; border: 1px solid #E0E5DB; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 24px; text-transform: uppercase; letter-spacing: 0.04em;
  }
  [data-theme="dark"] .profile-hero-avatar { background: #16301f; color: #a7f3d0; border-color: #334155; }

  /* The picture lives on the avatar itself: tap the picture and it
     offers what can be done with it. No separate card for it. */
  .profile-avatar-wrap { position: relative; flex-shrink: 0; }
  .profile-avatar-btn {
    display: block; padding: 0; border: none; background: none;
    border-radius: 50%; cursor: pointer;
  }
  .profile-avatar-btn:focus-visible { outline: 2px solid #648268; outline-offset: 3px; }

  .profile-photo-menu {
    position: absolute; top: calc(100% + 10px); left: 0; z-index: 40;
    min-width: 216px; padding: 6px;
    background: #ffffff; border: 1px solid #E0E5DB; border-radius: 14px;
    box-shadow: 0 16px 40px rgba(20, 61, 35, 0.16);
  }
  .profile-photo-menu[hidden] { display: none; }
  .profile-photo-menu__item {
    display: flex; align-items: center; gap: 9px; width: 100%;
    background: none; border: none; border-radius: 9px;
    padding: 9px 11px; text-align: left;
    font-size: 13px; font-weight: 600; font-family: inherit; color: #344B38;
    cursor: pointer; transition: background 0.15s ease;
  }
  .profile-photo-menu__item:hover { background: #F3F5F0; }
  .profile-photo-menu__item svg { width: 15px; height: 15px; flex-shrink: 0; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .profile-photo-menu__item--danger { color: #9b2c2c; }
  .profile-photo-menu__item--danger:hover { background: #FDF2F2; }
  [data-theme="dark"] .profile-photo-menu { background: #111827; border-color: #334155; }
  [data-theme="dark"] .profile-photo-menu__item { color: #e2e8f0; }
  [data-theme="dark"] .profile-photo-menu__item:hover { background: #1f2937; }
  [data-theme="dark"] .profile-photo-menu__item--danger { color: #fca5a5; }
  [data-theme="dark"] .profile-photo-menu__item--danger:hover { background: #3b1220; }

  /* The notice under the name: what happened to the picture, or why it
     was refused. It is only there when there is something to say — no
     standing reminder of what a picture picker already shows. */
  .profile-photo-notice {
    margin: 10px 0 0 0; font-size: 12.5px; line-height: 1.55;
    color: #5F6F62; max-width: 46ch;
  }
  .profile-photo-notice[hidden] { display: none; }
  .profile-photo-notice.is-error { color: #9b2c2c; }
  [data-theme="dark"] .profile-photo-notice { color: #9fb3a6; }
  [data-theme="dark"] .profile-photo-notice.is-error { color: #fca5a5; }

  /* The picture at full size, over the page. */
  .profile-photo-view {
    position: fixed; inset: 0; z-index: 1200;
    display: flex; align-items: center; justify-content: center;
    padding: 24px; background: rgba(15, 23, 42, 0.72);
  }
  .profile-photo-view[hidden] { display: none; }
  .profile-photo-view img {
    max-width: min(560px, 92vw); max-height: 86vh;
    border-radius: 16px; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
  }
  .profile-photo-view__close {
    position: absolute; top: 18px; right: 18px;
    width: 38px; height: 38px; border-radius: 50%; border: none; cursor: pointer;
    background: rgba(255, 255, 255, 0.92); color: #1e293b;
    font-size: 20px; line-height: 1; display: flex; align-items: center; justify-content: center;
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
  /* The grid's own `display` would beat the UA's [hidden] rule, so the
     hidden attribute needs saying here. */
  .profile-grid[hidden] { display: none; }
  .profile-card-panel + .profile-card-panel .profile-grid { margin-top: 0; }

  .p-item { padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #eef1f4; }
  .p-item.wide { grid-column: 1 / -1; }
  .p-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 4px; }
  .p-value { font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.45; word-break: break-word; }
  .p-value.muted { color: #94a3b8; font-weight: 500; }

  /* ── The address: the one part a member keeps current ─────────
     A small pencil opens the fields in place; saving posts them back
     and the page returns with the new address. */
  .profile-section-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 14px;
  }
  .profile-section-head .profile-section-title { margin-bottom: 0; }
  .p-edit__toggle {
    width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
    border: 1px solid #E0E5DB; background: #ffffff; color: #647263;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
  }
  .p-edit__toggle:hover { background: #F3F5F0; border-color: #C8D5C5; color: #143d23; }
  .p-edit__toggle svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  [data-theme="dark"] .p-edit__toggle { background: #1f2937; border-color: #334155; color: #9fb3a6; }
  [data-theme="dark"] .p-edit__toggle:hover { background: #16301f; border-color: #4ade80; color: #d6f5e3; }

  .p-input {
    width: 100%; box-sizing: border-box;
    padding: 9px 11px; border: 1px solid #E0E5DB; border-radius: 10px;
    background: #ffffff; font-family: inherit; font-size: 13.5px; color: #1e293b;
  }
  .p-input:focus {
    /* A soft, light edge on the field being typed in — the focus must
       read as a gentle lift, not a dark outline. */
    outline: none; border-color: #C4D0C4; box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.07);
  }
  [data-theme="dark"] .p-input { background: #0f172a; border-color: #334155; color: #e2e8f0; }

  /* Editing happens where the address already is: the value gives way
     to its own field, in the same row. */
  .p-item .p-input { display: none; margin-top: 2px; }
  .profile-card-panel.is-editing .p-item .p-value { display: none; }
  .profile-card-panel.is-editing .p-item .p-input { display: block; }

  .p-edit__actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px; }
  .p-edit__actions[hidden] { display: none; }
  .p-btn {
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    border-radius: 10px; padding: 9px 16px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    border: 1px solid #E0E5DB; background: #ffffff; color: #344B38;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .p-btn:hover { background: #F3F5F0; border-color: #C8D5C5; }
  .p-btn--primary { background: #3F6B4D; border-color: #3F6B4D; color: #ffffff; }
  .p-btn--primary:hover { background: #375F43; border-color: #375F43; }
  [data-theme="dark"] .p-btn { background: #1f2937; border-color: #334155; color: #d6f5e3; }
  [data-theme="dark"] .p-btn--primary { background: #1a5230; border-color: #1a5230; color: #ffffff; }

  /* The confirmation before anything is saved. */
  .p-confirm {
    position: fixed; inset: 0; z-index: 1300;
    display: flex; align-items: center; justify-content: center;
    padding: 24px; background: rgba(15, 23, 42, 0.55);
  }
  .p-confirm[hidden] { display: none; }
  .p-confirm__card {
    width: min(420px, 100%); background: #ffffff;
    border: 1px solid #E0E5DB; border-radius: 16px; padding: 22px 24px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.32);
  }
  .p-confirm__card h3 { font-size: 16px; font-weight: 700; color: #1e2922; margin: 0 0 8px 0; }
  .p-confirm__text { font-size: 13px; color: #647263; margin: 0 0 8px 0; }
  .p-confirm__address {
    font-size: 13.5px; font-weight: 700; color: #143d23; margin: 0;
    background: #F3F5F0; border: 1px solid #E0E5DB; border-radius: 10px;
    padding: 11px 13px; line-height: 1.5; word-break: break-word;
  }
  .p-confirm__actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
  [data-theme="dark"] .p-confirm__card { background: #111827; border-color: #334155; }
  [data-theme="dark"] .p-confirm__card h3 { color: #e2e8f0; }
  [data-theme="dark"] .p-confirm__text { color: #9fb3a6; }
  [data-theme="dark"] .p-confirm__address { background: #1f2937; border-color: #334155; color: #d6f5e3; }

  .profile-flash {
    display: flex; align-items: center; gap: 10px;
    background: #F1F6F1; border: 1px solid #D3E3D5; border-radius: 12px;
    padding: 12px 16px; margin-bottom: 18px;
    font-size: 13.5px; font-weight: 600; color: #3f6b4d;
  }
  [data-theme="dark"] .profile-flash { background: #16301f; border-color: #2f5d3c; color: #d6f5e3; }

  .p-status { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; letter-spacing: 0.03em; }
  .p-status.active { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
  .p-status.pending { background: #edf1e9; color: #648268; border: 1px solid #bfdbfe; }
  .p-status.disabled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

  .field-row { grid-column: 1 / -1; display: flex; gap: 14px; justify-content: flex-end; align-items: center; }

  @media (max-width: 640px) {
    .profile-hero { flex-direction: column; text-align: center; }
    .profile-grid { grid-template-columns: 1fr; }
    .p-item.wide { grid-column: 1 / -1; }
    /* The picture sits in the middle on a phone, so its menu does too. */
    .profile-photo-menu { left: 50%; transform: translateX(-50%); }
  }
</style>

<div class="profile-wrap">
  @if(session('success'))
    <div class="profile-flash">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      {{ session('success') }}
    </div>
  @endif

  <div class="profile-hero">
    {{-- The member's own picture. It follows the account everywhere it is
         shown, and removing it puts the account back on its initials. The
         picture at the top IS the control: tap it and it offers what can
         be done with it. --}}
    <div class="profile-avatar-wrap">
      <button type="button" class="profile-avatar-btn" id="profileAvatarBtn"
              aria-haspopup="menu" aria-expanded="false" aria-controls="profilePhotoMenu"
              data-photo="{{ $me->profilePhotoUrl() }}"
              title="Picture options">
        <x-user-avatar :user="$me" :size="72" class="profile-hero-avatar" />
      </button>

      <div class="profile-photo-menu" id="profilePhotoMenu" role="menu" hidden>
        <button type="button" class="profile-photo-menu__item" role="menuitem" id="profilePhotoView"
                @if(! $me->profilePhotoUrl()) hidden @endif>
          <svg viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
          View picture
        </button>

        <button type="button" class="profile-photo-menu__item" role="menuitem" id="profilePhotoUpload">
          <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span id="profilePhotoLabel">{{ $me->profile_photo ? 'Change picture' : 'Upload a picture' }}</span>
        </button>

        <button type="button" class="profile-photo-menu__item profile-photo-menu__item--danger" role="menuitem" id="profilePhotoRemove" @if(! $me->profile_photo) hidden @endif>
          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Remove picture
        </button>

        <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp" hidden>
      </div>
    </div>

    <div class="profile-hero-info">
      <h1>{{ $me->name }}</h1>
      <span class="profile-hero-role">{{ str_replace('_', ' ', $me->role) }}</span>
      <div class="profile-hero-meta">
        Joined {{ optional($me->approved_at ?? $me->created_at)->format('M d, Y') ?? '—' }}
        &middot; {{ $me->email }}
      </div>
      {{-- Only when there is something to say: what happened to the
           picture, or why it was refused. --}}
      <p class="profile-photo-notice" id="profilePhotoNotice" hidden></p>
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
  {{-- The address is the one part of the profile a member keeps
       current: where they farm or trade changes, and a stale address
       sends the association's people to the wrong place. Everything
       else here is the association's record and is changed from the
       admin side.

       Editing happens where the address already is: the pencil turns
       each value into its own field, and nothing is saved until the
       farmer confirms it. --}}
  <div class="profile-card-panel" id="addressCard">
    <div class="profile-section-head">
      <div class="profile-section-title">Address</div>
      @if($me->role !== 'admin')
        <button type="button" class="p-edit__toggle" id="addressEditBtn"
                aria-expanded="false" aria-controls="addressForm" title="Edit your address" aria-label="Edit your address">
          <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </button>
      @endif
    </div>

    <form method="POST" action="{{ route('profile.address.update') }}" id="addressForm">
      @csrf
      @method('PATCH')
      <div class="profile-grid" id="addressRows">
        @php $addr = trim(implode(', ', array_filter([$me->barangay, $me->city, $me->province]))); @endphp
        <div class="p-item wide">
          <div class="p-label">Address</div>
          <div class="p-value">{{ $me->address ?: '-' }}</div>
          <input class="p-input" name="address" type="text" maxlength="255"
                 value="{{ $me->address }}" placeholder="Street, purok, building">
        </div>
        <div class="p-item">
          <div class="p-label">Barangay / District</div>
          <div class="p-value">{{ $me->barangay ?: '-' }}</div>
          <input class="p-input" name="barangay" type="text" maxlength="255"
                 value="{{ $me->barangay }}" placeholder="e.g. Brgy. Aquino">
        </div>
        <div class="p-item">
          <div class="p-label">City / Municipality</div>
          <div class="p-value">{{ $me->city ?: '-' }}</div>
          <input class="p-input" name="city" type="text" maxlength="255"
                 value="{{ $me->city }}" placeholder="e.g. Bulan">
        </div>
        <div class="p-item">
          <div class="p-label">Province</div>
          <div class="p-value">{{ $me->province ?: '-' }}</div>
          <input class="p-input" name="province" type="text" maxlength="255"
                 value="{{ $me->province }}" placeholder="e.g. Sorsogon">
        </div>
      </div>

      <div class="p-edit__actions" id="addressActions" hidden>
        <button type="button" class="p-btn" id="addressCancelBtn">Cancel</button>
        <button type="button" class="p-btn p-btn--primary" id="addressSaveBtn">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Save address
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Nothing is saved until the farmer says so: the confirmation shows
     the address as it will be, and only then does the form go. --}}
<div class="p-confirm" id="addressConfirm" hidden>
  <div class="p-confirm__card" role="dialog" aria-modal="true" aria-labelledby="addressConfirmTitle">
    <h3 id="addressConfirmTitle">Save this address?</h3>
    <p class="p-confirm__text">Your address will be saved as:</p>
    <p class="p-confirm__address" id="addressConfirmText"></p>
    <div class="p-confirm__actions">
      <button type="button" class="p-btn" id="addressConfirmCancel">Keep editing</button>
      <button type="button" class="p-btn p-btn--primary" id="addressConfirmSave">Save address</button>
    </div>
  </div>
</div>

<div class="profile-photo-view" id="profilePhotoViewOverlay" hidden>
  <button type="button" class="profile-photo-view__close" id="profilePhotoViewClose" aria-label="Close">&times;</button>
  <img id="profilePhotoViewImg" alt="Profile picture">
</div>

<script>
  // The address: a pencil on the card turns each value into its own
  // field, in place. Nothing is saved until the farmer confirms it.
  (function () {
    var card = document.getElementById('addressCard');
    var editBtn = document.getElementById('addressEditBtn');
    var cancelBtn = document.getElementById('addressCancelBtn');
    var saveBtn = document.getElementById('addressSaveBtn');
    var form = document.getElementById('addressForm');
    var actions = document.getElementById('addressActions');
    var confirm = document.getElementById('addressConfirm');
    var confirmText = document.getElementById('addressConfirmText');
    var confirmSave = document.getElementById('addressConfirmSave');
    var confirmCancel = document.getElementById('addressConfirmCancel');
    if (!editBtn || !form || !card) return;

    // What the fields held when the farmer opened the editor: Cancel
    // puts them back exactly as they were.
    var original = {};

    function fields() {
      return Array.prototype.slice.call(form.querySelectorAll('.p-input'));
    }

    function setEditing(on) {
      card.classList.toggle('is-editing', on);
      if (actions) actions.hidden = !on;
      editBtn.setAttribute('aria-expanded', on ? 'true' : 'false');

      if (on) {
        fields().forEach(function (input) { original[input.name] = input.value; });
        var first = fields()[0];
        if (first) { first.focus(); }
      } else {
        fields().forEach(function (input) {
          if (original[input.name] !== undefined) input.value = original[input.name];
        });
      }
    }

    function setConfirm(open) {
      if (!confirm) return;
      confirm.hidden = !open;
    }

    editBtn.addEventListener('click', function () {
      setConfirm(false);
      setEditing(true);
    });

    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        setConfirm(false);
        setEditing(false);
      });
    }

    // Save asks first: the confirmation shows the address as it will be.
    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        var parts = fields()
          .map(function (input) { return input.value.trim(); })
          .filter(function (v) { return v !== ''; });

        if (confirmText) {
          confirmText.textContent = parts.length ? parts.join(', ') : 'No address';
        }
        setConfirm(true);
        if (confirmSave) { confirmSave.focus(); }
      });
    }

    if (confirmCancel) {
      confirmCancel.addEventListener('click', function () { setConfirm(false); });
    }

    if (confirmSave) {
      confirmSave.addEventListener('click', function () { form.submit(); });
    }

    if (confirm) {
      confirm.addEventListener('click', function (e) {
        if (e.target === confirm) setConfirm(false);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && confirm && !confirm.hidden) setConfirm(false);
    });
  })();
</script>

<script>
  // The member's own picture: view it, upload it, replace it, or take it
  // away and go back to the initials. The picture follows the account
  // everywhere it is shown, so the page updates every avatar it can reach.
  (function () {
    var input = document.getElementById('profilePhotoInput');
    var removeBtn = document.getElementById('profilePhotoRemove');
    var uploadBtn = document.getElementById('profilePhotoUpload');
    var viewBtn = document.getElementById('profilePhotoView');
    var label = document.getElementById('profilePhotoLabel');
    var notice = document.getElementById('profilePhotoNotice');
    var avatarBtn = document.getElementById('profileAvatarBtn');
    var menu = document.getElementById('profilePhotoMenu');
    var overlay = document.getElementById('profilePhotoViewOverlay');
    var overlayImg = document.getElementById('profilePhotoViewImg');
    var overlayClose = document.getElementById('profilePhotoViewClose');
    if (!input) return;

    // What the picker accepts. The picker already filters, but a file can
    // still arrive from "All files" — so the refusal is spelled out here
    // rather than left to a silent failure.
    var ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];
    var MAX_BYTES = 2 * 1024 * 1024;

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // The notice: there when there is something to say, gone when there
    // is not.
    function say(text, isError) {
        if (!notice) return;
        notice.textContent = text;
        notice.classList.toggle('is-error', !!isError);
        notice.hidden = false;
    }

    // The menu under the picture: open, closed, and out of the way.
    function setMenu(open) {
        if (!menu) return;
        menu.hidden = !open;
        if (avatarBtn) avatarBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (avatarBtn) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            setMenu(menu ? menu.hidden : false);
        });
    }

    document.addEventListener('click', function (e) {
        if (!menu || menu.hidden) return;
        if (e.target.closest('.profile-avatar-wrap')) return;
        setMenu(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        setMenu(false);
        if (overlay && !overlay.hidden) overlay.hidden = true;
    });

    // Every avatar of this account on this page, so the change is visible
    // at once instead of only after a reload.
    function paint(url, initials) {
        document.querySelectorAll('.user-avatar').forEach(function (el) {
            var img = el.querySelector('img');
            if (url) {
                if (!img) {
                    img = document.createElement('img');
                    img.alt = '';
                    el.innerHTML = '';
                    el.appendChild(img);
                }
                img.src = url;
            } else {
                el.textContent = initials || '?';
            }
        });

        if (label) label.textContent = url ? 'Change picture' : 'Upload a picture';
        if (removeBtn) removeBtn.hidden = !url;
        if (viewBtn) viewBtn.hidden = !url;
        if (avatarBtn) avatarBtn.setAttribute('data-photo', url || '');
    }

    // The picture at full size, over the page.
    function openViewer() {
        var url = avatarBtn ? avatarBtn.getAttribute('data-photo') : '';
        if (!url || !overlay) return;
        overlayImg.src = url;
        overlay.hidden = false;
        setMenu(false);
    }

    if (viewBtn) { viewBtn.addEventListener('click', openViewer); }
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target === overlayClose) overlay.hidden = true;
        });
    }

    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            setMenu(false);
            input.click();
        });
    }

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        this.value = '';
        if (!file) return;

        if (file.type && ACCEPTED.indexOf(file.type) === -1) {
            say('That file type is not supported. Use a JPG, PNG or WebP picture.', true);
            return;
        }
        if (file.size > MAX_BYTES) {
            say('That picture is larger than 2 MB.', true);
            return;
        }

        var fd = new FormData();
        fd.append('photo', file);
        say('Uploading…');

        fetch('{{ route('profile.photo.update') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: fd,
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (res.ok && res.d && res.d.ok) {
                    paint(res.d.url, null);
                    say('Your picture is saved and follows your account.');
                } else {
                    say((res.d && (res.d.message || res.d.error)) || 'Could not save the picture.', true);
                }
            })
            .catch(function () { say('Could not save the picture.', true); });
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            setMenu(false);
            say('Removing…');
            fetch('{{ route('profile.photo.remove') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (res.ok && res.d && res.d.ok) {
                        paint(null, res.d.initials);
                        say('Picture removed.');
                    } else {
                        say((res.d && (res.d.message || res.d.error)) || 'Could not remove the picture.', true);
                    }
                })
                .catch(function () { say('Could not remove the picture.', true); });
        });
    }
  })();
</script>
@endsection