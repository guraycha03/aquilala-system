@extends('layouts.auth-layout')

@section('title', 'Login')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Welcome back</h1>
    <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px;">Sign in to access your dashboard workspace.</p>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" novalidate>
        @csrf
        <div style="margin-bottom: 16px;">
            <label for="login-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                Email Address <span style="color: #dc2626;">*</span>
            </label>
            <input type="email" id="login-email" name="email" value="{{ old('email') }}" required placeholder="name@gmail.com"
                   autocomplete="email" aria-required="true"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
            <div id="login-email-feedback" style="font-size: 12px; margin-top: 4px; min-height: 18px; transition: color 0.2s;"></div>
        </div>

        <div style="margin-bottom: 24px;">
            <label for="login-password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                Password <span style="color: #dc2626;">*</span>
            </label>
            <div style="position: relative;">
                <input type="password" id="login-password" name="password" required placeholder="••••••••"
                       minlength="8" aria-required="true"
                       style="width: 100%; padding: 11px 44px 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
                
                <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #606d64;">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path id="eye-open" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                        <circle id="eye-pupil" cx="12" cy="12" r="3"></circle>
                        <line id="eye-slash" x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2" style="display: none;"></line>
                    </svg>
                </button>
            </div>
            <div id="login-password-feedback" style="font-size: 12px; margin-top: 4px; min-height: 18px; transition: color 0.2s;"></div>
        </div>

        <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
            Log In
        </button>

        <p style="margin: 20px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
            Don't have an account yet? 
            <a href="{{ route('register') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Register</a>
        </p>
    </form>
@endsection

@section('scripts')
<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('login-password');
        const eyeSlash = document.getElementById('eye-slash');
        const eyePupil = document.getElementById('eye-pupil');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeSlash.style.display = 'block';
            eyePupil.style.display = 'none';
        } else {
            passwordInput.type = 'password';
            eyeSlash.style.display = 'none';
            eyePupil.style.display = 'block';
        }
    }

    // ── Inline validation helpers ──
    function setFieldState(inputEl, feedbackEl, isValid, message) {
        if (isValid) {
            inputEl.style.borderColor = '#16a34a';
            inputEl.style.boxShadow = '0 0 0 2px rgba(22, 163, 74, 0.15)';
            feedbackEl.style.color = '#16a34a';
            feedbackEl.textContent = message || '✓ Looks good!';
        } else {
            inputEl.style.borderColor = '#dc2626';
            inputEl.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.15)';
            feedbackEl.style.color = '#dc2626';
            feedbackEl.textContent = message || 'Required';
        }
    }

    function clearFieldState(inputEl, feedbackEl) {
        inputEl.style.borderColor = '#e2e8f0';
        inputEl.style.boxShadow = 'none';
        feedbackEl.textContent = '';
    }

    // ── Login Email validation ──
    document.getElementById('login-email').addEventListener('blur', function () {
        const val = this.value.trim();
        const fb = document.getElementById('login-email-feedback');
        if (val.length === 0) {
            if (this.hasAttribute('required')) {
                setFieldState(this, fb, false, 'Email address is required.');
            } else {
                clearFieldState(this, fb);
            }
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            setFieldState(this, fb, false, 'Please enter a valid email address.');
        } else {
            setFieldState(this, fb, true);
        }
    });

    // ── Login Password validation ──
    document.getElementById('login-password').addEventListener('blur', function () {
        const val = this.value;
        const fb = document.getElementById('login-password-feedback');
        if (val.length === 0) {
            setFieldState(this, fb, false, 'Password is required.');
        } else if (val.length < 8) {
            setFieldState(this, fb, false, 'Password must be at least 8 characters.');
        } else {
            setFieldState(this, fb, true);
        }
    });

    // ── Clear red state on focus for all fields ──
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function () {
            const fieldId = this.getAttribute('id');
            if (fieldId) {
                const fb = document.getElementById(fieldId + '-feedback');
                if (fb) {
                    this.style.borderColor = '#e2e8f0';
                    this.style.boxShadow = 'none';
                    fb.textContent = '';
                }
            }
        });
    });
</script>
@endsection

