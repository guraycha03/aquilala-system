<script>
(function () {
  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-paginated] a[href]');
    if (!link) return;

    var container = link.closest('[data-paginated]');
    if (!container) return;

    var url = link.getAttribute('href');
    if (!url || url === '#') return;

    e.preventDefault();

    // Brief loading state
    container.style.opacity = '0.5';
    container.style.transition = 'opacity 0.15s';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var incoming = doc.querySelector('[data-paginated]');
        if (!incoming) return;

        // Replace the content
        container.innerHTML = incoming.innerHTML;
        container.style.opacity = '1';

        // Update the browser URL without reload
        history.pushState({ turbolinks: true }, '', url);

        // Re-initialize any page-specific JS that depends on table rows
        if (typeof window.initAjaxPage === 'function') {
          window.initAjaxPage();
        }
      })
      .catch(function () {
        // Fallback: full navigation on error
        window.location.href = url;
      });
  });

  // Handle browser back/forward
  window.addEventListener('popstate', function () {
    window.location.reload();
  });
})();
</script>
