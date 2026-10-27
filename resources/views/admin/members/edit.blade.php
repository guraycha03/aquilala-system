@extends('layouts.sidebar')

@section('title', 'Edit Member')

@section('content')
  <style>
    .register-page { position: relative; max-width: 960px; }
    .register-page::before,
    .register-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .register-page::before { width: 300px; height: 300px; background: rgba(94,144,112,0.14); top: -70px; right: -60px; }
    .register-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

    .header-row { position: relative; z-index: 1; margin-bottom: 20px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; line-height: 1.55; }

    .account-strip {
      position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 13px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 18px; line-height: 1.5;
      background: #f3f8f4; border: 1px solid #dce8df; color: #2f4a39;
    }
    .account-strip.none { background: #fbf7ee; border-color: #f0e4cb; color: #6b5a34; }
    .account-strip svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .account-strip a { color: inherit; font-weight: 700; }

    .form-card {
      position: relative; z-index: 1;
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px;
    }

    .form-section + .form-section { margin-top: 24px; padding-top: 22px; border-top: 1px solid #eef1ef; }
    .form-section h2 {
      font-size: 12px; font-weight: 800; letter-spacing: 0.09em; text-transform: uppercase;
      color: #6b7f71; margin: 0 0 4px 0;
    }
    .form-section .hint { font-size: 12px; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.5; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 720px) {
      .form-grid, .form-grid.cols-3 { grid-template-columns: 1fr; }
    }

    .invite-field label { display: block; font-size: 12px; font-weight: 700; color: #3d4a41; margin-bottom: 6px; }
    .invite-field label .req { color: #dc2626; }
    .invite-field input, .invite-field select, .invite-field textarea {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field textarea { resize: vertical; min-height: 84px; line-height: 1.5; }
    .invite-field input:focus, .invite-field select:focus, .invite-field textarea:focus {
      border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18);
    }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }
    .field-note { display: block; font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.45; }

    .phone-row {
      display: flex; align-items: stretch; border: 1px solid #e2e8f0; border-radius: 9px;
      background: #fafbfa; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .phone-row:focus-within { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .phone-prefix {
      display: inline-flex; align-items: center; gap: 7px; padding: 0 11px;
      background: #eef1ef; border-right: 1px solid #e6ebe8;
      font-size: 14px; font-weight: 700; letter-spacing: 0.03em;
      color: #aab5ac; user-select: none; cursor: default;
    }
    .ph-flag {
      width: 22px; height: 15px; display: block; border-radius: 2.5px;
      box-shadow: 0 0 0 1px rgba(20, 61, 35, 0.15);
      opacity: 0.75; filter: saturate(0.45);
    }
    .phone-row input {
      flex: 1; min-width: 0; border: none !important; background: transparent !important;
      box-shadow: none !important; padding: 11px 13px !important;
    }

    .form-alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      margin-bottom: 18px; line-height: 1.45;
    }
    .form-alert.error { background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; }
    .form-alert svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; flex-shrink: 0; }

    .form-actions {
      display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;
      margin-top: 22px; padding-top: 18px; border-top: 1px solid #eef1ef;
    }
    .actions-right { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-users {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer;
      font-family: inherit; letter-spacing: 0.01em; line-height: 1;
      background: #14532d; border: 1px solid rgba(255,255,255,0.10);
      color: #f4faf5; padding: 11px 20px; border-radius: 11px; font-size: 13px; font-weight: 600;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(20,61,35,0.06), 0 4px 12px rgba(20,61,35,0.10);
    }
    .btn-users:hover { background: #0f4228; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(20,61,35,0.08), 0 7px 16px rgba(20,61,35,0.14); }
    .btn-users:active { transform: translateY(0); box-shadow: 0 1px 2px rgba(20,61,35,0.06); }
    .btn-users:focus-visible { outline: 3px solid rgba(74,122,84,0.28); outline-offset: 2px; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }
    .btn-users.danger { background: #ffffff; color: #b02a2a; border-color: #f0d4d4; box-shadow: 0 1px 2px rgba(176,42,42,0.05); }
    .btn-users.danger:hover { background: #fdf2f2; border-color: #e6b6b6; color: #8f1f1f; transform: translateY(-1px); }
    .btn-users.danger:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Members', 'url' => route('admin.members.index')],
      ['label' => $member->fullName() ?: 'Member'],
    ]"/>

    <div class="header-row">
      <h1>{{ $member->fullName() ?: 'Member Record' }}</h1>
      <p>This edits the person's details on the members list. It does not touch their sign-in account.</p>
    </div>

    @if ($member->user_id !== null && $member->user)
      <div class="account-strip">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>
          <b>{{ $member->user->name }}</b> has a system account
          ({{ $member->user->email }}) —
          <a href="{{ route('admin.users.edit', $member->user) }}">manage the account</a>.
        </span>
      </div>
    @else
      <div class="account-strip none">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>No system account yet — this member cannot sign in. Create one from the
          <a href="{{ route('admin.members.index') }}">Members list</a>.</span>
      </div>
    @endif

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.members.update', $member) }}" id="memberForm" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="phone" value="{{ old('phone', $member->phone) }}">

        <div class="form-section">
          <h2>Identity</h2>
          <p class="hint">How the member is recorded on the list.</p>
          <div class="form-grid cols-3">
            <div class="invite-field">
              <label>First Name <span class="req">*</span></label>
              <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" maxlength="50">
              <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Middle Name <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="middle_initial" value="{{ old('middle_initial', $member->middle_initial) }}" maxlength="50">
              <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Last Name <span class="req">*</span></label>
              <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" maxlength="50">
              <small class="field-error">@error('last_name'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Member No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="assoc_member_no" value="{{ old('assoc_member_no', $member->assoc_member_no) }}" maxlength="60">
              <small class="field-error">@error('assoc_member_no'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Sex <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="sex">
                <option value="">— not recorded —</option>
                @foreach (['Male', 'Female'] as $sex)
                  <option value="{{ $sex }}" @selected(old('sex', $member->sex) === $sex)>{{ $sex }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('sex'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Birth Date <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="date" name="birth_date" value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
              <small class="field-error">@error('birth_date'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Civil Status <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="civil_status">
                <option value="">— not recorded —</option>
                @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $cs)
                  <option value="{{ $cs }}" @selected(old('civil_status', $member->civil_status) === $cs)>{{ $cs }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('civil_status'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Member Status</label>
              <select name="status">
                <option value="active" @selected(old('status', $member->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $member->status) === 'inactive')>Inactive</option>
              </select>
              <small class="field-error">@error('status'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Contact</h2>
          <p class="hint">An email is required before an account can be created for this member.</p>
          <div class="form-grid">
            <div class="invite-field full">
              <label>Email Address <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="email" name="email" value="{{ old('email', $member->email) }}" autocomplete="off">
              <small class="field-error">@error('email'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field full">
              <label>Mobile Number <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <div class="phone-row">
                <span class="phone-prefix" title="Philippine mobile numbers only — prefix is fixed">
                  <svg class="ph-flag" viewBox="0 0 36 25" role="img" aria-label="Philippine flag">
                    <rect width="36" height="12.5" fill="#0038a8"/>
                    <rect y="12.5" width="36" height="12.5" fill="#ce1126"/>
                    <path d="M0 0 L17 12.5 L0 25 Z" fill="#ffffff"/>
                    <g stroke="#fcd116" stroke-width="1.1" stroke-linecap="round">
                      <line x1="5.6" y1="7.4" x2="5.6" y2="17.6"/>
                      <line x1="0.5" y1="12.5" x2="10.7" y2="12.5"/>
                      <line x1="1.9" y1="8.8" x2="9.3" y2="16.2"/>
                      <line x1="1.9" y1="16.2" x2="9.3" y2="8.8"/>
                    </g>
                    <circle cx="5.6" cy="12.5" r="2.3" fill="#fcd116"/>
                  </svg>
                  +639
                </span>
                <input type="tel" id="phoneLocal" inputmode="numeric" autocomplete="off"
                       maxlength="9" placeholder="171234567"
                       value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : ($member->phone ? substr(preg_replace('/\D/', '', $member->phone), -9) : '') }}">
              </div>
              <small class="field-error">@error('phone'){{ $message }}@enderror</small>
              <span class="field-note">Enter the 9 digits after +639, or leave it empty.</span>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Address</h2>
          <p class="hint">Where the member lives, and where the farm is.</p>
          <div class="form-grid">
            <div class="invite-field full">
              <label>Street / House No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="address" value="{{ old('address', $member->address) }}" maxlength="500">
              <small class="field-error">@error('address'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Barangay <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="barangay" value="{{ old('barangay', $member->barangay) }}" maxlength="100">
              <small class="field-error">@error('barangay'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>City / Municipality <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="city" value="{{ old('city', $member->city) }}" maxlength="100">
              <small class="field-error">@error('city'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Province <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="province" value="{{ old('province', $member->province) }}" maxlength="100">
              <small class="field-error">@error('province'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Farm Location <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="farm_location" value="{{ old('farm_location', $member->farm_location) }}" maxlength="500">
              <small class="field-error">@error('farm_location'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Farming Background</h2>
          <div class="form-grid cols-3">
            <div class="invite-field">
              <label>Land Ownership <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="land_ownership">
                <option value="">— not recorded —</option>
                @foreach (['Owned', 'Rented', 'Shared', 'Communal'] as $own)
                  <option value="{{ $own }}" @selected(old('land_ownership', $member->land_ownership) === $own)>{{ $own }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('land_ownership'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Years Farming <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="number" name="years_farming" value="{{ old('years_farming', $member->years_farming) }}" min="0" max="80" step="1">
              <small class="field-error">@error('years_farming'){{ $message }}@enderror</small>
            </div>
          </div>

          <div class="form-grid" style="margin-top:16px;">
            <div class="invite-field full">
              <label>Notes <span style="color:#94a3b8;font-weight:400;">(admin only)</span></label>
              <textarea name="notes" maxlength="2000">{{ old('notes', $member->notes) }}</textarea>
              <small class="field-error">@error('notes'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>
      </form>

      <div class="form-actions">
        @if ($member->user_id === null)
          <form method="POST" action="{{ route('admin.members.destroy', $member) }}"
                onsubmit="return confirm('Remove {{ $member->fullName() }} from the members list? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-users danger">
              <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Remove Member
            </button>
          </form>
        @else
          <span></span>
        @endif

        <div class="actions-right">
          <a href="{{ route('admin.members.index') }}" class="btn-users ghost">Cancel</a>
          {{-- The Save button lives outside the form it submits, so it carries
               the form attribute instead of nesting two forms. --}}
          <button type="submit" form="memberForm" class="btn-users" id="memberSubmitBtn">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('memberForm');
      var phoneLocal = document.getElementById('phoneLocal');
      var phoneHidden = form.querySelector('input[name="phone"]');
      var submitBtn = document.getElementById('memberSubmitBtn');

      phoneLocal.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
      });

      form.addEventListener('submit', function (e) {
        var digits = phoneLocal.value.replace(/\D/g, '');
        if (digits.length > 0 && digits.length !== 9) {
          e.preventDefault();
          phoneLocal.closest('.invite-field').querySelector('.field-error').textContent =
            'Enter the 9-digit number after +639, or leave it empty.';
          phoneLocal.focus();
          return;
        }
        phoneHidden.value = digits.length === 9 ? '+639' + digits : '';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';
      });
    });
  </script>
@endsection
