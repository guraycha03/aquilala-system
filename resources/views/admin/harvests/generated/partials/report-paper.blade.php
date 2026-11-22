@php
  use Illuminate\Support\Carbon;

  $tl = $language === 'tl';
  $sections = $narratives[$language] ?? [];
  $totals = $context['totals'] ?? [];
  $coverage = $context['coverage'] ?? [];
  $visual = $visual ?? ($context['visual'] ?? []);

  $pick = function (array $names) use ($sections) {
    foreach ($names as $name) {
      if (! empty($sections[$name])) return $sections[$name];
    }
    return [];
  };

  $paragraphs = function (array $lines) {
    return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
  };

  $period = function () use ($coverage, $tl) {
    try {
      $first = ! empty($coverage['first_harvest']) ? Carbon::parse($coverage['first_harvest']) : null;
      $last = ! empty($coverage['last_harvest']) ? Carbon::parse($coverage['last_harvest']) : null;
    } catch (\Throwable $error) {
      $first = $last = null;
    }

    if ($first === null || $last === null) {
      return $tl ? 'Buong napiling saklaw' : 'Entire selected scope';
    }

    $format = $tl ? 'j F Y' : 'F j, Y';

    return $first->toDateString() === $last->toDateString()
      ? $first->format($format)
      : $first->format($format) . ' - ' . $last->format($format);
  };

  $blocks = [
    [
      'page' => 1,
      'heading' => $tl ? 'Buod ng Tagapagpaganap' : 'Executive Summary',
      'names' => ['Buod ng Tagapagpaganap', 'Executive Summary'],
    ],
    [
      'page' => 2,
      'heading' => $tl ? 'Pagganap sa Produksiyon' : 'Production Performance',
      'names' => ['Pagganap sa Produksiyon', 'Production Performance', 'Mga Pangunahing Resulta', 'Performance Highlights'],
    ],
    [
      'page' => 2,
      'heading' => $tl ? 'Pamamahagi ayon sa Pananim at Panahon' : 'Crop and Seasonal Distribution',
      'names' => ['Pamamahagi ayon sa Pananim at Panahon', 'Crop and Seasonal Distribution', 'Obserbasyon sa Pananim at Panahon', 'Crop and Season Observations', 'Obserbasyon sa Pananim', 'Crop Observations'],
    ],
  ];

  $renderBlocks = function (int $page) use ($blocks, $paragraphs, $pick) {
    ob_start();

    foreach ($blocks as $block) {
      if ($block['page'] !== $page) continue;

      $lines = $paragraphs($pick($block['names']));
      if ($lines === []) continue;

      echo '<section class="rp-block"><h2>' . e($block['heading']) . '</h2>';

      foreach ($lines as $line) {
        echo '<p>' . e($line) . '</p>';
      }

      echo '</section>';
    }

    return ob_get_clean();
  };

  $logoPath = public_path('images/logo.png');
  $logoData = is_file($logoPath)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
    : null;
  $reportNumber = 'HR-' . $report->created_at->format('Y') . '-' . str_pad((string) $report->id, 4, '0', STR_PAD_LEFT);

  // The name is frozen with the report so the paper always credits the admin
  // who actually generated it, even if that account is renamed later.
  $preparedBy = trim((string) ($context['prepared_by'] ?? (optional($report->user)->name ?? '')));
  if ($preparedBy === '') $preparedBy = 'Aquilala Irrigators Association, Inc.';

  // The official title stays short; the selected years remain only in the
  // paper subtitle, not in page headings or the saved list.
  $reportTitle = $report->displayTitle();
  if ($tl && strcasecmp($reportTitle, 'Harvest Report') === 0) $reportTitle = 'Ulat sa Ani';

  $reportFilters = $report->filters ?? [];
  $selectedYears = $reportFilters['years'] ?? (isset($reportFilters['year']) ? [$reportFilters['year']] : []);
  $selectedYears = array_values(array_filter(array_map('strval', is_array($selectedYears) ? $selectedYears : [])));
  $yearsLine = $selectedYears !== []
    ? ($tl ? 'Mga Taon: ' : 'Years: ') . implode(', ', $selectedYears)
    : (string) ($scope ?? ($tl ? 'Buong napiling saklaw' : 'Entire selected scope'));

  $scopeBits = [];
  $seasonValue = (string) ($reportFilters['season'] ?? 'all');
  if ($seasonValue !== '' && $seasonValue !== 'all') $scopeBits[] = ucfirst($seasonValue);
  $cropValues = $reportFilters['crops'] ?? ($reportFilters['crop'] ?? []);
  $cropValues = is_array($cropValues) ? array_values(array_filter(array_map('strval', $cropValues))) : array_values(array_filter([(string) $cropValues]));
  if ($cropValues !== []) $scopeBits[] = ($tl ? 'Pananim: ' : 'Crops: ') . implode(', ', $cropValues);
  $scopeDetail = $scopeBits !== []
    ? implode(' · ', $scopeBits)
    : ($tl ? 'Buong napiling saklaw' : 'All included harvests');

  // All chart series come from the figures frozen with this saved report.
  $byYear = collect($visual['by_year'] ?? [])->sortBy('year')->values()->all();
  $byCrop = collect($visual['by_crop'] ?? [])
    ->sortBy(fn ($row) => strtolower((string) ($row['crop_type'] ?? '')))
    ->values()
    ->all();
  $topCrops = collect($visual['by_crop'] ?? [])
    ->sortByDesc(fn ($row) => (float) ($row['total_yield_mt'] ?? 0))
    ->take(6)
    ->values()
    ->all();
  $chartYears = array_slice($byYear, -6);
  $maxYield = 1.0;
  foreach ($chartYears as $yearRow) {
    $maxYield = max($maxYield, (float) ($yearRow['total_yield_mt'] ?? 0));
  }

  $plotLeft = 46;
  $plotRight = 498;
  $plotTop = 16;
  $plotBottom = 166;
  $plotHeight = $plotBottom - $plotTop;
  $band = ($plotRight - $plotLeft) / max(count($chartYears), 1);
  $barWidth = min(36, $band * 0.55);

  $wetShare = max(0, min(100, (float) ($visual['wet_share_pct'] ?? 0)));
  $dryShare = 100 - $wetShare;
  $donutRadius = 48;
  $donutLength = 2 * pi() * $donutRadius;
  $wetLength = $donutLength * $wetShare / 100;
  $dryLength = $donutLength - $wetLength;
