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
        <button type="button" class="main-settings-item" data-target="account-profile" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Account profile</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Name, contact, and account details.</div>
        </button>
        <button type="button" class="main-settings-item" data-target="saved-items" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Saved items</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Products you saved from product cards ({{ $savedProducts->count() }}).</div>
        </button>
        <button type="button" class="main-settings-item" data-target="notifications" style="display:block;width:100%;text-align:left;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;color:#1e2922;cursor:pointer;">
          <div style="font-weight:700;font-size:14px;">Notification preferences</div>
          <div style="font-size:12px;color:#64748b;margin-top:3px;">Alerts and reminders.</div>
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
        @include('partials.sidebar-theme-picker')
        <hr style="border:none;border-top:1px solid #eef1f4;margin:22px 0 18px;">
        @include('partials.theme-mode-picker')
      </div>

      <div id="account-profile" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Account profile</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Name, contact, and address. (Display only for now.)</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;opacity:.75;">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;"><div style="font-size:11px;color:#64748b;">Name</div><div style="font-size:14px;font-weight:700;color:#1e2922;">{{ auth()->user()->name }}</div></div>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;"><div style="font-size:11px;color:#64748b;">Contact</div><div style="font-size:14px;font-weight:700;color:#1e2922;">{{ auth()->user()->email ?? 'Phone / email' }}</div></div>
        </div>
      </div>

      <div id="saved-items" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Saved items</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 18px;">Products you saved from the bookmark button on product cards.</p>
        @if($savedProducts->isEmpty())
          <div style="padding:28px 12px;text-align:center;border:1px dashed #cbd5e1;border-radius:12px;color:#64748b;font-size:13px;">
            You have no saved products yet. Browse supplies and tap the bookmark icon to save one.
          </div>
        @else
          <div class="product-grid saved-settings-grid">
            @foreach($savedProducts as $product)
              <x-product-card :product="$product" :href="route('farmer.products.show', $product)" />
            @endforeach
          </div>
        @endif
      </div>

      <div id="notifications" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Notification preferences</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 16px;">Choose which alerts appear in your notifications and notification bell.</p>
        <form method="POST" action="{{ route('farmer.settings.notifications') }}">
          @csrf
          <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:#334155;margin-bottom:12px;">
            <input type="hidden" name="notify_planting_updates" value="0">
            <input type="checkbox" name="notify_planting_updates" value="1" @checked(auth()->user()->notify_planting_updates)>
            Planting decision updates
          </label>
          <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:#334155;margin-bottom:18px;">
            <input type="hidden" name="notify_association_events" value="0">
            <input type="checkbox" name="notify_association_events" value="1" @checked(auth()->user()->notify_association_events)>
            Association event alerts
          </label>
          <button type="submit" style="padding:9px 14px;border:0;border-radius:9px;background:#648268;color:#fff;font-weight:700;cursor:pointer;">Save preferences</button>
        </form>
      </div>

      <div id="security" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Security</h2>
        <a href="{{ route('password.change') }}" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#f1f5f9;color:#3f5a46;font-weight:700;text-decoration:none;">Change password</a>
      </div>

      <div id="language" class="settings-panel" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;">
        <h2 style="font-size:15px;font-weight:800;color:#1e2922;margin:0 0 4px;">Language</h2>
        <select disabled style="padding:8px 10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;"><option selected>English</option><option>Tagalog</option></select>
      </div>
    </div>

    @include('partials.notifications-settings')
  </div>

  <style>
    .saved-settings-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px; }
    @media (max-width: 900px) { .saved-settings-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width: 560px) { .saved-settings-grid { grid-template-columns:1fr; } }
  </style>

  <script>
    (function () {
      var home = document.getElementById('settingsHome');
      var wrap = document.getElementById('settingsPanelWrapper');
      var back = document.getElementById('settingsBack');
      var panels = document.querySelectorAll('.settings-panel');
      function show(target) {
        home.style.display = 'none';
        wrap.style.display = 'block';
        panels.forEach(function (panel) { panel.style.display = panel.id === target ? 'block' : 'none'; });
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
