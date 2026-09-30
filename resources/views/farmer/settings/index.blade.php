@extends('layouts.sidebar')

@section('title', 'Settings')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'settings', 'label' => 'Settings',    'url' => route('farmer.settings.index')],
      ['key' => 'activity', 'label' => 'Activity Log', 'url' => route('farmer.activity.index')],
    ],
    'activeTab' => 'settings',
  ])
  <div style="max-width: 860px;">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px;">
      <div>
        <h1 style="font-size: 24px; font-weight: 800; color: #1e293b; margin: 0; letter-spacing: -0.01em;">Settings</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0;">Personalize how your side menu looks across the platform.</p>
      </div>
    </div>

    <div style="background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.55); border-radius: 18px; box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); padding: 22px;">

      <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 4px;">Appearance</div>
      <h2 style="font-size: 15px; font-weight: 800; color: #1e293b; margin: 0 0 4px;">Side menu color</h2>
      <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">Choose one of three colors for your navigation sidebar. It applies everywhere you are signed in.</p>

      @include('partials.sidebar-theme-picker')

      <hr style="border:none;border-top:1px solid #eef1f4;margin:22px 0 18px;">

      <h2 style="font-size: 15px; font-weight: 800; color: #1e293b; margin: 0 0 4px;">Sub-page navigation</h2>
      <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">Choose how grouped pages appear. Side menu style shows them under the current menu name; Tabs on top pins them above the content.</p>

      @include('partials.sub-pages-style-picker')
    </div>
  </div>
@endsection