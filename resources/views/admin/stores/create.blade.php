@extends('layouts.sidebar')

@section('title', 'Add Store')

@section('content')
  <style>
    .store-page { position: relative; max-width: 940px; }
    .store-page::before,
    .store-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .store-page::before { width: 300px; height: 300px; background: rgba(94,144,112,0.14); top: -70px; right: -60px; }
    .store-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

    .header-row { position: relative; z-index: 1; margin-bottom: 20px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; max-width: 720px; line-height: 1.55; }

    .form-card {
      position: relative; z-index: 1;
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 26px;
    }

    .section-title {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.13em;
      color: #6b7c72; margin: 4px 0 16px 0;
    }
    .section-title::after { content: ""; flex: 1; height: 1px; background: #e8ede9; }
    .section-title.spaced { margin-top: 26px; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

    .invite-field label { display: block; font-size: 12px; font-weight: 700; color: #3d4a41; margin-bottom: 6px; }
    .invite-field label .req { color: #dc2626; }
    .invite-field input,
    .invite-field select {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field input:focus,
    .invite-field select:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .invite-field input[readonly] { background: #f1f4f1; color: #5b6b60; cursor: default; }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }
    .field-note { display: block; font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.45; }

    .phone-row {
      display: flex; align-items: stretch; border: 1px solid #e2e8f0; border-radius: 9px;
      background: #fafbfa; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .phone-row:focus-within { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .phone-prefix {
      display: inline-flex; align-items: center; gap: 7px; padding: 0 11px;
      background: #eef1ef; border-right: 1px solid #e6ebe8;
      font-size: 14px; font-weight: 700; letter-spacing: 0.03em;
      color: #aab5ac; user-select: none; cursor: default;
    }
    .ph-flag {
      width: 22px; height: 15px; display: block; border-radius: 2.5px;
      box-shadow: 0 0 0 1px rgba(20, 61, 35, 0.15);
      opacity: 0.75; filter: saturate(0.45);
    }
    .phone-row input {
      flex: 1; min-width: 0; border: none !important; background: transparent !important;
      box-shadow: none !important; padding: 11px 13px !important;
    }

    .form-alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      margin-bottom: 18px; line-height: 1.45;
    }
    .form-alert.error { background: #fdf2f2; color: #9b2c2c; border: 1px solid #fde8e8; }
    .form-alert svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; flex-shrink: 0; }

    .form-actions {
      display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;
      margin-top: 22px; padding-top: 18px; border-top: 1px solid #eef1ef;
    }
    .btn-users {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer;
      font-family: inherit; letter-spacing: 0.01em; line-height: 1;
      background: #14532d; border: 1px solid rgba(255,255,255,0.10);
      color: #f4faf5; padding: 11px 20px; border-radius: 11px; font-size: 13px; font-weight: 600;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(20,61,35,0.06), 0 4px 12px rgba(20,61,35,0.10);
    }
    .btn-users:hover { background: #0f4228; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(20,61,35,0.08), 0 7px 16px rgba(20,61,35,0.14); }
    .btn-users:active { transform: translateY(0); box-shadow: 0 1px 2px rgba(20,61,35,0.06); }
    .btn-users:focus-visible { outline: 3px solid rgba(74,122,84,0.28); outline-offset: 2px; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }

    .rule-strip {
      display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 12px;
      background: #f3f8f4; border: 1px solid #dce8df; color: #2f4a39;
      font-size: 13px; line-height: 1.55; margin-bottom: 22px;
    }
    .rule-strip svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; margin-top: 1px; }
    .rule-strip b { color: #14532d; }
  </style>

  <div class="store-page">
    <x-breadcrumb :items="[
      ['label' => 'Stores', 'url' => route('admin.stores.index')],
      ['label' => 'Add Store'],
    ]"/>

    <div class="header-row">
      <h1>Register a Store</h1>
      <p>A store account belongs to an outside business — it does not make the owner a member of the association. The owner is emailed a
         6&#8209;digit activation code and chooses their own password on first sign&#8209;in.</p>
    </div>

    <div class="form-card">
      <div class="rule-strip">
        <svg viewBox="0 0 24 24"><path d="M21 10c0-7-9-13-9-13s-9 6-9 13a9 9 0 0 0 18 0z"/><circle cx="12" cy="10" r="3"/><path d="M2 10h.01"/><path d="M22 10h.01"/></svg>
        <span>
          After registering, the store appears in the list with an empty paperwork file.
          <b>Legal papers are filed one at a time</b> from the store's own page — pick the document type from the
          dropdown, attach that one file, and add the next.
        </span>
      </div>

      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.stores.store') }}" id="storeForm" novalidate>
        @csrf
        <input type="hidden" name="phone" value="{{ old('phone') }}">

        <div class="section-title">Store Details</div>
        <div class="form-grid">
          <div class="invite-field full">
            <label>Store / Business Name <span class="req">*</span></label>
            <input type="text" name="store_name" value="{{ old('store_name') }}" placeholder="Aquilala Agri Depot" maxlength="255">
            <small class="field-error">@error('store_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Street Address <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="address" value="{{ old('address') }}" placeholder="Purok 4, National Highway" maxlength="500">
            <small class="field-error">@error('address'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Barangay <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <select name="barangay">
              <option value="">— select a barangay —</option>
              @foreach (config('bulan.barangays') as $barangay)
                <option value="{{ $barangay }}" @selected(old('barangay') === $barangay)>{{ $barangay }}</option>
              @endforeach
            </select>
            <small class="field-error">@error('barangay'){{ $message }}@enderror</small>
            <span class="field-note">Bulan, Sorsogon's 63 barangays only.</span>
          </div>
          <div class="invite-field">
            <label>City / Municipality <span style="color:#94a3b8;font-weight:400;">(fixed)</span></label>
            <input type="text" name="city" value="{{ old('city', config('bulan.city')) }}" readonly maxlength="100">
            <small class="field-error">@error('city'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Province <span style="color:#94a3b8;font-weight:400;">(fixed)</span></label>
            <input type="text" name="province" value="{{ old('province', config('bulan.province')) }}" readonly maxlength="100">
            <small class="field-error">@error('province'){{ $message }}@enderror</small>
          </div>
        </div>

        <div class="section-title spaced">Owner's Account</div>
        <div class="form-grid">
          <div class="invite-field">
            <label>First Name <span class="req">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" maxlength="50" placeholder="Juan">
            <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Middle Name <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="50" placeholder="D.">
            <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Last Name <span class="req">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" maxlength="50" placeholder="Dela Cruz">
            <small class="field-error">@error('last_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Phone <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
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
              <input type="tel" id="phoneLocal" inputmode="numeric" autocomplete="off"
                     maxlength="9" placeholder="171234567"
                     value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : '' }}">
            </div>
            <small class="field-error">@error('phone'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="store@example.com" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
            <small style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;">
              The owner signs in with this address and receives the activation code here.
            </small>
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.stores.index') }}" class="btn-users ghost">Cancel</a>
          <button type="submit" class="btn-users" id="storeSubmitBtn">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Register Store
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('storeForm');
      var phoneLocal = document.getElementById('phoneLocal');
      var phoneHidden = form.querySelector('input[name="phone"]');
      var submitBtn = document.getElementById('storeSubmitBtn');

      phoneLocal.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
      });

      form.addEventListener('submit', function (e) {
        var digits = phoneLocal.value.replace(/\D/g, '');
        if (digits.length > 0 && digits.length !== 9) {
          e.preventDefault();
          phoneLocal.closest('.invite-field').querySelector('.field-error').textContent =
            'Enter the 9-digit number after +639, or leave it empty.';
          phoneLocal.focus();
          return;
        }
        phoneHidden.value = digits.length === 9 ? '+639' + digits : '';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Registering…';
      });
    });
  </script>
@endsection
