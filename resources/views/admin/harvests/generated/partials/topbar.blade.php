@php
  $tabQuery = request()->except('tab');
  $tabLink = fn (string $tab) => route('admin.harvests.reports.generate', $tabQuery + ['tab' => $tab]);
@endphp

<header class="ahr-topbar">
  <div class="ahr-topbar-inner">
    <nav class="ahr-tabs" aria-label="Harvest report sections">
      <a class="ahr-tab {{ $activeTab === 'generate' ? 'active' : '' }}" href="{{ $tabLink('generate') }}">Generate Report</a>
      <a class="ahr-tab {{ $activeTab === 'saved' ? 'active' : '' }}" href="{{ $tabLink('saved') }}">
        Saved Reports @if($savedReports->count() > 0)({{ $savedReports->count() }})@endif
      </a>
    </nav>
  </div>
</header>
