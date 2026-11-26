@php
  $isSaved = isset($report);
  $language = $language ?? 'en';
  $narratives = $narratives ?? [];
  $options = $options ?? [];
  $filters = $filters ?? [];

  // The repeat KPI cards were removed: their figures already live inside the
  // report sheets, so the preview now goes straight to the paper itself.
  $records = (int) ($visual['total_records'] ?? 0);
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
    {{-- Saved report - the same paper layout the export uses. --}}
    @if($isSaved)
      @php
        $downloadBaseName = $downloadBaseName ?? $report->downloadBaseName();
        $downloadFilename = $downloadBaseName . '-' . $language . '.html';
      @endphp
      <div class="ahr-report-paper-host"
           data-report-id="{{ $report->id }}"
           data-language="{{ $language }}"
           data-language-url="{{ route('admin.harvests.reports.generated.language', $report) }}"
           data-insight-url="{{ route('admin.harvests.reports.generated.insight', $report) }}"
           data-download-base="{{ route('admin.harvests.reports.generated.download', $report) }}"
           data-download-base-name="{{ $downloadBaseName }}">
        <div class="ahr-report-toolbar">
          <div class="ahr-actions">
            <button type="button" class="ahr-btn" id="ahr-overview-toggle" aria-pressed="false">
              <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-layers"></use></svg>
              <span>All pages</span>
            </button>
            <a class="ahr-btn primary" id="ahr-download-report"
               href="{{ route('admin.harvests.reports.generated.download', [$report, 'lang' => $language]) }}"
               download="{{ $downloadFilename }}">
              <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-download"></use></svg>
              Download report
            </a>
          </div>
          <div class="ahr-lang-switch" role="group" aria-label="Report language">
            <button type="button" class="ahr-lang-btn" data-lang-btn="en">English</button>
            <button type="button" class="ahr-lang-btn" data-lang-btn="tl">Tagalog</button>
          </div>
        </div>

        {{-- AI insight only: never part of the report paper. --}}
        <button type="button" class="ahr-ai-fab" id="ahr-ai-insight-open" aria-expanded="false" aria-controls="ahr-ai-insight-panel">
          <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-sparkles"></use></svg>
          AI Insight
        </button>

        <aside class="ahr-ai-panel" id="ahr-ai-insight-panel" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="ahr-ai-insight-title">
          <div class="ahr-ai-head">
            <div>
              <p class="ahr-ai-eyebrow">Plain-language guide</p>
              <h2 id="ahr-ai-insight-title">AI Insight</h2>
            </div>
            <button type="button" class="ahr-ai-close" id="ahr-ai-insight-close" aria-label="Close AI insight">&times;</button>
          </div>
          <div class="ahr-ai-body" id="ahr-ai-insight-body" aria-live="polite"></div>
        </aside>

        @foreach(['en', 'tl'] as $panelLanguage)
          <div class="ahr-lang-panel" data-lang-panel="{{ $panelLanguage }}" {{ $panelLanguage !== $language ? 'hidden' : '' }}>
            @include('admin.harvests.generated.partials.report-paper', [
              'language' => $panelLanguage,
              'visual' => $visual,
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
    var aiOpen = host.querySelector('#ahr-ai-insight-open');
    var aiPanel = host.querySelector('#ahr-ai-insight-panel');
    var aiClose = host.querySelector('#ahr-ai-insight-close');
    var aiBody = host.querySelector('#ahr-ai-insight-body');
    var aiBusy = false;

    // Match the crop-directory reference drawer: detect the real system header
    // height so the mobile sub-page starts exactly below it.
    function syncAiHeaderOffset() {
      if (!aiPanel) return;
      var candidates = [
        '.aq-global-search-host',
        '.mobile-header',
        'header:not(.ahr-ai-panel)',
        '.topbar',
        '[data-topbar]'
      ];

      for (var i = 0; i < candidates.length; i++) {
        var el = document.querySelector(candidates[i]);
        if (!el) continue;

        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') continue;

        var rect = el.getBoundingClientRect();
        if (rect.height > 0 && rect.top <= 10) {
          document.documentElement.style.setProperty('--ahr-header-offset', rect.height + 'px');
          return;
        }
      }
    }
    syncAiHeaderOffset();
    window.addEventListener('resize', syncAiHeaderOffset);

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

      // An open explanation follows the paper's active language.
      if (aiPanel && aiPanel.classList.contains('open')) requestInsight(language);

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

    // One-click paper overview for screenshots and quick review: the three A4
    // sheets are shown together as thumbnails instead of one long scroll.
    var overviewToggle = host.querySelector('#ahr-overview-toggle');
    var overviewLabel = overviewToggle ? overviewToggle.querySelector('span') : null;
    if (overviewToggle) {
      overviewToggle.addEventListener('click', function () {
        var on = host.classList.toggle('overview');
        overviewToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
        if (overviewLabel) overviewLabel.textContent = on ? 'Single page' : 'All pages';
      });
    }

    function csrfToken() {
      var token = document.querySelector('meta[name="csrf-token"]');
      return token ? token.getAttribute('content') : '';
    }

    function renderInsight(data) {
      aiBody.innerHTML = '';

      var text = document.createElement('p');
      text.className = 'ahr-ai-text';
      text.textContent = data.text || '';
      aiBody.appendChild(text);

      var meta = document.createElement('div');
      meta.className = 'ahr-ai-meta';

      var source = document.createElement('span');
      source.className = 'ahr-ai-pill';
      source.textContent = data.source_label || 'Explanation';
      meta.appendChild(source);

      if (data.model) {
        var model = document.createElement('span');
        model.className = 'ahr-ai-pill';
        model.textContent = data.model;
        meta.appendChild(model);
      }

      aiBody.appendChild(meta);

      var note = document.createElement('p');
      note.className = 'ahr-ai-note';
      note.textContent = data.source === 'ai'
        ? 'This guide uses only the figures saved in this report. The report paper remains the official record.'
        : 'This guide is prepared from the saved figures. The report paper remains the official record.';
      aiBody.appendChild(note);
    }

    function requestInsight(lang) {
      if (!aiOpen || aiBusy) return;
      aiBusy = true;
      aiBody.innerHTML = '<div class="ahr-ai-loading"><span class="ahr-generating-spinner"></span><span>Preparing explanation…</span></div>';

      fetch(host.getAttribute('data-insight-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken()
        },
        body: JSON.stringify({ language: lang })
      })
        .then(function (response) {
          if (!response.ok) throw new Error('Insight unavailable');
          return response.json();
        })
        .then(function (data) {
          renderInsight(data);
        })
        .catch(function () {
          aiBody.innerHTML = '<p class="ahr-ai-error">The explanation could not be loaded right now. The saved report and its official figures remain available.</p>';
        })
        .finally(function () { aiBusy = false; });
    }

    function openInsight() {
      if (!aiPanel) return;
      syncAiHeaderOffset();
      aiPanel.classList.add('open');
      aiPanel.setAttribute('aria-hidden', 'false');
      aiOpen.setAttribute('aria-expanded', 'true');
      if (window.innerWidth <= 768) document.body.style.overflow = 'hidden';
      aiClose.focus();
      requestInsight(language);
    }

    function closeInsight() {
      if (!aiPanel) return;
      aiPanel.classList.remove('open');
      aiPanel.setAttribute('aria-hidden', 'true');
      aiOpen.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      aiOpen.focus();
    }

    if (aiOpen) {
      aiOpen.addEventListener('click', openInsight);
      aiClose.addEventListener('click', closeInsight);
    }

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
      if (event.key !== 'Escape') return;
      if (aiPanel && aiPanel.classList.contains('open')) closeInsight();
      else if (confirmOverlay && !confirmOverlay.hidden) closeConfirm();
    });
  })();
</script>
