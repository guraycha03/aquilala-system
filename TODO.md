# TODO — Responsive Product Cards & Inventory Dashboard

## Part A: Responsive product cards (2 per row on mobile) ✅

1. [x] `farmer/products/browse.blade.php` — 2-column grid on small screens
2. [x] `store/storefront/index.blade.php` — 2-column grid + removed farmer-only preview button
3. [x] `farmer/recommendations/index.blade.php` — 2-column grid
4. [x] `farmer/products/show.blade.php` — responsive related grid + role-conditional actions
5. [x] `dashboard/store_owner.blade.php` — clickable cards, dedicated Edit, 2-column grid

## Part B: Modern interactive inventory dashboard ✅

1. [x] Redesigned metric cards with real data + health indicators
2. [x] Stock-health doughnut chart (Chart.js) fed by real data
3. [x] Rich inventory table (images, category, progress bars, status badges, stock value)
4. [x] Live search + status filter (client-side JS)
5. [x] Inline quick-adjust modal posting to `store.inventory.update-stock`
6. [x] Verified: `php artisan view:cache` passes; routes registered
