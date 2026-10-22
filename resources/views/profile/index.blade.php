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
  .profile-photo-menu__hint {
    margin: 6px 4px 2px; padding-top: 8px; border-top: 1px solid #F0F2F1;
    font-size: 11.5px; color: #94a3b8; line-height: 1.5;
  }
  [data-theme="dark"] .profile-photo-menu { background: #111827; border-color: #334155; }
  [data-theme="dark"] .profile-photo-menu__item { color: #e2e8f0; }
  [data-theme="dark"] .profile-photo-menu__item:hover { background: #1f2937; }
  [data-theme="dark"] .profile-photo-menu__item--danger { color: #fca5a5; }
  [data-theme="dark"] .profile-photo-menu__item--danger:hover { background: #3b1220; }
  [data-theme="dark"] .profile-photo-menu__hint { border-top-color: #334155; color: #94a3b8; }

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
  .profile-card-panel + .profile-card-panel .profile-grid { margin-top: 0; }

  .p-item { padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #eef1f4; }
  .p-item.wide { grid-column: 1 / -1; }
  .p-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 4px; }
  .p-value { font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.45; word-break: break-word; }
  .p-value.muted { color: #94a3b8; font-weight: 500; }

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

        <div class="profile-photo-menu__hint" id="profilePhotoHint">JPG, PNG or WebP, up to 2 MB.</div>
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

<div class="profile-photo-view" id="profilePhotoViewOverlay" hidden>
  <button type="button" class="profile-photo-view__close" id="profilePhotoViewClose" aria-label="Close">&times;</button>
  <img id="profilePhotoViewImg" alt="Profile picture">
</div>

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
    var hint = document.getElementById('profilePhotoHint');
    var avatarBtn = document.getElementById('profileAvatarBtn');
    var menu = document.getElementById('profilePhotoMenu');
    var overlay = document.getElementById('profilePhotoViewOverlay');
    var overlayImg = document.getElementById('profilePhotoViewImg');
    var overlayClose = document.getElementById('profilePhotoViewClose');
    if (!input) return;

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
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
        if (file.size > 2 * 1024 * 1024) {
            if (hint) hint.textContent = 'That picture is larger than 2 MB.';
            return;
        }

        var fd = new FormData();
        fd.append('photo', file);
        if (hint) hint.textContent = 'Uploading…';

        fetch('{{ route('profile.photo.update') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: fd,
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (res.ok && res.d && res.d.ok) {
                    paint(res.d.url, null);
                    if (hint) hint.textContent = 'Your picture is saved and follows your account.';
                } else {
                    if (hint) hint.textContent = (res.d && (res.d.message || res.d.error)) || 'Could not save the picture.';
                }
            })
            .catch(function () { if (hint) hint.textContent = 'Could not save the picture.'; });
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            setMenu(false);
            if (hint) hint.textContent = 'Removing…';
            fetch('{{ route('profile.photo.remove') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (res.ok && res.d && res.d.ok) {
                        paint(null, res.d.initials);
                        if (hint) hint.textContent = 'Picture removed.';
                    } else {
                        if (hint) hint.textContent = (res.d && (res.d.message || res.d.error)) || 'Could not remove the picture.';
                    }
                })
                .catch(function () { if (hint) hint.textContent = 'Could not remove the picture.'; });
        });
    }
  })();
</script>
@endsection