@php
  $optionYears = collect($options['years'] ?? [])->pluck('year')->map(fn ($y) => (string) $y)->all();
  $selectedYears = $filters['years'] ?? (isset($filters['year']) ? [(string) $filters['year']] : $optionYears);
  $selectedYears = array_values(array_map('strval', is_array($selectedYears) ? $selectedYears : []));

  $selectedCrops = $filters['crops'] ?? (isset($filters['crop']) ? [$filters['crop']] : []);
  $selectedCrops = array_values(array_map('strval', is_array($selectedCrops) ? $selectedCrops : []));

  $selectedSeason = (string) ($filters['season'] ?? 'all');

  $cleanQuery = request()->except([
    'tab', 'filters', 'record_q', 'record_sort', 'record_dir',
    'years', 'crops', 'season', 'crop', 'year', 'month', 'q',
  ]);
  $scopeUrl = fn (array $overrides = []) => route(
    'admin.harvests.reports.generate',
    $cleanQuery + $overrides + ['tab' => $activeTab]
  );
  $collapseUrl = route('admin.harvests.reports.generate', array_merge(
    request()->except('filters'),
    ['filters' => $filtersOpen ? 'closed' : 'open']
  ));
@endphp

<section class="ahr-card" aria-label="Report scope configurator">
  <div class="ahr-card-head">
    <div>
      <h2 class="ahr-card-title">Report Scope</h2>
      <p class="ahr-sub">
        {{ $filtersOpen ? 'Choose the harvests to include in this report.' : $scope }}
      </p>
    </div>
    <div class="ahr-actions">
      @if($filtersOpen)
        <a class="ahr-link-btn" data-filter-link href="{{ $scopeUrl() }}">Reset</a>
      @endif
      <a class="ahr-btn" data-filter-link href="{{ $collapseUrl }}">
        {{ $filtersOpen ? 'Hide filters' : 'Adjust filters' }}
        <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-{{ $filtersOpen ? 'reset' : 'layers' }}"></use></svg>
      </a>
    </div>
  </div>

  @if($filtersOpen)
    <form method="GET" action="{{ route('admin.harvests.reports.generate') }}" id="ahr-scope-form">
      <input type="hidden" name="tab" value="{{ $activeTab }}">
      <input type="hidden" name="filters" value="open">

      <div class="ahr-scope-band ahr-band">
        <div class="ahr-band-head">
          <span class="ahr-band-label">Years</span>
        </div>
        <div class="ahr-year-grid">
          @forelse($options['years'] ?? [] as $year)
            @php $yearKey = (string) $year['year']; @endphp
            <label class="ahr-year-chip {{ in_array($yearKey, $selectedYears, true) ? 'active' : '' }}">
              <input type="checkbox" name="years[]" value="{{ $yearKey }}"
                     {{ in_array($yearKey, $selectedYears, true) ? 'checked' : '' }}
                     style="position:absolute;opacity:0;pointer-events:none">
              <span>{{ $year['year'] }}</span>
            </label>
          @empty
            <p class="ahr-sub" style="margin:0">No harvest years are recorded yet.</p>
          @endforelse
        </div>
      </div>

      <div class="ahr-scope-band ahr-band">
        <div class="ahr-band-head">
          <span class="ahr-band-label">Season</span>
        </div>
        <div class="ahr-season-grid">
          @foreach([
            'all' => ['Both seasons', 'layers'],
            'wet' => ['Wet season', 'rain'],
            'dry' => ['Dry season', 'sun'],
          ] as $id => $seasonOption)
            <a class="ahr-season-btn {{ $selectedSeason === $id ? 'active' : '' }}" data-filter-link
               href="{{ $scopeUrl($id === 'all' ? [] : ['season' => $id]) }}">
              <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-{{ $seasonOption[1] }}"></use></svg>
              <span>{{ $seasonOption[0] }}</span>
            </a>
          @endforeach
        </div>
      </div>

      <div class="ahr-scope-band ahr-band">
        <div class="ahr-band-head">
          <span class="ahr-band-label">Crops</span>
          @if($selectedCrops !== [])
            <a class="ahr-link-btn" data-filter-link href="{{ $scopeUrl() }}">All crops</a>
          @endif
        </div>
        <div class="ahr-crop-list">
          <a class="ahr-crop-chip {{ $selectedCrops === [] ? 'active' : '' }}" data-filter-link href="{{ $scopeUrl() }}">
            All crops
          </a>
          @forelse($options['crops'] ?? [] as $crop)
            @php $cropName = (string) $crop['crop']; @endphp
            <label class="ahr-crop-chip {{ in_array($cropName, $selectedCrops, true) ? 'active' : '' }}">
              <input type="checkbox" name="crops[]" value="{{ $cropName }}"
                     {{ in_array($cropName, $selectedCrops, true) ? 'checked' : '' }}
                     style="position:absolute;opacity:0;pointer-events:none">
              <span>{{ $cropName }}</span>
              <span class="ahr-crop-meta">{{ number_format((float) $crop['area_ha'], 1) }} ha</span>
            </label>
          @empty
            <span class="ahr-sub" style="margin:0">No crop records are available yet.</span>
          @endforelse
        </div>
      </div>
    </form>
  @endif

  <div class="ahr-scope-footer">
    <div class="ahr-live-scope">
      <strong>In scope:</strong>
      {{ (int) ($previewVisual['total_records'] ?? 0) }} records ·
      {{ number_format((float) ($previewVisual['total_area_ha'] ?? 0), 1) }} ha ·
      {{ number_format((float) ($previewVisual['total_yield_mt'] ?? 0), 1) }} MT
    </div>

    <form method="POST" action="{{ route('admin.harvests.reports.generate.store') }}" id="ahr-generate-form">
      @csrf
      <input type="hidden" name="default_language" value="{{ old('default_language', 'en') }}">
      <input type="hidden" name="title" value="{{ old('title') }}">
      @foreach($filters as $filterKey => $filterValue)
        @if(is_array($filterValue))
          @foreach($filterValue as $filterItem)
            <input type="hidden" name="filters[{{ $filterKey }}][]" value="{{ $filterItem }}">
          @endforeach
        @elseif($filterKey !== '_report_ids')
          <input type="hidden" name="filters[{{ $filterKey }}]" value="{{ $filterValue }}">
        @endif
      @endforeach
      <button type="submit" class="ahr-generate-btn" @disabled((int) ($previewVisual['total_records'] ?? 0) === 0)>
        <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-file"></use></svg>
        Generate report
      </button>
    </form>
  </div>
</section>