@endphp

<article class="report-paper">
  <div class="rp-document">
    {{-- PAGE 1 — official cover and executive summary --}}
    <div class="rp-sheet-frame">
    <section class="rp-sheet">
      <header class="rp-header">
        @if($logoData)
          <img class="rp-logo" src="{{ $logoData }}" alt="Aquilala Irrigators Association Inc. logo">
        @endif
        <div class="rp-org">
          <strong>Aquilala Irrigators Association, Inc.</strong>
          <span>{{ $tl ? 'Ulat sa Pagganap ng Ani' : 'Harvest Performance Report' }}</span>
          <small>{{ $tl ? 'Sistema ng Patubig na San Francisco River — Barangay Aquino, Bulan, Sorsogon — Rehistro Blg. CN201228664' : 'San Francisco River Irrigation System — Barangay Aquino, Bulan, Sorsogon — Registration No. CN201228664' }}</small>
        </div>
        <div class="rp-date">
          <span>{{ $tl ? 'Petsa ng Pag-isyu' : 'Date issued' }}</span>
          {{ $report->created_at->format('F d, Y') }}
        </div>
      </header>

      <div class="rp-title-row">
        <div class="rp-title-copy">
          <div class="rp-document-type">{{ $tl ? 'OPISYAL NA ULA' : 'OFFICIAL REPORT' }}</div>
          <h1 class="rp-title">{{ $reportTitle }}</h1>
          <p class="rp-years">{{ $yearsLine }}</p>
        </div>
      </div>

      <table class="rp-facts">
        <tbody>
          <tr><th>{{ $tl ? 'Blg. ng Ulat' : 'Report No.' }}</th><td>{{ $reportNumber }}</td></tr>
          <tr><th>{{ $tl ? 'Panahon ng Saklaw' : 'Reporting period' }}</th><td>{{ $period() }}</td></tr>
          <tr><th>{{ $tl ? 'Saklaw' : 'Scope' }}</th><td>{{ $scopeDetail }}</td></tr>
          <tr><th>{{ $tl ? 'Inihanda ni' : 'Prepared by' }}</th><td>{{ $preparedBy }}</td></tr>
          <tr><th>{{ $tl ? 'Wika' : 'Language' }}</th><td>{{ $tl ? 'Tagalog' : 'English' }}</td></tr>
        </tbody>
      </table>

      <section class="rp-figures" aria-label="{{ $tl ? 'Mga pangunahing bilang' : 'Key figures' }}">
        <div class="rp-figure"><span>{{ $tl ? 'Mga Talaan' : 'Records' }}</span><strong>{{ (int) ($totals['reports'] ?? ($coverage['reports'] ?? 0)) }}</strong><small>{{ $tl ? 'Mga ulat ng ani' : 'Harvest reports recorded' }}</small></div>
        <div class="rp-figure"><span>{{ $tl ? 'Mga Magsasaka' : 'Reporting farmers' }}</span><strong>{{ (int) ($totals['farmers'] ?? ($coverage['farmers'] ?? 0)) }}</strong><small>{{ $tl ? 'Kalahok sa saklaw' : 'Included in scope' }}</small></div>
        <div class="rp-figure"><span>{{ $tl ? 'Kabuuang Produksiyon' : 'Total production' }}</span><strong>{{ number_format((float) ($totals['yield_tons'] ?? 0), 2) }} MT</strong><small>{{ $tl ? 'Naitalang ani' : 'Recorded output' }}</small></div>
        <div class="rp-figure"><span>{{ $tl ? 'Lawak na Taniman' : 'Cultivated area' }}</span><strong>{{ number_format((float) ($totals['area_ha'] ?? 0), 2) }} ha</strong><small>{{ $tl ? 'Pinagtaniman' : 'Under cultivation' }}</small></div>
        <div class="rp-figure"><span>{{ $tl ? 'Ani bawat Ektarya' : 'Yield per hectare' }}</span><strong>{{ number_format((float) ($totals['yield_per_ha'] ?? 0), 3) }} MT/ha</strong><small>{{ $tl ? 'Average' : 'Average' }}</small></div>
        <div class="rp-figure"><span>{{ $tl ? 'Natalang Gastos' : 'Recorded expenditure' }}</span><strong>₱{{ number_format((float) ($totals['expenses_php'] ?? 0), 2) }}</strong><small>{{ $tl ? 'Input at pag-aani' : 'Input and harvest costs' }}</small></div>
      </section>

      {!! $renderBlocks(1) !!}

      <div class="rp-sheet-footer">
        <span>{{ $reportNumber }} · {{ $tl ? 'Kopya para sa pagsusumite' : 'Submission copy' }}</span>
        <span>{{ $tl ? 'Pahina' : 'Page' }} 1 / 3</span>
      </div>
    </section>
    </div>

    {{-- PAGE 2 — analytical tables supporting the narrative --}}
    <div class="rp-sheet-frame">
    <section class="rp-sheet">
      <header class="rp-running-head">
        <strong>Aquilala Irrigators Association, Inc.</strong>
        <span>{{ $reportNumber }}</span>
      </header>

      <h2 class="rp-section-title">{{ $tl ? 'Talahanayan ng Produksiyon' : 'Production Tables' }}</h2>
      <p class="rp-section-note">{{ $tl ? 'Ang mga bilang ay mula sa naitalang ulat ng ani sa napiling saklaw.' : 'Figures are drawn from the recorded harvest reports within the selected scope.' }}</p>

      {!! $renderBlocks(2) !!}

      <table class="rp-table">
        <caption>{{ $tl ? 'Talahanayan 1 — Taunang produksiyon' : 'Table 1 — Annual production' }}</caption>
        <thead>
          <tr>
            <th>{{ $tl ? 'Taon' : 'Year' }}</th>
            <th>{{ $tl ? 'Talaan' : 'Records' }}</th>
            <th>{{ $tl ? 'Lawak (ha)' : 'Area (ha)' }}</th>
            <th>{{ $tl ? 'Basang Ani (MT)' : 'Wet (MT)' }}</th>
            <th>{{ $tl ? 'Tuyong Ani (MT)' : 'Dry (MT)' }}</th>
            <th>{{ $tl ? 'Kabuuan (MT)' : 'Total (MT)' }}</th>
            <th>{{ $tl ? 'MT/ha' : 'MT/ha' }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($byYear as $yearRow)
            <tr>
              <td>{{ $yearRow['year'] }}</td>
              <td>{{ (int) ($yearRow['record_count'] ?? 0) }}</td>
              <td>{{ number_format((float) ($yearRow['total_area_ha'] ?? 0), 2) }}</td>
              <td>{{ number_format((float) ($yearRow['wet_yield_mt'] ?? 0), 2) }}</td>
              <td>{{ number_format((float) ($yearRow['dry_yield_mt'] ?? 0), 2) }}</td>
              <td><strong>{{ number_format((float) ($yearRow['total_yield_mt'] ?? 0), 2) }}</strong></td>
              <td>{{ number_format((float) ($yearRow['total_area_ha'] ?? 0) > 0 ? (float) ($yearRow['total_yield_mt'] ?? 0) / (float) $yearRow['total_area_ha'] : 0, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="7">{{ $tl ? 'Walang naitalang datos.' : 'No recorded data.' }}</td></tr>
          @endforelse
        </tbody>
      </table>

      <table class="rp-table">
        <caption>{{ $tl ? 'Talahanayan 2 — Pamamahagi ayon sa pananim' : 'Table 2 — Distribution by crop' }}</caption>
        <thead>
          <tr>
            <th>{{ $tl ? 'Pananim' : 'Crop' }}</th>
            <th>{{ $tl ? 'Talaan' : 'Records' }}</th>
            <th>{{ $tl ? 'Lawak (ha)' : 'Area (ha)' }}</th>
            <th>{{ $tl ? 'Ani (MT)' : 'Yield (MT)' }}</th>
            <th>{{ $tl ? 'Bahagi' : 'Share' }}</th>
            <th>{{ $tl ? 'MT/ha' : 'MT/ha' }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($byCrop as $crop)
            <tr>
              <td>{{ $crop['crop_type'] }}</td>
              <td>{{ (int) ($crop['record_count'] ?? 0) }}</td>
              <td>{{ number_format((float) ($crop['total_area_ha'] ?? 0), 2) }}</td>
              <td>{{ number_format((float) ($crop['total_yield_mt'] ?? 0), 2) }}</td>
              <td>{{ (int) ($crop['share_pct'] ?? 0) }}%</td>
              <td>{{ number_format((float) ($crop['yield_per_ha'] ?? 0), 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="6">{{ $tl ? 'Walang naitalang datos.' : 'No recorded data.' }}</td></tr>
          @endforelse
        </tbody>
      </table>

      <table class="rp-table rp-table-season">
        <caption>{{ $tl ? 'Talahanayan 3 — Buod ayon sa panahon' : 'Table 3 — Seasonal summary' }}</caption>
        <thead>
          <tr>
            <th>{{ $tl ? 'Panahon' : 'Season' }}</th>
            <th>{{ $tl ? 'Talaan' : 'Records' }}</th>
            <th>{{ $tl ? 'Lawak (ha)' : 'Area (ha)' }}</th>
            <th>{{ $tl ? 'Ani (MT)' : 'Yield (MT)' }}</th>
            <th>{{ $tl ? 'Bahagi' : 'Share' }}</th>
            <th>{{ $tl ? 'Gastos/MT' : 'Cost/MT' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ $tl ? 'Basang Panahon' : 'Wet season' }}</td>
            <td>{{ (int) (($visual['wet_summary'] ?? [])['record_count'] ?? 0) }}</td>
            <td>{{ number_format((float) (($visual['wet_summary'] ?? [])['total_area_ha'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) (($visual['wet_summary'] ?? [])['total_yield_mt'] ?? 0), 2) }}</td>
            <td>{{ number_format($wetShare, 1) }}%</td>
            <td>₱{{ number_format((float) (($visual['wet_summary'] ?? [])['cost_per_mt'] ?? 0), 0) }}</td>
          </tr>
          <tr>
            <td>{{ $tl ? 'Tuyong Panahon' : 'Dry season' }}</td>
            <td>{{ (int) (($visual['dry_summary'] ?? [])['record_count'] ?? 0) }}</td>
            <td>{{ number_format((float) (($visual['dry_summary'] ?? [])['total_area_ha'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) (($visual['dry_summary'] ?? [])['total_yield_mt'] ?? 0), 2) }}</td>
            <td>{{ number_format($dryShare, 1) }}%</td>
            <td>₱{{ number_format((float) (($visual['dry_summary'] ?? [])['cost_per_mt'] ?? 0), 0) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="rp-sheet-footer">
        <span>{{ $reportNumber }} · {{ $tl ? 'Talahanayan' : 'Analytical tables' }}</span>
        <span>{{ $tl ? 'Pahina' : 'Page' }} 2 / 3</span>
      </div>
    </section>
    </div>

    {{-- PAGE 3 — charts, governance and attestation --}}
    <div class="rp-sheet-frame">
    <section class="rp-sheet">
      <header class="rp-running-head">
        <strong>Aquilala Irrigators Association, Inc.</strong>
        <span>{{ $reportNumber }}</span>
      </header>

      <h2 class="rp-section-title">{{ $tl ? 'Buod ng Biswal' : 'Visual Summary' }}</h2>

      <div class="rp-chart-grid">
        <section class="rp-chart-card rp-chart-wide">
          <h3>{{ $tl ? 'Ani bawat Taon' : 'Yield by Year' }}</h3>
          @if($chartYears !== [])
            <svg class="rp-svg" viewBox="0 0 520 210" role="img" aria-label="{{ $tl ? 'Tsart ng ani bawat taon' : 'Bar chart of yield by year' }}">
              @for($i = 0; $i <= 4; $i++)
                @php
                  $gridY = $plotBottom - ($plotHeight * $i / 4);
                  $gridValue = $maxYield * $i / 4;
                @endphp
                <line x1="{{ $plotLeft }}" y1="{{ $gridY }}" x2="{{ $plotRight }}" y2="{{ $gridY }}" class="rp-grid"></line>
                <text x="{{ $plotLeft - 8 }}" y="{{ $gridY + 3 }}" text-anchor="end" class="rp-axis">{{ number_format($gridValue, 1) }}</text>
              @endfor
              @foreach($chartYears as $index => $yearRow)
                @php
                  $wetValue = (float) ($yearRow['wet_yield_mt'] ?? 0);
                  $dryValue = (float) ($yearRow['dry_yield_mt'] ?? 0);
                  $totalValue = (float) ($yearRow['total_yield_mt'] ?? ($wetValue + $dryValue));
                  $barHeight = $plotHeight * ($totalValue / $maxYield);
                  $x = $plotLeft + $band * $index + ($band - $barWidth) / 2;
                  $y = $plotBottom - $barHeight;
                  $wetHeight = $totalValue > 0 ? $barHeight * ($wetValue / $totalValue) : 0;
                  $dryHeight = $barHeight - $wetHeight;
                @endphp
                @if($wetHeight > 0)
                  <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $wetHeight }}" class="rp-bar-wet"></rect>
                @endif
                @if($dryHeight > 0)
                  <rect x="{{ $x }}" y="{{ $y + $wetHeight }}" width="{{ $barWidth }}" height="{{ $dryHeight }}" class="rp-bar-dry"></rect>
                @endif
                <text x="{{ $x + $barWidth / 2 }}" y="{{ $plotBottom + 15 }}" text-anchor="middle" class="rp-axis">{{ $yearRow['year'] }}</text>
              @endforeach
            </svg>
            <div class="rp-legend">
              <span><i class="rp-swatch wet"></i>{{ $tl ? 'Basang Ani' : 'Wet season' }}</span>
              <span><i class="rp-swatch dry"></i>{{ $tl ? 'Tuyong Ani' : 'Dry season' }}</span>
            </div>
          @else
            <p class="rp-chart-empty">{{ $tl ? 'Walang naitalang datos sa saklaw.' : 'No recorded data in this scope.' }}</p>
          @endif
        </section>

        <section class="rp-chart-card">
          <h3>{{ $tl ? 'Bahagi ayon sa Panahon' : 'Seasonal Share' }}</h3>
          <svg class="rp-donut" viewBox="0 0 140 140" role="img" aria-label="{{ $tl ? 'Bahagi ng basa at tuyong ani' : 'Wet and dry season production share' }}">
            <circle cx="70" cy="70" r="{{ $donutRadius }}" class="rp-donut-track"></circle>
            <circle cx="70" cy="70" r="{{ $donutRadius }}" class="rp-donut-wet"
                    stroke-dasharray="{{ $wetLength }} {{ $donutLength - $wetLength }}"
                    transform="rotate(-90 70 70)"></circle>
            <circle cx="70" cy="70" r="{{ $donutRadius }}" class="rp-donut-dry"
                    stroke-dasharray="{{ $dryLength }} {{ $wetLength }}"
                    stroke-dashoffset="-{{ $wetLength }}"
                    transform="rotate(-90 70 70)"></circle>
            <text x="70" y="66" text-anchor="middle" class="rp-donut-value">{{ number_format($wetShare, 1) }}%</text>
            <text x="70" y="84" text-anchor="middle" class="rp-donut-caption">{{ $tl ? 'basang ani' : 'wet season' }}</text>
          </svg>
          <div class="rp-legend rp-legend-center">
            <span><i class="rp-swatch dry"></i>{{ $tl ? 'Tuyo' : 'Dry' }} {{ number_format($dryShare, 1) }}%</span>
          </div>
        </section>

        <section class="rp-chart-card rp-chart-wide">
          <h3>{{ $tl ? 'Bahagi ayon sa Pananim' : 'Crop Contribution' }}</h3>
          <p class="rp-chart-note">{{ $tl ? 'Anim na pananim na may pinakamataas na naitalang ani sa saklaw.' : 'Top six crops by recorded yield in this scope.' }}</p>
          @forelse($topCrops as $crop)
            @php $cropShare = max(0, min(100, (float) ($crop['share_pct'] ?? 0))); @endphp
            <div class="rp-hbar-row">
              <span class="rp-hbar-label">{{ $crop['crop_type'] }}</span>
              <span class="rp-hbar-track"><span class="rp-hbar-fill" style="width: {{ $cropShare }}%"></span></span>
              <span class="rp-hbar-value">{{ number_format((float) ($crop['total_yield_mt'] ?? 0), 1) }} MT · {{ $cropShare }}%</span>
            </div>
          @empty
            <p class="rp-chart-empty">{{ $tl ? 'Walang naitalang datos sa saklaw.' : 'No recorded data in this scope.' }}</p>
          @endforelse
        </section>
      </div>

      <div class="rp-attestation">
        <div>
          <span>{{ $tl ? 'Inihanda ni:' : 'Prepared by:' }}</span>
          <strong>{{ $preparedBy }}</strong>
        </div>
        <div class="rp-attest-noted">
          <span>{{ $tl ? 'Pinatutunayan ni:' : 'Noted by:' }}</span>
          <strong>&nbsp;</strong>
        </div>
      </div>

      <div class="rp-sheet-footer">
        <span>{{ $reportNumber }} · {{ $tl ? 'Patunay' : 'Attestation' }}</span>
        <span>{{ $tl ? 'Pahina' : 'Page' }} 3 / 3</span>
      </div>
    </section>
    </div>
  </div>

  <p class="rp-outside-note">
    {{ $tl
      ? 'Lahat ng bilang ay mula sa mga ulat ng ani sa saklaw na nakasaad sa itaas at na-lock noong inihanda ang ulat. Walang tinantya o panlabas na datos ang idinagdag.'
      : 'All figures are taken from the harvest records in the stated scope and were fixed when this report was prepared. No estimates or external data were added.' }}
  </p>
</article>
