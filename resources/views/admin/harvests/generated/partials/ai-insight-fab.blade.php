{{-- AI Insight button for the Generate and Saved tabs, where no report paper is rendered. --}}
<script type="application/json" id="ahr-ai-fab-data">@json([
    'text' => $previewInsight,
    'source_label' => 'Preview-based explanation',
])</script>

<div id="ahr-ai-fab-root">
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
</div>

<script>
(function () {
  var root = document.getElementById('ahr-ai-fab-root');
  var dataNode = document.getElementById('ahr-ai-fab-data');
  if (!root || !dataNode) return;

  var payload;
  try {
    payload = JSON.parse(dataNode.textContent);
  } catch (error) {
    payload = { text: 'AI Insight is unavailable for this preview right now.', source_label: 'Preview-based explanation' };
  }

  var open = root.querySelector('#ahr-ai-insight-open');
  var panel = root.querySelector('#ahr-ai-insight-panel');
  var close = root.querySelector('#ahr-ai-insight-close');
  var body = root.querySelector('#ahr-ai-insight-body');

  function render() {
    body.innerHTML = '';

    String(payload.text || '')
      .split(/\r?\n/)
      .map(function (line) { return line.trim(); })
      .filter(function (line) { return line !== ''; })
      .forEach(function (line) {
        var paragraph = document.createElement('p');
        paragraph.className = 'ahr-ai-text';
        paragraph.textContent = line.replace(/^- /, '');
        body.appendChild(paragraph);
      });

    var meta = document.createElement('div');
    meta.className = 'ahr-ai-meta';
    var pill = document.createElement('span');
    pill.className = 'ahr-ai-pill';
    pill.textContent = payload.source_label || 'Preview-based explanation';
    meta.appendChild(pill);
    body.appendChild(meta);

    var note = document.createElement('p');
    note.className = 'ahr-ai-note';
    note.textContent = 'This note uses only the saved harvest records in the current preview. The generated report remains the official record.';
    body.appendChild(note);
  }

  function setOpen(state) {
    panel.classList.toggle('open', state);
    panel.setAttribute('aria-hidden', state ? 'false' : 'true');
    open.setAttribute('aria-expanded', state ? 'true' : 'false');
    if (state) render();
  }

  open.addEventListener('click', function () { setOpen(true); });
  close.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && panel.classList.contains('open')) setOpen(false);
  });
})();
</script>
