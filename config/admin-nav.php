<?php

/*
|--------------------------------------------------------------------------
| Admin control panel navigation
|--------------------------------------------------------------------------
|
| When the signed-in admin chooses the "In the side menu" sub-page style,
| every main menu name in the sidebar becomes a dropdown and each entry
| below is rendered as a sub-page link inside that dropdown. Only the
| sub-page matching the current route gets the active state.
|
| `icon`  -> raw inline SVG (same glyph as the classic top-level menu item)
| `items` -> ['label', 'routes'] the current request may be on
*/

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'items' => [
            ['label' => 'Overview', 'routes' => ['dashboard.admin']],
            ['label' => 'Recent Activity', 'routes' => ['admin.activity.index']],
        ],
    ],
    'users' => [
        'label' => 'Users',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'items' => [
            ['label' => 'All Users', 'routes' => ['admin.users.index']],
            ['label' => 'Approvals', 'routes' => ['admin.approvals.index']],
            ['label' => 'Stores', 'routes' => ['admin.stores.index']],
        ],
    ],
    'operations' => [
        'label' => 'Crops & Inputs',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>',
        'items' => [
            ['label' => 'Formulas', 'routes' => ['admin.operations.formulas']],
            ['label' => 'Catalog & Reviews', 'routes' => ['admin.operations.catalog']],
            ['label' => 'Seasons', 'routes' => ['admin.operations.seasons']],
        ],
    ],
    'analytics' => [
        'label' => 'Analytics',
        'icon' => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'items' => [
            ['label' => 'Demand Trends', 'routes' => ['admin.quotations.index']],
            ['label' => 'Harvest Performance', 'routes' => ['admin.harvests.index']],
            ['label' => 'Expenses', 'routes' => ['admin.analytics.expenses']],
        ],
    ],
    'settings' => [
        'label' => 'Settings',
        'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'items' => [
            ['label' => 'General', 'routes' => ['admin.settings.index']],
            ['label' => 'Seasons', 'routes' => ['admin.settings.seasons']],
            ['label' => 'Interface', 'routes' => ['admin.settings.interface']],
            ['label' => 'Audit Log', 'routes' => ['admin.audit.index']],
        ],
    ],
];
