<script>
(function () {
  if (window.__aqHarvestFilterBound) return;
  window.__aqHarvestFilterBound = true;

  var busy = false;
  var status = document.getElementById('ahr-filter-status');
  var statusMarkup = status ? status.innerHTML : '';
  var errorTimer = null;

  function setBusy(on) {
    busy = on;
    if (status) status.hidden = !on;
    document.querySelectorAll('#ahr-scope, #ahr-preview, #ahr-records').forEach(function (node) {
      node.setAttribute('aria-busy', on ? 'true' : 'false');
    });
  }

  function showFilterError() {
    if (!status) return;
    clearTimeout(errorTimer);
    status.innerHTML = 'Could not update the report. Please try again.';
    status.hidden = false;
    errorTimer = setTimeout(function () {
      status.innerHTML = statusMarkup;
      status.hidden = true;
    }, 3000);
  }

  function swap(html, id) {
    if (!html) return;
    var holder = document.createElement('div');
    holder.innerHTML = html.trim();
    var incoming = holder.firstElementChild;
    var current = document.getElementById(id);
    if (incoming && current) {
      // The fragment partials do not carry their wrapper id; keep it so
      // later delegated filter events still find this region.
      incoming.id = id;
      current.replaceWith(incoming);
    }
  }

  async function load(url) {
    if (busy) return;
    setBusy(true);
    var failed = false;

    try {
      var response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-AQ-Filter-Fragment': '1'
        }
      });

      if (!response.ok) throw new Error('Filter request failed');

      var data = await response.json();
      swap(data.scopeHtml, 'ahr-scope');
      swap(data.previewHtml, 'ahr-preview');
      swap(data.recordsHtml, 'ahr-records');

      var label = document.getElementById('ahr-scope-text');
      if (label && data.scopeText) label.textContent = data.scopeText;

      history.pushState(null, '', data.url || url);
    } catch (error) {
      // Never fall back to a full reload: filters must stay in-page.
      failed = true;
    } finally {
      setBusy(false);
      if (failed) showFilterError();
    }
  }

  function formUrl(form) {
    var url = new URL(form.action, window.location.href);
    url.search = '';

    new FormData(form).forEach(function (value, key) {
      if (typeof value === 'string') url.searchParams.append(key, value);
    });

    return url.toString();
  }

  document.addEventListener('click', function (event) {
    var link = event.target.closest ? event.target.closest('[data-filter-link]') : null;
    if (!link || event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    event.preventDefault();
    load(link.href);
  });

  document.addEventListener('change', function (event) {
    var target = event.target;

    if (target && target.id === 'ahr-kpi-year') {
      var url = new URL(window.location.href);
      url.searchParams.delete('year');
      url.searchParams.delete('years[]');
      if (target.value) url.searchParams.set('year', target.value);
      load(url.toString());
      return;
    }

    var input = target && target.closest
      ? target.closest('#ahr-scope input[name="years[]"], #ahr-scope input[name="crops[]"]')
      : null;
    if (!input || !input.form) return;

    load(formUrl(input.form));
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('#ahr-records form.ahr-search')) return;
    event.preventDefault();
    load(formUrl(form));
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('#ahr-generate-form')) return;

    var overlay = document.getElementById('ahr-generating-overlay');
    if (overlay) overlay.hidden = false;

    var button = form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    }
  });

  window.addEventListener('pageshow', function () {
    var overlay = document.getElementById('ahr-generating-overlay');
    if (overlay) overlay.hidden = true;
  });

  window.addEventListener('popstate', function () {
    load(window.location.href);
  });
})();
</script>
