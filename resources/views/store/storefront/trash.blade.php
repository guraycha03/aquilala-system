@extends('layouts.sidebar')

@section('title', 'Product Archive')

@section('content')

<style>
    .page-header {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
        flex-wrap: wrap; margin-bottom: 20px;
    }
    .page-title { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .page-subtitle { font-size: 13px; color: #64748b; margin: 0; max-width: 640px; line-height: 1.6; }
    .page-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-ghost, .btn-primary {
        display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
        border: 1px solid #e2e8f0; background: #fff; color: #334155; cursor: pointer;
        transition: all .18s ease;
    }
    .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-primary { background: #143d23; border-color: #143d23; color: #fff; }
    .btn-primary:hover { background: #1b5230; }

    .alert-success {
        display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px;
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: 13px;
        font-weight: 500; margin-bottom: 18px;
    }
    .alert-info {
        display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px;
        background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; font-size: 13px;
        margin-bottom: 18px;
    }

    .archive-list { display: flex; flex-direction: column; gap: 12px; }
    .archive-item {
        display: flex; align-items: center; gap: 16px; padding: 14px 16px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04); flex-wrap: wrap;
    }
    .archive-thumb {
        width: 58px; height: 58px; border-radius: 10px; overflow: hidden; flex-shrink: 0;
        background: #f1f5f9; display: flex; align-items: center; justify-content: center;
    }
    .archive-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .archive-thumb svg { width: 22px; height: 22px; stroke: #94a3b8; }
    .archive-info { flex: 1; min-width: 180px; }
    .archive-name { font-size: 15px; font-weight: 600; color: #1e293b; margin: 0 0 3px 0; }
    .archive-meta { font-size: 12px; color: #64748b; display: flex; gap: 8px; flex-wrap: wrap; }
    .archive-meta .sku { font-family: ui-monospace, monospace; background: #f1f5f9; border-radius: 6px; padding: 1px 6px; }
    .archive-removed { font-size: 12px; color: #94a3b8; margin-top: 4px; }
    .archive-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-restore {
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
        padding: 8px 14px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-restore:hover { background: #dcfce7; }
    .btn-purge {
        background: #fff5f5; border: 1px solid #fecdd3; color: #e11d48;
        padding: 8px 14px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-purge:hover { background: #ffe4e6; color: #be123c; }

    .empty-state {
        text-align: center; padding: 56px 24px; background: #fff; border: 1px dashed #e2e8f0;
        border-radius: 16px; color: #64748b;
    }
    .empty-state svg { width: 44px; height: 44px; stroke: #cbd5e1; margin-bottom: 12px; }
    .empty-state h3 { margin: 0 0 6px 0; font-size: 16px; color: #334155; }
    .empty-state p { margin: 0; font-size: 13px; line-height: 1.6; }

    /* Add-to-archive modal */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, .45);
        display: none; align-items: center; justify-content: center; z-index: 60;
        /* extra top gap so the sheet never touches the app/system header */
        padding: 32px 20px 20px;
    }
    .modal-overlay.open { display: flex; }
    .modal {
        background: #fff; border-radius: 16px; width: 100%; max-width: 560px;
        max-height: 85vh; display: flex; flex-direction: column;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .25); animation: modalIn .18s ease-out;
    }
    @keyframes modalIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
    @media (max-width: 640px) {
        /* more breathing room under the sticky app header on phones */
        .modal-overlay { padding: 56px 14px 16px; }
        .modal { max-height: calc(100vh - 72px); }
    }
    .modal-head { padding: 18px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-shrink: 0; }
    .modal form { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    .modal-head h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 3px 0; }
    .modal-head p { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.5; }
    .modal-close {
        background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        color: #64748b; font-size: 18px; line-height: 1; flex-shrink: 0; transition: all .15s ease;
    }
    .modal-close:hover { background: #e2e8f0; color: #0f172a; }
    .modal-body { padding: 14px 20px; overflow-y: auto; flex: 1; min-height: 0; }
    .pick-toolbar {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 12px;
        font-size: 13px; color: #475569;
    }
    .pick-toolbar label { display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; }
    .pick-toolbar input { accent-color: #143d23; width: 15px; height: 15px; cursor: pointer; }
    #pickCount { color: #64748b; font-size: 12.5px; }
    .pick-list { display: flex; flex-direction: column; gap: 8px; }
    .pick-row {
        display: flex; align-items: center; gap: 12px; padding: 10px 12px;
        border: 1px solid #e5e7eb; border-radius: 12px; cursor: pointer;
        transition: all .15s ease; background: #fff;
    }
    .pick-row:hover { border-color: #cbd5e1; background: #f8fafc; }
    /* Selected/focused rows get the light green from the success palette,
       never the near-black #143d23 (it read as a black border on tap). */
    .pick-row.checked { border-color: #86efac; background: #f0fdf4; }
    .pick-row:focus-within {
        border-color: #86efac; box-shadow: 0 0 0 3px rgba(134, 239, 172, .35);
        outline: none;
    }
    .pick-row input:focus-visible { outline: none; }
    .pick-row input { accent-color: #143d23; width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; }
    .pick-thumb {
        width: 44px; height: 44px; border-radius: 9px; overflow: hidden; flex-shrink: 0;
        background: #f1f5f9; display: flex; align-items: center; justify-content: center;
    }
    .pick-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .pick-thumb svg { width: 18px; height: 18px; stroke: #94a3b8; }
    .pick-info { flex: 1; min-width: 0; }
    .pick-name { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 2px 0; }
    .pick-meta { font-size: 12px; color: #64748b; display: flex; gap: 8px; flex-wrap: wrap; }
    .pick-meta .sku { font-family: ui-monospace, monospace; background: #f1f5f9; border-radius: 6px; padding: 1px 6px; }
    .pick-empty { text-align: center; color: #94a3b8; font-size: 13px; padding: 24px 8px; }
    .modal-foot { padding: 14px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }
    .btn-primary:disabled { opacity: .5; cursor: not-allowed; }

    @media (max-width: 640px) {
        .archive-item { align-items: flex-start; }
        .archive-actions { width: 100%; }
        .archive-actions form { flex: 1; }
        .archive-actions button { width: 100%; }
    }
</style>

<!-- Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Product Archive</h1>
        <p class="page-subtitle">
            Products you remove from the catalog are archived here instead of being erased.
            They stay out of the storefront and the marketplace until you restore them —
            or you can delete them permanently.
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('store.storefront.index') }}" class="btn-ghost">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Catalog
        </a>
        <button type="button" class="btn-primary" id="openArchiveModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Product
        </button>
    </div>
</div>

@if (session('success'))
<div class="alert-success">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

<div class="alert-info">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
    <span><strong>{{ $archivedCount }}</strong> archived product{{ $archivedCount === 1 ? '' : 's' }}. </span>
</div>

@if ($products->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
            <path d="M3 9h18"></path>
        </svg>
        <h3>Archive is empty</h3>
        <p>Nothing has been archived.</p>
    </div>
@else
    <div class="archive-list">
        @foreach ($products as $product)
        <div class="archive-item">
            <div class="archive-thumb">
                @if ($product->mainImage && !empty($product->mainImage->thumb_url))
                    <img src="{{ $product->mainImage->thumb_url }}" alt="{{ $product->name }}">
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                @endif
            </div>

            <div class="archive-info">
                <h3 class="archive-name">{{ $product->name }}</h3>
                <div class="archive-meta">
                    <span class="sku">{{ $product->sku }}</span>
                    <span>{{ $product->category_display_name }}</span>
                    <span>&#8369;{{ number_format($product->price, 2) }}</span>
                </div>
                <div class="archive-removed">
                    Archived {{ $product->deleted_at?->diffForHumans() }}
                    @if($product->deleted_at) &middot; {{ $product->deleted_at->format('M d, Y g:i A') }} @endif
                </div>
            </div>

            <div class="archive-actions">
                <form method="POST" action="{{ route('store.products.restore', $product->id) }}">
                    @csrf
                    <button type="submit" class="btn-restore">
                        Restore to catalog
                    </button>
                </form>
                <form method="POST" action="{{ route('store.products.force-destroy', $product->id) }}"
                      data-aqui-confirm="Permanently delete “{{ $product->name }}”? This removes the product, its images and its records from the database and cannot be undone."
                      data-aqui-danger data-aqui-confirm-label="Delete permanently">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-purge">
                        Delete permanently
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
@endif

<!-- Add to Archive modal -->
<div class="modal-overlay" id="archiveModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="archiveModalTitle">Add Product to Archive</h3>
                <p>Tick the products you want to move into the archive. They stay recoverable — you can restore them anytime.</p>
            </div>
            <button type="button" class="modal-close" id="closeArchiveModal" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="{{ route('store.products.archive-bulk') }}" id="archiveBulkForm">
            @csrf
            <div class="modal-body">
                @if ($activeProducts->isEmpty())
                    <div class="pick-empty">You have no active products to archive.</div>
                @else
                    <div class="pick-toolbar">
                        <label><input type="checkbox" id="pickAll"> Select all</label>
                        <span id="pickCount">0 selected</span>
                    </div>
                    <div class="pick-list">
                        @foreach ($activeProducts as $product)
                            <label class="pick-row">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="pick-check">
                                <div class="pick-thumb">
                                    @if ($product->mainImage && !empty($product->mainImage->thumb_url))
                                        <img src="{{ $product->mainImage->thumb_url }}" alt="{{ $product->name }}">
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                                    @endif
                                </div>
                                <div class="pick-info">
                                    <p class="pick-name">{{ $product->name }}</p>
                                    <div class="pick-meta">
                                        <span class="sku">{{ $product->sku }}</span>
                                        <span>{{ $product->category_display_name }}</span>
                                        <span>&#8369;{{ number_format($product->price, 2) }}</span>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-ghost" id="cancelArchiveModal">Cancel</button>
                <button type="submit" class="btn-primary" id="archiveSubmit" disabled>Add to Archive</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('archiveModal');
    const openBtn = document.getElementById('openArchiveModal');
    const closeBtn = document.getElementById('closeArchiveModal');
    const cancelBtn = document.getElementById('cancelArchiveModal');
    const pickAll = document.getElementById('pickAll');
    const pickCount = document.getElementById('pickCount');
    const submitBtn = document.getElementById('archiveSubmit');
    const checks = () => Array.from(document.querySelectorAll('.pick-check'));

    if (!pickAll) { // no active products — nothing to pick
        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        cancelBtn.addEventListener('click', close);
        return;
    }

    function open() {
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
    }
    function close() {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
    }
    function refresh() {
        const rows = checks();
        const checked = rows.filter(c => c.checked).length;
        pickCount.textContent = checked + ' selected';
        submitBtn.disabled = checked === 0;
        pickAll.checked = rows.length > 0 && checked === rows.length;
        pickAll.indeterminate = checked > 0 && checked < rows.length;
        rows.forEach(c => c.closest('.pick-row').classList.toggle('checked', c.checked));
    }

    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    cancelBtn.addEventListener('click', close);
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    pickAll.addEventListener('change', () => {
        checks().forEach(c => { c.checked = pickAll.checked; });
        refresh();
    });
    document.querySelector('.pick-list')?.addEventListener('change', refresh);

    refresh();
})();
</script>

@endsection
