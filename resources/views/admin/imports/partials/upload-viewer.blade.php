{{--
  Full-screen viewer for archived scans (OCR images only).

  Spreadsheets never land here — their file cell is a plain download link.
  Document-level handlers are bound once per session so a Turbo body swap
  brings fresh markup without stacking duplicate listeners.
--}}
<style>
  .up-viewer { position: fixed; inset: 0; z-index: 210; background: rgba(5, 12, 9, 0.9); display: flex; align-items: center; justify-content: center; padding: 24px; }
  .up-viewer[hidden] { display: none; }
  .upv-close { position: absolute; top: 16px; right: 18px; width: 42px; height: 42px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.3); background: rgba(15, 23, 42, 0.7); color: #ffffff; font-size: 24px; line-height: 1; cursor: pointer; transition: background 0.15s ease; }
  .upv-close:hover { background: rgba(15, 23, 42, 0.95); }
  .upv-frame { margin: 0; max-width: min(1100px, 94vw); display: flex; flex-direction: column; align-items: center; gap: 10px; }
  .upv-frame img { max-width: 100%; max-height: 80vh; border-radius: 10px; background: #ffffff; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.55); object-fit: contain; }
  .upv-caption { margin: 0; color: #d7e3da; font-size: 13px; font-weight: 600; text-align: center; word-break: break-all; }
</style>

<div class="up-viewer" id="upViewer" hidden>
  <button type="button" class="upv-close" id="upvClose" aria-label="Close the image viewer">&times;</button>
  <figure class="upv-frame">
    <img id="upvImg" alt="">
    <figcaption class="upv-caption" id="upvCaption"></figcaption>
  </figure>
</div>

<script>
  (function () {
    if (window.__upViewerReady) return;
    window.__upViewerReady = true;

    function viewer() { return document.getElementById('upViewer'); }
    function close() { var v = viewer(); if (v) v.hidden = true; }

    document.addEventListener('click', function (e) {
      var opener = e.target && e.target.closest ? e.target.closest('a[data-up-preview]') : null;
      if (opener) {
        e.preventDefault();
        var v = viewer(); if (!v) return;
        document.getElementById('upvImg').src = opener.getAttribute('href');
        var name = opener.querySelector('.file-name');
        document.getElementById('upvCaption').textContent = name ? name.textContent.trim() : '';
        v.hidden = false;
        return;
      }
      var box = viewer();
      if (box && !box.hidden) {
        var t = e.target;
        if (t === box || (t && t.closest && t.closest('.upv-close'))) close();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  })();
</script>
