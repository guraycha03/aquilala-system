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
| Ordering follows the navigation plan: Dashboard, Members, User
| Management, Report and Request, Calendar & Events, Farm Supplies, System
| Settings — the association's daily work first, Farm Supplies (the least
| association-specific section) just above Settings. The `harvest` group is
| titled "Report and Request" because it now carries the planting requests
| next to the harvest pages, so the name cannot be read as harvest-only.
| There is no separate reporting section: Farming Cost Reports sits in
| Harvest because it analyses the same harvest data, and Quotation Demand
| Trends sits with Farm Supplies because it describes product demand. No
| notification items or badges are shown here — notifications live outside
| the sidebar. Quotation terminology is preserved intentionally. The
| `harvest` and `settings` group keys are referenced by feature tests, so
| they must not be renamed.
|
| Decision formulas no longer have their own sub-page: they will be maintained
| on each product's own info instead of in a standalone list.
*/

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'standalone' => true,
        'items' => [
            ['label' => 'Dashboard', 'routes' => ['dashboard.admin']],
        ],
    ],
    'ai_insight' => [
        'label' => 'AI Assistant',
        'icon' => '<i data-lucide="sparkles" aria-hidden="true"></i>',
        'standalone' => true,
        'items' => [
            ['label' => 'AI Assistant', 'routes' => ['admin.ai-chat.index']],
        ],
    ],
    // The association's members. Accounts are always created by picking
    // a member from this list, so Members is its own section rather than a
    // sub-page of User Management.
    'members' => [
        'label' => 'Members',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
        'items' => [
            ['label' => 'All Members', 'routes' => ['admin.members.index']],
            ['label' => 'Import Members', 'routes' => ['admin.members.import', 'admin.members.import.preview', 'admin.members.import.confirm']],
            ['label' => 'Import Uploads', 'routes' => ['admin.members.uploads', 'admin.members.uploads.download']],
        ],
    ],
    'users' => [
        'label' => 'User Management',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'items' => [
            ['label' => 'All Users', 'routes' => ['admin.users.index']],
            ['label' => 'Create Account', 'routes' => ['admin.users.create', 'admin.users.store']],
        ],
    ],
    // Reports *and* requests: the harvest pages share this dropdown with the
    // planting requests, so the title promises both halves of the name.
    'harvest' => [
        'label' => 'Report and Request',
        'icon' => '<svg viewBox="0 0 24 24" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>',
        // Importing and the archive of its files carry no menu entries of
        // their own: both pages are one button away on "All Harvest Reports",
        // so a sidebar line would only repeat that page and pad the menu.
        // The routes stay listed here so the section still opens while
        // either page is on screen — every page remains reachable, it just
        // no longer takes a permanent slot in the side menu.
        'hidden_routes' => [
            'admin.harvests.import',
            'admin.harvests.import.*',
            'admin.harvests.uploads',
            'admin.harvests.uploads.*',
        ],
        'items' => [
            ['label' => 'Harvest Performance', 'routes' => ['admin.harvests.index']],
            ['label' => 'All Harvest Reports', 'routes' => ['admin.harvests.reports']],
            ['label' => 'Estimate vs Actual Harvest', 'routes' => ['admin.harvests.compare']],
            // The cost report is budgeted-vs-actual off this same harvest
            // table, so it belongs beside the rest of the harvest figures
            // rather than in a reporting section of its own.
            ['label' => 'Farming Cost Reports', 'routes' => ['admin.analytics.expenses']],
            // Moved here from Farm Supplies so the section's name holds true:
            // this dropdown lists the association's reports *and* its one
            // request page.
            ['label' => 'Planting Requests', 'routes' => ['admin.plantings.index']],
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
    // Farm Supplies takes the slot just above System Settings: of everything
    // in the menu it is the least association-specific section (a product
    // catalog and stores), so it belongs at the bottom rather than between
    // account management and harvest. Planting Requests moved up into
    // "Report and Request".
    'operations' => [
        'label' => 'Farm Supplies',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>',
        'items' => [
            ['label' => 'Product Catalog', 'routes' => ['admin.operations.catalog']],
            ['label' => 'Stores', 'routes' => ['admin.stores.index', 'admin.stores.create', 'admin.stores.edit']],
            // Which products are being quoted, i.e. product demand in
            // numbers — a supply-and-stores report, so it sits with them.
            ['label' => 'Quotation Demand Trends', 'routes' => ['admin.quotations.index']],
        ],
    ],
    'settings' => [
        'label' => 'System Settings',
        'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'items' => [
            ['label' => 'General Settings', 'routes' => ['admin.settings.index']],
            ['label' => 'Activity History', 'routes' => ['admin.activity.index']],
            ['label' => 'Audit Log', 'routes' => ['admin.audit.index']],
        ],
    ],
];
