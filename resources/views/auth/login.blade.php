@extends('layouts.auth-layout')

@section('title', 'Login')

@section('form-content')
    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    @endpush

    <style>
        /* Login page uses the Urbanist font (test only on this page) */
        .auth-container { font-family: 'Urbanist', ui-sans-serif, system-ui, sans-serif; }

        /* Make the brand logo slightly larger (login page only) */
        .form-brand-logo { width: 64px; height: 64px; }

        /* More breathing room below the Aquilala brand name (login page only) */
        .form-brand-sub { margin-top: 8px; }

        /* Consistent, breathable letter spacing across the whole login page */
        .auth-container .form-brand-name,
        .auth-container .form-brand-sub,
        .auth-container .visual-pane h2,
        .auth-container .visual-pane p { letter-spacing: 0.03em; }

        /* Log In button — matches the app's primary green */
        .login-btn { background: #16a34a; color: #ffffff; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); }
        .login-btn:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3); }

        /* Letter-spacing on typed input text */
        #loginForm input:not([type="hidden"]) { letter-spacing: 0.05em; color: #4a554d; }

        /* Keep autofilled text from turning dark/black */
        #loginForm input:-webkit-autofill,
        #loginForm input:-webkit-autofill:hover,
        #loginForm input:-webkit-autofill:focus,
        #loginForm input:-webkit-autofill:active {
            -webkit-text-fill-color: #4a554d;
            -webkit-box-shadow: 0 0 0 1000px #fafbfa inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Login method tabs */
        .method-tabs {
            display: flex; gap: 4px; background: #eef1ef;
            border-radius: 8px; padding: 4px; margin-bottom: 20px;
        }
        .method-tab {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 9px 10px; border: none; border-radius: 6px; background: transparent;
            font-size: 13px; font-weight: 700; color: #606d64; cursor: pointer;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s; font-family: inherit;
        }
        .method-tab.active { background: #ffffff; color: #436149; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08); }
        .method-tab svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        /* Philippine mobile prefix (+639, locked) */
        .phone-row {
            display: flex; align-items: stretch;
            border: 1px solid #e2e8f0; border-radius: 8px;
            background: #fafbfa; overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .phone-row:focus-within { border-color: #7fae8b; }
        .phone-prefix {
            display: inline-flex; align-items: center; gap: 7px; padding: 0 12px;
            background: #eef1ef; border-right: 1px solid #e6ebe8;
            font-size: 15px; font-weight: 700; letter-spacing: 0.03em;
            color: #aab5ac; /* faded: locked, not editable */
            user-select: none; cursor: default; white-space: nowrap;
        }
        .ph-flag {
            width: 22px; height: 15px; display: block; border-radius: 2.5px;
            box-shadow: 0 0 0 1px rgba(20, 61, 35, 0.15);
            opacity: 0.75; filter: saturate(0.45); /* faded to match locked prefix */
        }
        .phone-row input { flex: 1; min-width: 0; border: none; background: transparent; padding: 11px 14px; font-size: 15px; outline: none; }
    </style>

    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: 0.03em;">Welcome back</h1>
    <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px; letter-spacing: 0.03em;">Sign in to access your dashboard.</p>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
        @csrf
        <input type="hidden" name="login_method" id="login_method" value="{{ old('login_method', 'email') }}">

        {{-- Email / Phone toggle --}}
        <div class="method-tabs" role="tablist" aria-label="Login method">
            <button type="button" id="tab-email" class="method-tab active" role="tab" aria-selected="true" onclick="setLoginMethod('email')">
                <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                Email
            </button>
            <button type="button" id="tab-phone" class="method-tab" role="tab" aria-selected="false" onclick="setLoginMethod('phone')">
                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Phone Number
            </button>
        </div>

        {{-- Email credential --}}
        <div id="email-group" style="margin-bottom: 16px;">
            <label for="login-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Email Address <span style="color: #e87070; font-size: 24px; font-weight: 400; font-family: Georgia, 'Times New Roman', serif; position: relative; top: -7px; vertical-align: middle; line-height: 1;">∗</span>
            </label>
            <input type="email" id="login-email" name="email" value="{{ old('email') }}" required placeholder="name@gmail.com"
                   autocomplete="email" aria-required="true"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
            <div id="login-email-feedback" style="font-size: 12px; margin-top: 4px; min-height: 18px; transition: color 0.2s;"></div>
        </div>

        {{-- Phone credential (locked +639 prefix) --}}
        <div id="phone-group" style="margin-bottom: 16px; display: none;">
            <label for="login-phone-local" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Phone Number <span style="color: #e87070; font-size: 24px; font-weight: 400; font-family: Georgia, 'Times New Roman', serif; position: relative; top: -7px; vertical-align: middle; line-height: 1;">∗</span>
            </label>
            <input type="hidden" name="phone" value="{{ old('phone') }}">
            <div class="phone-row">
                <span class="phone-prefix" title="Philippine mobile numbers only — prefix is fixed">
                    <svg class="ph-flag" viewBox="0 0 36 25" role="img" aria-label="Philippine flag">
                        <rect width="36" height="12.5" fill="#0038a8"/>
                        <rect y="12.5" width="36" height="12.5" fill="#ce1126"/>
                        <path d="M0 0 L17 12.5 L0 25 Z" fill="#ffffff"/>
                        <g stroke="#fcd116" stroke-width="1.1" stroke-linecap="round">
                            <line x1="5.6" y1="7.4" x2="5.6" y2="17.6"/>
                            <line x1="0.5" y1="12.5" x2="10.7" y2="12.5"/>
                            <line x1="1.9" y1="8.8" x2="9.3" y2="16.2"/>
                            <line x1="1.9" y1="16.2" x2="9.3" y2="8.8"/>
                        </g>
                        <circle cx="5.6" cy="12.5" r="2.3" fill="#fcd116"/>
                    </svg>
                    +639
                </span>
                <input type="tel" id="login-phone-local" inputmode="numeric" autocomplete="tel-national"
                       maxlength="9" placeholder="171234567" aria-required="true"
                       value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : '' }}" />
            </div>
            <div id="login-phone-local-feedback" style="font-size: 12px; margin-top: 4px; min-height: 18px; transition: color 0.2s;"></div>
        </div>

        <div style="margin-bottom: 24px;">
            <label for="login-password" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; letter-spacing: 0.05em;">
                Password <span style="color: #e87070; font-size: 24px; font-weight: 400; font-family: Georgia, 'Times New Roman', serif; position: relative; top: -7px; vertical-align: middle; line-height: 1;">∗</span>
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

        <div style="text-align: right; margin: -14px 0 18px 0;">
            <a href="{{ route('password.request') }}" style="color: #436149; font-size: 13px; font-weight: 600; letter-spacing: 0.03em; text-decoration: none;">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="login-btn" style="width: 100%; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.08em; cursor: pointer; transition: background 0.2s, transform 0.2s, box-shadow 0.2s;">
            Log In
        </button>

        <div style="margin: 24px 0 0 0; text-align: center; font-family: sans-serif;">
            <p style="margin: 0; color: #606d64; font-size: 12px; font-weight: 500; letter-spacing: 0.03em; text-wrap: balance;">
                Need access? Contact the Aquilala administrator.
            </p>
            <p style="margin: 6px 0 0 0; color: #8a968e; font-size: 12px; letter-spacing: 0.03em; text-wrap: balance;">
                Visit the IA Office, Barangay Aquino, Bulan, Sorsogon.
            </p>
        </div>

    </form>
