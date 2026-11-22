@php
  $optionYears = collect($options['years'] ?? [])->pluck('year')->map(fn ($y) => (string) $y)->all();
  $selectedYears = $filters['years'] ?? (isset($filters['year']) ? [(string) $filters['year']] : $optionYears);
  $selectedYears = array_values(array_map('strval', is_array($selectedYears) ? $selectedYears : []));

  $selectedCrops = $filters['crops'] ?? (isset($filters['crop']) ? [$filters['crop']] : []);
  $selectedCrops = array_values(array_map('strval', is_array($selectedCrops) ? $selectedCrops : []));

  $selectedSeason = (string) ($filters['season'] ?? 'all');

  // The browser keeps one authoritative copy of the filter state so clicking a
  // chip never needs a server round-trip. Carry-over filters from Harvest
  // Reports (farmer, search, month) are folded in as well.
  $filterState = $filters;
  unset($filterState['year'], $filterState['crop'], $filterState['_report_ids']);
  $filterState['years'] = $selectedYears;
  $filterState['crops'] = $selectedCrops;
  $filterState['season'] = $selectedSeason;

  // Every control below is a button handled entirely in the browser. None of
  // them carries an href, so a click can never reload the page while scripts
  // are still loading.
@endphp

<section class="ahr-card" aria-label="Report scope configurator">
  <div class="ahr-card-head">
    <div>
      <h2 class="ahr-card-title">Report Scope</h2>
      <p class="ahr-sub" id="ahr-scope-subtitle">
        {{ $filtersOpen ? 'Choose the harvests to include in this report.' : $scope }}
      </p>
    </div>
    <div class="ahr-actions">
      @if($filtersOpen)
        <button type="button" class="ahr-link-btn" data-filter-reset>Reset</button>
      @endif
      <button type="button" class="ahr-btn {{ $filtersOpen ? '' : 'ahr-collapsed' }}" data-filter-toggle aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}">
        <svg class="ahr-icon sm ahr-toggle-chevron" aria-hidden="true"><use href="#ahr-i-chevron-down"></use></svg>
        <span>{{ $filtersOpen ? 'Hide filters' : 'Adjust filters' }}</span>
      </button>
    </div>
  </div>

  <form method="GET" action="{{ route('admin.harvests.reports.generate') }}" id="ahr-scope-form"
        data-filter-state="{{ json_encode($filterState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}">
    <input type="hidden" name="tab" value="{{ $activeTab }}">
    <input type="hidden" name="filters" id="ahr-filters-visibility" value="{{ $filtersOpen ? 'open' : 'closed' }}">

    <div id="ahr-filter-fields" {{ $filtersOpen ? '' : 'hidden' }}>
      <div class="ahr-scope-band ahr-band">
        <div class="ahr-band-head">
          <span class="ahr-band-label">Years</span>
        </div>
        <div class="ahr-year-grid">
          @forelse($options['years'] ?? [] as $year)
            @php $yearKey = (string) $year['year']; @endphp
            <button type="button" class="ahr-year-chip {{ in_array($yearKey, $selectedYears, true) ? 'active' : '' }}" data-year-chip data-year="{{ $yearKey }}" aria-pressed="{{ in_array($yearKey, $selectedYears, true) ? 'true' : 'false' }}">
              <span>{{ $year['year'] }}</span>
            </button>
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
            <button type="button" class="ahr-season-btn {{ $selectedSeason === $id ? 'active' : '' }}" data-filter-season="{{ $id }}" aria-pressed="{{ $selectedSeason === $id ? 'true' : 'false' }}">
              <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-{{ $seasonOption[1] }}"></use></svg>
              <span>{{ $seasonOption[0] }}</span>
            </button>
          @endforeach
        </div>
      </div>

      <div class="ahr-scope-band ahr-band">
        <div class="ahr-band-head">
          <span class="ahr-band-label">Crops</span>
          @if($selectedCrops !== [])
            <button type="button" class="ahr-link-btn" data-filter-crops-reset>All crops</button>
          @endif
        </div>
        <div class="ahr-crop-list">
          <button type="button" class="ahr-crop-chip {{ $selectedCrops === [] ? 'active' : '' }}" data-filter-crops-clear aria-pressed="{{ $selectedCrops === [] ? 'true' : 'false' }}">
            All crops
          </button>
          @forelse($options['crops'] ?? [] as $crop)
            @php $cropName = (string) $crop['crop']; @endphp
            <button type="button" class="ahr-crop-chip {{ in_array($cropName, $selectedCrops, true) ? 'active' : '' }}" data-crop-chip data-crop="{{ $cropName }}" aria-pressed="{{ in_array($cropName, $selectedCrops, true) ? 'true' : 'false' }}">
              <span>{{ $cropName }}</span>
              <span class="ahr-crop-meta">{{ number_format((float) $crop['area_ha'], 1) }} ha</span>
            </button>
          @empty
            <span class="ahr-sub" style="margin:0">No crop records are available yet.</span>
          @endforelse
        </div>
      </div>
    </div>
  </form>

  <div class="ahr-scope-footer">
    <div class="ahr-live-scope" id="ahr-scope-summary" aria-live="polite">
      <strong>In scope:</strong>
      <span id="ahr-scope-summary-text">{{ (int) ($previewVisual['total_records'] ?? 0) }} records · {{ number_format((float) ($previewVisual['total_area_ha'] ?? 0), 1) }} ha · {{ number_format((float) ($previewVisual['total_yield_mt'] ?? 0), 1) }} MT</span>
    </div>

    <form method="POST" action="{{ route('admin.harvests.reports.generate.store') }}" id="ahr-generate-form">
      @csrf
      <input type="hidden" name="default_language" value="{{ old('default_language', 'en') }}">
      <input type="hidden" name="title" value="{{ old('title') }}">
      <div id="ahr-filter-inputs">
        @foreach($filterState as $filterKey => $filterValue)
          @if(is_array($filterValue))
            @foreach($filterValue as $filterItem)
              <input type="hidden" name="filters[{{ $filterKey }}][]" value="{{ $filterItem }}">
            @endforeach
          @elseif($filterValue !== '' && $filterValue !== null)
            <input type="hidden" name="filters[{{ $filterKey }}]" value="{{ $filterValue }}">
          @endif
        @endforeach
      </div>
      <button type="submit" id="ahr-generate-button" class="ahr-generate-btn" @disabled((int) ($previewVisual['total_records'] ?? 0) === 0)>
        <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-file"></use></svg>
        Generate report
      </button>
    </form>
  </div>
</section>
