@extends('layouts.auth-layout')

@section('title', 'Confirm Account')

@section('form-content')
    @if ($state === 'form')
        <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Confirm your account</h1>
        <p style="margin: 0 0 20px 0; color: #606d64; font-size: 14px;">You were invited to join the Aquilala System. Set a password below to confirm your phone number and activate your account.</p>

        <div style="display: flex; align-items: center; gap: 14px; background: #f6f8f7; border: 1px solid #e2e8f0; border-left: 3px solid #5DC257; border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;">
            <div style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: rgb(172, 242, 121); color: #14532d; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
                <div style="font-size: 15px; font-weight: 700; color: #232f27;">{{ $user->name }}</div>
                <div style="font-size: 13px; color: #606d64;">
                    Registered phone number:
                    <strong style="color: #143d23;">{{ $user->phone }}</strong>
                </div>
                <div style="font-size: 12px; color: #94a3b8;">{{ $user->email }}</div>
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

        <form method="POST" action="{{ route('account.activation.complete') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                    Set Your Password <span style="color: #dc2626;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••" aria-required="true"
                           style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
                    <button type="button" onclick="toggleVisibility('password')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path id="password-eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                            <circle id="password-eye-pupil" cx="12" cy="12" r="3"></circle>
                            <line id="password-eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                        </svg>
                    </button>
                </div>
                <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
            </div>

            <div class="field-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                    Confirm Password <span style="color: #dc2626;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" placeholder="••••••••" aria-required="true"
                           style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
                    <button type="button" onclick="toggleVisibility('password_confirmation')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path id="password_confirmation-eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                            <circle id="password_confirmation-eye-pupil" cx="12" cy="12" r="3"></circle>
                            <line id="password_confirmation-eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                        </svg>
                    </button>
                </div>
                <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
            </div>

            <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
                Activate My Account
            </button>

            <p style="margin: 20px 0 0 0; color: #606d64; font-size: 13px; text-align: center;">
                Already activated?
                <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Log in</a>
            </p>
        </form>
    @elseif ($state === 'expired')
        <div style="text-align: center; padding: 12px 4px;">
            <div style="width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%; background: #fef9ec; color: #a57f43; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <h1 style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #232f27;">Link Expired</h1>
            <p style="margin: 0 0 8px; color: #606d64; font-size: 14px; line-height: 1.6;">
                This activation link is valid for 72 hours and it has now expired.
                For your security, expired links cannot be reused.
            </p>
            <p style="margin: 0 0 24px; color: #606d64; font-size: 14px;">
                Please ask the Aquilala administrator to send you a new invitation.
            </p>
            <a href="{{ route('login') }}" style="display: inline-block; background: #436149; color: #ffffff; text-decoration: none; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 600;">
                Go to Login
            </a>
        </div>
    @elseif ($state === 'already_active')
        <div style="text-align: center; padding: 12px 4px;">
            <div style="width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%; background: #eef3ef; color: #3f7a4f; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1 style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #232f27;">Account Already Active</h1>
            <p style="margin: 0 0 24px; color: #606d64; font-size: 14px; line-height: 1.6;">
                This invitation has already been used and the account is fully activated.
                You can sign in with your email and password.
            </p>
            <a href="{{ route('login') }}" style="display: inline-block; background: #436149; color: #ffffff; text-decoration: none; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 600;">
                Go to Login
            </a>
        </div>
    @else
        <div style="text-align: center; padding: 12px 4px;">
            <div style="width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%; background: #fdf2f2; color: #9b2c2c; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <h1 style="margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #232f27;">Invalid Activation Link</h1>
            <p style="margin: 0 0 24px; color: #606d64; font-size: 14px; line-height: 1.6;">
                We couldn't verify this activation link — it may be malformed, already used, or revoked.
                Please request a fresh invitation from the Aquilala administrator.
            </p>
            <a href="{{ route('login') }}" style="display: inline-block; background: #436149; color: #ffffff; text-decoration: none; padding: 11px 28px; border-radius: 8px; font-size: 14px; font-weight: 600;">
                Go to Login
            </a>
        </div>
    @endif
@endsection

@section('scripts')
@if ($state === 'form')
<script>
    function toggleVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const eyeSlash = document.getElementById(fieldId + '-eye-slash');
        const eyePupil = document.getElementById(fieldId + '-eye-pupil');

        if (input.type === 'password') {
            input.type = 'text';
            if (eyeSlash) eyeSlash.style.display = 'block';
            if (eyePupil) eyePupil.style.display = 'none';
        } else {
            input.type = 'password';
            if (eyeSlash) eyeSlash.style.display = 'none';
            if (eyePupil) eyePupil.style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const passEl = document.getElementById('password');
        const confirmEl = document.getElementById('password_confirmation');

        function setState(inputEl, isValid, message) {
            const group = inputEl.closest('.field-group');
            const feedback = group ? group.querySelector('.field-feedback') : null;
            inputEl.style.borderColor = isValid ? '#16a34a' : '#dc2626';
            inputEl.style.boxShadow = isValid ? '0 0 0 3px rgba(22, 163, 74, 0.18)' : '0 0 0 3px rgba(220, 38, 38, 0.18)';
            if (feedback) {
                feedback.style.color = isValid ? '#16a34a' : '#dc2626';
                feedback.textContent = message;
            }
        }

        passEl.addEventListener('blur', function () {
            if (!this.value) setState(this, false, 'Password is required.');
            else if (this.value.length < 8) setState(this, false, 'Password must be at least 8 characters.');
            else setState(this, true, '✓ Looks good!');
        });

        confirmEl.addEventListener('blur', function () {
            if (!this.value) setState(this, false, 'Please confirm your password.');
            else if (this.value !== passEl.value) setState(this, false, 'Passwords do not match.');
            else setState(this, true, '✓ Passwords match.');
        });
    });
</script>
@endif
@endsection
