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
  @endphp
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafdfb;border-radius:0 0 12px 12px;">
    <span class="pagination-info">Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
    <div class="pagination-controls">
      @if ($paginator->onFirstPage())
        <span class="page-btn disabled">&lsaquo;</span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($pageNums as $i)
        @if ($i === $currentPage)
          <span class="page-btn active">{{ $i }}</span>
        @else
          @if (!$loop->first && $i - $pageNums[$loop->index - 1] > 1)
            <span class="page-ellipsis">&hellip;</span>
          @endif
          <a href="{{ $paginator->url($i) }}" class="page-btn">{{ $i }}</a>
        @endif
      @endforeach
      @if ($currentPage >= $end && $end < $lastPage)
        <span class="page-ellipsis">&hellip;</span>
      @endif
      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn disabled">&rsaquo;</span>
      @endif
    </div>
  </div>
@endif
