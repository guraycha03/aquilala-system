@extends('layouts.auth-layout')

@section('title', 'Register')

@section('form-content')
    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Create account</h1>
    <p style="margin: 0 0 24px 0; color: #606d64; font-size: 14px;">Get started by creating your account profile.</p>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}" novalidate>
        @csrf
        
        <input type="hidden" name="role" value="farmer">

        {{-- First Name, M.I., Last Name Grid --}}
<div style="display: grid; grid-template-columns: 2fr 1.1fr 2fr; gap: 12px; margin-bottom: 16px;">
    <div class="field-group">
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; white-space: nowrap;">
            First Name <span style="color: #dc2626;">*</span>
        </label>
        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
               placeholder="Jane" pattern="^[\pL\s\-]+$" aria-required="true"
               style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
    </div>

    <div class="field-group">
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; white-space: nowrap;">
            M.I. <span style="color: #9ca3af; font-weight: 400; font-size: 11px;">(opt.)</span>
        </label>
        <input type="text" name="middle_initial" id="middle_initial" value="{{ old('middle_initial') }}" maxlength="2"
               placeholder="D."
               style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; text-align: center; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
    </div>

    <div class="field-group">
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41; white-space: nowrap;">
            Last Name <span style="color: #dc2626;">*</span>
        </label>
        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
               placeholder="Doe" pattern="^[\pL\s\-]+$" aria-required="true"
               style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
        <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
    </div>
</div>

        {{-- Email Address --}}
        <div class="field-group" style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                Email Address <span style="color: #dc2626;">*</span>
            </label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="name@gmail.com" aria-required="true"
                   style="width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; background: #fafbfa; transition: border-color 0.2s, box-shadow 0.2s; outline: none;" />
            <small class="field-feedback" style="display: block; min-height: 18px; font-size: 12px; margin-top: 4px;"></small>
        </div>

        {{-- Password & Confirm Password --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="field-group">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                    Password <span style="color: #dc2626;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••"
                           aria-required="true"
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
            
            <div class="field-group">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;">
                    Confirm Password <span style="color: #dc2626;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                           placeholder="••••••••" aria-required="true"
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
        </div>

        {{-- Submit Button --}}
        <button type="submit" style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 12px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
            Register Account
        </button>

        {{-- Footer Link --}}
        <p style="margin: 20px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
            Already have an account? 
            <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Log in</a>
        </p>
    </form>
@endsection

@section('scripts')
<script>
    // ── Toggle password eye icons ──
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

    // ── Inline validation helpers ──
    function setFieldState(inputEl, isValid, message) {
        const group = inputEl.closest('.field-group');
        if (!group) return;
        const feedbackEl = group.querySelector('.field-feedback');

        if (isValid) {
            inputEl.style.borderColor = '#16a34a';
            inputEl.style.boxShadow = '0 0 0 2px rgba(22, 163, 74, 0.15)';
            if (feedbackEl) {
                feedbackEl.style.color = '#16a34a';
                feedbackEl.textContent = message || '✓ Looks good!';
            }
        } else {
            inputEl.style.borderColor = '#dc2626';
            inputEl.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.15)';
            if (feedbackEl) {
                feedbackEl.style.color = '#dc2626';
                feedbackEl.textContent = message || 'Required field';
            }
        }
    }

    function clearFieldState(inputEl) {
        const group = inputEl.closest('.field-group');
        inputEl.style.borderColor = '#e2e8f0';
        inputEl.style.boxShadow = 'none';
        if (group) {
            const feedbackEl = group.querySelector('.field-feedback');
            if (feedbackEl) feedbackEl.textContent = '';
        }
    }

    // ── Validation logic on Blur ──
    document.addEventListener('DOMContentLoaded', () => {
        // Name validations
        ['first_name', 'last_name'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    setFieldState(this, false, 'This field is required.');
                } else {
                    setFieldState(this, true);
                }
            });
        });

        // Email validation
        const emailEl = document.getElementById('email');
        if (emailEl) {
            emailEl.addEventListener('blur', function() {
                const val = this.value.trim();
                if (!val) {
                    setFieldState(this, false, 'Email is required.');
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    setFieldState(this, false, 'Please enter a valid email address.');
                } else {
                    setFieldState(this, true);
                }
            });
        }

        // Password validation
        const passEl = document.getElementById('password');
        const confirmEl = document.getElementById('password_confirmation');

        if (passEl) {
            passEl.addEventListener('blur', function() {
                if (!this.value) {
                    setFieldState(this, false, 'Password is required.');
                } else if (this.value.length < 8) {
                    setFieldState(this, false, 'Password must be at least 8 characters.');
                } else {
                    setFieldState(this, true);
                }
            });
        }

        if (confirmEl) {
            confirmEl.addEventListener('blur', function() {
                if (!this.value) {
                    setFieldState(this, false, 'Please confirm your password.');
                } else if (passEl && this.value !== passEl.value) {
                    setFieldState(this, false, 'Passwords do not match.');
                } else {
                    setFieldState(this, true);
                }
            });
        }

        // Reset state on input focus
        document.querySelectorAll('.field-group input').forEach(input => {
            input.addEventListener('focus', function() {
                clearFieldState(this);
            });
        });
    });
</script>
@endsection