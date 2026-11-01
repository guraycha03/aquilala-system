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
                'accent'            => '#8FB89A',
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
            'chip' => ['bg' => '#f1efe2', 'fg' => '#4c5c30'],
            'colors' => [
                'bg'                => '#f1efe2',
                'deep'              => '#e3e0cc',
                'fg'                => '#33421d',
                'nav'               => '#4c5c30',
                'muted'             => '#758954',
                'nav_hover'         => '#20280f',
                'hover_bg'          => 'rgba(51, 66, 29, 0.08)',
                'hover_strong'      => 'rgba(51, 66, 29, 0.14)',
                'active_bg'         => '#f3f6f4',
                'active_fg'         => '#5f7c2e',
                'accent'            => '#7FA886',
                'avatar_bg'         => 'rgba(51, 66, 29, 0.12)',

               
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
            'name' => 'Dark Brown',
            'desc' => 'Dark coffee brown with warm accents',
            'chip' => ['bg' => '#4e493f', 'fg' => '#f5efe4'],
            'colors' => [
                'bg'                => '#4e493f',
                'deep'              => '#37342f',
                'fg'                => '#f5efe4',
                'nav'               => '#d7cfc2',
                'muted'             => '#b3aba0',
                'nav_hover'         => '#fffaf3',
                'hover_bg'          => 'rgba(255,255,255,0.08)',
                'hover_strong'      => 'rgba(255,255,255,0.14)',
                'active_bg'         => '#e8e0d4',
                'active_fg'         => '#3a352d',
                'accent'            => '#d5c2a1',
                'avatar_bg'         => 'rgba(255,255,255,0.16)',
                'avatar_fg'         => '#f5efe4',
                'border'            => 'rgba(255,255,255,0.16)',
                'border_soft'       => 'rgba(255,255,255,0.10)',
                'card_a'            => 'rgba(255,255,255,0.14)',
                'card_b'            => 'rgba(255,255,255,0.04)',
                'card_border'       => 'rgba(255,255,255,0.18)',
                'card_border_hover' => 'rgba(255,255,255,0.28)',
                'card_shadow'       => 'inset 0 1px 0 rgba(255,255,255,0.11)',
                'badge_bg'          => 'rgba(255,255,255,0.18)',
                'logout_bg'         => 'rgba(239,68,68,0.09)',
                'logout_fg'         => '#ffc4c4',
                'logout_border'     => 'rgba(255,255,255,0.14)',
                'logout_hover_bg'   => '#ef4444',
                'logout_hover_fg'   => '#ffffff',
                'menu_ico_dark'     => false,
            ],
        ],
    ],
];