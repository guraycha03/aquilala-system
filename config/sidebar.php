<?php

/*
|--------------------------------------------------------------------------
| Side Menu Color Themes
|--------------------------------------------------------------------------
|
| Single place to adjust the side menu colors available to users.
|
| 'default'        -> theme applied when a user has no saved choice.
| 'themes'         -> one entry per color option.  The array KEY is the
|                     identifier stored in the database (users.sidebar_theme)
|                     and rendered as data-sidebar-theme, so keys must stay
|                     unique and stable across edits.  'name' and 'desc' are
|                     only display text and can be changed freely.
|
| Inside each 'colors' block every value maps 1:1 to the CSS variables used
| by the sidebar (--sb-bg, --sb-accent, ...).  'menu_ico_dark' chooses which
| store-icon PNG to show when hovering a nav link; set true on light themes.
|
*/

return [
    'default' => 'emerald',

    'themes' => [
        'emerald' => [
            'name' => 'Forest Green',
            'desc' => 'Deep natural green',
            'chip' => ['bg' => '#143d23', 'fg' => '#b9cfc0'],
            'colors' => [
                'bg'                => '#143d23',
                'deep'              => '#0d2917',
                'fg'                => '#ffffff',
                'nav'               => '#b9cfc0',
                'muted'             => '#87a992',
                'nav_hover'         => '#ffffff',
                'hover_bg'          => 'rgba(255,255,255,0.05)',
                'hover_strong'      => 'rgba(255,255,255,0.10)',
                'active_bg'         => '#f3f6f4',
                'active_fg'         => '#143d23',
                'accent'            => '#6aeb2f',
                'avatar_bg'         => '#1b4d2e',
                'avatar_fg'         => '#c8e6c9',
                'border'            => 'rgba(255,255,255,0.08)',
                'border_soft'       => 'rgba(255,255,255,0.05)',
                'card_a'            => 'rgba(255,255,255,0.07)',
                'card_b'            => 'rgba(255,255,255,0.02)',
                'card_border'       => 'rgba(255,255,255,0.09)',
                'card_border_hover' => 'rgba(255,255,255,0.16)',
                'card_shadow'       => 'inset 0 1px 0 rgba(255,255,255,0.05)',
                'badge_bg'          => 'rgba(135,169,146,0.25)',
                'logout_bg'         => 'rgba(239, 68, 68, 0.08)',
                'logout_fg'         => '#fca5a5',
                'logout_border'     => 'rgba(239, 68, 68, 0.15)',
                'logout_hover_bg'   => '#ef4444',
                'logout_hover_fg'   => '#ffffff',
                'menu_ico_dark'     => false,
            ],
        ],

        'matcha' => [
            'name' => 'Pistachio',
            'desc' => 'Soft light green',
            'chip' => ['bg' => '#e5f3c3', 'fg' => '#4c5c30'],
            'colors' => [
                'bg'                => '#e5f3c3',
                'deep'              => '#c4e092',
                'fg'                => '#33421d',
                'nav'               => '#4c5c30',
                'muted'             => '#758954',
                'nav_hover'         => '#20280f',
                'hover_bg'          => 'rgba(51,66,29,0.10)',
                'hover_strong'      => 'rgba(51,66,29,0.16)',
                'active_bg'         => '#f3f6f4',
                'active_fg'         => '#5f7c2e',
                'accent'            => '#3ca811',
                'avatar_bg'         => 'rgba(51,66,29,0.12)',
                'avatar_fg'         => '#4c5c30',
                'border'            => 'rgba(51,66,29,0.16)',
                'border_soft'       => 'rgba(51,66,29,0.10)',
                'card_a'            => 'rgba(255,255,255,0.45)',
                'card_b'            => 'rgba(255,255,255,0.15)',
                'card_border'       => 'rgba(51,66,29,0.14)',
                'card_border_hover' => 'rgba(51,66,29,0.22)',
                'card_shadow'       => 'inset 0 1px 0 rgba(255,255,255,0.5)',
                'badge_bg'          => 'rgba(51,66,29,0.12)',
                'logout_bg'         => 'rgba(220,38,38,0.10)',
                'logout_fg'         => '#b91c1c',
                'logout_border'     => 'rgba(220,38,38,0.25)',
                'logout_hover_bg'   => '#ef4444',
                'logout_hover_fg'   => '#ffffff',
                'menu_ico_dark'     => true,
            ],
        ],

        'khaki' => [
            'name' => 'Sage Green',
            'desc' => 'Muted earthy green',
            'chip' => ['bg' => '#49802a', 'fg' => '#eef3dd'],
            'colors' => [
                'bg'                => '#49802a',
                'deep'              => '#417921',
                'fg'                => '#ffffff',
                'nav'               => '#eef3dd',
                'muted'             => '#dbe6bd',
                'nav_hover'         => '#ffffff',
                'hover_bg'          => 'rgba(255,255,255,0.12)',
                'hover_strong'      => 'rgba(255,255,255,0.20)',
                'active_bg'         => '#f3f6f4',
                'active_fg'         => '#5f6b28',
                'accent'            => '#8af04f',
                'avatar_bg'         => 'rgba(255,255,255,0.20)',
                'avatar_fg'         => '#f4f8ea',
                'border'            => 'rgba(255,255,255,0.18)',
                'border_soft'       => 'rgba(255,255,255,0.10)',
                'card_a'            => 'rgba(255,255,255,0.18)',
                'card_b'            => 'rgba(255,255,255,0.05)',
                'card_border'       => 'rgba(255,255,255,0.20)',
                'card_border_hover' => 'rgba(255,255,255,0.30)',
                'card_shadow'       => 'inset 0 1px 0 rgba(255,255,255,0.12)',
                'badge_bg'          => 'rgba(255,255,255,0.20)',
                'logout_bg'         => 'rgba(255,255,255,0.10)',
                'logout_fg'         => '#ffe1e1',
                'logout_border'     => 'rgba(255,255,255,0.20)',
                'logout_hover_bg'   => '#ef4444',
                'logout_hover_fg'   => '#ffffff',
                'menu_ico_dark'     => false,
            ],
        ],
    ],
];