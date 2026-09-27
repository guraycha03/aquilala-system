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
*/

return [
    'shop' => [
        'label' => 'My Store',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'items' => [
            ['label' => 'Storefront', 'routes' => ['store.storefront.index']],
            ['label' => 'Import Products', 'routes' => ['store.products.import', 'store.products.import.preview', 'store.products.import.revalidate', 'store.products.import.ai-fix', 'store.products.import.confirm', 'store.products.import.cancel', 'store.products.import.template']],
            ['label' => 'Inventory Management', 'routes' => ['store.inventory.index']],
            ['label' => 'Input Formulas', 'routes' => ['store.formulas.index', 'store.formulas.create', 'store.formulas.edit']],
        ],
    ],
    'demand' => [
        'label' => 'Demand Insights',
        'icon' => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'items' => [
            ['label' => 'Demand Intelligence', 'routes' => ['store.quotations.index', 'store.quotations.show']],
            ['label' => 'AI Assistant', 'routes' => ['store.ai-chat.index', 'store.ai-chat.history.show']],
        ],
    ],
    'notifications' => [
        'label' => 'Notifications',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'items' => [
            ['label' => 'Notifications', 'routes' => ['store.notifications.index']],
            ['label' => 'Activity Log', 'routes' => ['store.activity.index']],
        ],
    ],
];