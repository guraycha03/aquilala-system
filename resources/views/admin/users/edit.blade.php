@extends('layouts.sidebar')

@section('title', 'Edit ' . $user->name)

@section('content')
  <style>
    .register-page { position: relative; max-width: 860px; }
    .register-page::before,
    .register-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .register-page::before { width: 300px; height: 300px; background: rgba(94,144,112,0.14); top: -70px; right: -60px; }
    .register-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

    .header-row { position: relative; z-index: 1; margin-bottom: 20px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-meta { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
    .profile-chip {
      display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; border-radius: 999px;
      font-size: 12px; font-weight: 700; background: #eef3ef; color: #3f7a4f;
    }
    .profile-chip.warn { background: #fef9ec; color: #a57f43; }
    .profile-chip.danger { background: #fdf2f2; color: #b91c1c; }
    .profile-chip svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }

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
    .invite-field input:focus, .invite-field select:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .invite-field input[readonly] { background: #f1f3f2; color: #94a3b8; cursor: not-allowed; }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }

    .phone-row {
      display: flex; align-items: stretch; border: 1px solid #e2e8f0; border-radius: 9px;
      background: #fafbfa; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .phone-row:focus-within { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .phone-prefix {
      display: inline-flex; align-items: center; padding: 0 11px;
      background: #eef1ef; border-right: 1px solid #e6ebe8;
      font-size: 14px; font-weight: 700; letter-spacing: 0.03em; color: #aab5ac;
      user-select: none; cursor: default;
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
    .form-alert.info { background: #edf1e9; color: #648268; border: 1px solid #dbeafe; }
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
    .btn-users.warning { background: #8a6d1f; border-color: rgba(120,95,20,0.3); box-shadow: 0 1px 2px rgba(120,95,20,0.12), 0 4px 12px rgba(120,95,20,0.14); }
    .btn-users.warning:hover { background: #7a6019; box-shadow: 0 2px 4px rgba(120,95,20,0.14), 0 7px 16px rgba(120,95,20,0.18); }

    /* ── Privileged action card ──────────────────────────────────── */
    .grant-card {
      position: relative; z-index: 1; margin-top: 20px;
      background: #fffcf4; border: 1px solid #f2e3c4; border-radius: 18px;
      box-shadow: 0 6px 28px rgba(150,110,30,0.06), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px;
    }
    .grant-eyebrow {
      display: flex; align-items: center; gap: 8px; margin: 0 0 8px 0;
      font-size: 11px; font-weight: 800; letter-spacing: 0.14em;
      text-transform: uppercase; color: #a57f43;
    }
    .grant-eyebrow svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .grant-card h2 { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; }
    .grant-card > p { font-size: 13.5px; line-height: 1.65; color: #64748b; margin: 0 0 16px 0; max-width: 640px; }
    .grant-list { margin: 0 0 18px 0; padding: 0; list-style: none; display: grid; gap: 7px; }
    .grant-list li { display: flex; gap: 9px; align-items: flex-start; font-size: 13px; line-height: 1.55; color: #475569; }
    .grant-list li svg { width: 15px; height: 15px; flex: 0 0 15px; margin-top: 2px; stroke: #a57f43; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .grant-check { display: flex; gap: 10px; align-items: flex-start; margin-top: 14px; font-size: 13px; line-height: 1.55; color: #334155; cursor: pointer; }
    .grant-check input { width: 17px; height: 17px; margin: 1px 0 0 0; accent-color: #14532d; flex: 0 0 17px; }
    .grant-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 18px; border-top: 1px solid #f1e7d2; }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => $user->name],
    ]"/>

    <div class="header-row">
      <h1>Edit User Profile</h1>
      <p>Update account details, status, role, and contact information. Changes take effect immediately.</p>
      <div class="header-meta">
        <span class="profile-chip">
          {{ ucwords(str_replace('_', ' ', $user->role)) }}
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
        </span>
@if($user->isPending())
          <span class="profile-chip warn">
            Pending activation
            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </span>
        @elseif($user->isDisabled())
          <span class="profile-chip danger">
            Disabled
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          </span>
        @else
          <span class="profile-chip">
            Active
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </span>
        @endif
        <span style="font-size:12px;color:#94a3b8;">Joined {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</span>
      </div>
    </div>

    @if (session('success'))
      <div class="form-alert info" style="position:relative;z-index:1;">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
      </div>
    @endif
    @if (session('error'))
      <div class="form-alert error" style="position:relative;z-index:1;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
      </div>
    @endif

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      @if($user->isPending())
        <div class="form-alert info">
          <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          This account is still awaiting email activation. You can resend the activation code from the Users list.
        </div>
      @endif

      <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">

        <div class="form-grid">
          <div class="invite-field full">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Juan D. Dela Cruz" maxlength="255">
            <small class="field-error">@error('name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="name@gmail.com" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Registered Phone Number</label>
            <div class="phone-row">
              <span class="phone-prefix" title="Philippine mobile numbers only — prefix is fixed">+639</span>
              <input type="tel" id="phoneLocal" inputmode="numeric" autocomplete="off"
                     maxlength="9" placeholder="171234567"
                     value="{{ old('phone', $user->phone) ? substr(preg_replace('/\D/', '', old('phone', $user->phone)), -9) : '' }}">
            </div>
            <small class="field-error">@error('phone'){{ $message }}@enderror</small>
            <small style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;">
              Leave blank if this account has no registered mobile number.
            </small>
          </div>
          <div class="invite-field">
            <label>Account Role</label>
            @if($user->role === 'admin')
              <input type="text" value="Administrator" readonly>
            @else
              <select name="role">
                <option value="farmer" @selected(old('role', $user->role) === 'farmer')>Farmer</option>
                <option value="store_owner" @selected(old('role', $user->role) === 'store_owner')>Store Owner</option>
              </select>
              <small class="field-error">@error('role'){{ $message }}@enderror</small>
            @endif
          </div>
          <div class="invite-field">
            <label>Account Status</label>
            <select name="status">
              @php($status = old('status', $user->status))
              @if($status === \App\Models\User::STATUS_PENDING)
                <option value="{{ \App\Models\User::STATUS_PENDING }}" @selected($status === \App\Models\User::STATUS_PENDING)>Pending (unchanged)</option>
              @endif
              <option value="{{ \App\Models\User::STATUS_ACTIVE }}" @selected($status === \App\Models\User::STATUS_ACTIVE)>Active</option>
              <option value="{{ \App\Models\User::STATUS_DISABLED }}" @selected($status === \App\Models\User::STATUS_DISABLED)>Disabled</option>
            </select>
            <small class="field-error">@error('status'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Barangay / District</label>
            <input type="text" name="barangay" value="{{ old('barangay', $user->barangay) }}" maxlength="120">
            <small class="field-error">@error('barangay'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>City / Municipality</label>
            <input type="text" name="city" value="{{ old('city', $user->city) }}" maxlength="120">
            <small class="field-error">@error('city'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Province</label>
            <input type="text" name="province" value="{{ old('province', $user->province) }}" maxlength="120">
            <small class="field-error">@error('province'){{ $message }}@enderror</small>
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.users.index') }}" class="btn-users ghost">Cancel</a>
          <button type="submit" class="btn-users" id="saveBtn">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>

    {{-- ── Privilege escalation ─────────────────────────────────────
         Kept out of the profile form on purpose: granting administrator
         access asks for this account's password again, states plainly what
         is being handed over, is throttled, and is written to the audit log
         with both names on it. --}}
    @if ($user->role !== 'admin')
      <div class="grant-card">
        <div class="grant-eyebrow">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Privileged action
        </div>
        <h2>Grant administrator access</h2>
        <p>
          Administrator is the highest level in {{ config('app.name') }}. Only an existing
          administrator can grant it, and it is never granted by accident — you will be asked
          to confirm with your own password.
        </p>

        <ul class="grant-list">
          <li>
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Full access to the members list, system accounts, stores, their legal paperwork and every association report.</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ $user->name }} will be emailed a security notice the moment it takes effect.</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <span>The grant is recorded in the audit log against your account, with the time and IP address.</span>
          </li>
        </ul>

        @if ($user->isActive())
          <form method="POST" action="{{ route('admin.users.promote', $user) }}" id="promoteForm" novalidate>
            @csrf

            <div class="invite-field" style="max-width:340px;">
              <label>Your Password <span class="req">*</span></label>
              <input type="password" name="password" autocomplete="current-password"
                     placeholder="Re-enter your password" value="">
              <small class="field-error">@error('password'){{ $message }}@enderror</small>
            </div>

            <label class="grant-check">
              <input type="checkbox" name="confirm" value="1" @checked(old('confirm'))>
              <span>
                I understand this gives <strong>{{ $user->name }}</strong> full control of the
                system — members, accounts, stores and all association data — and that they can
                change anything, including this.
              </span>
            </label>
            <small class="field-error">@error('confirm'){{ $message }}@enderror</small>

            <div class="grant-actions">
              <button type="submit" class="btn-users warning"
                      data-aqui-confirm="Give {{ $user->name }} full administrator access? They will be able to manage members, accounts, stores and every association record."
                      data-aqui-title="Grant administrator access"
                      data-aqui-confirm-label="Grant access">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                Grant Administrator Access
              </button>
            </div>
          </form>
        @else
          <p class="field-error" style="min-height:0;font-size:13px;">
            This account is still <strong>{{ $user->status }}</strong>. Only an active account can be granted administrator access — activate it first.
          </p>
        @endif
      </div>
    @endif
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var phoneLocal = document.getElementById('phoneLocal');
      var phoneHidden = document.querySelector('form input[name="phone"]');
      var saveBtn = document.getElementById('saveBtn');
      var form = saveBtn.closest('form');

      phoneLocal.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
      });

      form.addEventListener('submit', function (e) {
        var digits = phoneLocal.value.replace(/\D/g, '');
        if (digits.length > 0 && digits.length !== 9) {
          e.preventDefault();
          phoneLocal.closest('.invite-field').querySelector('.field-error').textContent =
            'Enter the 9-digit number after +639.';
          phoneLocal.focus();
          return;
        }
        phoneHidden.value = digits.length === 9 ? '+639' + digits : '';
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
      });
    });
  </script>
@endsection