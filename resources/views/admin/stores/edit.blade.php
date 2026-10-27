@extends('layouts.sidebar')

@section('title', ($store->store_name ?: $store->name) . ' — Manage Store')

@section('content')
  <style>
    .store-page { position: relative; max-width: 980px; }
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
      margin-bottom: 20px;
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
    .invite-field input {
      width: 100%; padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 14px; background: #fafbfa; outline: none; transition: border-color 0.15s, box-shadow 0.15s;
      box-sizing: border-box; color: #334155; font-family: inherit;
    }
    .invite-field input:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .field-error { display: block; min-height: 16px; font-size: 11px; color: #dc2626; margin-top: 4px; }

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
    .form-alert.success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
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
    .btn-users:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }
    .btn-users.danger { background: #b91c1c; border-color: rgba(153,27,27,0.3); box-shadow: 0 1px 2px rgba(127,29,29,0.12), 0 4px 12px rgba(127,29,29,0.14); }
    .btn-users.danger:hover { background: #991b1b; box-shadow: 0 2px 4px rgba(127,29,29,0.14), 0 7px 16px rgba(127,29,29,0.18); }
    .btn-users.small { padding: 8px 14px; font-size: 12px; border-radius: 9px; }

    /* ---- Paperwork ---- */
    .doc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .doc-head h2 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .doc-head p { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.55; max-width: 620px; }

    .doc-form {
      display: grid; grid-template-columns: minmax(220px, 1.3fr) minmax(220px, 1.2fr) auto;
      gap: 12px; align-items: start; margin-top: 18px;
      padding: 16px; border-radius: 13px; background: #f7fbf4; border: 1px solid #dce8df;
    }
    @media (max-width: 760px) { .doc-form { grid-template-columns: 1fr; } }

    .doc-form label { display: block; font-size: 11px; font-weight: 700; color: #3d4a41; margin-bottom: 6px; }
    .doc-form select,
    .doc-form input[type="file"] {
      width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 9px;
      font-size: 13.5px; background: #fff; outline: none; box-sizing: border-box;
      color: #334155; font-family: inherit;
    }
    .doc-form select:focus,
    .doc-form input[type="file"]:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .doc-form .btn-users { align-self: end; height: 40px; }

    .doc-list { display: grid; gap: 9px; margin-top: 18px; }
    .doc-item {
      display: flex; align-items: center; gap: 14px; padding: 13px 15px;
      border: 1px solid #e8ede9; border-radius: 13px; background: #fff;
    }
    .doc-icon {
      width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
      background: #eef3ef; color: #3f7a4f;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .doc-icon svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .doc-body { flex: 1; min-width: 0; }
    .doc-type { font-size: 13.5px; font-weight: 700; color: #1e293b; line-height: 1.3; }
    .doc-meta { font-size: 11.5px; color: #8a97a5; margin-top: 3px; line-height: 1.45; word-break: break-word; }
    .doc-notes { font-size: 11.5px; color: #5b6b78; margin-top: 4px; font-style: italic; }
    .doc-actions { display: flex; gap: 7px; flex-shrink: 0; }

    .doc-empty {
      text-align: center; padding: 34px 18px; margin-top: 18px;
      border: 1px dashed #cdd9d1; border-radius: 13px; background: #fbfcfb; color: #94a3b8;
    }
    .doc-empty svg { width: 40px; height: 40px; stroke: currentColor; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; opacity: 0.75; }
    .doc-empty p { font-size: 13px; margin: 12px 0 0 0; line-height: 1.6; }

    .danger-zone {
      display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;
      padding: 18px 20px; border-radius: 14px;
      background: #fef6f6; border: 1px solid #f7dede;
    }
    .danger-zone h3 { font-size: 14px; font-weight: 700; color: #991b1b; margin: 0 0 4px 0; }
    .danger-zone p { font-size: 12.5px; color: #8b5b5b; margin: 0; line-height: 1.55; max-width: 620px; }
  </style>

  <div class="store-page">
    <x-breadcrumb :items="[
      ['label' => 'Stores', 'url' => route('admin.stores.index')],
      ['label' => $store->store_name ?: $store->name],
    ]"/>

    @if (session('success'))
      <div class="form-alert success">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
      </div>
    @endif
    @if (session('error'))
      <div class="form-alert error">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="form-alert error">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ $errors->first() }}
      </div>
    @endif

    <div class="header-row">
      <h1>{{ $store->store_name ?: $store->name }}</h1>
      <p>
        Outside business · managed by {{ $store->name }} · {{ $store->email }} ·
        {{ $store->documents_count ?? $store->documents->count() }} legal paper(s) on file.
      </p>
    </div>

    {{-- ── Paperwork: one document at a time ─────────────────────────── --}}
    <div class="form-card" id="paperwork">
      <div class="doc-head">
        <div>
          <h2>Legal Papers</h2>
          <p>Choose the type of paper you are filing, then attach that one file. Repeat for each document — there are no
             fixed slots to fill, so a store can be registered before all of its paperwork has arrived.</p>
        </div>
      </div>

      <form class="doc-form" method="POST" action="{{ route('admin.stores.documents.store', $store) }}" enctype="multipart/form-data" id="docForm">
        @csrf
        <div>
          <label for="document_type">Document Type <span style="color:#dc2626;">*</span></label>
          <select name="document_type" id="document_type" required>
            <option value="" disabled {{ old('document_type') ? '' : 'selected' }}>— Select document type —</option>
            @foreach ($types as $type)
              <option value="{{ $type }}" {{ old('document_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="file">File <span style="color:#dc2626;">*</span> <span style="color:#94a3b8;font-weight:400;">(PDF / JPG / PNG, max 10 MB)</span></label>
          <input type="file" name="file" id="file" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn-users" id="docSubmitBtn">
          <svg viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
          File Paper
        </button>
      </form>

      @php $documents = $store->documents; @endphp

      @if ($documents->isEmpty())
        <div class="doc-empty">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          <p>No legal paper has been filed for this store yet.<br>Choose a document type above and attach the first one.</p>
        </div>
      @else
        <div class="doc-list">
          @foreach ($documents as $document)
            <div class="doc-item">
              <span class="doc-icon">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              </span>
              <span class="doc-body">
                <span class="doc-type">{{ $document->document_type }}</span>
                <span class="doc-meta">
                  {{ $document->original_name }}
                  @if ($document->size_bytes) · {{ $document->sizeLabel() }} @endif
                  · filed {{ $document->created_at->format('M d, Y') }}
                  @if ($document->uploader) · by {{ $document->uploader->name }} @endif
                </span>
                @if ($document->notes)
                  <span class="doc-notes">{{ $document->notes }}</span>
                @endif
              </span>
              <span class="doc-actions">
                <a class="btn-users ghost small" href="{{ route('admin.stores.documents.download', [$store, $document]) }}" title="Download">
                  <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
                <form method="POST" action="{{ route('admin.stores.documents.destroy', [$store, $document]) }}"
                      data-aqui-confirm="Remove the {{ $document->document_type }} filed for {{ $store->store_name ?: $store->name }}? The file itself will be deleted."
                      data-aqui-danger data-aqui-title="Remove filed paper">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn-users danger small" title="Remove">
                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </form>
              </span>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- ── Store + owner details ────────────────────────────────────── --}}
    <div class="form-card">
      <div class="section-title">Store Details</div>

      <form method="POST" action="{{ route('admin.stores.update', $store) }}" id="storeForm" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="phone" value="{{ old('phone', $store->contact_number ?? ($store->phone ?? '')) }}">

        <div class="form-grid">
          <div class="invite-field full">
            <label>Store / Business Name <span class="req">*</span></label>
            <input type="text" name="store_name" value="{{ old('store_name', $store->store_name) }}" maxlength="255">
            <small class="field-error">@error('store_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Street Address <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="address" value="{{ old('address', $store->address) }}" maxlength="500">
            <small class="field-error">@error('address'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Barangay <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="barangay" value="{{ old('barangay', $store->barangay) }}" maxlength="100">
            <small class="field-error">@error('barangay'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>City / Municipality <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="city" value="{{ old('city', $store->city) }}" maxlength="100">
            <small class="field-error">@error('city'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Province <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="province" value="{{ old('province', $store->province) }}" maxlength="100">
            <small class="field-error">@error('province'){{ $message }}@enderror</small>
          </div>
        </div>

        <div class="section-title spaced">Owner's Account</div>
        <div class="form-grid">
          <div class="invite-field">
            <label>First Name <span class="req">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name', $store->first_name) }}" maxlength="50">
            <small class="field-error">@error('first_name'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Middle Name <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <input type="text" name="middle_initial" value="{{ old('middle_initial', $store->middle_initial) }}" maxlength="50">
            <small class="field-error">@error('middle_initial'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field">
            <label>Last Name <span class="req">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name', $store->last_name) }}" maxlength="50">
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
                     value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : (($store->contact_number ?? $store->phone ?? '') ? substr(preg_replace('/\D/', '', $store->contact_number ?? $store->phone), -9) : '') }}">
            </div>
            <small class="field-error">@error('phone'){{ $message }}@enderror</small>
          </div>
          <div class="invite-field full">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email', $store->email) }}" autocomplete="off">
            <small class="field-error">@error('email'){{ $message }}@enderror</small>
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('admin.stores.index') }}" class="btn-users ghost">Back to Stores</a>
          <button type="submit" class="btn-users" id="storeSubmitBtn">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>

    {{-- ── Danger zone (separate form: HTML forbids nested forms) ────── --}}
    <div class="danger-zone">
      <div>
        <h3>Remove this store</h3>
        <p>Deletes the owner's account, every product they listed, and all filed paperwork including the files
           themselves. There is no undo.</p>
      </div>
      <form method="POST" action="{{ route('admin.stores.destroy', $store) }}"
            data-aqui-confirm="Delete {{ $store->store_name ?: $store->name }}, its account, its products and all {{ $store->documents->count() }} filed paper(s)? This cannot be undone."
            data-aqui-danger data-aqui-title="Delete store" data-aqui-confirm-label="Delete store">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-users danger">Delete Store</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // ── +639 phone widget on the details form ─────────────────────
      var form = document.getElementById('storeForm');

      if (form) {
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
          submitBtn.textContent = 'Saving…';
        });
      }

      // ── Paperwork upload ──────────────────────────────────────────
      var docForm = document.getElementById('docForm');

      if (docForm) {
        var docBtn = document.getElementById('docSubmitBtn');
        var docLabel = docBtn.innerHTML;

        docForm.addEventListener('submit', function (e) {
          if (!docForm.querySelector('input[name="file"]').files.length) {
            e.preventDefault();
            alert('Choose the file for this paper first.');
            return;
          }
          docBtn.disabled = true;
          docBtn.textContent = 'Filing…';
        });

        docForm.querySelector('input[name="file"]').addEventListener('change', function () {
          if (docBtn.disabled) { docBtn.disabled = false; docBtn.innerHTML = docLabel; }
        });
      }
    });
  </script>
@endsection
