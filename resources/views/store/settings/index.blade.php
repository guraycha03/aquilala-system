@extends('layouts.sidebar')

@section('title', 'Settings')

@section('content')
  <div style="max-width: 860px;">
    <div id="settingsHome">
      <div style="margin-bottom: 18px;">
        <h1 style="font-size: 24px; font-weight: 800; color: #1e2922; margin: 0; letter-spacing: -0.01em;">Settings</h1>
        <p style="font-size: 13px; color: #64748b; margin: 5px 0 0;">Choose an area below to open its settings.</p>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <button type="button" class="main-settings-item" data-target="appearance" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Appearance</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Sidebar color theme and dark/light mode.</div>
        </button>
        <button type="button" class="main-settings-item" data-target="store-profile" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Store profile</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Store name, address, and contact (display only).</div>
        </button>
        <button type="button" class="main-settings-item" data-target="notifications" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Notification preferences</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Quotation demand alerts.</div>
        </button>
        <button type="button" class="main-settings-item" data-target="security" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Security</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Change password and account security.</div>
        </button>
        <button type="button" class="main-settings-item" data-target="language" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Language</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Display language (coming soon).</div>
        </button>
      </div>
    </div>

    <div id="settingsPanelWrapper" style="display:none;">
      <button type="button" id="settingsBack" style="margin-bottom:14px;padding:8px 12px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Back to settings</button>

      <div id="appearance" class="settings-panel" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Appearance</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 16px;">Sidebar color theme and dark/light mode.</p>
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Side menu color</div>
        @include('partials.sidebar-theme-picker')
        <hr style="border:none;border-top:1px solid #eef1f4;margin:22px 0 18px;">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Display theme</div>
        @include('partials.theme-mode-picker')
      </div>

      <div id="store-profile" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Store profile</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Store name, address, and contact. (Display only.)</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;opacity:0.75;">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
            <div style="font-size:11px;color:#64748b;">Store name</div>
            <div style="font-size:14px;font-weight:700;color:#1e2922;">Your store name</div>
          </div>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
            <div style="font-size:11px;color:#64748b;">Contact</div>
            <div style="font-size:14px;font-weight:700;color:#1e2922;">Phone / email</div>
          </div>
        </div>
      </div>

      <div id="notifications" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Notification preferences</h2>
        <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:#334155;">
          <input type="checkbox" disabled>
          Quotation demand alerts
        </label>
      </div>

      <div id="security" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Security</h2>
        <a href="{{ route('password.change') }}" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#f1f5f9;color:#3f5a46;font-weight:700;text-decoration:none;">Change password</a>
      </div>

      <div id="language" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Language</h2>
        <select disabled style="padding:8px 10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;">
          <option selected>English</option>
          <option>Tagalog</option>
        </select>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var home = document.getElementById('settingsHome');
      var wrap = document.getElementById('settingsPanelWrapper');
      var back = document.getElementById('settingsBack');
      var panels = document.querySelectorAll('.settings-panel');
      function show(target) {
        home.style.display = 'none';
        wrap.style.display = 'block';
        panels.forEach(function (p) { p.style.display = (p.id === target) ? 'block' : 'none'; });
      }
      document.querySelectorAll('.main-settings-item').forEach(function (item) {
        item.addEventListener('click', function () { show(item.dataset.target); });
      });
      back.addEventListener('click', function () {
        wrap.style.display = 'none';
        home.style.display = 'block';
      });
      var initialPanel = window.location.hash ? window.location.hash.slice(1) : '';
      if (initialPanel && document.getElementById(initialPanel)) show(initialPanel);
    })();
  </script>
@endsection