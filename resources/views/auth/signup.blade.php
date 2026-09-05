@php $hideVisualPane = true; @endphp

@extends('layouts.auth-layout')

@section('title', 'Member Registration')

@section('form-content')
    <style>
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
            color: #aab5ac; user-select: none; cursor: default; white-space: nowrap;
        }
        .ph-flag {
            width: 22px; height: 15px; display: block; border-radius: 2.5px;
            box-shadow: 0 0 0 1px rgba(20, 61, 35, 0.15);
            opacity: 0.75; filter: saturate(0.45);
        }
        .input {
            width: 100%; padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
            box-sizing: border-box; font-size: 15px; background: #fafbfa; color: #2c3530;
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        .input:focus { border-color: #436149; box-shadow: 0 0 0 3px rgba(67, 97, 73, 0.12); }
        select.input { appearance: none; }
        .label {
            display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #3d4a41;
        }
        .field-feedback { display: block; min-height: 18px; font-size: 12px; margin-top: 4px; }
        .req { color: #dc2626; }
        .opt { color: #9ca3af; font-weight: 400; font-size: 11px; }
        .section-title {
            display: flex; align-items: center; gap: 9px; margin: 26px 0 14px 0;
        }
        .section-title .st-icon {
            width: 30px; height: 30px; flex-shrink: 0; border-radius: 8px;
            background: rgb(172, 242, 121); color: #14532d;
            display: flex; align-items: center; justify-content: center;
        }
        .section-title .st-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .section-title .st-text { font-size: 14px; font-weight: 700; color: #232f27; }
        .section-title .st-sub { font-size: 11px; color: #94a3b8; font-weight: 400; }
        .section-divider { height: 1px; background: #eef1ef; margin: 0 0 4px 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .area-hint { font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.4; }
        @media (max-width: 440px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>

    <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #232f27; letter-spacing: -0.025em;">Apply for membership</h1>
    <p style="margin: 0 0 4px 0; color: #606d64; font-size: 14px;">
        Fill in your member details below. Your application will be reviewed by the association administrator.
    </p>
    <p style="margin: 0 0 24px 0; color: #94a3b8; font-size: 12px;">
        You will set your own password only after your membership is approved.
    </p>

    @if ($errors->any())
        <div style="background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}" id="signupForm" novalidate>
        @csrf

        <input type="hidden" name="role" value="farmer">
        <input type="hidden" name="phone" value="{{ old('phone') }}">

        {{-- ── Personal Information ── --}}
        <div class="section-divider"></div>
        <div class="section-title">
            <span class="st-icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <div class="st-text">Personal Information</div>
        </div>

        <div class="grid-2" style="margin-bottom: 8px;">
            <div class="field-group">
                <label class="label">First Name <span class="req">*</span></label>
                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                       placeholder="Juan" pattern="^[\pL\s\-]+$" aria-required="true" class="input" />
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">Last Name <span class="req">*</span></label>
                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                       placeholder="Dela Cruz" pattern="^[\pL\s\-]+$" aria-required="true" class="input" />
                <small class="field-feedback"></small>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom: 8px;">
            <div class="field-group">
                <label class="label">Middle Initial <span class="opt">(optional)</span></label>
                <input type="text" name="middle_initial" id="middle_initial" value="{{ old('middle_initial') }}" maxlength="2"
                       placeholder="D." class="input" style="text-align:center;" />
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">Sex</label>
                <select name="sex" class="input">
                    <option value="">— Select —</option>
                    <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                    <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                </select>
                <small class="field-feedback"></small>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom: 8px;">
            <div class="field-group">
                <label class="label">Date of Birth</label>
                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}"
                       max="{{ date('Y-m-d') }}" class="input" />
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">Civil Status</label>
                <select name="civil_status" class="input">
                    <option value="">— Select —</option>
                    @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $status)
                        <option value="{{ $status }}" @selected(old('civil_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <small class="field-feedback"></small>
            </div>
        </div>

        {{-- ── Contact Information ── --}}
        <div class="section-divider"></div>
        <div class="section-title">
            <span class="st-icon"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
            <div class="st-text">Contact Information</div>
        </div>

        <div class="field-group" style="margin-bottom: 16px;">
            <label class="label">Email Address <span class="req">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="name@gmail.com" aria-required="true" class="input" />
            <small class="field-feedback"></small>
        </div>

        <div class="field-group" style="margin-bottom: 16px;">
            <label for="phone_local" class="label">Mobile Number <span class="req">*</span></label>
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
                <input type="tel" id="phone_local" inputmode="numeric" autocomplete="tel-national"
                       maxlength="9" placeholder="171234567" aria-required="true"
                       value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : '' }}"
                       style="flex: 1; min-width: 0; border: none; background: transparent; padding: 11px 14px; font-size: 15px; outline: none;" />
            </div>
            <small class="field-feedback"></small>
        </div>

        {{-- ── Address ── --}}
        <div class="section-divider"></div>
        <div class="section-title">
            <span class="st-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div class="st-text">Home Address</div>
        </div>

        <div class="field-group" style="margin-bottom: 16px;">
            <label class="label">House / Street Address</label>
            <input type="text" name="address" id="address" value="{{ old('address') }}" maxlength="500"
                   placeholder="123 Purok Santol, Sitio Mahayahay" class="input" />
            <small class="field-feedback"></small>
        </div>

        <div class="grid-2" style="margin-bottom: 16px;">
            <div class="field-group">
                <label class="label">Barangay</label>
                <input type="text" name="barangay" id="barangay" value="{{ old('barangay') }}" maxlength="100"
                       placeholder="Inararan" class="input" />
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">City / Municipality</label>
                <input type="text" name="city" id="city" value="{{ old('city') }}" maxlength="100"
                       placeholder="Bulan" class="input" />
                <small class="field-feedback"></small>
            </div>
        </div>

        <div class="field-group" style="margin-bottom: 16px;">
            <label class="label">Province</label>
            <input type="text" name="province" id="province" value="{{ old('province') }}" maxlength="100"
                   placeholder="Sorsogon" class="input" />
            <small class="field-feedback"></small>
        </div>

        {{-- ── Farm / Membership Details ── --}}
        <div class="section-divider"></div>
        <div class="section-title">
            <span class="st-icon"><svg viewBox="0 0 24 24"><path d="M12 22v-9"/><path d="M8 6l4-4 4 4"/><path d="M17 6v6H7V6"/><path d="M5 22h14"/></svg></span>
            <div class="st-text">Farm &amp; Membership Details</div>
        </div>

        <div class="field-group" style="margin-bottom: 8px;">
            <label class="label">Farm Location <span class="opt">(where your farm is located)</span></label>
            <input type="text" name="farm_location" id="farm_location" value="{{ old('farm_location') }}" maxlength="500"
                   placeholder="e.g. Salog River banks, Barangay Inararan" class="input" />
            <small class="field-feedback"></small>
        </div>

        <div class="grid-2" style="margin-bottom: 8px;">
            <div class="field-group">
                <label class="label">Total Land Area For Farming</label>
                <input type="number" name="default_land_area" id="default_land_area" value="{{ old('default_land_area') }}"
                       step="0.01" min="0" placeholder="2.50" class="input" />
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">Land Area Unit</label>
                <select name="default_land_area_unit" class="input">
                    <option value="">— Select —</option>
                    @foreach (['hectare(s)', 'square meter(s)'] as $unit)
                        <option value="{{ $unit }}" @selected(old('default_land_area_unit') === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
                <small class="field-feedback"></small>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom: 8px;">
            <div class="field-group">
                <label class="label">Land Ownership</label>
                <select name="land_ownership" class="input">
                    <option value="">— Select —</option>
                    @foreach (['Owned', 'Rented', 'Shared', 'Communal'] as $own)
                        <option value="{{ $own }}" @selected(old('land_ownership') === $own)>{{ $own }}</option>
                    @endforeach
                </select>
                <small class="field-feedback"></small>
            </div>
            <div class="field-group">
                <label class="label">Years Farming</label>
                <input type="number" name="years_farming" id="years_farming" value="{{ old('years_farming') }}"
                       min="0" max="120" placeholder="e.g. 8" class="input" />
                <small class="field-feedback"></small>
            </div>
        </div>

        <div class="field-group" style="margin-bottom: 24px;">
            <label class="label">Association Membership Number <span class="opt">(if already a member)</span></label>
            <input type="text" name="assoc_member_no" id="assoc_member_no" value="{{ old('assoc_member_no') }}"
                   maxlength="60" placeholder="e.g. IAI-2026-1043" class="input" />
            <small class="field-feedback"></small>
        </div>

        {{-- Submit --}}
        <button type="submit" id="submitBtn"
                style="width: 100%; background: #436149; color: #ffffff; border: none; padding: 13px 14px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
            Submit Registration Request
        </button>

        <p style="margin: 18px 0 0 0; color: #94a3b8; font-size: 12px; text-align: center; line-height: 1.5;">
            Your application will be reviewed by the Aquilala administrator before you can access the system.
        </p>

        <p style="margin: 14px 0 0 0; color: #606d64; font-size: 14px; text-align: center;">
            Already approved?
            <a href="{{ route('login') }}" style="color: #436149; font-weight: 700; text-decoration: none;">Log in</a>
        </p>
    </form>
@endsection

@section('scripts')
<script>
    function setFieldState(inputEl, isValid, message) {
        const group = inputEl.closest('.field-group');
        if (!group) return;
        const feedbackEl = group.querySelector('.field-feedback');
        if (isValid) {
            inputEl.style.borderColor = '#16a34a';
            inputEl.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.18)';
            if (feedbackEl) { feedbackEl.style.color = '#16a34a'; feedbackEl.textContent = message || '✓ Looks good!'; }
        } else {
            inputEl.style.borderColor = '#dc2626';
            inputEl.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.18)';
            if (feedbackEl) { feedbackEl.style.color = '#dc2626'; feedbackEl.textContent = message || 'Required field'; }
        }
    }
    function clearFieldState(inputEl) {
        const group = inputEl.closest('.field-group');
        inputEl.style.borderColor = '#e2e8f0';
        inputEl.style.boxShadow = 'none';
        if (group) { const fb = group.querySelector('.field-feedback'); if (fb) fb.textContent = ''; }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Required name fields
        ['first_name', 'last_name'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('blur', function () {
                if (!this.value.trim()) setFieldState(this, false, 'This field is required.');
                else setFieldState(this, true);
            });
        });

        // Email
        const emailEl = document.getElementById('email');
        if (emailEl) {
            emailEl.addEventListener('blur', function () {
                const val = this.value.trim();
                if (!val) setFieldState(this, false, 'Email is required.');
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) setFieldState(this, false, 'Please enter a valid email address.');
                else setFieldState(this, true);
            });
        }

        // Reset state on focus
        document.querySelectorAll('.field-group input, .field-group select').forEach(input => {
            input.addEventListener('focus', function () { if (input.tagName === 'INPUT') clearFieldState(this); });
        });

        // ── Phone number (locked +639 prefix) ──
        const form = document.getElementById('signupForm');
        const phoneHidden = form.querySelector('input[name="phone"]');
        const phoneLocal = document.getElementById('phone_local');
        const phoneRow = phoneLocal.closest('.phone-row');

        function setPhoneState(isValid, message) {
            const fb = phoneLocal.closest('.field-group').querySelector('.field-feedback');
            if (isValid) {
                phoneRow.style.borderColor = '#16a34a'; phoneRow.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.18)';
                fb.style.color = '#16a34a'; fb.textContent = message || '✓ Looks good!';
            } else {
                phoneRow.style.borderColor = '#dc2626'; phoneRow.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.18)';
                fb.style.color = '#dc2626'; fb.textContent = message || 'Required field';
            }
        }
        function clearPhoneState() {
            phoneRow.style.borderColor = '#e2e8f0'; phoneRow.style.boxShadow = 'none';
            phoneLocal.closest('.field-group').querySelector('.field-feedback').textContent = '';
        }

        phoneLocal.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
        phoneLocal.addEventListener('blur', function () {
            const digits = this.value.replace(/\D/g, '');
            if (!digits) setPhoneState(false, 'Mobile number is required.');
            else if (digits.length !== 9) setPhoneState(false, 'Enter the 9-digit number after +639 (e.g. 171234567).');
            else setPhoneState(true);
        });
        phoneLocal.addEventListener('focus', clearPhoneState);

        form.addEventListener('submit', function (e) {
            const digits = phoneLocal.value.replace(/\D/g, '');
            if (digits.length !== 9) {
                e.preventDefault();
                setPhoneState(false, 'Enter the 9-digit number after +639 (e.g. 171234567).');
                phoneLocal.focus();
                return;
            }
            phoneHidden.value = '+639' + digits;
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Submitting…';
        });
    });
</script>
@endsection
