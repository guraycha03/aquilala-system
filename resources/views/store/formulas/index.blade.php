@extends('layouts.sidebar')

@section('title', 'Application Rules')

@section('content')
  <div style="max-width: 1080px; margin: 0 auto;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
      <div>
        <h1 style="font-size:24px;font-weight:800;color:#1e2922;margin:0;">Application Rules</h1>
        <p style="font-size:13px;color:#64748b;margin:5px 0 0;">Choose a product to create or manage its application rule.</p>
      </div>
      <a href="{{ route('store.formulas.create', ['ai' => 1]) }}" style="padding:10px 16px;border-radius:10px;background:#648268;color:#fff;font-weight:700;text-decoration:none;">Add rule with AI</a>
    </div>

    <div style="margin-bottom:18px;">
      <input type="search" id="ruleProductSearch" placeholder="Search products..." style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;" />
    </div>

    @if(($products ?? collect())->isEmpty())
      <div style="text-align:center;padding:48px 20px;background:#fff;border:1.5px dashed #e2e8f0;border-radius:14px;color:#64748b;">
        <h3 style="font-size:17px;color:#1e2922;margin:0 0 6px 0;">No products yet</h3>
        <p style="font-size:14px;margin:0 0 16px;">Add products first.</p>
        <a href="{{ route('store.products.create') }}" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#648268;color:#fff;font-weight:700;text-decoration:none;">Add Product</a>
      </div>
    @else
      <div class="product-grid rule-product-grid">
        @foreach($products as $product)
          <x-product-card :product="$product" :href="route('store.formulas.create') . '?product_id=' . $product->id" />
        @endforeach
      </div>
    @endif
  </div>

  <style>
    .product-grid.rule-product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1200px) { .product-grid.rule-product-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 900px) { .product-grid.rule-product-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .product-grid.rule-product-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; } }
  </style>
  <script>
    (function () {
      var input = document.getElementById('ruleProductSearch');
      if (!input) return;
      input.addEventListener('input', function () {
        var q = (input.value || '').toLowerCase().trim();
        document.querySelectorAll('.rule-product-grid .product-card').forEach(function (card) {
          var text = (card.textContent || '').toLowerCase();
          card.style.display = (q === '' || text.indexOf(q) >= 0) ? '' : 'none';
        });
      });
    })();
  </script>
@endsection