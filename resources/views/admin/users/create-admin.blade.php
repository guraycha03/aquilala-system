@extends('layouts.sidebar')

@section('title', 'Create Administrator Account')

@section('content')
  <style>
    .register-page { position: relative; max-width: 940px; }
    .register-page::before,
    .register-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .register-page::before { width: 300px; height: 300px; background: rgba(94,144,112,0.14); top: -70px; right: -60px; }
    .register-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

    .header-row { position: relative; z-index: 1; margin-bottom: 20px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; max-width: 760px; line-height: 1.55; }

    .form-card {
      position: relative; z-index: 1;
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px;
    }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

    .invite-field label { display: block; font-size: 12px; font-weight: 700; color: #3d4a41; margin-bottom: 6px; }
    .invite-field label .req { color: #dc2626; }
    .invite-field input {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field input:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }
    .field-help { display: block; font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.5; }

    .form-alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      margin-bottom: 18px; line-height: 1.45;
    }
    .form-alert.error { background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; }
    .form-alert svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; flex-shrink: 0; }

    .rule-strip {
      display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 12px;
      background: #f3f8f4; border: 1px solid #dce8df; color: #2f4a39;
      font-size: 13px; line-height: 1.55; margin-bottom: 18px;
    }
    .rule-strip svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; margin-top: 1px; }

    .step-divider {
      display: flex; align-items: center; gap: 12px; grid-column: 1 / -1;
      margin-top: 6px; font-size: 10.5px; font-weight: 800; letter-spacing: 0.12em;
      text-transform: uppercase; color: #8a97a5;
    }
    .step-divider::after { content: ""; flex: 1; height: 1px; background: #eef1ef; }

    .grant-check {
      display: flex; align-items: flex-start; gap: 10px; grid-column: 1 / -1;
      font-size: 13px; line-height: 1.55; color: #3d4a41; cursor: pointer; margin-top: 2px;
    }
    .grant-check input { width: 17px; height: 17px; margin: 1px 0 0; accent-color: #14532d; flex-shrink: 0; cursor: pointer; }

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
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }

    [data-theme="dark"] .rule-strip { background: rgba(15, 23, 42, 0.72); border-color: #334155; color: #cbd5e1; }
    [data-theme="dark"] .grant-check { color: #cbd5e1; }
    [data-theme="dark"] .step-divider { color: #7d8f86; }
    [data-theme="dark"] .step-divider::after { background: #334155; }
    [data-theme="dark"] .field-help { color: #7d8f86; }
    [data-theme="dark"] .form-actions { border-top-color: #334155; }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => 'Create Account', 'url' => route('admin.users.create')],
      ['label' => 'Administrator'],
    ]"/>

    <div class="header-row">
      <h1>Create an Administrator Account</h1>
      <p>Administrator is the highest level of access in {{ config('app.name') }}. The account is created as
         <b>pending</b> and a 6&#8209;digit activation code is emailed — the new administrator signs in with that
         code and chooses their own password.</p>
    </div>

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <div class="rule-strip">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>
          This creates a <b>brand-new administrator</b>, so the form asks for your own password and an explicit
          confirmation — the same safeguards as granting admin to an existing account. The creation is written to
          the audit log with your name on it.
        </span>
      </div>

      <form method="POST" action="{{ route('admin.users.storeAdmin') }}" id="adminCreateForm" novalidate>
        @csrf

        <div class="form-grid">
          <div class="invite-field">
            <label>First Name <span class="req">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name') }}"
                   placeholder="Juan" maxlength="80" autocomplete="off">
            <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
          </div>

          <div class="invite-field">
            <label>Middle Initial <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="middle_initial" value="{{ old('middle_initial') }}"
                   placeholder="S" maxlength="10" autocomplete="off">
            <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
          </div>

          <div class="invite-field full">
            <label>Last Name <span class="req">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name') }}"
                   placeholder="Dela Cruz" maxlength="80" autocomplete="off">
            <small class="field-error">@error('last_name'){{ $message }}@enderror</small>
          </div>

          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}"
                   placeholder="name@gmail.com" maxlength="254" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
            <small class="field-help">
              This is what they sign in with and where the activation code is sent.
            </small>
          </div>

          <div class="invite-field full">
            <label>Phone Number <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="tel" name="phone" value="{{ old('phone') }}"
                   placeholder="09171234567" maxlength="25" autocomplete="off">
            <small class="field-error">@error('phone'){{ $message }}@enderror</small>
            <small class="field-help">
              Philippine mobile number (11 digits starting with 09). Leave it blank if it is already used by
              another account.
            </small>
          </div>

          <div class="step-divider">Confirm it&rsquo;s you</div>

          <div class="invite-field full">
            <label>Your Password <span class="req">*</span></label>
            <input type="password" name="password" autocomplete="current-password"
                   placeholder="Re-enter your password" value="">
            <small class="field-error">@error('password'){{ $message }}@enderror</small>
            <small class="field-help">
              Your own account password — not the new administrator's. They will choose theirs when they enter
              the emailed code.
            </small>
          </div>

          <label class="grant-check">
            <input type="checkbox" name="confirm" value="1" @checked(old('confirm'))>
            <span>
              I understand this account will have full control of the system — members, accounts, stores and
              all association data — and that it can change anything, including this.
            </span>
          </label>
          <small class="field-error" style="grid-column:1/-1;margin-top:-8px;">@error('confirm'){{ $message }}@enderror</small>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.users.create') }}" class="btn-users ghost">Cancel</a>
          <button type="submit" class="btn-users" id="adminCreateBtn"
                  data-aqui-confirm="Create this account as an administrator? They will be able to manage members, accounts, stores and every association record."
                  data-aqui-title="Create administrator account"
                  data-aqui-confirm-label="Create administrator">
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
            Create Administrator Account
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('adminCreateForm');
      var btn = document.getElementById('adminCreateBtn');

      if (!form || !btn) { return; }

      var first = form.querySelector('input[name="first_name"]');
      var last = form.querySelector('input[name="last_name"]');

      function refresh() {
        var name = ((first && first.value) || '').trim() + ' ' + ((last && last.value) || '').trim();
        name = name.trim();

        btn.setAttribute('data-aqui-confirm', name
          ? 'Create ' + name + ' as an administrator? They will be able to manage members, accounts, stores and every association record.'
          : 'Create this account as an administrator? They will be able to manage members, accounts, stores and every association record.');
      }

      if (first) { first.addEventListener('input', refresh); }
      if (last) { last.addEventListener('input', refresh); }
      refresh();
    });
  </script>
@endsection
