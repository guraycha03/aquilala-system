<?php

/*
|--------------------------------------------------------------------------
| Store owner navigation
|--------------------------------------------------------------------------
|
| Mirrors config/admin-nav.php. When the signed-in user chooses the
| "In the side menu" sub-page style, each main menu name below is rendered
| as a dropdown and its entries as sub-page links inside that dropdown.
| Only the sub-page matching the current route gets the active state.
|
| `icon`  -> raw inline SVG (same glyph as the classic top-level menu item)
| `items` -> ['label', 'routes'] the current request may be on
|
| Ordering follows the navigation plan: Dashboard, Store Management,
| Quotations & Reports, Account Settings. No notification items or badges are
| shown here — notifications live outside the sidebar. Quotation
| terminology is preserved intentionally.
*/

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'standalone' => true,
        'items' => [
            ['label' => 'Dashboard', 'routes' => ['store.dashboard']],
        ],
    ],
    'ai_assistant' => [
        'label' => 'AI Assistant',
        'icon' => '<i data-lucide="sparkles" aria-hidden="true"></i>',
        'standalone' => true,
        'items' => [
            ['label' => 'AI Assistant', 'routes' => ['store.ai-chat.index', 'store.ai-chat.history.show']],
        ],
    ],
    'store-management' => [
        'label' => 'Store Management',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'items' => [
            ['label' => 'Product Listings', 'routes' => ['store.storefront.index']],
            ['label' => 'Inventory', 'routes' => ['store.inventory.index']],
            ['label' => 'Top Products', 'routes' => ['store.dashboard.performance']],
            ['label' => 'Input Formulas', 'routes' => ['store.formulas.index', 'store.formulas.create', 'store.formulas.edit']],
        ],
    ],
    'demand-reports' => [
        'label' => 'Quotations & Reports',
        'icon' => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'items' => [
            ['label' => 'Quotation Demand Trends', 'routes' => ['store.quotations.index', 'store.quotations.show']],
        ],
    ],
    'account-settings' => [
        'label' => 'Account Settings',
        'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'items' => [
            ['label' => 'General Settings', 'routes' => ['store.settings.index']],
            ['label' => 'Activity History', 'routes' => ['store.activity.index']],
        ],
    ],
];
