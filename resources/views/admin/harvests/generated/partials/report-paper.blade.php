@php
  $tl = $language === 'tl';
  $sections = $narratives[$language] ?? [];
  $totals = $context['totals'] ?? [];
  $coverage = $context['coverage'] ?? [];

  $pick = function (array $names) use ($sections) {
    foreach ($names as $name) {
      if (! empty($sections[$name])) return $sections[$name];
    }
    return [];
  };

  $readable = function (array $lines, int $limit = 4) {
    $items = [];
    foreach ($lines as $line) {
      foreach (preg_split('/(?<=[.;])\s+/u', trim($line)) ?: [trim($line)] as $piece) {
        $piece = trim($piece);
        if ($piece !== '') $items[] = $piece;
      }
    }
    return array_slice($items, 0, $limit);
  };

  $labelPart = function (string $line): array {
    if (preg_match('/^([^:]{2,38}):\s*(.+)$/u', $line, $matches)) {
      return [trim($matches[1]), trim($matches[2])];
    }
    return [null, $line];
  };

  $summary = $readable($pick([$tl ? 'Buod ng Tagapagpaganap' : 'Executive Summary']), 2);
  $highlights = $readable($pick([$tl ? 'Mga Pangunahing Resulta' : 'Performance Highlights']), 4);
  $cropNotes = $readable($pick([
    $tl ? 'Obserbasyon sa Pananim at Panahon' : 'Crop and Season Observations',
    $tl ? 'Obserbasyon sa Pananim' : 'Crop Observations',
  ]), 4);
  $quality = $readable($pick([$tl ? 'Kalidad ng Data at Beripikasyon' : 'Data Quality and Verification']), 2);
  $actions = $readable($pick([$tl ? 'Mga Inirerekomendang Hakbang' : 'Recommended Next Steps']), 4);

  $logoPath = public_path('images/logo.png');
  $logoData = is_file($logoPath)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
    : null;
@endphp

