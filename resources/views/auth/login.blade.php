@extends('layouts.auth-layout')

@section('title', 'Login')

@section('form-content')
    <style>
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
        .phone-row:focus-within { border-color: #436149; box-shadow: 0 0 0 3px rgba(67, 97, 73, 0.12); }
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
            <label for="login-email" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                Email Address <span style="color: #dc2626;">*</span>
            </label>
            <input type="email" id="login-email" name="email" value="{{ old('email') }}" required placeholder="name@gmail.com"
                   autocomplete="email" aria-required="true"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
            <div id="login-email-feedback" style="font-size: 12px; margin-top: 4px; min-height: 18px; transition: color 0.2s;"></div>
        </div>

        {{-- Phone credential (locked +639 prefix) --}}
        <div id="phone-group" style="margin-bottom: 16px; display: none;">
            <label for="login-phone-local" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                Phone Number <span style="color: #dc2626;">*</span>
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
            inputEl.style.borderColor = '#16a34a';
            inputEl.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.18)';
            feedbackEl.style.color = '#16a34a';
            feedbackEl.textContent = message || '✓ Looks good!';
        } else {
            inputEl.style.borderColor = '#dc2626';
            inputEl.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.18)';
            feedbackEl.style.color = '#dc2626';
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
            row.style.borderColor = '#16a34a';
            row.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.18)';
            fb.style.color = '#16a34a';
            fb.textContent = message || '✓ Looks good!';
        } else {
            row.style.borderColor = '#dc2626';
            row.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.18)';
            fb.style.color = '#dc2626';
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
                setPhoneRowState(false, 'Enter the 9-digit number after +639 (e.g. 171234567).');
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
            setFieldState(this, fb, true);
        }
    });

    // ── Login Phone validation ──
    document.getElementById('login-phone-local').addEventListener('blur', function () {
        const digits = this.value.replace(/\D/g, '');
        if (this.disabled) return;
        if (digits.length === 0) {
            setPhoneRowState(false, 'Phone number is required.');
        } else if (digits.length !== 9) {
            setPhoneRowState(false, 'Enter the 9-digit number after +639 (e.g. 171234567).');
        } else {
            setPhoneRowState(true);
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
                // Reset locked-prefix row styling as well
                const row = this.closest('.phone-row');
                if (row) {
                    row.style.borderColor = '#e2e8f0';
                    row.style.boxShadow = 'none';
                }
            }
        });
    });
</script>
@endsection

