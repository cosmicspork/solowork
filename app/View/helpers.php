<?php

if (! function_exists('theme_color')) {
    /**
     * Get a color value from the current theme configuration.
     *
     * @param  string  $key  The color key (e.g., 'bg-primary', 'text-primary')
     * @param  string|null  $theme  Optional theme name, defaults to current theme
     * @return string|null The color value or null if not found
     */
    function theme_color(string $key, ?string $theme = null): ?string
    {
        // Use specified theme or fall back to default
        $theme = $theme ?? config('themes.default');

        // Get the color from the theme configuration
        return config("themes.themes.{$theme}.colors.{$key}");
    }
}

if (! function_exists('current_theme')) {
    /**
     * Get the current theme name.
     */
    function current_theme(): string
    {
        return config('themes.default');
    }
}

if (! function_exists('available_themes')) {
    /**
     * Get all available themes.
     */
    function available_themes(): array
    {
        return config('themes.themes', []);
    }
}
