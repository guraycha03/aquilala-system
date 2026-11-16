@extends('layouts.sidebar')

@section('title', $member ? 'Create Account' : 'Choose a Member')

@section('content')
  <style>
    .register-page { position: relative; max-width: 940px; }
    .register-page::before,
    .register-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .register-page::before { width: 300px; height: 300px; background: rgba(94,144,112,0.14); top: -70px; right: -60px; }
    .register-page::after { width: 260px; height: 260px; background: rgba(93,194,87,0.10); bottom: -60px; left: -40px; }

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
    .btn-users:disabled { opacity: 0.5; cursor: not-allowed; transform: none; background: #e6ece6; color: #8aa296; border-color: #d8e3db; box-shadow: none; }
    .btn-users svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .btn-users.ghost { background: #ffffff; color: #334c3e; border-color: #d8e3db; box-shadow: 0 1px 2px rgba(20,61,35,0.05); }
    .btn-users.ghost:hover { background: #f3f8f4; border-color: #b9cec0; color: #1e3829; transform: translateY(-1px); }

    /* ---- Member picker ---- */
    .rule-strip {
      display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 12px;
      background: #f3f8f4; border: 1px solid #dce8df; color: #2f4a39;
      font-size: 13px; line-height: 1.55; margin-bottom: 18px;
    }
    .rule-strip svg { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; margin-top: 1px; }

    .pick-search { position: relative; margin-bottom: 16px; }
    .pick-search input {
      width: 100%; padding: 12px 14px 12px 40px; border: 1px solid #e2e8f0; border-radius: 11px;
      font-size: 14px; background: #fafbfa; outline: none; box-sizing: border-box;
      color: #334155; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .pick-search input:focus { border-color: #8FB89A; box-shadow: 0 0 0 3px rgba(93,194,87,0.18); }
    .pick-search svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 16px; height: 16px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    }
    .pick-count { font-size: 12px; color: #94a3b8; margin: 0 0 10px 2px; }

    .pick-list { display: grid; gap: 8px; max-height: 520px; overflow-y: auto; padding-right: 4px; }
    .pick-item {
      display: flex; align-items: center; gap: 13px; text-decoration: none;
      padding: 12px 14px; border: 1px solid #e8ede9; border-radius: 12px; background: #fff;
      transition: all 0.15s ease;
    }
    .pick-item:hover { border-color: #b5d97d; background: #f7fbf4; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(20,61,35,0.07); }
    .pick-item .p-name { font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3; }
    .pick-item .p-meta { font-size: 12px; color: #8a97a5; margin-top: 2px; line-height: 1.45; }
    .pick-item .p-body { flex: 1; min-width: 0; }
    .pick-item .p-go {
      width: 30px; height: 30px; border-radius: 9px; background: #eef3ef; color: #3f7a4f;
      display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .pick-item .p-go svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .pick-item[hidden] { display: none; }

    .pick-empty { text-align: center; padding: 44px 20px; color: #94a3b8; }
    .pick-empty svg { width: 44px; height: 44px; stroke: currentColor; fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; opacity: 0.7; }
    .pick-empty p { font-size: 14px; margin: 14px 0 0 0; line-height: 1.6; }

    /* ---- Chosen-member summary ---- */
    .chosen {
      display: flex; align-items: flex-start; gap: 14px; padding: 16px 18px;
      border-radius: 14px; background: #f7fbf4; border: 1px solid #dce8df; margin-bottom: 22px;
    }
    .chosen .c-name { font-size: 16px; font-weight: 700; color: #143d23; margin: 0 0 3px 0; }
    .chosen .c-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px 20px; margin-top: 12px;
    }
    .chosen .c-cell .k { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #8a97a5; margin-bottom: 3px; }
    .chosen .c-cell .v { display: block; font-size: 13px; color: #334155; font-weight: 600; word-break: break-word; }
    .c-back {
      margin-left: auto; flex-shrink: 0; font-size: 12px; font-weight: 700; color: #3f7a4f;
      text-decoration: none; border: 1px solid #cfe0d4; background: #fff;
      padding: 7px 12px; border-radius: 9px; white-space: nowrap;
    }
    .c-back:hover { background: #eef3ef; }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => $member ? 'Create Account' : 'Choose a Member'],
    ]"/>

    @if ($member === null)
      {{-- ── Step 1: pick the member the account will belong to ─────────── --}}
      <div class="header-row">
        <h1>Choose a Member</h1>
        <p>Accounts are made from an existing member — never typed in from scratch. Pick the person below and their
           name, address and farming details carry straight over to the new account.</p>
      </div>

      <div class="form-card">
        @if (session('error'))
          <div class="form-alert error">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
          </div>
        @endif

        <div class="rule-strip">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>
            Only <b>active members without an account</b> are listed here. Anyone already signed in has been taken off
            the list, and there is no blank form to fill in — if somebody is missing, add them to the
            <a href="{{ route('admin.members.index') }}" style="color:#14532d;font-weight:700;">members list</a> first.
          </span>
        </div>

        @if ($members->isEmpty())
          <div class="pick-empty">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <p>
              Every active member already has an account — or the members list is empty.<br>
              <a href="{{ route('admin.members.index') }}" style="color:#14532d;font-weight:700;">Go to Members</a>
              to add people, then come back.
            </p>
          </div>
        @else
          <div class="pick-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" id="memberSearch" placeholder="Search by name, member number, phone or barangay…" autocomplete="off">
          </div>

          <p class="pick-count" id="memberCount" hidden></p>

          <div class="pick-list" id="memberList">
            @foreach ($members as $m)
              @php
                $place = trim(implode(', ', array_filter([$m->barangay, $m->city])));
                $meta = trim(implode(' · ', array_filter([
                  $m->assoc_member_no ? '#' . $m->assoc_member_no : '',
                  $place,
                  $m->phone,
                ])));
                $haystack = strtolower(trim($m->fullName() . ' ' . ($m->assoc_member_no ?? '') . ' ' . ($m->phone ?? '') . ' ' . $place . ' ' . ($m->email ?? '')));
              @endphp
              <a class="pick-item" href="{{ route('admin.users.create', ['member' => $m->id]) }}" data-search="{{ $haystack }}">
                <x-user-avatar :user="$m" :size="40" />
                <span class="p-body">
                  <span class="p-name">{{ $m->fullName() }}</span>
                  <span class="p-meta">{{ $meta !== '' ? $meta : 'No contact details on file' }}</span>
                </span>
                <span class="p-go">
                  <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
              </a>
            @endforeach
          </div>

          <div class="pick-empty" id="pickNoMatch" hidden>
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p>No member matches that search.</p>
          </div>
        @endif
      </div>
    @else
      {{-- ── Step 2: confirm the account details for the chosen member ──── --}}
      <div class="header-row">
        <h1>Create Account for a Member</h1>
        <p>The account is created as <b>pending</b> and a 6&#8209;digit activation code is emailed. The member signs in
           with their email, enters that code, and sets their own password.</p>
      </div>

      <div class="form-card">
        @if ($errors->any())
          <div class="form-alert error">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $errors->first() }}
          </div>
        @endif

        <div class="chosen">
          <x-user-avatar :user="$member" :size="46" />
          <div style="flex:1;min-width:0;">
            <p class="c-name">{{ $member->fullName() }}</p>
            <p style="margin:0;font-size:12px;color:#6b7c72;">
              Member status: {{ $member->status === \App\Models\Member::STATUS_ACTIVE ? 'active' : 'inactive' }}
              @if ($member->assoc_member_no) · Member no. {{ $member->assoc_member_no }} @endif
            </p>
            <div class="c-grid">
              <div class="c-cell">
                <span class="k">Address</span>
                <span class="v">{{ $member->addressLine() ?: '—' }}</span>
              </div>
              <div class="c-cell">
                <span class="k">Phone on file</span>
                <span class="v">{{ $member->phone ?: '—' }}</span>
              </div>
              <div class="c-cell">
                <span class="k">Farm</span>
                <span class="v">{{ $member->farmAddressLine() ?: '—' }}</span>
              </div>
              <div class="c-cell">
                <span class="k">Land tenure</span>
                <span class="v">{{ $member->land_ownership ?: '—' }}{{ $member->years_farming !== null ? ' · ' . $member->years_farming . ' yr' : '' }}</span>
              </div>
              <div class="c-cell">
                <span class="k">Total land</span>
                <span class="v">
                  {{ $member->land_area !== null ? number_format((float) $member->land_area, 2) . ' ' . ($member->land_area_unit === 'sq_m' ? 'm²' : ($member->land_area_unit === 'sq_ft' ? 'ft²' : 'ha')) : '—' }}
                </span>
              </div>
            </div>
          </div>
          <a class="c-back" href="{{ route('admin.users.create', ['type' => 'farmer']) }}">← Different member</a>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" id="inviteForm" novalidate>
          @csrf
          <input type="hidden" name="member_id" value="{{ $member->id }}">
          <input type="hidden" name="phone" value="{{ old('phone', $member->phone ?? '') }}">

          <div class="form-grid">
            <div class="invite-field full">
              <label>Email Address <span class="req">*</span></label>
              <input type="email" name="email" value="{{ old('email', $member->email ?? '') }}"
                     placeholder="name@gmail.com" autocomplete="off">
              <small class="field-error">@error('email'){{ $message }}@enderror</small>
              <small style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;">
                This is what the member signs in with and where the activation code is sent. It is the only detail
                taken from you — everything else comes off {{ $member->fullName() }}'s member record.
              </small>
            </div>

            <div class="invite-field full">
              <label>Phone Number <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
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
                       value="{{ old('phone') ? substr(preg_replace('/\D/', '', old('phone')), -9) : ($member->phone ? substr(preg_replace('/\D/', '', $member->phone), -9) : '') }}">
              </div>
              <small class="field-error">@error('phone'){{ $message }}@enderror</small>
              <small style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;">
                Pre-filled from the member record. Philippine mobile number only — enter the 9 digits after
                +639, or leave it empty. Leave blank if the number is already used by another account.
              </small>
            </div>
          </div>

          <div class="form-actions">
            <a href="{{ route('admin.users.create', ['type' => 'farmer']) }}" class="btn-users ghost">Cancel</a>
            <button type="submit" class="btn-users" id="inviteSubmitBtn">
              <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Create Account &amp; Send Invitation
            </button>
          </div>
        </form>
      </div>
    @endif
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var search = document.getElementById('memberSearch');

      if (search) {
        var items = Array.prototype.slice.call(document.querySelectorAll('#memberList .pick-item'));
        var noMatch = document.getElementById('pickNoMatch');
        var count = document.getElementById('memberCount');

        function applyFilter() {
          var term = search.value.trim().toLowerCase();
          var shown = 0;

          items.forEach(function (el) {
            var match = term === '' || (el.dataset.search || '').indexOf(term) !== -1;
            el.hidden = !match;
            if (match) { shown++; }
          });

          noMatch.hidden = shown !== 0;
          count.hidden = term === '';
          count.textContent = shown + ' of ' + items.length + ' members';
        }

        search.addEventListener('input', applyFilter);
        applyFilter();
      }

      var form = document.getElementById('inviteForm');
      if (!form) { return; }

      var phoneLocal = document.getElementById('phoneLocal');
      var phoneHidden = form.querySelector('input[name="phone"]');
      var submitBtn = document.getElementById('inviteSubmitBtn');

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
        submitBtn.textContent = 'Creating…';
      });
    });
  </script>
@endsection