<article class="report-paper">
  <header class="rp-header">
    @if($logoData)
      <img class="rp-logo" src="{{ $logoData }}" alt="Aquilala Irrigators Association Inc. logo">
    @endif
    <div class="rp-org">
      <strong>Aquilala Irrigators Association Inc.</strong>
      <span>{{ $tl ? 'Ulat sa Pagganap ng Ani' : 'Harvest Performance Report' }}</span>
    </div>
    <div class="rp-date">{{ $report->created_at->format('F d, Y') }}</div>
  </header>

  <h1 class="rp-title">{{ $report->title }}</h1>

  <table class="rp-facts">
    <tbody>
      <tr><th>{{ $tl ? 'Saklaw' : 'Scope' }}</th><td>{{ $scope }}</td></tr>
      <tr><th>{{ $tl ? 'Inihanda' : 'Prepared' }}</th><td>{{ $report->created_at->format('F d, Y \a\t h:i A') }}</td></tr>
      <tr><th>{{ $tl ? 'Wika' : 'Language' }}</th><td>{{ $tl ? 'Tagalog' : 'English' }}</td></tr>
    </tbody>
  </table>

  <section class="rp-figures" aria-label="{{ $tl ? 'Mga pangunahing bilang' : 'Key figures' }}">
    <div class="rp-figure"><span>{{ $tl ? 'Mga Ulat' : 'Reports' }}</span><strong>{{ (int) ($totals['reports'] ?? ($coverage['reports'] ?? 0)) }}</strong><small>{{ $tl ? 'mga naitalang ulat' : 'harvest reports recorded' }}</small></div>
    <div class="rp-figure"><span>{{ $tl ? 'Mga Magsasaka' : 'Farmers' }}</span><strong>{{ (int) ($totals['farmers'] ?? ($coverage['farmers'] ?? 0)) }}</strong><small>{{ $tl ? 'kalahok sa saklaw' : 'included in scope' }}</small></div>
    <div class="rp-figure"><span>{{ $tl ? 'Kabuuang Ani' : 'Total yield' }}</span><strong>{{ number_format((float) ($totals['yield_tons'] ?? 0), 2) }} MT</strong><small>{{ $tl ? 'naitalang produksyon' : 'recorded production' }}</small></div>
    <div class="rp-figure"><span>{{ $tl ? 'Lugar' : 'Area' }}</span><strong>{{ number_format((float) ($totals['area_ha'] ?? 0), 2) }} ha</strong><small>{{ $tl ? 'pinagtaniman' : 'cultivated area' }}</small></div>
    <div class="rp-figure"><span>{{ $tl ? 'Ani bawat Ektarya' : 'Yield per hectare' }}</span><strong>{{ number_format((float) ($totals['yield_per_ha'] ?? 0), 3) }} MT/ha</strong><small>{{ $tl ? 'average' : 'average' }}</small></div>
    <div class="rp-figure"><span>{{ $tl ? 'Natalang Gastos' : 'Recorded expenses' }}</span><strong>₱{{ number_format((float) ($totals['expenses_php'] ?? 0), 2) }}</strong><small>{{ $tl ? 'input at pag-aani' : 'input and harvest costs' }}</small></div>
  </section>

  <section class="rp-block">
    <h2>{{ $tl ? 'Buod ng Tagapagpaganap' : 'Executive Summary' }}</h2>
    @foreach($summary as $line) <p>{{ $line }}</p> @endforeach
    @if($summary === [])
      <p>{{ $tl ? 'Buod ng mga naitalang resulta ng ani sa napiling saklaw.' : 'A brief summary of the harvest records included in this scope.' }}</p>
    @endif
  </section>

  @if($highlights !== [])
    <section class="rp-block">
      <h2>{{ $tl ? 'Mga Pangunahing Resulta' : 'Key Observations' }}</h2>
      <ul>
        @foreach($highlights as $line)
          @php [$label, $text] = $labelPart($line); @endphp
          <li>@if($label !== null)<strong>{{ $label }}:</strong> @endif{{ $text }}</li>
        @endforeach
      </ul>
    </section>
  @endif

  @if($cropNotes !== [])
    <section class="rp-block">
      <h2>{{ $tl ? 'Mga Tala sa Pananim at Panahon' : 'Crop and Season Notes' }}</h2>
      <ul>
        @foreach($cropNotes as $line)
          @php [$label, $text] = $labelPart($line); @endphp
          <li>@if($label !== null)<strong>{{ $label }}:</strong> @endif{{ $text }}</li>
        @endforeach
      </ul>
    </section>
  @endif

  @if($quality !== [])
    <section class="rp-block">
      <h2>{{ $tl ? 'Mga Paalala sa Beripikasyon' : 'Verification Notes' }}</h2>
      <ul>
        @foreach($quality as $line)
          @php [$label, $text] = $labelPart($line); @endphp
          <li>@if($label !== null)<strong>{{ $label }}:</strong> @endif{{ $text }}</li>
        @endforeach
      </ul>
    </section>
  @endif

  @if($actions !== [])
    <section class="rp-block">
      <h2>{{ $tl ? 'Mga Inirerekomendang Hakbang' : 'Recommended Actions' }}</h2>
      <ol>
        @foreach($actions as $line)
          @php [$label, $text] = $labelPart($line); @endphp
          <li>@if($label !== null)<strong>{{ $label }}:</strong> @endif{{ $text }}</li>
        @endforeach
      </ol>
    </section>
  @endif

  <hr class="rp-rule">
  <p class="rp-foot">
    {{ $tl
      ? 'Lahat ng bilang ay mula sa mga ulat ng ani sa saklaw na nakasaad sa itaas at na-lock noong inihanda ang ulat. Walang tinantya o panlabas na datos ang idinagdag.'
      : 'All figures are taken from the harvest records in the stated scope and were fixed when this report was prepared. No estimates or external data were added.' }}
  </p>
</article>
