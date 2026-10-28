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
    .invite-field select,
    .invite-field textarea {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field textarea { min-height: 92px; resize: vertical; line-height: 1.5; }
    .invite-field input[type="file"] { padding: 9px 10px; background: #fff; }
    .invite-field input:focus,
    .invite-field select:focus,
    .invite-field textarea:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .invite-field input[readonly] { background: #f1f4f1; color: #5b6b60; cursor: default; }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }

    /* Dropdowns: the same chevron treatment as the catalog filter selects —
       the native arrow is hidden and drawn back as a background so the full
       tap target the browser gives on phones is kept. */
    .invite-field select {
      -webkit-appearance: none; -moz-appearance: none; appearance: none;
      padding-right: 34px;
      background-color: #fafbfa;
      background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      cursor: pointer;
    }
    .invite-field select:hover { border-color: #b9cec0; }
    .schedule-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .schedule-label {
      display: block; font-size: 11px; font-weight: 700; color: #64748b;
      margin: 0 0 5px 2px; text-transform: uppercase; letter-spacing: 0.06em;
    }
    .time-control { position: relative; }
    .time-control input { padding-right: 42px; }
    .time-control::after {
      content: ""; position: absolute; right: 13px; top: 50%; width: 15px; height: 15px;
      transform: translateY(-50%); pointer-events: none; opacity: .58;
      background: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpolyline points='12 7 12 12 15 14'/%3E%3C/svg%3E") center / contain no-repeat;
    }
    @media (max-width: 480px) { .schedule-row { grid-template-columns: 1fr; gap: 10px; } }

    /* Date picker: same box as the text inputs, with the calendar icon
       tinted and clickable-looking the way the Weather Log dates behave. */
    .invite-field input[type="date"] { cursor: pointer; }
    .invite-field input[type="date"]::-webkit-calendar-picker-indicator {
      opacity: 0.45; cursor: pointer; transition: opacity 0.15s ease;
    }
    .invite-field input[type="date"]:hover::-webkit-calendar-picker-indicator,
    .invite-field input[type="date"]:focus::-webkit-calendar-picker-indicator { opacity: 0.8; }

    /* File picker: a light button (same green family as the rest of the
       admin, kept soft) instead of the raw browser control. The real input
       stays in the DOM — only visually hidden — so the label still opens
       the file dialog and the form still posts the file. */
    .file-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .file-row input[type="file"] {
      position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none;
    }
    .file-btn {
      display: inline-flex; align-items: center; gap: 7px;
      background: #edf1e9; color: #4A7A54;
      border: 1.5px solid #d8e3db; border-radius: 9px;
      padding: 9px 16px; font-size: 13px; font-weight: 700;
      cursor: pointer; user-select: none;
      transition: all 0.15s ease;
    }
    .file-btn:hover { background: #e4ece0; border-color: #b9cec0; transform: translateY(-1px); }
    .file-btn:active { transform: translateY(0); }
    .file-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .file-name { font-size: 12px; color: #94a3b8; word-break: break-all; }
    .file-name.has-file { color: #4A7A54; font-weight: 600; }

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
  </style>

  <div class="store-page">
    <x-breadcrumb :items="[
      ['label' => 'Stores', 'url' => route('admin.stores.index')],
      ['label' => 'Add Store'],
    ]"/>

    <div class="header-row">
      <h1>Register a Store</h1>
      <p>This is an administrator-only transcription of the store's paper application submitted in person.
         Complete the business and owner sections from the offline form; the store owner does not fill this out.
         The owner is emailed a 6&#8209;digit activation code and chooses their own password on first sign&#8209;in.</p>
    </div>

    <div class="form-card">
      @if ($errors->any())
        <div class="form-alert error">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.stores.store') }}" id="storeForm" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="phone" value="{{ old('phone') }}">

        <div class="section-title">Store Details</div>
        <div class="form-grid">
          <div class="invite-field full">
            <label>Store / Business Name <span class="req">*</span></label>
            <input type="text" name="store_name" value="{{ old('store_name') }}" placeholder="Aquilala Agri Depot" maxlength="255">
            <small class="field-error">@error('store_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Business Type</label>
            <input type="text" name="business_type" value="{{ old('business_type') }}" placeholder="Agricultural supply store or service provider" maxlength="100">
            <small class="field-error">@error('business_type'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Ownership Type</label>
            <select name="ownership_type">
              <option value="">— select ownership —</option>
              @foreach (['Sole proprietorship', 'Partnership', 'Corporation', 'Cooperative', 'Other'] as $ownership)
                <option value="{{ $ownership }}" @selected(old('ownership_type') === $ownership)>{{ $ownership }}</option>
              @endforeach
            </select>
            <small class="field-error">@error('ownership_type'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Business / Store Description</label>
            <textarea name="business_description" maxlength="2000" placeholder="Describe the store's agricultural services, clientele, and main activities. Do not list products.">{{ old('business_description') }}</textarea>
            <small class="field-error">@error('business_description'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Business Registration No.</label>
            <input type="text" name="business_registration_number" value="{{ old('business_registration_number') }}" placeholder="DTI / SEC / cooperative number" maxlength="100">
            <small class="field-error">@error('business_registration_number'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Tax Identification No.</label>
            <input type="text" name="tax_identification_number" value="{{ old('tax_identification_number') }}" placeholder="TIN, if stated on the application" maxlength="50">
            <small class="field-error">@error('tax_identification_number'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Year Established</label>
            <input type="number" id="yearEstablished" name="year_established" value="{{ old('year_established') }}" min="1900" max="{{ now()->year }}" placeholder="{{ now()->year }}">
            <small class="field-error">@error('year_established'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Years Operating</label>
            <input type="number" id="yearsOperating" name="years_operating" value="{{ old('years_operating') }}" min="0" max="200" placeholder="e.g. 8">
            <small class="field-error">@error('years_operating'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Operating Status</label>
            <select name="operating_status">
              <option value="">— select status —</option>
              @foreach (['Currently operating', 'Seasonal', 'Temporarily closed'] as $status)
                <option value="{{ $status }}" @selected(old('operating_status') === $status)>{{ $status }}</option>
              @endforeach
            </select>
            <small class="field-error">@error('operating_status'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Usual Operating Days</label>
            <input type="hidden" id="operatingDays" name="operating_days" value="{{ old('operating_days') }}">
            <div class="schedule-row">
              <div>
                <label class="schedule-label" for="operatingDaysFrom">From</label>
                <select id="operatingDaysFrom">
                  <option value="">Select day</option>
                  @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                    <option value="{{ $day }}">{{ $day }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="schedule-label" for="operatingDaysTo">To</label>
                <select id="operatingDaysTo">
                  <option value="">Select day</option>
                  @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                    <option value="{{ $day }}">{{ $day }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <small class="field-error">@error('operating_days'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Usual Operating Hours</label>
            <input type="hidden" id="operatingHours" name="operating_hours" value="{{ old('operating_hours') }}">
            <div class="schedule-row">
              <div>
                <label class="schedule-label" for="operatingHoursFrom">Opens at</label>
                <div class="time-control">
                  <input type="time" id="operatingHoursFrom" aria-label="Opening time">
                </div>
              </div>
              <div>
                <label class="schedule-label" for="operatingHoursTo">Closes at</label>
                <div class="time-control">
                  <input type="time" id="operatingHoursTo" aria-label="Closing time">
                </div>
              </div>
            </div>
            <small class="field-error">@error('operating_hours'){{ $message }}@enderror</small>
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
            <label>Sex</label>
            <select name="sex">
              <option value="">— select —</option>
              @foreach (['Female', 'Male', 'Prefer not to say'] as $sex)
                <option value="{{ $sex }}" @selected(old('sex') === $sex)>{{ $sex }}</option>
              @endforeach
            </select>
            <small class="field-error">@error('sex'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Birth Date</label>
            <input type="date" name="birth_date" value="{{ old('birth_date') }}">
            <small class="field-error">@error('birth_date'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Civil Status</label>
            <select name="civil_status">
              <option value="">— select —</option>
              @foreach (['Single', 'Married', 'Widowed', 'Separated', 'Prefer not to say'] as $status)
                <option value="{{ $status }}" @selected(old('civil_status') === $status)>{{ $status }}</option>
              @endforeach
            </select>
            <small class="field-error">@error('civil_status'){{ $message }}@enderror</small>
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
                     maxlength="9" placeholder="0000 000 00"
                     value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : '' }}">
            </div>
            <small class="field-error">@error('phone'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="store@example.com" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Owner's Residential Address</label>
            <input type="text" name="owner_address" value="{{ old('owner_address') }}" placeholder="House number, street, barangay, city and province" maxlength="500">
            <small class="field-error">@error('owner_address'){{ $message }}@enderror</small>
          </div>
        </div>

        <div class="section-title spaced">Legal Papers from the Offline Application</div>
        <p style="font-size:12px;color:#64748b;line-height:1.55;margin:-6px 0 16px;">
          Attach up to five scanned or photographed papers received from the store. Select the matching paper type for each file.
          PDFs, JPGs and PNGs up to 10 MB are accepted.
        </p>
        <div class="form-grid">
          @for ($i = 0; $i < 5; $i++)
            <div class="invite-field">
              <label>Paper {{ $i + 1 }} type</label>
              <select name="legal_document_types[{{ $i }}]">
                <option value="">— no paper —</option>
                @foreach (\App\Models\StoreDocument::TYPES as $type)
                  <option value="{{ $type }}" @selected(old("legal_document_types.$i") === $type)>{{ $type }}</option>
                @endforeach
              </select>
              <small class="field-error">@error("legal_document_types.$i"){{ $message }}@enderror</small>
            </div>
            <div class="invite-field">
              <label>Paper {{ $i + 1 }} file</label>
              <div class="file-row">
                <input type="file" id="legal_file_{{ $i }}" name="legal_files[{{ $i }}]" accept=".pdf,.jpg,.jpeg,.png">
                <label for="legal_file_{{ $i }}" class="file-btn">
                  <svg viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                  Choose file
                </label>
                <span class="file-name" data-file-name="{{ $i }}">No file chosen</span>
              </div>
              <small class="field-error">@error("legal_files.$i"){{ $message }}@enderror</small>
            </div>
          @endfor
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
      var yearEstablished = document.getElementById('yearEstablished');
      var yearsOperating = document.getElementById('yearsOperating');
      var operatingDays = document.getElementById('operatingDays');
      var operatingDaysFrom = document.getElementById('operatingDaysFrom');
      var operatingDaysTo = document.getElementById('operatingDaysTo');
      var operatingHours = document.getElementById('operatingHours');
      var operatingHoursFrom = document.getElementById('operatingHoursFrom');
      var operatingHoursTo = document.getElementById('operatingHoursTo');
      var currentYear = {{ now()->year }};

      function syncYears(source) {
        var established = parseInt(yearEstablished.value, 10);
        var operating = parseInt(yearsOperating.value, 10);

        if (source === 'established' && Number.isInteger(established)) {
          if (established >= 1900 && established <= currentYear) {
            yearsOperating.value = currentYear - established;
          } else {
            yearsOperating.value = '';
          }
        }

        if (source === 'operating' && Number.isInteger(operating)) {
          if (operating >= 0 && operating <= 200) {
            yearEstablished.value = currentYear - operating;
          } else {
            yearEstablished.value = '';
          }
        }
      }

      function setSelectFromStoredDays() {
        var stored = (operatingDays.value || '').replace(/\s*[–—-]\s*/g, '-');
        var parts = stored.split('-');
        if (parts.length === 2) {
          operatingDaysFrom.value = parts[0].trim();
          operatingDaysTo.value = parts[1].trim();
        }
      }

      function updateStoredDays() {
        operatingDays.value = operatingDaysFrom.value && operatingDaysTo.value
          ? operatingDaysFrom.value + '-' + operatingDaysTo.value
          : '';
      }

      function toDisplayTime(value) {
        var parts = (value || '').split(':');
        if (parts.length !== 2) return '';
        var hour = parseInt(parts[0], 10);
        var minute = parts[1];
        if (!Number.isInteger(hour) || !/^\d{2}$/.test(minute)) return '';
        var suffix = hour >= 12 ? 'PM' : 'AM';
        var displayHour = hour % 12 || 12;
        return displayHour + ':' + minute + ' ' + suffix;
      }

      function fromDisplayTime(value) {
        var match = (value || '').trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return '';
        var hour = parseInt(match[1], 10);
        var minute = match[2];
        var suffix = match[3].toUpperCase();
        if (hour < 1 || hour > 12) return '';
        if (suffix === 'PM' && hour !== 12) hour += 12;
        if (suffix === 'AM' && hour === 12) hour = 0;
        return String(hour).padStart(2, '0') + ':' + minute;
      }

      function setTimesFromStoredHours() {
        var stored = (operatingHours.value || '').trim();
        var parts = stored.split(/\s*[-–—]\s*/);
        if (parts.length === 2) {
          operatingHoursFrom.value = fromDisplayTime(parts[0]);
          operatingHoursTo.value = fromDisplayTime(parts[1]);
        }
      }

      function updateStoredHours() {
        operatingHours.value = operatingHoursFrom.value && operatingHoursTo.value
          ? toDisplayTime(operatingHoursFrom.value) + '-' + toDisplayTime(operatingHoursTo.value)
          : '';
      }

      yearEstablished.addEventListener('input', function () { syncYears('established'); });
      yearsOperating.addEventListener('input', function () { syncYears('operating'); });
      operatingDaysFrom.addEventListener('change', updateStoredDays);
      operatingDaysTo.addEventListener('change', updateStoredDays);
      operatingHoursFrom.addEventListener('change', updateStoredHours);
      operatingHoursTo.addEventListener('change', updateStoredHours);
      setSelectFromStoredDays();
      setTimesFromStoredHours();

      if (!yearEstablished.value && yearsOperating.value) {
        syncYears('operating');
      } else if (yearEstablished.value && !yearsOperating.value) {
        syncYears('established');
      }

      phoneLocal.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
      });

      // File picker buttons: show the chosen file name beside the button.
      document.querySelectorAll('.file-row input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
          var nameEl = document.querySelector('[data-file-name="' + this.id.replace('legal_file_', '') + '"]');
          if (!nameEl) return;
          if (this.files && this.files.length) {
            nameEl.textContent = this.files[0].name;
            nameEl.classList.add('has-file');
          } else {
            nameEl.textContent = 'No file chosen';
            nameEl.classList.remove('has-file');
          }
        });
      });

      form.addEventListener('submit', function (e) {
        updateStoredDays();
        updateStoredHours();
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
