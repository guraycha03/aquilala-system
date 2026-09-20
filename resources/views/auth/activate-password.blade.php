@extends('layouts.auth-layout')

@section('title', 'Choose Password')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Great, your email is verified</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        Welcome, @if (isset($maskedEmail)) <strong style="color: #143d23;">{{ $maskedEmail }}</strong>! @endif
        One last step — choose a password for your Aquilala account.
    </p>

    <div style="display: flex; align-items: center; gap: 14px; background: #f6f8f7; border: 1px solid #e2e8f0; border-left: 3px solid #5DC257; border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;">
        <div style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: rgb(172, 242, 121); color: #14532d; display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div style="font-size: 13px; color: #606d64; line-height: 1.5;">
            Your password must be at least <strong style="color: #143d23;">8 characters</strong>.
            After this step, you will be signed in automatically.
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('account.activation.complete') }}" id="passwordForm" novalidate>
        @csrf

        <div style="margin-bottom: 16px;">
            <label for="password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                New Password
            </label>
            <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
                   placeholder="At least 8 characters"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <div style="margin-bottom: 24px;">
            <label for="password-confirm" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Confirm Password
            </label>
            <input type="password" id="password-confirm" name="password_confirmation" required autocomplete="new-password" minlength="8"
                   placeholder="Repeat your password"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <button type="submit" style="width: 100%; background: #16a34a; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: background 0.2s, transform 0.2s;">
            Activate My Account
        </button>
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Something went wrong?
        <a href="{{ route('account.activation.index') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Start over</a>
    </p>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('passwordForm');
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