@php
  $exportQuery = [];
  foreach ($filters as $key => $value) {
    if (is_array($value)) {
      foreach ($value as $item) {
        if ($key !== '_report_ids') $exportQuery[$key . '[]'][] = $item;
      }
    } elseif ($key !== '_report_ids') {
      $exportQuery[$key] = $value;
    }
  }

  $sortUrl = fn (string $key) => route('admin.harvests.reports.generate', array_merge(
    request()->except(['record_sort', 'record_dir']),
    [
      'tab' => 'records',
      'record_sort' => $key,
      'record_dir' => ($recordSort === $key && $recordDir === 'desc') ? 'asc' : 'desc',
    ]
  ));
@endphp

<section class="ahr-card ahr-table-card">
  <div class="ahr-table-toolbar">
    <div>
      <h2 class="ahr-card-title">Harvest Reports</h2>
      <p class="ahr-sub">Filtered by active scope: {{ $scope }}</p>
    </div>
    <div class="ahr-actions">
      <form method="GET" action="{{ route('admin.harvests.reports.generate') }}" class="ahr-search">
        <input type="hidden" name="tab" value="records">
        @foreach($filters as $key => $value)
          @if(is_array($value))
            @foreach($value as $item)
              @if($key !== '_report_ids')<input type="hidden" name="{{ $key }}[]" value="{{ $item }}">@endif
            @endforeach
          @elseif($key !== '_report_ids')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-search"></use></svg>
        <input type="search" name="record_q" value="{{ $recordSearch }}" placeholder="Search farmer, crop, variety…" aria-label="Search harvest records">
      </form>
      <a class="ahr-btn primary" href="{{ route('admin.harvests.reports.generate.export', $exportQuery) }}">
        <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-download"></use></svg>
        Export CSV ({{ $records->count() }})
      </a>
    </div>
  </div>

  @if($records->isEmpty())
    <div class="ahr-empty-state">
      <p style="margin:0;font-weight:700">No records match your current scope or search query.</p>
      <a class="ahr-btn" data-filter-link style="margin-top:14px" href="{{ route('admin.harvests.reports.generate', ['tab' => 'records']) }}">Reset Scope Filters</a>
    </div>
  @else
    <div class="ahr-table-scroll">
      <table class="ahr-table">
        <thead>
          <tr>
            <th>Farmer</th>
            <th>
              <a class="ahr-sort" data-filter-link href="{{ $sortUrl('crop') }}">Crop &amp; Variety <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-sort"></use></svg></a>
            </th>
            <th>Season</th>
            <th class="right"><a class="ahr-sort" data-filter-link href="{{ $sortUrl('area') }}">Area <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-sort"></use></svg></a></th>
            <th class="right"><a class="ahr-sort" data-filter-link href="{{ $sortUrl('yield') }}">Yield <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-sort"></use></svg></a></th>
            <th class="right"><a class="ahr-sort" data-filter-link href="{{ $sortUrl('expenses') }}">Expenses <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-sort"></use></svg></a></th>
            <th class="right"><a class="ahr-sort" data-filter-link href="{{ $sortUrl('date') }}">Harvested <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-sort"></use></svg></a></th>
          </tr>
        </thead>
        <tbody>
          @foreach($records as $harvest)
            @php $attributes = $harvest->getAttributes(); @endphp
            <tr>
              <td>
                <div class="ahr-cell-main">{{ $harvest->reporterName() }}</div>
                <div class="ahr-cell-sub">#{{ $harvest->id }}</div>
              </td>
              <td>
                <div class="ahr-cell-main">{{ $harvest->crop_type }}</div>
                <div class="ahr-cell-sub">{{ $harvest->variety ?: 'No variety recorded' }}</div>
              </td>
              <td>
                <span class="ahr-season-tag {{ $harvest->season === 'wet' ? 'wet' : 'dry' }}">
                  <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-{{ $harvest->season === 'wet' ? 'rain' : 'sun' }}"></use></svg>
                  {{ ucfirst((string) $harvest->season) }}
                </span>
              </td>
              <td class="right ahr-num">{{ number_format((float) ($attributes['land_area'] ?? 0), 2) }} ha</td>
              <td class="right">
                <div class="ahr-num ahr-cell-main">{{ number_format((float) ($attributes['actual_yield_metric_tons'] ?? 0), 2) }} MT</div>
                <div class="ahr-cell-sub ahr-num">
                  {{ ((float) ($attributes['land_area'] ?? 0) > 0)
                      ? number_format((float) ($attributes['actual_yield_metric_tons'] ?? 0) / (float) $attributes['land_area'], 2)
                      : '0.00' }} MT/ha
                </div>
              </td>
              <td class="right ahr-num">₱{{ number_format((float) ($attributes['actual_total_expenses'] ?? 0), 0) }}</td>
              <td class="right ahr-num">{{ $harvest->harvest_date?->format('M d, Y') ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</section>
