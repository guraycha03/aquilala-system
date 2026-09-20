@extends('layouts.auth-layout')

@section('title', 'Reset Password')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Choose a new password</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        Your email was verified. Set a new password for your Aquilala account (at least 8 characters).
    </p>

    @if (session('success'))
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.reset') }}" id="resetForm" novalidate>
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
            Reset Password
        </button>
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Changed your mind?
        <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Back to log in</a>
    </p>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('resetForm');
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