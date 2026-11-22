@extends('layouts.sidebar')

@section('title', 'AI Harvest Reports')

@section('content')
@include('admin.harvests.generated.partials.styles')

<div class="ahr-page">
  <main class="ahr-main">
    <div class="ahr-page-head">
      <h1 class="ahr-h1">AI Harvest Report</h1>
      <p class="ahr-sub" id="ahr-scope-text">{{ $scope }}</p>
    </div>

    @include('admin.harvests.generated.partials.topbar', ['activeTab' => $activeTab, 'savedReports' => $savedReports])

    @if (session('success'))
      <div class="ahr-alert success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="ahr-alert error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="ahr-alert error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div id="ahr-filter-status" class="ahr-filter-status" role="status" aria-live="polite" hidden>
      <span class="ahr-spinner" aria-hidden="true"></span>
      <span>Updating report…</span>
    </div>

    <div id="ahr-generating-overlay" class="ahr-generating-overlay" role="status" aria-live="assertive" aria-busy="true" hidden>
      <div class="ahr-generating-card">
        <span class="ahr-generating-spinner" aria-hidden="true"></span>
        <h2>Preparing your report&hellip;</h2>
        <p>Reading the selected harvest records and formatting the report. Please do not close this page.</p>
        <span class="ahr-generating-track" aria-hidden="true"></span>
      </div>
    </div>

    @if($activeTab === 'generate')
      <div id="ahr-scope" class="ahr-fragment">
        @include('admin.harvests.generated.partials.scope-studio')
      </div>
    @elseif($activeTab === 'saved')
      @include('admin.harvests.generated.partials.saved-reports')
    @else
      <div id="ahr-preview" class="ahr-fragment">
        @include('admin.harvests.generated.partials.visual-report', [
          'visual' => $previewVisual,
          'scope' => $scope,
          'seasonFilter' => $seasonFilter,
          'options' => $options,
          'filters' => $filters,
        ])
      </div>

      <div id="ahr-scope" class="ahr-fragment">
        @include('admin.harvests.generated.partials.scope-studio')
      </div>

      <div id="ahr-records" class="ahr-fragment">
        @include('admin.harvests.generated.partials.records-table')
      </div>
    @endif
  </main>
</div>

@include('admin.harvests.generated.partials.filter-script')
@endsection
