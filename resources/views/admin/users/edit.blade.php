@extends('layouts.sidebar')

@section('title', 'Edit ' . $user->name)

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
    .invite-field input:focus, .invite-field select:focus { border-color: #5DC257; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .invite-field input[readonly] { background: #f1f3f2; color: #94a3b8; cursor: not-allowed; }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }

    .phone-row {
      display: flex; align-items: stretch; border: 1px solid #e2e8f0; border-radius: 9px;
      background: #fafbfa; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .phone-row:focus-within { border-color: #5DC257; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
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
    .form-alert.info { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
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
        @if($user->isPendingVerification())
          <span class="profile-chip warn">
            Pending verification
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </span>
        @elseif($user->isPendingApproval())
          <span class="profile-chip warn">
            Pending approval
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </span>
        @elseif($user->status === \App\Models\User::STATUS_SUSPENDED)
          <span class="profile-chip danger">
            Suspended
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

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      @if($user->isPendingVerification())
        <div class="form-alert info">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          This account is still awaiting email activation. You can resend the activation link from the Users list.
        </div>
      @elseif($user->isPendingApproval())
        <div class="form-alert info">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          This registration is awaiting approval. Approve it from the Users list to email the secure activation link.
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
              @if($status === \App\Models\User::STATUS_PENDING_APPROVAL)
                <option value="{{ \App\Models\User::STATUS_PENDING_APPROVAL }}" @selected($status === \App\Models\User::STATUS_PENDING_APPROVAL)>Pending approval (unchanged)</option>
              @elseif($status === \App\Models\User::STATUS_PENDING_VERIFICATION)
                <option value="{{ \App\Models\User::STATUS_PENDING_VERIFICATION }}" @selected($status === \App\Models\User::STATUS_PENDING_VERIFICATION)>Pending verification (unchanged)</option>
              @endif
              <option value="{{ \App\Models\User::STATUS_ACTIVE }}" @selected($status === \App\Models\User::STATUS_ACTIVE)>Active</option>
              <option value="{{ \App\Models\User::STATUS_SUSPENDED }}" @selected($status === \App\Models\User::STATUS_SUSPENDED)>Suspended</option>
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
            'Enter the 9-digit number after +639 (e.g. 171234567), or clear the field to leave it blank.';
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