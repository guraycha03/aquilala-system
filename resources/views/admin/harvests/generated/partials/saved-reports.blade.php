@php
  // Saved titles may be older; the model normalises them in one place.
  $cleanTitle = fn ($saved): string => $saved->displayTitle();
@endphp

@if($savedReports->isEmpty())
  <section class="ahr-card ahr-empty-state">
    <svg class="ahr-icon lg" aria-hidden="true"><use href="#ahr-i-bookmark"></use></svg>
    <h2>No Saved Harvest Reports Yet</h2>
    <p>
      Select the harvests to include, prepare a written report,
      and save it here for association meetings and seasonal audits.
    </p>
    <a class="ahr-btn primary" href="{{ route('admin.harvests.reports.generate', ['tab' => 'generate']) }}">
      Open Harvest Report
      <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-arrow"></use></svg>
    </a>
  </section>
@else
  <section class="ahr-card">
    <div class="ahr-card-head">
      <div>
        <h2 class="ahr-card-title">Saved Reports</h2>
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
          $title = $cleanTitle($saved);
        @endphp
        <div class="ahr-saved-row">
          <div style="min-width:0;max-width:760px">
            <h3 class="ahr-saved-title">{{ $title }}</h3>
            <div class="ahr-saved-meta">
              <span>Saved {{ $saved->created_at->format('M d, Y') }}</span>
              <span>·</span>
              <span>{{ number_format($yield, 1) }} MT across {{ number_format($area, 1) }} ha ({{ $count }} records)</span>
            </div>
          </div>
          <div class="ahr-actions">
            <a class="ahr-btn primary" href="{{ route('admin.harvests.reports.generated.show', $saved) }}">
              Open Report
              <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-arrow"></use></svg>
            </a>
            <a class="ahr-btn" href="{{ route('admin.harvests.reports.generated.download', [$saved, 'lang' => $saved->default_language]) }}">Download report</a>
            <form method="POST" action="{{ route('admin.harvests.reports.generated.destroy', $saved) }}" class="ahr-delete-form">
              @csrf
              @method('DELETE')
              <button type="button" class="ahr-btn danger" data-delete-trigger data-delete-title="{{ $title }}" aria-label="Delete {{ $title }}">
                <svg class="ahr-icon" aria-hidden="true"><use href="#ahr-i-trash"></use></svg>
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <div class="ahr-modal" id="ahr-delete-modal" hidden>
    <div class="ahr-modal-card" role="dialog" aria-modal="true" aria-labelledby="ahr-delete-modal-title">
      <h2 id="ahr-delete-modal-title">Delete saved report?</h2>
      <p>
        <strong id="ahr-delete-modal-name"></strong> will be removed from this list.
        This action cannot be undone.
      </p>
      <div class="ahr-modal-actions">
        <button type="button" class="ahr-btn" id="ahr-delete-cancel">Cancel</button>
        <button type="button" class="ahr-btn danger" id="ahr-delete-confirm">Delete report</button>
      </div>
    </div>
  </div>

  <div class="ahr-delete-loading" id="ahr-delete-loading" hidden aria-live="assertive" aria-busy="true">
    <div class="ahr-delete-loading-card" role="status">
      <span class="ahr-delete-spinner" aria-hidden="true"></span>
      <h2>Deleting report&hellip;</h2>
      <p><strong id="ahr-delete-loading-name"></strong> is being removed. Please wait.</p>
    </div>
  </div>

  <script>
  (function () {
    function close(ctx) {
      if (!ctx) return;
      ctx.pending = null;
      if (ctx.modal) ctx.modal.hidden = true;
      if (ctx.confirm) ctx.confirm.disabled = false;
    }

    function boot() {
      var modal = document.getElementById('ahr-delete-modal');
      if (!modal) return;

      // Rebuild this context on every Turbo visit: the old modal/form nodes
      // are gone, but the document-level listeners below are still alive.
      var ctx = {
        modal: modal,
        name: document.getElementById('ahr-delete-modal-name'),
        cancel: document.getElementById('ahr-delete-cancel'),
        confirm: document.getElementById('ahr-delete-confirm'),
        loading: document.getElementById('ahr-delete-loading'),
        loadingName: document.getElementById('ahr-delete-loading-name'),
        pending: null,
        lastTitle: ''
      };
      window.__aqReportDeleteCtx = ctx;

      if (ctx.cancel) ctx.cancel.addEventListener('click', function () { close(ctx); });
      if (ctx.confirm) {
        ctx.confirm.addEventListener('click', function () {
          var form = ctx.pending;
          if (!form) return;

          close(ctx);
          if (ctx.loadingName) ctx.loadingName.textContent = ctx.lastTitle || 'Saved report';
          if (ctx.loading) ctx.loading.hidden = false;
          if (ctx.confirm) ctx.confirm.disabled = true;

          // Let the centered overlay paint before Turbo starts the request.
          requestAnimationFrame(function () {
            setTimeout(function () { form.submit(); }, 180);
          });

          // Failsafe: if navigation never happens, do not trap the user behind
          // a permanent loading screen.
          setTimeout(function () {
            if (ctx.loading && !ctx.loading.hidden) {
              ctx.loading.hidden = true;
              if (ctx.confirm) ctx.confirm.disabled = false;
            }
          }, 8000);
        });
      }
      modal.addEventListener('click', function (event) {
        if (event.target === modal) close(ctx);
      });
    }

    if (!window.__aqReportDeleteDelegatesBound) {
      window.__aqReportDeleteDelegatesBound = true;

      document.addEventListener('click', function (event) {
        var ctx = window.__aqReportDeleteCtx;
        if (!ctx || !ctx.modal) return;

        var trigger = event.target && event.target.closest
          ? event.target.closest('[data-delete-trigger]')
          : null;
        if (!trigger) return;

        event.preventDefault();
        ctx.pending = trigger.closest('form');
        ctx.lastTitle = trigger.dataset.deleteTitle || 'This report';
        if (ctx.name) ctx.name.textContent = ctx.lastTitle;
        ctx.modal.hidden = false;
        if (ctx.confirm) ctx.confirm.focus();
      });

      document.addEventListener('keydown', function (event) {
        var ctx = window.__aqReportDeleteCtx;
        if (ctx && ctx.modal && event.key === 'Escape' && !ctx.modal.hidden) close(ctx);
      });
    }

    boot();
  })();
  </script>
@endif
