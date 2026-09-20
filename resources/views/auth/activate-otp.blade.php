@extends('layouts.auth-layout')

@section('title', 'Activate Account')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Activate your account</h1>
    <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">
        Your account was created by the Aquilala administrator. Enter the 6-digit verification code emailed to you to confirm this account and choose your password.
    </p>

    @if (session('success'))
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('info') }}
        </div>
    @endif

    @if ($tokenExpired)
        <div style="background: #fdf7e7; color: #8a5a00; border: 1px solid #fdebaa; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            This activation link has expired. Enter your email below and a fresh verification code will be sent to you.
        </div>
    @elseif ($state === 'invalid')
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            That activation link was not recognized. Enter your email below and a verification code will be sent to you.
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

    @if ($user)
        <div style="display: flex; align-items: center; gap: 14px; background: #f6f8f7; border: 1px solid #e2e8f0; border-left: 3px solid #5DC257; border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;">
            <div style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: rgb(172, 242, 121); color: #14532d; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
            </div>
            <div style="font-size: 13px; color: #606d64; line-height: 1.5;">
                A code was sent to <strong style="color: #143d23;">{{ \App\Services\EmailOtpService::maskEmail($user->email) }}</strong>.
                It is valid for <strong style="color: #143d23;">10 minutes</strong>, can be used
                <strong style="color: #143d23;">once</strong>, and is limited to
                <strong style="color: #143d23;">5 attempts</strong>.
                @if ($expiresSeconds > 0)
                    <br><span style="color: #8a5a00;">The code expires in
                    <span id="expires-count">{{ gmdate('i:s', $expiresSeconds) }}</span>.</span>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('account.activation.verify') }}" id="activationForm" novalidate>
        @csrf

        <div style="margin-bottom: 16px;">
            <label for="activation-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Email Address
            </label>
            <input type="email" id="activation-email" name="email" value="{{ old('email', $email) }}" required placeholder="name@gmail.com"
                   autocomplete="email" {{ $user ? 'readonly' : '' }}
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: {{ $user ? '#eef1ef' : '#fafbfa' }}; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <div style="margin-bottom: 24px;">
            <label for="activation-otp" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                6-Digit Verification Code
            </label>
            <input type="text" id="activation-otp" name="otp" value="{{ old('otp') }}" required inputmode="numeric"
                   autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 18px; letter-spacing: 0.4em; text-align: center; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        </div>

        <button type="submit" style="width: 100%; background: #16a34a; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: background 0.2s, transform 0.2s;">
            Verify Code
        </button>
    </form>

    <div style="margin: 20px 0 0 0; text-align: center;">
        <p style="margin: 0 0 4px 0; color: #606d64; font-size: 13px; letter-spacing: 0.03em;">
            Didn't receive the code? Check your spam folder, or
        </p>
        <button type="button" id="resend-btn" onclick="resendCode()" data-cooldown="{{ $cooldownSeconds }}"
                style="background: none; border: none; color: #436149; font-size: 13px; font-weight: 700; letter-spacing: 0.05em; cursor: pointer; text-decoration: underline;">
            Resend Code
        </button>
        @if ($cooldownSeconds > 0)
            <span style="color: #8a5a00; font-size: 12px;"> (wait <span id="resend-count">{{ $cooldownSeconds }}</span>s)</span>
        @endif
    </div>

    <form method="POST" action="{{ route('account.activation.resend') }}" id="resendForm">
        @csrf
        <input type="hidden" name="email" id="resend-email">
    </form>

    <p style="margin: 22px 0 0 0; color: #606d64; font-size: 13px; text-align: center; letter-spacing: 0.03em;">
        Already activated?
        <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Log in</a>
    </p>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var emailInput = document.getElementById('activation-email');
        var otpInput = document.getElementById('activation-otp');
        var form = document.getElementById('activationForm');
        var resendEmail = document.getElementById('resend-email');
        var resendBtn = document.getElementById('resend-btn');

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

        // Live countdown for the code-expiry notice.
        var expires = document.getElementById('expires-count');
        if (expires) {
            var remaining = {{ $expiresSeconds }};
            setInterval(function () {
                remaining = Math.max(0, remaining - 1);
                expires.textContent = String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0');
            }, 1000);
        }

        // Live countdown for the resend cooldown.
        if (resendBtn.getAttribute('data-cooldown')) {
            var cooldownRemaining = parseInt(resendBtn.getAttribute('data-cooldown'), 10);
            resendBtn.disabled = true;
            var cooldownTimer = setInterval(function () {
                cooldownRemaining = Math.max(0, cooldownRemaining - 1);
                if (cooldownRemaining <= 0) {
                    clearInterval(cooldownTimer);
                    resendBtn.disabled = false;
                }
            }, 1000);
        }
    });

    function resendCode() {
        var email = document.getElementById('activation-email').value.trim();
        var btn = document.getElementById('resend-btn');

        if (!email) {
            document.getElementById('activation-email').style.border = '1px solid #e87070';
            document.getElementById('activation-email').focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending…';
        document.getElementById('resend-email').value = email;
        document.getElementById('resendForm').submit();
    }
</script>
@endsection