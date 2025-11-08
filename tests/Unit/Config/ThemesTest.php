<?php

uses(Tests\TestCase::class);

/**
 * List of all required color keys that must be present in each theme.
 */
dataset('requiredColorKeys', [
    // Background colors
    'bg-primary',
    'bg-secondary',
    'bg-tertiary',
    'bg-hover',
    'bg-active',

    // Text colors
    'text-primary',
    'text-secondary',
    'text-muted',
    'text-disabled',

    // Border colors
    'border-primary',
    'border-secondary',
    'border-muted',

    // Accent colors
    'accent-primary',
    'accent-success',
    'accent-error',
    'accent-warning',
    'accent-info',

    // Link colors
    'link',
    'link-hover',
    'link-visited',

    // Input colors
    'input-bg',
    'input-border',
    'input-focus',
    'input-text',

    // Button colors
    'button-primary-bg',
    'button-primary-text',
    'button-secondary-bg',
    'button-secondary-text',
]);

it('has a themes config file', function () {
    $configPath = __DIR__.'/../../../config/themes.php';
    expect(file_exists($configPath))->toBeTrue('themes.php config file does not exist');
});

it('has required config structure', function () {
    $config = config('themes');

    expect($config)->toBeArray()
        ->toHaveKeys(['default', 'font', 'themes']);
});

it('defines at least one theme', function () {
    $themes = config('themes.themes');

    expect($themes)->toBeArray()
        ->not()->toBeEmpty();
});

it('has default theme in themes array', function () {
    $defaultTheme = config('themes.default');
    $themes = config('themes.themes');

    expect($themes)->toHaveKey($defaultTheme);
});

it('has required metadata for all themes', function () {
    $themes = config('themes.themes');

    foreach ($themes as $themeName => $themeData) {
        expect($themeData)
            ->toHaveKeys(['name', 'description', 'colors'], "Theme '{$themeName}' missing required metadata");
    }
});

it('has all required color keys in each theme', function () {
    $requiredKeys = [
        'bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-hover', 'bg-active',
        'text-primary', 'text-secondary', 'text-muted', 'text-disabled',
        'border-primary', 'border-secondary', 'border-muted',
        'accent-primary', 'accent-success', 'accent-error', 'accent-warning', 'accent-info',
        'link', 'link-hover', 'link-visited',
        'input-bg', 'input-border', 'input-focus', 'input-text',
        'button-primary-bg', 'button-primary-text', 'button-secondary-bg', 'button-secondary-text',
    ];

    $themes = config('themes.themes');

    foreach ($themes as $themeName => $themeData) {
        expect($themeData)->toHaveKey('colors');

        foreach ($requiredKeys as $colorKey) {
            expect($themeData['colors'])->toHaveKey($colorKey);
        }
    }
});

it('has no extra undefined color keys', function () {
    $requiredKeys = [
        'bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-hover', 'bg-active',
        'text-primary', 'text-secondary', 'text-muted', 'text-disabled',
        'border-primary', 'border-secondary', 'border-muted',
        'accent-primary', 'accent-success', 'accent-error', 'accent-warning', 'accent-info',
        'link', 'link-hover', 'link-visited',
        'input-bg', 'input-border', 'input-focus', 'input-text',
        'button-primary-bg', 'button-primary-text', 'button-secondary-bg', 'button-secondary-text',
    ];

    $themes = config('themes.themes');

    foreach ($themes as $themeName => $themeData) {
        $extraKeys = array_diff(array_keys($themeData['colors']), $requiredKeys);

        expect($extraKeys)->toBeEmpty(
            "Theme '{$themeName}' has unexpected color keys: ".implode(', ', $extraKeys)
        );
    }
});

it('has valid hex color codes', function () {
    $themes = config('themes.themes');

    foreach ($themes as $themeName => $themeData) {
        foreach ($themeData['colors'] as $colorKey => $colorValue) {
            expect($colorValue)->toMatch(
                '/^#[0-9A-Fa-f]{6}$/',
                "Theme '{$themeName}' has invalid hex color for '{$colorKey}': '{$colorValue}' (expected format: #RRGGBB)"
            );
        }
    }
});

it('has font config as string', function () {
    $font = config('themes.font');

    expect($font)->toBeString()
        ->not()->toBeEmpty();
});

it('has dark theme', function () {
    $themes = config('themes.themes');

    expect($themes)->toHaveKey('dark');
});

it('has light theme', function () {
    $themes = config('themes.themes');

    expect($themes)->toHaveKey('light');
});
