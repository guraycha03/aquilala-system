@extends('layouts.auth-layout')

@section('title', 'Verify Email')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Verify your email</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        A 6-digit verification code was sent to your email. Enter it below to activate your administrator account.
    </p>

    <div style="display: flex; align-items: center; gap: 14px; background: #f6f8f7; border: 1px solid #e2e8f0; border-left: 3px solid #8FB89A; border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;">
        <div style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: rgb(172, 242, 121); color: #14532d; display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
        </div>
        <div style="font-size: 13px; color: #606d64; line-height: 1.5;">
            The code is valid for <strong style="color: #143d23;">10 minutes</strong>, can be used
            <strong style="color: #143d23;">once</strong>, and is limited to
            <strong style="color: #143d23;">5 attempts</strong>.
        </div>
    </div>

    @if (session('success'))
        <div style="background: #edf1e9; color: #648268; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
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

    <form method="POST" action="{{ route('email.verify') }}" id="otpForm" novalidate>
        @csrf

        <div style="margin-bottom: 16px;">
            <label for="verify-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Email Address
            </label>
            <input type="email" id="verify-email" name="email" value="{{ old('email') }}" required placeholder="name@gmail.com"
                   autocomplete="email"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <div style="margin-bottom: 24px;">
            <label for="verify-otp" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                6-Digit Verification Code
            </label>
            <input type="text" id="verify-otp" name="otp" value="{{ old('otp') }}" required inputmode="numeric"
                   autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 18px; letter-spacing: 0.4em; text-align: center; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <button type="submit" style="width: 100%; background: #4A7A54; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: background 0.2s, transform 0.2s;">
            Verify Account
        </button>
    </form>

    <div style="margin: 20px 0 0 0; text-align: center;">
        <p style="margin: 0 0 4px 0; color: #606d64; font-size: 13px; letter-spacing: 0.03em;">
            Didn't receive the code? Check your spam folder, or
        </p>
        <button type="button" id="resend-btn" onclick="resendCode()"
                style="background: none; border: none; color: #436149; font-size: 13px; font-weight: 700; letter-spacing: 0.05em; cursor: pointer; text-decoration: underline;">
            Resend Code
        </button>
    </div>

    <form method="POST" action="{{ route('email.verify.resend') }}" id="resendForm">
        @csrf
        <input type="hidden" name="email" id="resend-email">
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Already verified?
        <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Log in</a>
    </p>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var emailInput = document.getElementById('verify-email');
        var otpInput = document.getElementById('verify-otp');
        var form = document.getElementById('otpForm');
        var resendEmail = document.getElementById('resend-email');

        // Mirror any email typed here into the hidden resend field.
        emailInput.addEventListener('input', function () {
            resendEmail.value = this.value.trim();
        });

        if (emailInput.value) resendEmail.value = emailInput.value.trim();

        // Digits only, 6 max.
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        // Prevent submitting an obviously incomplete code.
        form.addEventListener('submit', function (e) {
            if (!/^\d{6}$/.test(otpInput.value)) {
                e.preventDefault();
                otpInput.style.border = '1px solid #e87070';
                otpInput.focus();
            }
        });
    });

    function resendCode() {
        var email = document.getElementById('verify-email').value.trim();
        var btn = document.getElementById('resend-btn');

        if (!email) {
            document.getElementById('verify-email').style.border = '1px solid #e87070';
            document.getElementById('verify-email').focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending…';
        document.getElementById('resend-email').value = email;
        document.getElementById('resendForm').submit();
    }
</script>
@endsection