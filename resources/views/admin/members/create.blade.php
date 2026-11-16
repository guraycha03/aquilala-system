@extends('layouts.sidebar')

@section('title', 'Add Member')

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
    .invite-field input[readonly] { background: #f1f4f1; color: #5b6b60; cursor: default; }

    /* Philippine mobile prefix (+639, locked) */
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
      display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;
      margin-top: 22px; padding-top: 18px; border-top: 1px solid #eef1ef;
    }
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
    .btn-users:disabled { opacity: 0.5; cursor: not-allowed; transform: none; background: #e6ece6; color: #8aa296; border-color: #d8e3db; box-shadow: none; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Members', 'url' => route('admin.members.index')],
      ['label' => 'Add Member'],
    ]"/>

    <div class="header-row">
      <h1>Add Association Member</h1>
      <p>Members are people of the association — not accounts. Adding someone here does not
         give them a login. To let them sign in, pick them from the <b>Members</b> list afterwards and create
         an account for them.</p>
    </div>

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.members.store') }}" id="memberForm" novalidate>
        @csrf
        <input type="hidden" name="phone" value="{{ old('phone') }}">

        <div class="form-section">
          <h2>Identity</h2>
          <p class="hint">How the member is recorded on the list.</p>
          <div class="form-grid cols-3">
            <div class="invite-field">
              <label>First Name <span class="req">*</span></label>
              <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Juan" maxlength="50">
              <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Middle Name <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="middle_initial" value="{{ old('middle_initial') }}" placeholder="D. or Dela" maxlength="50">
              <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Last Name <span class="req">*</span></label>
              <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Dela Cruz" maxlength="50">
              <small class="field-error">@error('last_name'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Member No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="assoc_member_no" value="{{ old('assoc_member_no') }}" placeholder="AQU-2026-001" maxlength="60">
              <small class="field-error">@error('assoc_member_no'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Sex <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="sex">
                <option value="">— not recorded —</option>
                <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                <option value="Female" @selected(old('sex') === 'Female')>Female</option>
              </select>
              <small class="field-error">@error('sex'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Birth Date <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="date" name="birth_date" value="{{ old('birth_date') }}" max="{{ date('Y-m-d') }}">
              <small class="field-error">@error('birth_date'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Civil Status <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="civil_status">
                <option value="">— not recorded —</option>
                @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $status)
                  <option value="{{ $status }}" @selected(old('civil_status') === $status)>{{ $status }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('civil_status'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Contact</h2>
          <p class="hint">An email is only needed once you create an account for this member — you can leave it
             blank now and add it later.</p>
          <div class="form-grid">
            <div class="invite-field full">
              <label>Email Address <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="email" name="email" value="{{ old('email') }}" placeholder="name@gmail.com" autocomplete="off">
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
                       value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : '' }}">
              </div>
              <small class="field-error">@error('phone'){{ $message }}@enderror</small>
              <span class="field-note">Enter the 9 digits after +639, or leave it empty.</span>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Address</h2>
          <p class="hint">Where the member lives, and where the farm is. The address is in Bulan, Sorsogon — only the barangay is picked from the list.</p>
          <div class="form-grid">
            <div class="invite-field full">
              <label>Street / House No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="address" value="{{ old('address') }}" placeholder="Purok 3, Sitio Maligaya" maxlength="500">
              <small class="field-error">@error('address'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Barangay <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="barangay">
                <option value="">— select a barangay —</option>
                @foreach (config('bulan.barangays') as $barangay)
                  <option value="{{ $barangay }}" @selected(old('barangay') === $barangay)>{{ $barangay }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('barangay'){{ $message }}@enderror</small>
              <span class="field-note">Bulan, Sorsogon's 63 barangays only.</span>
            </div>
            <div class="invite-field">
              <label>City / Municipality <span style="color:#94a3b8;font-weight:400;">(fixed)</span></label>
              <input type="text" name="city" value="{{ old('city', config('bulan.city')) }}" readonly maxlength="100">
              <small class="field-error">@error('city'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Province <span style="color:#94a3b8;font-weight:400;">(fixed)</span></label>
              <input type="text" name="province" value="{{ old('province', config('bulan.province')) }}" readonly maxlength="100">
              <small class="field-error">@error('province'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Farm Location <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="text" name="farm_location" value="{{ old('farm_location') }}" placeholder="Sitio Bagong Silang" maxlength="500">
              <small class="field-error">@error('farm_location'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Farming Background</h2>
          <p class="hint">Optional details that help the association understand its members.</p>
          <div class="form-grid cols-3">
            <div class="invite-field">
              <label>Land Ownership <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <select name="land_ownership">
                <option value="">— not recorded —</option>
                @foreach (['Owned', 'Rented', 'Shared', 'Communal'] as $own)
                  <option value="{{ $own }}" @selected(old('land_ownership') === $own)>{{ $own }}</option>
                @endforeach
              </select>
              <small class="field-error">@error('land_ownership'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Total Land Area <span class="req">*</span></label>
              <input type="number" name="land_area" value="{{ old('land_area') }}" min="0.01" max="999999.99" step="0.01" placeholder="e.g. 2.50">
              <small class="field-error">@error('land_area'){{ $message }}@enderror</small>
              <span class="field-note">The farmer's total recorded land. Only administrators can change it.</span>
            </div>
            <div class="invite-field">
              <label>Land Area Unit <span class="req">*</span></label>
              <select name="land_area_unit">
                <option value="ha" @selected(old('land_area_unit', 'ha') === 'ha')>Hectares (ha)</option>
                <option value="sq_m" @selected(old('land_area_unit') === 'sq_m')>Square meters (m²)</option>
                <option value="sq_ft" @selected(old('land_area_unit') === 'sq_ft')>Square feet (ft²)</option>
              </select>
              <small class="field-error">@error('land_area_unit'){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Years Farming <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
              <input type="number" name="years_farming" value="{{ old('years_farming') }}" min="0" max="80" step="1" placeholder="12">
              <small class="field-error">@error('years_farming'){{ $message }}@enderror</small>
            </div>
          </div>

          <div class="form-grid" style="margin-top:16px;">
            <div class="invite-field full">
              <label>Notes <span style="color:#94a3b8;font-weight:400;">(admin only)</span></label>
              <textarea name="notes" maxlength="2000" placeholder="Anything the association should remember about this member.">{{ old('notes') }}</textarea>
              <small class="field-error">@error('notes'){{ $message }}@enderror</small>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.members.index') }}" class="btn-users ghost">Cancel</a>
          <button type="submit" class="btn-users" id="memberSubmitBtn">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Add Member
          </button>
        </div>
      </form>
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
