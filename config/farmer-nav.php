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
|
| Ordering follows the navigation plan: Dashboard, My Farm, Cost Quotations,
| Farm Supplies, Farming Guide, Calendar & Events, Account Settings. No
| notification items or badges are shown here — notifications live outside
| the sidebar. Quotation terminology is preserved intentionally.
*/

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'items' => [
            ['label' => 'Overview',             'routes' => ['dashboard.farmer', 'farmer.dashboard']],
            ['label' => 'Submit Harvest Report', 'routes' => ['farmer.harvests.create']],
        ],
    ],
    'my-farm' => [
        'label' => 'My Farm',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>',
        'items' => [
            ['label' => 'My Crops', 'routes' => ['farmer.crops.index', 'farmer.crops.show']],
            ['label' => 'Harvest Reports', 'routes' => ['farmer.harvests.index']],
            ['label' => 'Estimate vs Actual Harvest', 'routes' => ['farmer.harvests.compare']],
        ],
    ],
    'cost-quotations' => [
        'label' => 'Cost Quotations',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        // Only the list itself: generating a quotation and the archived
        // estimates are reached from that page, so repeating them here
        // only made the menu longer than the work.
        'items' => [
            ['label' => 'My Cost Quotations', 'routes' => ['farmer.quotations.index', 'farmer.quotations.show', 'farmer.quotations.breakdown']],
        ],
    ],
    'farm-supplies' => [
        'label' => 'Farm Supplies',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'items' => [
            ['label' => 'Browse Supplies', 'routes' => ['farmer.products.browse', 'farmer.products.show']],
            ['label' => 'Recommended Supplies', 'routes' => ['farmer.recommendations.index']],
            ['label' => 'Top Quoted Products', 'routes' => ['farmer.top-quoted.index']],
            ['label' => 'Available Stores', 'routes' => ['farmer.stores.index']],
        ],
    ],
    'farming-guide' => [
        'label' => 'Farming Guide',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>',
        'items' => [
            ['label' => 'Crop Guide', 'routes' => ['farmer.crops-directory.index']],
            ['label' => 'AI Assistant', 'routes' => ['farmer.ai-chat.index', 'farmer.ai-chat.history.show']],
        ],
    ],
    'calendar' => [
        'label' => 'Calendar & Events',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'items' => [
            ['label' => 'Calendar', 'routes' => ['farmer.calendar.index']],
            ['label' => 'Events', 'routes' => ['farmer.calendar.events']],
        ],
    ],
    'account-settings' => [
        'label' => 'Account Settings',
        'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'items' => [
            ['label' => 'Settings', 'routes' => ['farmer.settings.index']],
            ['label' => 'Activity History', 'routes' => ['farmer.activity.index']],
        ],
    ],
];
