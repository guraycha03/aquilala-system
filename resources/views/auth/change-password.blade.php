@extends('layouts.sidebar')

@section('title', 'Change Password')

@section('content')
  <style>
    .cp-page {
      max-width: 560px; margin: 0 auto;
      padding: 8px 4px 40px;
    }
    .cp-card {
      background: #ffffff; border: 1px solid rgba(20,61,35,0.08); border-radius: 18px;
      box-shadow: 0 4px 18px rgba(20,61,35,0.06), 0 1px 3px rgba(20,61,35,0.03);
      padding: 28px 30px;
    }
    .cp-card h1 { font-size: 20px; font-weight: 700; color: #143d23; letter-spacing: 0.02em; margin: 0 0 6px 0; }
    .cp-card .cp-sub { font-size: 13px; color: #64748b; line-height: 1.55; margin: 0 0 22px 0; }
    .cp-field { margin-bottom: 16px; }
    .cp-field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em; }
    .cp-field input {
      width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;
      font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;
    }
    .cp-btn {
      width: 100%; background: #14532d; color: #ffffff; border: none; padding: 12px 14px; border-radius: 10px;
      font-size: 14px; font-weight: 600; letter-spacing: 0.05em; cursor: pointer; font-family: inherit;
      box-shadow: 0 4px 12px rgba(20,61,35,0.25); transition: background 0.2s, transform 0.2s;
    }
    .cp-btn:hover { background: #0f4228; }
    .cp-note {
      background: #f6f8f7; border-left: 3px solid #5DC257; border-radius: 6px;
      padding: 12px 16px; font-size: 13px; color: #475569; line-height: 1.6;
    }
    .cp-alert-success {
      background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;
      padding: 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px;
    }
    .cp-alert-error {
      background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8;
      padding: 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px;
    }
    .cp-alert-error ul { margin: 0; padding-left: 20px; line-height: 1.5; }
    @media (max-width: 768px) {
      .cp-card { padding: 22px 20px; }
    }
  </style>

  <div class="cp-page">
    @if (session('success'))
      <div class="cp-alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="cp-alert-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="cp-alert-error">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="cp-card">
      <h1>Set your own password</h1>
      <p class="cp-sub">
        This account is still using the temporary password from your invitation email.
        Choose a permanent password to unlock the rest of {{ config('app.name') }}.
      </p>

      <div class="cp-note" style="margin-bottom: 22px;">
        Strengths of a good password: at least 8 characters, a mix of letters &amp; numbers.
        Don't reuse the temporary password from your email.
      </div>

      <form method="POST" action="{{ route('password.change.post') }}" id="changePwForm" novalidate>
        @csrf

        <div class="cp-field">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                 placeholder="Temporary password from your email" />
        </div>

        <div class="cp-field">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
                 placeholder="At least 8 characters" />
        </div>

        <div class="cp-field" style="margin-bottom: 24px;">
          <label for="password-confirm">Confirm New Password</label>
          <input type="password" id="password-confirm" name="password_confirmation" required autocomplete="new-password" minlength="8"
                 placeholder="Repeat your new password" />
        </div>

        <button type="submit" class="cp-btn">Update Password</button>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('changePwForm');
    var password = document.getElementById('password');
    var confirm = document.getElementById('password-confirm');

    form.addEventListener('submit', function (e) {
      if (password.value.length < 8) {
        e.preventDefault();
        password.style.border = '1px solid #e87070';
        password.focus();
        return;
      }
      if (password.value !== confirm.value) {
        e.preventDefault();
        confirm.style.border = '1px solid #e87070';
        confirm.focus();
      }
    });
  });
</script>
@endsection