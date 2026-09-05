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
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    margin-bottom: 16px; font-size: 12.5px; font-weight: 600;
    position: relative; z-index: 1;
  }
  .page-breadcrumb a {
    color: #7c8a80; text-decoration: none;
    transition: color 0.15s ease;
    display: inline-flex; align-items: center; gap: 5px;
  }
  .page-breadcrumb a:hover { color: #14532d; }
  .page-breadcrumb a::before {
    content: ""; width: 13px; height: 13px; flex-shrink: 0;
    background: currentColor;
    -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E") center / contain no-repeat;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E") center / contain no-repeat;
  }
  .pb-sep { color: #cbd5e1; }
  .pb-current { color: #1e293b; }
</style>
