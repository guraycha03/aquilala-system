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
|
| Ordering follows the navigation plan: Dashboard, User Management, Farm
| Supplies, Harvest Reports, Calendar & Events, Reports & Analytics, System
| Settings. No notification items or badges are shown here — notifications
| live outside the sidebar. Quotation terminology is preserved
| intentionally. The `harvest` and `settings` group keys are referenced by
| feature tests, so they must not be renamed.
*/

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'items' => [
            ['label' => 'Overview', 'routes' => ['dashboard.admin']],
        ],
    ],
    'users' => [
        'label' => 'User Management',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'items' => [
            ['label' => 'All Users', 'routes' => ['admin.users.index']],
        ],
    ],
    'operations' => [
        'label' => 'Farm Supplies',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>',
        'items' => [
            ['label' => 'Product Catalog', 'routes' => ['admin.operations.catalog']],
            ['label' => 'Stores', 'routes' => ['admin.stores.index']],
            ['label' => 'Input Formulas', 'routes' => ['admin.operations.formulas']],
            ['label' => 'Planting Requests', 'routes' => ['admin.plantings.index']],
        ],
    ],
    'harvest' => [
        'label' => 'Harvest Reports',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M12 20V10"/><path d="M12 10C9 10 6 9 6 5c3 0 6 2 6 5"/><path d="M12 14c3 0 6-1 6-5-3 0-6 2-6 5"/><path d="M3 20h18"/><path d="M3 12l4-4 4 4 5-5 5 5"/></svg>',
        'items' => [
            ['label' => 'Harvest Performance', 'routes' => ['admin.harvests.index']],
            ['label' => 'All Harvest Reports', 'routes' => ['admin.harvests.reports']],
            ['label' => 'Estimate vs Actual Harvest', 'routes' => ['admin.harvests.compare']],
            ['label' => 'AI Insights', 'routes' => ['admin.harvests.ai']],
        ],
    ],
    'calendar' => [
        'label' => 'Calendar & Events',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'items' => [
            ['label' => 'Calendar', 'routes' => ['admin.calendar.index']],
            ['label' => 'Events', 'routes' => ['admin.calendar.events']],
            ['label' => 'Planting Seasons', 'routes' => ['admin.operations.seasons']],
        ],
    ],
    'analytics' => [
        'label' => 'Reports & Analytics',
        'icon' => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'items' => [
            ['label' => 'Quotation Demand Trends', 'routes' => ['admin.quotations.index']],
            ['label' => 'Farming Cost Reports', 'routes' => ['admin.analytics.expenses']],
        ],
    ],
    'settings' => [
        'label' => 'System Settings',
        'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'items' => [
            ['label' => 'General Settings', 'routes' => ['admin.settings.index']],
            ['label' => 'Season Settings', 'routes' => ['admin.settings.seasons']],
            ['label' => 'Appearance', 'routes' => ['admin.settings.interface']],
            ['label' => 'Weather Log', 'routes' => ['admin.settings.weather']],
            ['label' => 'Audit Log', 'routes' => ['admin.audit.index']],
            ['label' => 'Activity History', 'routes' => ['admin.activity.index']],
        ],
    ],
];
