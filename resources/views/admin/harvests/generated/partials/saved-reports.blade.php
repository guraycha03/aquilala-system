@php
  $savedScope = function ($saved): string {
    $filters = collect($saved->filters ?? [])->except('_report_ids');
    if ($filters->isEmpty()) return 'All recorded harvest reports';
    return $filters->map(function ($value, $key) {
      if (is_array($value)) $value = implode(', ', array_map('strval', $value));
      return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
    })->implode(', ');
  };
@endphp

@if($savedReports->isEmpty())
  <section class="ahr-card ahr-empty-state">
    <svg class="ahr-icon lg" aria-hidden="true"><use href="#ahr-i-bookmark"></use></svg>
    <h2>No Saved AI Harvest Reports Yet</h2>
    <p>
      Select the harvests to include, prepare a written report,
      and save it here for association meetings and seasonal audits.
    </p>
    <a class="ahr-btn primary" href="{{ route('admin.harvests.reports.generate', ['tab' => 'generate']) }}">
      Open AI Harvest Report
      <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-arrow"></use></svg>
    </a>
  </section>
@else
  <section class="ahr-card">
    <div class="ahr-card-head">
      <div>
        <h2 class="ahr-card-title">Saved Association Reports</h2>
        <p class="ahr-sub">Archived reports ready to reopen, review, or download</p>
      </div>
    </div>
    <div>
      @foreach($savedReports as $saved)
        @php
          $totals = $saved->context['totals'] ?? [];
          $coverage = $saved->context['coverage'] ?? [];
          $yield = (float) ($totals['yield_tons'] ?? 0);
          $area = (float) ($totals['area_ha'] ?? 0);
          $count = (int) ($totals['reports'] ?? ($coverage['reports'] ?? 0));
        @endphp
        <div class="ahr-saved-row">
          <div style="min-width:0;max-width:760px">
            <h3 class="ahr-saved-title">{{ $saved->title }}</h3>
            <p class="ahr-saved-headline">
              Prepared from the figures recorded for this scope
              · {{ $saved->default_language === 'tl' ? 'Tagalog' : 'English' }} first
            </p>
            <div class="ahr-saved-meta">
              <span>Saved {{ $saved->created_at->format('M d, Y') }}</span>
              <span>·</span>
              <span>{{ $savedScope($saved) }}</span>
              <span>·</span>
              <span>{{ number_format($yield, 1) }} MT across {{ number_format($area, 1) }} ha ({{ $count }} records)</span>
            </div>
          </div>
          <div class="ahr-actions">
            <a class="ahr-btn primary" href="{{ route('admin.harvests.reports.generated.show', $saved) }}">
              Open Report
              <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-arrow"></use></svg>
            </a>
            <a class="ahr-btn" href="{{ route('admin.harvests.reports.generated.download', [$saved, 'lang' => 'en']) }}">Download EN</a>
            <a class="ahr-btn" href="{{ route('admin.harvests.reports.generated.download', [$saved, 'lang' => 'tl']) }}">Download TL</a>
            <form method="POST" action="{{ route('admin.harvests.reports.generated.destroy', $saved) }}"
                  onsubmit="return confirm('Remove this saved report?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="ahr-btn danger" aria-label="Delete {{ $saved->title }}">
                <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-trash"></use></svg>
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endif
