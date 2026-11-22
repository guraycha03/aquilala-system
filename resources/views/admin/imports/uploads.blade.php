@extends('layouts.sidebar')

@section('title', $title)

@section('content')
  <style>
    :root {
      --primary: #15803d;
      --slate-50: #f8fafc;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-600: #475569;
      --slate-900: #0f172a;
      --radius-lg: 16px;
      --radius-md: 12px;
      --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
      --shadow-lg: 0 10px 30px rgba(15, 23, 42, 0.1);
    }

    .import-page { position: relative; font-family: inherit; }
    .import-content { position: relative; z-index: 1; }

    .header-row { margin-bottom: 24px; }
    .header-row h1 {
      font-size: 24px; font-weight: 700; color: var(--slate-900);
      letter-spacing: -0.02em; margin: 0 0 4px 0;
    }
    .header-row p { font-size: 14px; color: var(--slate-600); margin: 0; max-width: 760px; }

    .import-panel {
      position: relative;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--slate-200);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 32px;
      max-width: 1100px;
    }

    /* Flash messages — removal results land here after the redirect back. */
    .page-flash { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 600; margin: 0 0 14px; border: 1px solid; }
    .page-flash--success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
    .page-flash--error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
  </style>

  <x-breadcrumb :items="[
    ['label' => $backLabel, 'url' => route($backRoute)],
    ['label' => $title],
  ]"/>

  @if (session('success'))
    <div class="page-flash page-flash--success" role="status">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="page-flash page-flash--error" role="alert">{{ session('error') }}</div>
  @endif

  <div class="import-page">
    <div class="import-content">
      <div class="header-row">
        <h1>{{ $title }}</h1>
        {{-- The layout hides a plain <p> after an <h1>; this one explains the
             archive, so it opts in like the other pages do. --}}
        <p class="keep-sub">{{ $subtitle }}</p>
      </div>

      <div class="import-panel">
        @include('admin.imports.partials.uploads-list', [
            'uploads' => $uploads,
            'kind' => $kind,
            'downloadRoute' => $downloadRoute,
            'previewRoute' => $previewRoute,
        ])
      </div>
    </div>
  </div>

  @include('admin.imports.partials.upload-viewer')
@endsection
