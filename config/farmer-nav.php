<?php

/*
|--------------------------------------------------------------------------
| Farmer navigation
|--------------------------------------------------------------------------
|
| Mirrors config/store-nav.php and config/admin-nav.php. When the signed-in
| user chooses the "In the side menu" sub-page style, each main menu name
| below is rendered as a dropdown and its entries as sub-page links inside
| that dropdown. Only the sub-page matching the current route gets the
| active state.
|
| `icon`  -> raw inline SVG (same glyph as the classic top-level menu item)
| `items` -> ['label', 'routes'] the current request may be on
*/

return [
    'my-farm' => [
        'label' => 'My Farm',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>',
        'items' => [
            ['label' => 'My Crops', 'routes' => ['farmer.crops.index', 'farmer.crops.show']],
            ['label' => 'Harvest Reports', 'routes' => ['farmer.harvests.index']],
        ],
    ],
    'smart-farming' => [
        'label' => 'Smart Farming',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>',
        'items' => [
            ['label' => 'Recommendations', 'routes' => ['farmer.recommendations.index']],
            ['label' => 'Crop Directory', 'routes' => ['farmer.crops-directory.index']],
            ['label' => 'AI Assistant', 'routes' => ['farmer.ai-chat.index', 'farmer.ai-chat.history.show']],
        ],
    ],
    'marketplace' => [
        'label' => 'Marketplace',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'items' => [
            ['label' => 'Browse Products', 'routes' => ['farmer.products.browse', 'farmer.products.show']],
            ['label' => 'Top Rated Products', 'routes' => ['farmer.reviews.index']],
            ['label' => 'Stores Directory', 'routes' => ['farmer.stores.index']],
        ],
    ],
    'quotations' => [
        'label' => 'Cost Quotations',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'items' => [
            ['label' => 'Generate Quotation', 'routes' => ['farmer.quotations.request', 'farmer.quotations.products']],
            ['label' => 'My Cost Quotations', 'routes' => ['farmer.quotations.index', 'farmer.quotations.show', 'farmer.quotations.breakdown']],
        ],
    ],
    'notifs' => [
        'label' => 'Notifications & Activity',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'items' => [
            ['label' => 'Notifications', 'routes' => ['farmer.notifications.index']],
            ['label' => 'Activity Log', 'routes' => ['farmer.activity.index']],
        ],
    ],
];