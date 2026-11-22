<script>
(function () {
  // Pure helpers live outside boot() so the delegated listeners can use them
  // across Turbo visits without being re-registered.
  function str(value) {
    return value === null || value === undefined ? '' : String(value);
  }

  function asArray(value) {
    if (Array.isArray(value)) {
      return value.map(str).filter(function (item) { return item !== ''; });
    }

    return str(value).split(',').map(function (item) {
      return item.trim();
    }).filter(function (item) { return item !== ''; });
  }

  function closest(node, selector) {
    return node && node.closest ? node.closest(selector) : null;
  }

  function copyState(source) {
    return {
      q: source.q,
      farmer: source.farmer,
      month: source.month,
      year: source.year,
      crop: source.crop,
      years: source.years.slice(),
      crops: source.crops.slice(),
      season: source.season,
      filtersOpen: source.filtersOpen
    };
  }

  function toggleValue(list, value) {
    var index = list.indexOf(value);
    if (index === -1) list.push(value);
    else list.splice(index, 1);
  }

  function normalizeName(value) {
    return str(value).toLowerCase().replace(/\./g, '').replace(/\s+/g, ' ').trim();
  }

  function readJson(value) {
    try {
      return JSON.parse(value || '{}');
    } catch (error) {
      return {};
    }
  }

  function normalizeRow(row) {
    if (Array.isArray(row)) {
      return {
        year: String(row[0] || ''),
        month: String(row[1] || ''),
        season: String(row[2] || ''),
        crop: String(row[3] || ''),
        area: Number(row[4]) || 0,
        yield: Number(row[5]) || 0,
        farmer: String(row[6] || '')
      };
    }

    return {
      year: String(row.year || ''),
      month: String(row.month || ''),
      season: String(row.season || ''),
      crop: String(row.crop || ''),
      area: Number(row.area) || 0,
      yield: Number(row.yield) || 0,
      farmer: String(row.farmer || '')
    };
  }

  function normaliseState(raw) {
    raw = raw || {};
    var years = asArray(raw.years);
    var crops = asArray(raw.crops);
    var hasYears = Object.prototype.hasOwnProperty.call(raw, 'years');
    var hasCrops = Object.prototype.hasOwnProperty.call(raw, 'crops');

    if (!hasYears && raw.year) years = [str(raw.year)];
    if (!hasCrops && raw.crop) crops = [str(raw.crop)];

    return {
      q: str(raw.q),
      farmer: str(raw.farmer),
      month: str(raw.month),
      year: hasYears ? '' : str(raw.year),
      crop: hasCrops ? '' : str(raw.crop),
      years: years,
      crops: crops,
      season: str(raw.season || 'all') || 'all',
      filtersOpen: raw.filtersOpen === undefined ? true : !!raw.filtersOpen
    };
  }

  function format1(value) {
    return Number(value || 0).toLocaleString('en-US', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1
    });
  }

  function boot() {
    bindGenerateOverlay();

    var form = document.getElementById('ahr-scope-form');
    if (!form) {
      window.__aqHarvestFilterCtx = null;
      return;
    }

    var dataNode = document.getElementById('ahr-scope-data');
    var rows = [];
    try {
      rows = (JSON.parse(dataNode ? dataNode.textContent : '[]') || []).map(normalizeRow);
    } catch (error) {
      rows = [];
    }

    var allYears = toArray(document.querySelectorAll('[data-year-chip][data-year]')).map(function (node) {
      return node.dataset.year;
    });

    var state = normaliseState(readJson(form.dataset.filterState));
    var fields = document.getElementById('ahr-filter-fields');
    var visibility = document.getElementById('ahr-filters-visibility');
    var toggle = document.querySelector('[data-filter-toggle]');
    var summaryText = document.getElementById('ahr-scope-summary-text');
    var summary = document.getElementById('ahr-scope-summary');
    var scopeText = document.getElementById('ahr-scope-text');
    var subtitle = document.getElementById('ahr-scope-subtitle');
    var hiddenInputs = document.getElementById('ahr-filter-inputs');
    var generateButton = document.getElementById('ahr-generate-button');

    function matches(row, filter) {
      if (filter.q && row.crop.toLowerCase().indexOf(str(filter.q).toLowerCase()) === -1) return false;
      if (filter.crop && row.crop !== filter.crop) return false;
      if (filter.season && filter.season !== 'all' && filter.season !== 'both' && row.season !== filter.season) return false;
      if (filter.farmer && normalizeName(row.farmer) !== normalizeName(filter.farmer)) return false;
      if (filter.year && row.year !== str(filter.year)) return false;
      if (filter.month && row.month !== str(filter.month)) return false;
      if (filter.years.length && filter.years.indexOf(row.year) === -1) return false;
      if (filter.crops.length && filter.crops.indexOf(row.crop) === -1) return false;

      return true;
    }

    function totalsFor(filter) {
      var totals = { count: 0, area: 0, yield: 0 };

      rows.forEach(function (row) {
        if (!matches(row, filter)) return;
        totals.count += 1;
        totals.area += row.area;
        totals.yield += row.yield;
      });

      totals.area = Math.round(totals.area * 100) / 100;
      totals.yield = Math.round(totals.yield * 100) / 100;

      return totals;
    }

    function scopeLabel(filter) {
      var labels = [
        ['years', 'Years'], ['q', 'Search'], ['crop', 'Crop'], ['crops', 'Crops'],
        ['season', 'Season'], ['farmer', 'Farmer'], ['year', 'Year'], ['month', 'Month']
      ];
      var parts = [];

      labels.forEach(function (pair) {
        var key = pair[0];
        var value = filter[key];

        if (Array.isArray(value)) {
          var values = value.map(str).filter(function (item) { return item !== ''; });
          if (values.length) parts.push(pair[1] + ': ' + values.join(', '));
          return;
        }

        value = str(value);
        if (value === '' || value === 'all' || value === 'both') return;
        parts.push(pair[1] + ': ' + (key === 'season' ? value.charAt(0).toUpperCase() + value.slice(1) : value));
      });

      return parts.length ? parts.join(', ') : 'All recorded harvest reports';
    }

    function renderHiddenInputs(filter) {
      if (!hiddenInputs) return;

      hiddenInputs.textContent = '';

      function addHidden(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        hiddenInputs.appendChild(input);
      }

      ['years', 'crops'].forEach(function (key) {
        filter[key].forEach(function (value) {
          addHidden('filters[' + key + '][]', value);
        });
      });

      ['q', 'farmer', 'season', 'year', 'crop', 'month'].forEach(function (key) {
        var value = filter[key];
        if (value !== '' && value !== 'all' && value !== 'both') {
          addHidden('filters[' + key + ']', value);
        }
      });
    }

    function syncControls(filter) {
      document.querySelectorAll('[data-year-chip][data-year]').forEach(function (chip) {
        var active = filter.years.indexOf(chip.dataset.year) !== -1;
        chip.classList.toggle('active', active);
        chip.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      document.querySelectorAll('[data-crop-chip][data-crop]').forEach(function (chip) {
        var active = filter.crops.indexOf(chip.dataset.crop) !== -1;
        chip.classList.toggle('active', active);
        chip.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      document.querySelectorAll('[data-filter-crops-clear]').forEach(function (button) {
        var active = filter.crops.length === 0;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      document.querySelectorAll('[data-filter-crops-reset]').forEach(function (button) {
        button.hidden = filter.crops.length === 0;
      });

      document.querySelectorAll('[data-filter-season]').forEach(function (button) {
        var active = button.dataset.filterSeason === filter.season;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      if (fields) fields.hidden = !filter.filtersOpen;
      if (visibility) visibility.value = filter.filtersOpen ? 'open' : 'closed';

      if (toggle) {
        toggle.setAttribute('aria-expanded', filter.filtersOpen ? 'true' : 'false');
        toggle.classList.toggle('ahr-collapsed', !filter.filtersOpen);
        toggle.innerHTML = '<svg class="ahr-icon sm ahr-toggle-chevron" aria-hidden="true">'
          + '<use href="#ahr-i-chevron-down"></use></svg><span>'
          + (filter.filtersOpen ? 'Hide filters' : 'Adjust filters') + '</span>';
      }

      if (subtitle) {
        subtitle.textContent = filter.filtersOpen
          ? 'Choose the harvests to include in this report.'
          : scopeLabel(filter);
      }
    }

    function updateUrl(filter) {
      try {
        var url = new URL(window.location.href);

        Array.from(url.searchParams.keys()).forEach(function (key) {
          if (/^(years|crops)(\[.*\])?$/.test(key)
            || ['tab', 'filters', 'season', 'q', 'farmer', 'year', 'crop', 'month'].indexOf(key) !== -1) {
            url.searchParams.delete(key);
          }
        });

        url.searchParams.set('tab', 'generate');
        url.searchParams.set('filters', filter.filtersOpen ? 'open' : 'closed');

        ['q', 'farmer', 'month'].forEach(function (key) {
          if (filter[key] !== '') url.searchParams.set(key, filter[key]);
        });

        if (filter.season !== 'all' && filter.season !== 'both') {
          url.searchParams.set('season', filter.season);
        }

        filter.years.forEach(function (year) { url.searchParams.append('years[]', year); });
        filter.crops.forEach(function (crop) { url.searchParams.append('crops[]', crop); });

        history.replaceState(null, '', url.toString());
      } catch (error) {
        // History changes can be refused in restricted contexts; the report
        // state is already applied to the form.
      }
    }

    function valuesFor(params, base) {
      var values = [];

      params.forEach(function (value, key) {
        if (key === base || key === base + '[]' || new RegExp('^' + base + '\\[\\d*\\]$').test(key)) {
          str(value).split(',').forEach(function (part) {
            part = part.trim();
            if (part !== '') values.push(part);
          });
        }
      });

      return values;
    }

    function stateFromUrl() {
      var params = new URLSearchParams(window.location.search);
      var raw = {
        years: valuesFor(params, 'years'),
        crops: valuesFor(params, 'crops'),
        season: params.get('season') || 'all',
        q: params.get('q') || '',
        farmer: params.get('farmer') || '',
        month: params.get('month') || '',
        filtersOpen: params.get('filters') !== 'closed'
      };

      if (!raw.years.length && params.get('year')) raw.years = [params.get('year')];
      if (!raw.years.length && !params.get('year')) raw.years = allYears.slice();
      if (!raw.crops.length && params.get('crop')) raw.crops = [params.get('crop')];

      return normaliseState(raw);
    }

    function paint(filter, rewriteUrl) {
      state = filter;
      syncControls(state);
      renderHiddenInputs(state);

      var totals = totalsFor(state);
      if (summaryText) {
        summaryText.textContent = totals.count + ' records · ' + format1(totals.area)
          + ' ha · ' + format1(totals.yield) + ' MT';
      }
      if (scopeText) scopeText.textContent = scopeLabel(state);
      if (generateButton) generateButton.disabled = totals.count === 0;

      if (ctx) ctx.state = state;

      if (rewriteUrl) {
        updateUrl(state);

        // Immediate, visible acknowledgement of the click.
        if (summary) {
          summary.classList.remove('ahr-filter-updated');
          void summary.offsetWidth;
          summary.classList.add('ahr-filter-updated');
        }
      }
    }

    var ctx = {
      paint: paint,
      state: state,
      allYears: allYears,
      stateFromUrl: stateFromUrl
    };
    window.__aqHarvestFilterCtx = ctx;

    paint(state, false);
  }

  function toArray(list) {
    return Array.prototype.slice.call(list || []);
  }

  function bindGenerateOverlay() {
    if (window.__aqGenerateOverlayBound) return;
    window.__aqGenerateOverlayBound = true;

    document.addEventListener('submit', function (event) {
      var generateForm = event.target;
      if (!generateForm.matches || !generateForm.matches('#ahr-generate-form')) return;

      var overlay = document.getElementById('ahr-generating-overlay');
      if (overlay) overlay.hidden = false;

      var button = generateForm.querySelector('button[type="submit"]');
      if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
      }
    });

    window.addEventListener('pageshow', function () {
      var overlay = document.getElementById('ahr-generating-overlay');
      if (overlay) overlay.hidden = true;
    });
  }

  function bindDelegates() {
    if (window.__aqHarvestFilterDelegatesBound) return;
    window.__aqHarvestFilterDelegatesBound = true;

    // Listeners live on document, so one binding survives every Turbo swap.
    // The live context is recreated by boot() for each new page.
    document.addEventListener('click', function (event) {
      var ctx = window.__aqHarvestFilterCtx;
      if (!ctx) return;

      var target = event.target;
      var node;

      node = closest(target, '[data-filter-toggle]');
      if (node) {
        event.preventDefault();
        var toggled = copyState(ctx.state);
        toggled.filtersOpen = !toggled.filtersOpen;
        ctx.paint(toggled, true);
        return;
      }

      node = closest(target, '[data-filter-reset]');
      if (node) {
        event.preventDefault();
        var reset = copyState(ctx.state);
        reset.years = ctx.allYears.slice();
        reset.crops = [];
        reset.season = 'all';
        ctx.paint(reset, true);
        return;
      }

      node = closest(target, '[data-filter-crops-clear], [data-filter-crops-reset]');
      if (node) {
        event.preventDefault();
        var cleared = copyState(ctx.state);
        cleared.crops = [];
        ctx.paint(cleared, true);
        return;
      }

      node = closest(target, '[data-year-chip][data-year]');
      if (node) {
        event.preventDefault();
        var years = copyState(ctx.state);
        toggleValue(years.years, node.dataset.year);
        ctx.paint(years, true);
        return;
      }

      node = closest(target, '[data-crop-chip][data-crop]');
      if (node) {
        event.preventDefault();
        var crops = copyState(ctx.state);
        toggleValue(crops.crops, node.dataset.crop);
        ctx.paint(crops, true);
        return;
      }

      node = closest(target, '[data-filter-season]');
      if (node) {
        event.preventDefault();
        var season = copyState(ctx.state);
        season.season = node.dataset.filterSeason || 'all';
        ctx.paint(season, true);
      }
    });

    window.addEventListener('popstate', function () {
      var ctx = window.__aqHarvestFilterCtx;
      if (ctx) ctx.paint(ctx.stateFromUrl(), false);
    });
  }

  // The form is already in the DOM when this partial renders. Bind once, then
  // rebuild the live context on every evaluation so Turbo visits work too.
  bindDelegates();
  if (document.readyState === 'loading' && !document.getElementById('ahr-scope-form')) {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
</script>
