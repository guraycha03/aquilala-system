@extends('layouts.sidebar')

@section('title', $report->title)

@section('content')
@include('admin.harvests.generated.partials.styles')

<div class="ahr-page">
  @include('admin.harvests.generated.partials.topbar', ['activeTab' => 'saved', 'savedReports' => $savedReports])

  <main class="ahr-main">
    @if (session('success'))
      <div class="ahr-alert success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ahr-page-head">
      <h1 class="ahr-h1">{{ $report->title }}</h1>
    </div>

    @include('admin.harvests.generated.partials.visual-report', [
      'report' => $report,
      'visual' => $visual,
      'scope' => $scope,
      'narratives' => $narratives,
      'language' => $language,
      'seasonFilter' => (string) ($report->filters['season'] ?? 'all'),
    ])
  </main>
</div>
@endsection
