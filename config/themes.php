<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The default theme to use when a user hasn't selected one.
    |
    */

    'default' => env('APP_THEME', 'dark'),

    /*
    |--------------------------------------------------------------------------
    | Terminal Font
    |--------------------------------------------------------------------------
    |
    | The primary monospace font for the terminal aesthetic. This can be
    | overridden via APP_FONT environment variable.
    |
    */

    'font' => env('APP_FONT', 'JetBrains Mono'),

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | Define color schemes for each theme. Each theme must include all
    | required color properties for consistency across the application.
    |
    */

    'themes' => [
        'dark' => [
            'name' => 'Terminal Dark',
            'description' => 'Classic 80s terminal aesthetic with neon green on dark background',
            'colors' => [
                // Background colors
                'bg-primary' => '#000000',
                'bg-secondary' => '#0a0a0a',
                'bg-tertiary' => '#1a1a1a',
                'bg-hover' => '#1f1f1f',
                'bg-active' => '#2a2a2a',

                // Text colors
                'text-primary' => '#00ff00',
                'text-secondary' => '#00cc00',
                'text-muted' => '#008800',
                'text-disabled' => '#004400',

                // Border colors
                'border-primary' => '#00ff00',
                'border-secondary' => '#00cc00',
                'border-muted' => '#006600',

                // Accent colors
                'accent-primary' => '#00ff00',
                'accent-success' => '#00ff00',
                'accent-error' => '#ff00ff',
                'accent-warning' => '#ffff00',
                'accent-info' => '#00ffff',

                // Link colors
                'link' => '#00ffff',
                'link-hover' => '#00cccc',
                'link-visited' => '#ff00ff',

                // Form elements
                'input-bg' => '#0a0a0a',
                'input-border' => '#00ff00',
                'input-focus' => '#00ff00',
                'input-text' => '#00ff00',

                // Button colors
                'button-primary-bg' => '#00ff00',
                'button-primary-text' => '#000000',
                'button-secondary-bg' => '#1a1a1a',
                'button-secondary-text' => '#00ff00',
            ],
        ],

        'light' => [
            'name' => 'Terminal Light',
            'description' => 'Inverted terminal palette for bright environments',
            'colors' => [
                // Background colors
                'bg-primary' => '#fafafa',
                'bg-secondary' => '#f5f5f5',
                'bg-tertiary' => '#e5e5e5',
                'bg-hover' => '#e0e0e0',
                'bg-active' => '#d5d5d5',

                // Text colors
                'text-primary' => '#006600',
                'text-secondary' => '#008800',
                'text-muted' => '#00aa00',
                'text-disabled' => '#cccccc',

                // Border colors
                'border-primary' => '#006600',
                'border-secondary' => '#008800',
                'border-muted' => '#cccccc',

                // Accent colors
                'accent-primary' => '#006600',
                'accent-success' => '#006600',
                'accent-error' => '#cc0099',
                'accent-warning' => '#cc9900',
                'accent-info' => '#0099cc',

                // Link colors
                'link' => '#0099cc',
                'link-hover' => '#007799',
                'link-visited' => '#990099',

                // Form elements
                'input-bg' => '#ffffff',
                'input-border' => '#006600',
                'input-focus' => '#006600',
                'input-text' => '#006600',

                // Button colors
                'button-primary-bg' => '#006600',
                'button-primary-text' => '#fafafa',
                'button-secondary-bg' => '#e5e5e5',
                'button-secondary-text' => '#006600',
            ],
        ],
    ],

];
