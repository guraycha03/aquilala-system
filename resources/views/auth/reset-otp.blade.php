@extends('layouts.auth-layout')

@section('title', 'Verify Code')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Enter the verification code</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        We emailed a 6-digit verification code to the address on your account. Enter it below to reset your password.
    </p>

    @if (session('success'))
        <div style="background: #edf1e9; color: #648268; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('info') }}
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

    <form method="POST" action="{{ route('password.verify.post') }}" id="resetForm" novalidate>
        @csrf

        <div style="margin-bottom: 16px;">
            <label for="reset-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Email Address
            </label>
            <input type="email" id="reset-email" name="email" value="{{ old('email', session('reset_email_hint')) }}" required placeholder="name@gmail.com"
                   autocomplete="email"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <div style="margin-bottom: 24px;">
            <label for="reset-otp" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                6-Digit Verification Code
            </label>
            <input type="text" id="reset-otp" name="otp" value="{{ old('otp') }}" required inputmode="numeric"
                   autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 18px; letter-spacing: 0.4em; text-align: center; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <button type="submit" style="width: 100%; background: #4A7A54; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: background 0.2s, transform 0.2s;">
            Verify Code
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

    <form method="POST" action="{{ route('password.email') }}" id="resendForm">
        @csrf
        <input type="hidden" name="email" id="resend-email">
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Remembered it?
        <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Back to log in</a>
    </p>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var emailInput = document.getElementById('reset-email');
        var otpInput = document.getElementById('reset-otp');
        var form = document.getElementById('resetForm');
        var resendEmail = document.getElementById('resend-email');

        emailInput.addEventListener('input', function () {
            resendEmail.value = this.value.trim();
        });

        if (emailInput.value) resendEmail.value = emailInput.value.trim();

        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        form.addEventListener('submit', function (e) {
            if (!/^\d{6}$/.test(otpInput.value)) {
                e.preventDefault();
                otpInput.style.border = '1px solid #e87070';
                otpInput.focus();
            }
        });
    });

    function resendCode() {
        var email = document.getElementById('reset-email').value.trim();
        var btn = document.getElementById('resend-btn');

        if (!email) {
            document.getElementById('reset-email').style.border = '1px solid #e87070';
            document.getElementById('reset-email').focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending…';
        document.getElementById('resend-email').value = email;
        document.getElementById('resendForm').submit();
    }
</script>
@endsection