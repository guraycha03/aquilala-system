@extends('layouts.sidebar')

@section('title', 'Register User')

@section('content')
  <style>
    .register-page { position: relative; max-width: 860px; }
    .register-page::before,
    .register-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .register-page::before { width: 300px; height: 300px; background: rgba(16,185,129,0.14); top: -70px; right: -60px; }
    .register-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

    .header-row { position: relative; z-index: 1; margin-bottom: 20px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }

    .form-card {
      position: relative; z-index: 1;
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px;
    }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) {
      .form-grid { grid-template-columns: 1fr; }
    }

    .invite-field label { display: block; font-size: 12px; font-weight: 700; color: #3d4a41; margin-bottom: 6px; }
    .invite-field label .req { color: #dc2626; }
    .invite-field input, .invite-field select {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field input:focus, .invite-field select:focus { border-color: #5DC257; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }

    /* Philippine mobile prefix (+639, locked) */
    .phone-row {
      display: flex; align-items: stretch; border: 1px solid #e2e8f0; border-radius: 9px;
      background: #fafbfa; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .phone-row:focus-within { border-color: #5DC257; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .phone-prefix {
      display: inline-flex; align-items: center; gap: 7px; padding: 0 11px;
      background: #eef1ef; border-right: 1px solid #e6ebe8;
      font-size: 14px; font-weight: 700; letter-spacing: 0.03em;
      color: #aab5ac; /* faded: locked, not editable */
      user-select: none; cursor: default;
    }
    .ph-flag {
      width: 22px; height: 15px; display: block; border-radius: 2.5px;
      box-shadow: 0 0 0 1px rgba(20, 61, 35, 0.15);
      opacity: 0.75; filter: saturate(0.45); /* faded to match locked prefix */
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
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
      background: rgb(172, 242, 121); border: 1px solid rgba(20,61,35,0.12);
      color: #14532d; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08); cursor: pointer;
      font-family: inherit;
    }
    .btn-users:hover { background: rgb(160, 232, 108); transform: translateY(-1px); }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-users.ghost:hover { background: #f0fdf4; transform: translateY(-1px); }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => 'Register User'],
    ]"/>

    <div class="header-row">
      <h1>Register New User</h1>
      <p>An activation link will be emailed so the user can confirm their phone number and set their own password.</p>
    </div>

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.users.store') }}" id="inviteForm" novalidate>
        @csrf
        <input type="hidden" name="phone" value="{{ old('phone') }}">

        <div class="form-grid">
          <div class="invite-field">
            <label>First Name <span class="req">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Juan" maxlength="50">
            <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>M.I. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="middle_initial" value="{{ old('middle_initial') }}" placeholder="D." maxlength="2" style="text-align:center;">
            <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Last Name <span class="req">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Dela Cruz" maxlength="50">
            <small class="field-error">@error('last_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Account Role <span class="req">*</span></label>
            <select name="role">
              <option value="farmer" @selected(old('role', 'farmer') === 'farmer')>Farmer</option>
              <option value="store_owner" @selected(old('role') === 'store_owner')>Store Owner</option>
            </select>
            <small class="field-error">@error('role'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="name@gmail.com" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Registered Phone Number <span class="req">*</span></label>
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
            <small style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;">
              Philippine mobile number only — enter the 9 digits after +639.
              This becomes the user's unique identity, one number per account.
            </small>
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.users.index') }}" class="btn-users ghost">Cancel</a>
          <button type="submit" class="btn-users" id="inviteSubmitBtn">
            <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Invitation
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('inviteForm');
      var phoneLocal = document.getElementById('phoneLocal');
      var phoneHidden = form.querySelector('input[name="phone"]');
      var submitBtn = document.getElementById('inviteSubmitBtn');

      // Digits only, fixed +639 prefix
      phoneLocal.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
      });

      form.addEventListener('submit', function (e) {
        var digits = phoneLocal.value.replace(/\D/g, '');
        if (digits.length !== 9) {
          e.preventDefault();
          phoneLocal.closest('.invite-field').querySelector('.field-error').textContent =
            'Enter the 9-digit number after +639 (e.g. 171234567).';
          phoneLocal.focus();
          return;
        }
        phoneHidden.value = '+639' + digits;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending…';
      });
    });
  </script>
@endsection
