@extends('layouts.sidebar')

@section('title', 'General Settings')

@section('content')
  <div style="max-width: 760px;">
    <div style="margin-bottom: 18px;">
      <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0;">General Settings</h1>
      <p style="font-size: 13px; color: #64748b; margin: 5px 0 0;">Choose one of the areas below to open its settings page.</p>
    </div>

    <div style="display: flex; flex-direction: column; gap: 10px;">
      @php
        $settingPages = [
          ['label' => 'General Settings', 'route' => 'admin.settings.general', 'detail' => 'Organization identity, regional formatting, and location.'],
          ['label' => 'Appearance', 'route' => 'admin.settings.interface', 'detail' => 'Sidebar color theme and dark/light mode.'],
          ['label' => 'Season Settings', 'route' => 'admin.settings.seasons', 'detail' => 'Wet and dry season references and climate information.'],
          ['label' => 'Weather Log', 'route' => 'admin.settings.weather', 'detail' => 'Log typhoons, floods, and weather events.'],
          ['label' => 'Archive', 'route' => 'admin.archive', 'detail' => 'Archived records and restoration actions.'],
          ['label' => 'Audit Log', 'route' => 'admin.audit.index', 'detail' => 'Administrative action history.'],
          ['label' => 'Activity History', 'route' => 'admin.activity.index', 'detail' => 'Submitted activity timelines.'],
        ];
      @endphp

      @foreach ($settingPages as $page)
        @if (\Illuminate\Support\Facades\Route::has($page['route']))
          <a href="{{ route($page['route']) }}" style="display: block; padding: 14px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: #1e2922;">
            <div style="font-weight: 700; font-size: 14px;">{{ $page['label'] }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 3px;">{{ $page['detail'] }}</div>
          </a>
        @endif
      @endforeach
    </div>
  </div>
@endsection