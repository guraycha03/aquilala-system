@if ($paginator->hasPages())
  @php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $window = 1;
    $start = max(1, $currentPage - $window);
    $end = min($lastPage, $currentPage + $window);
    $pageNums = [];
    if ($start > 1) { $pageNums[] = 1; }
    for ($i = $start; $i <= $end; $i++) { $pageNums[] = $i; }
    if ($end < $lastPage) { $pageNums[] = $lastPage; }

    $btnBase = 'display:inline-flex;align-items:center;justify-content:center;padding:0 12px;height:30px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;transition:background 0.15s;';
    $btnNorm = $btnBase . 'color:#475569;background:#f8fafc;';
    $btnActive = $btnBase . 'color:#fff;background:#2563eb;border-color:#2563eb;';
    $btnDis = $btnBase . 'color:#cbd5e1;background:#f8fafc;border-color:transparent;cursor:default;pointer-events:none;';
  @endphp
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafdfb;border-radius:0 0 12px 12px;">
    <span style="font-size:12px;color:#64748b;white-space:nowrap;">Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
    <div style="display:flex;align-items:center;gap:4px;">
      @if ($paginator->onFirstPage())
        <span style="{{ $btnDis }}">&larr; Prev</span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" style="{{ $btnNorm }}">&larr; Prev</a>
      @endif
      @foreach ($pageNums as $i)
        @if ($i === $currentPage)
          <span style="{{ $btnActive }}">{{ $i }}</span>
        @else
          @if (!$loop->first && $i - $pageNums[$loop->index - 1] > 1)
            <span style="padding:0 3px;color:#94a3b8;font-size:12px;">&hellip;</span>
          @endif
          <a href="{{ $paginator->url($i) }}" style="{{ $btnNorm }}">{{ $i }}</a>
        @endif
      @endforeach
      @if ($currentPage >= $end && $end < $lastPage)
        <span style="padding:0 3px;color:#94a3b8;font-size:12px;">&hellip;</span>
      @endif
      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" style="{{ $btnNorm }}">Next &rarr;</a>
      @else
        <span style="{{ $btnDis }}">Next &rarr;</span>
      @endif
    </div>
  </div>
@endif
