@props(['items'])

<nav class="page-breadcrumb" aria-label="Breadcrumb">
  @foreach ($items as $item)
    @if (!empty($item['url']))
      <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
    @else
      <span class="pb-current">{{ $item['label'] }}</span>
    @endif
    @if (!$loop->last)
      <span class="pb-sep">›</span>
    @endif
  @endforeach
</nav>

<style>
  .page-breadcrumb {
    display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
    margin-bottom: 16px; font-size: 12.5px; font-weight: 600;
    position: relative; z-index: 1;
  }
  .page-breadcrumb a {
    color: #7c8a80; text-decoration: none;
    transition: color 0.15s ease;
    display: inline-flex; align-items: center; gap: 5px;
  }
  .page-breadcrumb a:hover { color: #14532d; }
  /* The separator is a real character, not a hairline: it has to be
     readable at a glance, not merely present. */
  .pb-sep {
    color: #9aa7b0; font-size: 19px; line-height: 1;
    font-weight: 700; padding: 0 1px;
  }
  .pb-current { color: #1e293b; }
  [data-theme="dark"] .pb-sep { color: #64748b; }
</style>
