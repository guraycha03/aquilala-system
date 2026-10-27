@extends('layouts.sidebar')

@section('title', 'Create Account')

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
    .header-row p { font-size: 13px; color: #64748b; margin: 0; max-width: 760px; line-height: 1.55; }

    .type-grid {
      position: relative; z-index: 1;
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    }
    @media (max-width: 880px) { .type-grid { grid-template-columns: 1fr; } }

    .type-card {
      display: flex; flex-direction: column; align-items: flex-start; gap: 10px;
      text-decoration: none; cursor: pointer;
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 22px;
      transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .type-card:hover {
      transform: translateY(-2px); border-color: #b5d97d;
      box-shadow: 0 10px 30px rgba(20,61,35,0.10), 0 2px 6px rgba(0,0,0,0.04);
    }
    .type-card:focus-visible { outline: 3px solid rgba(74,122,84,0.28); outline-offset: 2px; }

    .type-icon {
      width: 44px; height: 44px; border-radius: 13px; display: inline-flex;
      align-items: center; justify-content: center;
      background: #eef3ef; color: #3f7a4f; flex-shrink: 0;
    }
    .type-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }

    .type-card .t-sub {
      font-size: 10.5px; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.11em; color: #8a97a5;
    }
    .type-card h2 { font-size: 16.5px; font-weight: 700; color: #143d23; margin: 0; }
    .type-card p { font-size: 13px; color: #64748b; line-height: 1.6; margin: 0; flex: 1; }

    .type-go { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; color: #14532d; margin-top: 4px; }
    .type-go svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.15s ease; }
    .type-card:hover .type-go svg { transform: translateX(3px); }

    [data-theme="dark"] .type-card { background: rgba(15, 23, 42, 0.72); border-color: #334155; }
    [data-theme="dark"] .type-card:hover { border-color: #2f5d3c; }
    [data-theme="dark"] .type-icon { background: #16301f; color: #a7f3d0; }
    [data-theme="dark"] .type-card h2 { color: #d6f5e3; }
    [data-theme="dark"] .type-card p { color: #9fb3a6; }
    [data-theme="dark"] .type-card .t-sub { color: #7d8f86; }
    [data-theme="dark"] .type-go { color: #a8cdb2; }
  </style>

  <div class="register-page">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => 'Create Account'],
    ]"/>

    <div class="header-row">
      <h1>Create Account</h1>
      <p>What kind of account do you need? Each type opens its own form — farmers are made from the members list,
         store owners register together with their store, and an administrator account is typed in and confirmed
         with your password.</p>
    </div>

    <div class="type-grid">
      {{-- ── Farmers: picked off the members list ─────────────────────── --}}
      <a class="type-card" href="{{ route('admin.users.create', ['type' => 'farmer']) }}">
        <span class="type-icon">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <span class="t-sub">For association members</span>
        <h2>Farmer Account</h2>
        <p>Pick the person from the members list — their name, address and farming details carry straight over.
           The member receives a 6&#8209;digit code and chooses their own password.</p>
        <span class="type-go">
          Choose a member
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </span>
      </a>

      {{-- ── Store owners: their form lives on the Stores page ────────── --}}
      <a class="type-card" href="{{ route('admin.stores.create') }}">
        <span class="type-icon">
          <svg viewBox="0 0 24 24"><path d="M6 7h12l1 13H5z"/><path d="M9 7a3 3 0 0 1 6 0"/><path d="M3 7h18"/></svg>
        </span>
        <span class="t-sub">For an accredited supply store</span>
        <h2>Store Owner Account</h2>
        <p>Register a store and its owner together on the Stores page — business details and the sign-in account
           in one form. Legal papers are filed afterwards, from the store's own page.</p>
        <span class="type-go">
          Open the store form
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </span>
      </a>

      {{-- ── Administrators: typed in, behind a password re-check ─────── --}}
      <a class="type-card" href="{{ route('admin.users.create', ['type' => 'admin']) }}">
        <span class="type-icon">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </span>
        <span class="t-sub">Full system access</span>
        <h2>Administrator Account</h2>
        <p>Manage members, accounts, stores and every association record. Because this is the highest level of
           access, the form asks for your own password and an explicit confirmation.</p>
        <span class="type-go">
          Open the admin form
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </span>
      </a>
    </div>
  </div>
@endsection
