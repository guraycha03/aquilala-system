@php
  $isSaved = isset($report);
  $language = $language ?? 'en';
  $narratives = $narratives ?? [];
  $options = $options ?? [];
  $filters = $filters ?? [];

  // Seasonal charts now live on the Harvest Performance page. The report
  // keeps the narrative and the metrics that support it.
  $records = (int) ($visual['total_records'] ?? 0);
  $fmt = fn ($value, int $digits = 1) => $value === null ? '—' : number_format((float) $value, $digits);
  $money = fn ($value) => $value === null ? '—' : '₱' . number_format((float) $value, 0);
  $pickSection = function (array $sections, array $headings) {
    foreach ($headings as $heading) {
      if (! empty($sections[$heading])) {
        return $sections[$heading];
      }
    }
    return [];
  };

  // Quick year picker for the headline figures. The current year leads so a
  // reader can see this season's numbers at a glance without opening filters.
  $yearOptions = collect($options['years'] ?? [])
    ->pluck('year')
    ->map(fn ($value) => (string) $value)
    ->filter()
    ->unique()
    ->values()
    ->all();
  $currentYear = (string) now()->year;
  usort($yearOptions, function (string $left, string $right) use ($currentYear) {
    if ($left === $currentYear) return -1;
    if ($right === $currentYear) return 1;

    return (int) $right <=> (int) $left;
  });
  $selectedQuickYear = '';
  if (count($filters['years'] ?? []) === 1) {
    $selectedQuickYear = (string) ($filters['years'][0] ?? '');
  } elseif (! empty($filters['year'])) {
    $selectedQuickYear = (string) $filters['year'];
  }

@endphp

@if($records === 0)
  <section class="ahr-card ahr-empty-state">
    <svg class="ahr-icon lg" aria-hidden="true"><use href="#ahr-i-file"></use></svg>
    <h3>No Harvest Records Match This Scope</h3>
    <p>Adjust the selected years, season, or crops to preview the figures for this report.</p>
    <a class="ahr-btn primary" data-filter-link href="{{ route('admin.harvests.reports.generate', ['tab' => 'generate']) }}">Reset All Filters</a>
  </section>
@else
  <div class="ahr-stack">
    {{-- 1. KPI strip with a single-year quick filter --}}
    @if(!$isSaved)
      <div class="ahr-kpi-toolbar">
        <div>
          <h2 class="ahr-card-title">Performance at a glance</h2>
          <p class="ahr-sub">{{ $scope }}</p>
        </div>
        <label class="ahr-year-pick">
          <span>Year</span>
          <select id="ahr-kpi-year" aria-label="Show figures for one year">
            <option value="">All years</option>
            @foreach($yearOptions as $year)
              <option value="{{ $year }}" {{ $selectedQuickYear === $year ? 'selected' : '' }}>
                {{ $year }}{{ $year === $currentYear ? ' (current)' : '' }}
              </option>
            @endforeach
          </select>
        </label>
      </div>
    @endif

    <section class="ahr-kpis" aria-label="Key harvest metrics">
      <div class="ahr-card ahr-kpi">
        <div class="ahr-kpi-label">Total Harvest Volume</div>
        <div class="ahr-kpi-main">
          <span class="ahr-kpi-value">{{ $fmt($visual['total_yield_mt'] ?? 0, 1) }} <span class="ahr-kpi-unit">MT</span></span>
          @if(($visual['yoy_yield_delta_pct'] ?? null) !== null)
            <span class="ahr-delta {{ $visual['yoy_yield_delta_pct'] >= 0 ? 'up' : 'down' }}">
              <svg class="ahr-icon sm" aria-hidden="true"><use href="#ahr-i-trend"></use></svg>
              {{ $visual['yoy_yield_delta_pct'] >= 0 ? '+' : '' }}{{ $fmt($visual['yoy_yield_delta_pct'], 1) }}% YoY
            </span>
          @endif
        </div>
        <div class="ahr-kpi-note">
          {{ (int) ($visual['total_records'] ?? 0) }} harvests · {{ $fmt($visual['total_farmers'] ?? 0, 0) }} farmers
        </div>
      </div>

      <div class="ahr-card ahr-kpi">
        <div class="ahr-kpi-label">Yield Efficiency &amp; Area</div>
        <div class="ahr-kpi-main">
          <span class="ahr-kpi-value">{{ $fmt($visual['avg_yield_per_ha'] ?? null, 2) }} <span class="ahr-kpi-unit">MT/ha</span></span>
          <span class="ahr-kpi-unit">{{ $fmt($visual['total_area_ha'] ?? 0, 1) }} ha</span>
        </div>
        <div class="ahr-kpi-note">Across {{ count($visual['by_crop'] ?? []) }} crop(s) in this scope</div>
      </div>

      <div class="ahr-card ahr-kpi">
        <div class="ahr-kpi-label">Production Expenses</div>
        <div class="ahr-kpi-main">
          <span class="ahr-kpi-value">{{ $money($visual['total_expenses_php'] ?? 0) }}</span>
          <span class="ahr-kpi-unit">{{ $money($visual['cost_per_mt'] ?? null) }}/MT</span>
        </div>
        <div class="ahr-kpi-note">Recorded input and harvest costs</div>
      </div>

    </section>

    {{-- 2. Saved report - the same paper layout the export uses. --}}
    @if($isSaved)
      @php
        $downloadBaseName = $downloadBaseName ?? $report->downloadBaseName();
        $downloadFilename = $downloadBaseName . '-' . $language . '.html';
      @endphp
      <div class="ahr-report-paper-host"
           data-report-id="{{ $report->id }}"
           data-language="{{ $language }}"
           data-language-url="{{ route('admin.harvests.reports.generated.language', $report) }}"
           data-download-base="{{ route('admin.harvests.reports.generated.download', $report) }}"
           data-download-base-name="{{ $downloadBaseName }}">
        <div class="ahr-report-toolbar">
          <div class="ahr-lang-switch" role="group" aria-label="Report language">
            <button type="button" class="ahr-lang-btn" data-lang-btn="en">English</button>
            <button type="button" class="ahr-lang-btn" data-lang-btn="tl">Tagalog</button>
          </div>
          <div class="ahr-actions">
            <a class="ahr-btn primary" id="ahr-download-report"
               href="{{ route('admin.harvests.reports.generated.download', [$report, 'lang' => $language]) }}"
               download="{{ $downloadFilename }}">
              <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-download"></use></svg>
              Download report
            </a>
          </div>
        </div>

        @foreach(['en', 'tl'] as $panelLanguage)
          <div class="ahr-lang-panel" data-lang-panel="{{ $panelLanguage }}" {{ $panelLanguage !== $language ? 'hidden' : '' }}>
            @include('admin.harvests.generated.partials.report-paper', [
              'language' => $panelLanguage,
            ])
          </div>
        @endforeach

        <div class="ahr-confirm-overlay" id="ahr-download-confirm" hidden>
          <div class="ahr-confirm-card" role="dialog" aria-modal="true" aria-labelledby="ahr-download-confirm-title">
            <h2 id="ahr-download-confirm-title">Download report?</h2>
            <p>
              The report will be saved to this device as
              <strong id="ahr-download-filename">{{ $downloadFilename }}</strong>.
            </p>
            <div class="ahr-confirm-actions">
              <button type="button" class="ahr-btn" id="ahr-download-cancel">Cancel</button>
              <button type="button" class="ahr-btn primary" id="ahr-download-confirm-yes">Download</button>
            </div>
          </div>
        </div>

        <div class="ahr-download-toast" id="ahr-download-toast" role="status" hidden>
          Download started. Check this device's Downloads folder.
        </div>
      </div>
    @endif

  </div>
