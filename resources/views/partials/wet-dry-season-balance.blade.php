{{--
  Wet vs. Dry Season.

  This used to be one of the full-view cards on Harvest Performance. It is an
  overview, so it now sits on the admin dashboard directly below the harvest
  report graph. It has its own year filter: "Overall performance" plus each
  year in the digest, with no particular year selected by default.
--}}
<style>
  /* The panel that hosts this card normally clips its children to keep the
     rounded corners tidy. The year menu must escape that box when opened. */
  .wdb-panel { overflow: visible; }

  .wdb-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef1f4;
  }
  .wdb-title { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .wdb-sub { font-size: 12.5px; color: #879a8c; margin: 3px 0 0; }

  .wdb-filter { position: relative; flex-shrink: 0; }
  .wdb-filter-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font: inherit; font-size: 12px; font-weight: 900; letter-spacing: 0.04em; color: #14532d;
    padding: 6px 11px; border-radius: 9px;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.14);
    cursor: pointer; transition: all 0.18s ease;
    text-decoration: none;
  }
  .wdb-filter-btn:hover { background: #f8faf9; border-color: rgba(20,61,35,0.28); }
  .wdb-filter-btn .wdb-funnel { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .wdb-filter-btn .wdb-chev { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.2s ease; }
  .wdb-filter.open .wdb-filter-btn .wdb-chev { transform: rotate(180deg); }
  .wdb-menu {
    position: absolute; top: calc(100% + 6px); right: 0; z-index: 60;
    min-width: 190px; padding: 6px; margin: 0; list-style: none;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.12);
    border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,0.12);
    display: none;
    /* Keep the menu shorter than the card; long year lists scroll inside it. */
    max-height: min(220px, 42vh);
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: rgba(20,61,35,0.25) transparent;
  }
  .wdb-filter.open .wdb-menu { display: block; }
  .wdb-option {
    display: block; width: 100%; text-align: left; font: inherit;
    font-size: 12.5px; font-weight: 600; color: #40632d; letter-spacing: 0.02em;
    background: transparent; border: none; cursor: pointer;
    padding: 8px 12px; border-radius: 8px; transition: background 0.15s ease;
    white-space: nowrap;
  }
  .wdb-option:hover { background: #f0fdf4; }
  .wdb-option.is-active { background: #edf1e9; color: #648268; font-weight: 800; }

  .wdb-body { padding: 14px 20px 18px; }
  .wdb-share-row { display: flex; align-items: baseline; gap: 18px; padding-bottom: 8px; }
  .wdb-share { font-size: 18px; font-weight: 800; font-variant-numeric: tabular-nums; }
  .wdb-share.wet { color: #55799B; }
  .wdb-share.dry { color: #8F6B4B; }
  .wdb-track { height: 16px; background: #f1f5f9; border-radius: 8px; overflow: hidden; margin: 6px 0 14px; }
  .wdb-fill { height: 100%; transition: width .3s ease; }
  .wdb-fill.wet { background: #A9C9DF; border-radius: 8px 0 0 8px; }
  .wdb-fill.dry { background: #E3C9AF; border-radius: 0 8px 8px 0; }
  .wdb-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .wdb-col { padding: 12px; border-radius: 10px; display: flex; flex-direction: column; }
  .wdb-col.wet { background: #F2F7FB; border: 1px solid #E1ECF4; }
  .wdb-col.dry { background: #FBF7F2; border: 1px solid #F0E5D8; }
  .wdb-name { font-weight: 800; font-size: 12.5px; text-transform: uppercase; letter-spacing: .04em; }
  .wdb-col.wet .wdb-name, .wdb-col.wet .wdb-value { color: #55799B; }
  .wdb-col.dry .wdb-name, .wdb-col.dry .wdb-value { color: #8F6B4B; }
  .wdb-meta { display: block; color: #64748b; font-size: 12px; margin-top: 4px; }
  .wdb-value { display: block; font-weight: 800; font-size: 16px; margin-top: 4px; font-variant-numeric: tabular-nums; }
  @media (max-width: 620px) {
    .wdb-head { flex-direction: column; }
    .wdb-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="wdb-head">
  <div>
    <p class="wdb-title">Wet vs. Dry Season</p>
    <p class="wdb-sub">Share of total harvest output and unit efficiency across seasons in scope</p>
  </div>
  <div class="wdb-filter" id="wdbYearFilter">
    <button type="button" class="wdb-filter-btn" id="wdbYearFilterBtn" aria-haspopup="true" aria-expanded="false" aria-label="Filter balance by year">
      <svg class="wdb-funnel" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      <span id="wdbYearFilterLabel">Overall performance</span>
      <svg class="wdb-chev" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <ul class="wdb-menu" role="menu" aria-label="Balance year">
      <li><button type="button" class="wdb-option is-active" data-year="overall" data-label="Overall performance">Overall performance</button></li>
      @foreach($seasonBalance['options'] as $year)
        <li><button type="button" class="wdb-option" data-year="{{ $year }}" data-label="{{ $year }}">{{ $year }}</button></li>
      @endforeach
    </ul>
  </div>
</div>

<div class="wdb-body">
  <div class="wdb-share-row">
    <span class="wdb-share wet" id="wdbWetShare">Wet {{ $seasonBalance['overall']['wet']['share'] }}%</span>
    <span class="wdb-share dry" id="wdbDryShare">Dry {{ $seasonBalance['overall']['dry']['share'] }}%</span>
  </div>
  <div class="wdb-track">
    <div class="wdb-fill wet" id="wdbWetFill" style="width:{{ $seasonBalance['overall']['wet']['share'] }}%"></div>
    <div class="wdb-fill dry" id="wdbDryFill" style="width:{{ $seasonBalance['overall']['dry']['share'] }}%"></div>
  </div>
  <div class="wdb-grid">
    <div class="wdb-col wet">
      <span class="wdb-name">Wet Season (Aug–Nov)</span>
      <span class="wdb-meta" id="wdbWetMeta">{{ $seasonBalance['overall']['wet']['reports'] }} harvests · {{ number_format($seasonBalance['overall']['wet']['area'], 1) }} ha</span>
      <span class="wdb-value" id="wdbWetYield">{{ number_format($seasonBalance['overall']['wet']['yield'], 1) }} MT</span>
      <span class="wdb-meta" id="wdbWetEfficiency">{{ $seasonBalance['overall']['wet']['yield_per_ha'] !== null ? number_format($seasonBalance['overall']['wet']['yield_per_ha'], 2) . ' MT/ha' : '—' }} · {{ $seasonBalance['overall']['wet']['cost_per_mt'] !== null ? '₱' . number_format($seasonBalance['overall']['wet']['cost_per_mt'], 0) . '/MT' : '—' }}</span>
    </div>
    <div class="wdb-col dry">
      <span class="wdb-name">Dry Season (Feb–May)</span>
      <span class="wdb-meta" id="wdbDryMeta">{{ $seasonBalance['overall']['dry']['reports'] }} harvests · {{ number_format($seasonBalance['overall']['dry']['area'], 1) }} ha</span>
      <span class="wdb-value" id="wdbDryYield">{{ number_format($seasonBalance['overall']['dry']['yield'], 1) }} MT</span>
      <span class="wdb-meta" id="wdbDryEfficiency">{{ $seasonBalance['overall']['dry']['yield_per_ha'] !== null ? number_format($seasonBalance['overall']['dry']['yield_per_ha'], 2) . ' MT/ha' : '—' }} · {{ $seasonBalance['overall']['dry']['cost_per_mt'] !== null ? '₱' . number_format($seasonBalance['overall']['dry']['cost_per_mt'], 0) . '/MT' : '—' }}</span>
    </div>
  </div>
</div>

<script type="application/json" id="wdbBalanceData">{!! json_encode($seasonBalance, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script>
  (function () {
    if (!window.__aqWdbBound) {
      window.__aqWdbBound = true;
      window.addEventListener('turbo:load', initWetDryBalance);
    }

    function initWetDryBalance() {
      var dropdown = document.getElementById('wdbYearFilter');
      if (!dropdown || dropdown.dataset.ready === '1') return;
      dropdown.dataset.ready = '1';

      var dataEl = document.getElementById('wdbBalanceData');
      var balance = JSON.parse((dataEl && dataEl.textContent) || '{}');
      var btn = document.getElementById('wdbYearFilterBtn');
      var label = document.getElementById('wdbYearFilterLabel');

      function fixed(value, places) {
        var number = Number(value || 0);
        return number.toLocaleString('en-US', {
          minimumFractionDigits: places,
          maximumFractionDigits: places
        });
      }

      function efficiency(season) {
        var rate = season.yield_per_ha === null ? '—' : fixed(season.yield_per_ha, 2) + ' MT/ha';
        var cost = season.cost_per_mt === null ? '—' : '₱' + fixed(season.cost_per_mt, 0) + '/MT';
        return rate + ' · ' + cost;
      }

      function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
      }

      function render(yearKey) {
        var snapshot = yearKey === 'overall' ? balance.overall : (balance.years[yearKey] || balance.overall);
        var wet = snapshot.wet;
        var dry = snapshot.dry;

        setText('wdbWetShare', 'Wet ' + wet.share + '%');
        setText('wdbDryShare', 'Dry ' + dry.share + '%');
        setText('wdbWetMeta', wet.reports + ' harvests · ' + fixed(wet.area, 1) + ' ha');
        setText('wdbDryMeta', dry.reports + ' harvests · ' + fixed(dry.area, 1) + ' ha');
        setText('wdbWetYield', fixed(wet.yield, 1) + ' MT');
        setText('wdbDryYield', fixed(dry.yield, 1) + ' MT');
        setText('wdbWetEfficiency', efficiency(wet));
        setText('wdbDryEfficiency', efficiency(dry));

        var wetFill = document.getElementById('wdbWetFill');
        var dryFill = document.getElementById('wdbDryFill');
        if (wetFill) wetFill.style.width = wet.share + '%';
        if (dryFill) dryFill.style.width = dry.share + '%';
      }

      function closeFilter() {
        dropdown.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }

      if (btn) {
        btn.addEventListener('click', function (event) {
          event.stopPropagation();
          var open = dropdown.classList.toggle('open');
          btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      }

      dropdown.querySelectorAll('.wdb-option').forEach(function (option) {
        option.addEventListener('click', function () {
          dropdown.querySelectorAll('.wdb-option').forEach(function (other) {
            other.classList.toggle('is-active', other === option);
          });
          if (label) label.textContent = option.dataset.label;
          if (btn) btn.setAttribute('aria-label', 'Filter balance by year: ' + option.dataset.label);
          render(option.dataset.year);
          closeFilter();
        });
      });

      if (!window.__aqWdbDelegate) {
        window.__aqWdbDelegate = true;
        function closeCurrentFilter() {
          var host = document.getElementById('wdbYearFilter');
          if (host) host.classList.remove('open');
          var currentBtn = document.getElementById('wdbYearFilterBtn');
          if (currentBtn) currentBtn.setAttribute('aria-expanded', 'false');
        }
        document.addEventListener('click', function (event) {
          var host = document.getElementById('wdbYearFilter');
          if (host && !host.contains(event.target)) closeCurrentFilter();
        });
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') closeCurrentFilter();
        });
      }

      render('overall');
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initWetDryBalance, { once: true });
    } else {
      initWetDryBalance();
    }
  })();
</script>
