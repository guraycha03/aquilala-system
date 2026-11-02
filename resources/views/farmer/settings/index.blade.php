@extends('layouts.sidebar')

@section('title', 'Settings')

@section('content')
  <div style="max-width: 860px;">
    <div style="margin-bottom: 18px;">
      <h1 style="font-size: 24px; font-weight: 800; color: #1e2922; margin: 0; letter-spacing: -0.01em;">Settings</h1>
      <p style="font-size: 13px; color: #64748b; margin: 5px 0 0;">Choose an area. It opens below without leaving this page.</p>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
      <button type="button" class="set-tab" data-target="appearance" style="padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Appearance</button>
      <button type="button" class="set-tab" data-target="account-profile" style="padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Account profile</button>
      <button type="button" class="set-tab" data-target="notifications" style="padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Notifications</button>
      <button type="button" class="set-tab" data-target="security" style="padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Security</button>
      <button type="button" class="set-tab" data-target="language" style="padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Language</button>
    </div>

    <div id="appearance" class="settings-panel" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
      <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 4px;">Appearance</h2>
      <p style="font-size:12px;color:#64748b;margin:0 0 16px;">Sidebar color theme and dark/light mode.</p>
      <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Side menu color</div>
      @include('partials.sidebar-theme-picker')
      <hr style="border:none;border-top:1px solid #eef1f4;margin:22px 0 18px;">
      <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Display theme</div>
      @include('partials.theme-mode-picker')
    </div>

    <div id="account-profile" class="settings-panel" style="display:none;margin-top:18px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
      <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Account profile</h2>
      <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Name, contact, and address. (Display only for now.)</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;opacity:0.75;">
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
          <div style="font-size:11px;color:#64748b;">Name</div>
          <div style="font-size:14px;font-weight:700;color:#1e2922;">Your name</div>
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
          <div style="font-size:11px;color:#64748b;">Contact</div>
          <div style="font-size:14px;font-weight:700;color:#1e2922;">Phone / email</div>
        </div>
      </div>
    </div>

    <div id="notifications" class="settings-panel" style="display:none;margin-top:18px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
      <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Notification preferences</h2>
      <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Alerts and reminders. (Display only for now.)</p>
      <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:#334155;">
        <input type="checkbox" disabled>
        Harvest and weather reminders
      </label>
    </div>

    <div id="security" class="settings-panel" style="display:none;margin-top:18px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
      <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Security</h2>
      <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Protect your account access.</p>
      <a href="{{ route('password.change') }}" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#f1f5f9;color:#3f5a46;font-weight:700;text-decoration:none;">Change password</a>
    </div>

    <div id="language" class="settings-panel" style="display:none;margin-top:18px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
      <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Language</h2>
      <select disabled style="padding:8px 10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;">
        <option selected>English</option>
        <option>Tagalog</option>
      </select>
    </div>
  </div>

  <script>
    (function () {
      var tabs = document.querySelectorAll('.set-tab');
      var panels = document.querySelectorAll('.settings-panel');
      function show(target) {
        panels.forEach(function (p) { p.style.display = (p.id === target) ? 'block' : 'none'; });
        tabs.forEach(function (t) {
          if (t.dataset.target === target) { t.style.background = '#648268'; t.style.color = '#ffffff'; t.style.borderColor = '#648268'; }
          else { t.style.background = '#ffffff'; t.style.color = '#334155'; t.style.borderColor = '#e2e8f0'; }
        });
      }
      tabs.forEach(function (tab) { tab.addEventListener('click', function () { show(tab.dataset.target); }); });
      show('appearance');
    })();
  </script>
@endsection