@endsection

@section('scripts')
<script>
    // ── Login method toggle (Email / Phone) ──
    function setLoginMethod(method) {
        const methodInput = document.getElementById('login_method');
        const tabEmail = document.getElementById('tab-email');
        const tabPhone = document.getElementById('tab-phone');
        const emailGroup = document.getElementById('email-group');
        const phoneGroup = document.getElementById('phone-group');
        const emailInput = document.getElementById('login-email');
        const phoneHidden = document.querySelector('#phone-group input[name="phone"]');

        methodInput.value = method;

        const isEmail = method === 'email';
        tabEmail.classList.toggle('active', isEmail);
        tabPhone.classList.toggle('active', !isEmail);
        tabEmail.setAttribute('aria-selected', isEmail ? 'true' : 'false');
        tabPhone.setAttribute('aria-selected', isEmail ? 'false' : 'true');

        emailGroup.style.display = isEmail ? 'block' : 'none';
        phoneGroup.style.display = isEmail ? 'none' : 'block';

        // Only submit the active credential
        emailInput.disabled = !isEmail;
        if (!isEmail) emailInput.removeAttribute('required');
        else emailInput.setAttribute('required', '');
        phoneHidden.disabled = isEmail;
    }

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
            inputEl.style.border = '1px solid #16a34a';
            inputEl.style.boxShadow = 'none';
            feedbackEl.style.color = '#16a34a';
            feedbackEl.textContent = message || '✓ Valid';
        } else {
            inputEl.style.border = '1px solid #e87070';
            inputEl.style.boxShadow = 'none';
            feedbackEl.style.color = '#d05b5b';
            feedbackEl.textContent = message || 'Required';
        }
    }

    function clearFieldState(inputEl, feedbackEl) {
        inputEl.style.borderColor = '#e2e8f0';
        inputEl.style.boxShadow = 'none';
        feedbackEl.textContent = '';
    }

    function setPhoneRowState(isValid, message) {
        const row = document.querySelector('#phone-group .phone-row');
        const fb = document.getElementById('login-phone-local-feedback');
        if (isValid) {
            row.style.border = '1px solid #16a34a';
            row.style.boxShadow = 'none';
            fb.style.color = '#16a34a';
            fb.textContent = message || '✓ Valid';
        } else {
            row.style.border = '1px solid #e87070';
            row.style.boxShadow = 'none';
            fb.style.color = '#d05b5b';
            fb.textContent = message || 'Required';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');

        // Restore last-used method after a failed attempt (old() round-trip)
        setLoginMethod(document.getElementById('login_method').value === 'phone' ? 'phone' : 'email');

        // Phone digits-only enforcement
        const phoneLocal = document.getElementById('login-phone-local');
        phoneLocal.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });

        // Compose full E.164 number on submit
        form.addEventListener('submit', function (e) {
            if (document.getElementById('login_method').value !== 'phone') return;

            const digits = phoneLocal.value.replace(/\D/g, '');
            if (digits.length !== 9) {
                e.preventDefault();
                setPhoneRowState(false, 'Enter the 9-digit number after +639.');
                phoneLocal.focus();
                return;
            }
            document.querySelector('#phone-group input[name="phone"]').value = '+639' + digits;
        });
    });

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
            setFieldState(this, fb, true, '✓ Valid email format');
        }
    });

    // ── Login Phone validation ──
    document.getElementById('login-phone-local').addEventListener('blur', function () {
        const digits = this.value.replace(/\D/g, '');
        if (this.disabled) return;
        if (digits.length === 0) {
            setPhoneRowState(false, 'Phone number is required.');
        } else if (digits.length !== 9) {
            setPhoneRowState(false, 'Enter the 9-digit number after +639.');
        } else {
            setPhoneRowState(true, '✓ Phone number is valid');
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
            setFieldState(this, fb, true, '✓ Password meets requirements');
        }
    });

    // ── Clear red state on focus for all fields ──
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function () {
            const fieldId = this.getAttribute('id');
            const fb = fieldId ? document.getElementById(fieldId + '-feedback') : null;
            if (fb) fb.textContent = '';

            const row = this.closest('.phone-row');
            if (row) {
                // Phone input is borderless; the wrapper carries the border
                this.style.border = 'none';
                this.style.boxShadow = 'none';
                row.style.border = '1px solid #7fae8b';
                row.style.boxShadow = 'none';
            } else if (fb) {
                this.style.border = '1px solid #7fae8b';
                this.style.boxShadow = 'none';
            }
        });
    });
</script>
@endsection

