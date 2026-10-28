@extends('layouts.sidebar')

@section('title', 'Verify Administrator Creation')

@section('content')
  <div style="max-width:680px;">
    <x-breadcrumb :items="[
      ['label' => 'Users', 'url' => route('admin.users.index')],
      ['label' => 'Create Account', 'url' => route('admin.users.create')],
      ['label' => 'Security Verification'],
    ]"/>

    <div style="background:rgba(255,255,255,.86);border:1px solid #dce8df;border-radius:18px;padding:28px;box-shadow:0 6px 28px rgba(0,0,0,.05);">
      <h1 style="margin:0 0 8px;color:#1e293b;font-size:24px;">Verify administrator creation</h1>
      <p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.6;">
        Before creating another administrator, confirm your current password. We will send a one-time security key
        to your administrator email address. The key expires in 10 minutes and can be used once.
      </p>

      @if (session('success'))
        <div style="padding:12px 16px;margin-bottom:18px;border-radius:10px;background:#f0fdf4;color:#166534;border:1px solid #dcfce7;font-size:13px;">
          {{ session('success') }}
        </div>
      @endif
      @if ($errors->any())
        <div style="padding:12px 16px;margin-bottom:18px;border-radius:10px;background:#fdf2f2;color:#9b2c2c;border:1px solid #fde8e8;font-size:13px;">
          {{ $errors->first() }}
        </div>
      @endif

      @if (request('step') === 'verify')
        <form method="POST" action="{{ route('admin.users.verifyAdminCreationKey') }}">
          @csrf
          <label style="display:block;font-size:12px;font-weight:700;color:#3d4a41;margin-bottom:6px;">One-time key</label>
          <input type="text" name="key" value="{{ old('key') }}" required maxlength="100" autocomplete="one-time-code"
                 placeholder="Paste the key from your email"
                 style="width:100%;padding:12px 13px;border:1px solid #e2e8f0;border-radius:9px;font:14px monospace;box-sizing:border-box;">
          @error('key')<small style="display:block;color:#dc2626;margin-top:5px;">{{ $message }}</small>@enderror
          <p style="font-size:11px;color:#94a3b8;line-height:1.5;">Maximum 5 attempts. Requesting another key invalidates this one.</p>
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px;">
            <a href="{{ route('admin.users.create') }}" class="btn-users ghost">Cancel</a>
            <button type="submit" class="btn-users">Unlock Admin Form</button>
          </div>
        </form>
      @else
        <form method="POST" action="{{ route('admin.users.requestAdminCreationKey') }}">
          @csrf
          <label style="display:block;font-size:12px;font-weight:700;color:#3d4a41;margin-bottom:6px;">Your current administrator password</label>
          <input type="password" name="password" required autocomplete="current-password"
                 placeholder="Re-enter your password"
                 style="width:100%;padding:12px 13px;border:1px solid #e2e8f0;border-radius:9px;font-size:14px;box-sizing:border-box;">
          @error('password')<small style="display:block;color:#dc2626;margin-top:5px;">{{ $message }}</small>@enderror
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px;">
            <a href="{{ route('admin.users.create') }}" class="btn-users ghost">Cancel</a>
            <button type="submit" class="btn-users">Email Security Key</button>
          </div>
        </form>
      @endif
    </div>
  </div>
@endsection