@endif

<script>
  (function () {
    var host = document.querySelector('.ahr-report-paper-host');
    if (!host) return;

    var buttons = host.querySelectorAll('[data-lang-btn]');
    var panels = host.querySelectorAll('[data-lang-panel]');
    var download = host.querySelector('#ahr-download-report');
    var confirmOverlay = host.querySelector('#ahr-download-confirm');
    var confirmYes = host.querySelector('#ahr-download-confirm-yes');
    var confirmCancel = host.querySelector('#ahr-download-cancel');
    var confirmFilename = host.querySelector('#ahr-download-filename');
    var toast = host.querySelector('#ahr-download-toast');
    var toastTimer = null;
    var storageKey = 'aq-report-language-' + host.getAttribute('data-report-id');
    var language = host.getAttribute('data-language') || 'en';

    try {
      var remembered = localStorage.getItem(storageKey);
      if (remembered === 'en' || remembered === 'tl') language = remembered;
    } catch (error) { /* storage can be blocked; the saved column still wins */ }

    function apply(next, persist) {
      language = next === 'tl' ? 'tl' : 'en';

      buttons.forEach(function (button) {
        button.classList.toggle('active', button.getAttribute('data-lang-btn') === language);
      });
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-lang-panel') !== language;
      });
      if (download) {
        var filename = host.getAttribute('data-download-base-name') + '-' + language + '.html';
        download.href = host.getAttribute('data-download-base') + '?lang=' + language;
        download.setAttribute('download', filename);
        if (confirmFilename) confirmFilename.textContent = filename;
      }

      if (!persist) return;

      try { localStorage.setItem(storageKey, language); } catch (error) { /* ignore */ }
      history.replaceState(null, '', location.pathname + '?lang=' + language);

      var token = document.querySelector('meta[name="csrf-token"]');
      fetch(host.getAttribute('data-language-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
        },
        body: JSON.stringify({ language: language })
      }).catch(function () { /* the local choice still holds for this visit */ });
    }

    apply(language, false);

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        apply(button.getAttribute('data-lang-btn'), true);
      });
    });

    function openConfirm(event) {
      event.preventDefault();
      confirmOverlay.hidden = false;
      confirmYes.focus();
    }

    function closeConfirm() {
      confirmOverlay.hidden = true;
      download.focus();
    }

    function showToast(message) {
      toast.textContent = message || "Download started. Check this device's Downloads folder.";
      toast.hidden = false;
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { toast.hidden = true; }, 4500);
    }

    function startDownload() {
      var url = download.href;
      var filename = download.getAttribute('download') || 'harvest-report.html';
      confirmOverlay.hidden = true;

      fetch(url, { credentials: 'same-origin' })
        .then(function (response) {
          if (!response.ok) throw new Error('Download failed');
          return response.blob();
        })
        .then(function (blob) {
          var objectUrl = URL.createObjectURL(blob);
          var link = document.createElement('a');
          link.href = objectUrl;
          link.download = filename;
          document.body.appendChild(link);
          link.click();
          link.remove();
          setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1000);
          showToast();
        })
        .catch(function () {
          showToast('Download could not start. Please try again.');
        });
    }

    download.addEventListener('click', openConfirm);
    confirmCancel.addEventListener('click', closeConfirm);
    confirmYes.addEventListener('click', startDownload);
    confirmOverlay.addEventListener('click', function (event) {
      if (event.target === confirmOverlay) closeConfirm();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !confirmOverlay.hidden) closeConfirm();
    });
  })();
</script>
