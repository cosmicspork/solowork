<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeSwitch()" :data-theme="currentTheme" x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Solowork') }}</title>

    {{-- Dynamic Theme CSS Variables --}}
    <style>
        :root {
            --font-mono: '{{ config('themes.font') }}', ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, 'DejaVu Sans Mono', monospace;
        }

        @foreach (config('themes.themes') as $themeKey => $theme)
        [data-theme="{{ $themeKey }}"] {
            @foreach ($theme['colors'] as $colorKey => $colorValue)
            --color-{{ $colorKey }}: {{ $colorValue }};
            @endforeach
        }
        @endforeach
    </style>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-mono antialiased bg-terminal-bg text-terminal">
    {{-- Skip to main content link for accessibility --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-terminal-bg focus:text-terminal border-terminal-border">
        Skip to main content
    </a>

    {{-- Theme Switcher Button --}}
    <div class="fixed top-4 right-4 z-40">
        <button
            @click="toggleTheme()"
            @keydown.window.ctrl.shift.t.prevent="toggleTheme()"
            type="button"
            class="px-3 py-2 text-xs uppercase tracking-wide bg-terminal-bg-secondary text-terminal border border-terminal-border hover:bg-terminal-hover focus:outline-none focus:ring-2 focus:ring-terminal-border"
            :aria-label="'Switch to ' + (currentTheme === 'dark' ? 'light' : 'dark') + ' theme'"
            accesskey="t"
        >
            <span x-text="currentTheme === 'dark' ? '☀ LIGHT' : '● DARK'"></span>
        </button>
    </div>

    {{-- Main Content --}}
    <main id="main-content">
        {{ $slot }}
    </main>

    {{-- Alpine.js Theme Switcher Component --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('themeSwitch', () => ({
                currentTheme: '{{ config('themes.default') }}',

                init() {
                    // Load theme from localStorage or use default
                    const savedTheme = localStorage.getItem('theme');
                    if (savedTheme && ['dark', 'light'].includes(savedTheme)) {
                        this.currentTheme = savedTheme;
                    }

                    // Apply theme immediately
                    this.applyTheme();
                },

                toggleTheme() {
                    this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                    this.applyTheme();
                },

                applyTheme() {
                    // Persist to localStorage
                    localStorage.setItem('theme', this.currentTheme);

                    // Apply to HTML element (already bound via :data-theme)
                    // The binding happens automatically through Alpine
                }
            }));
        });
    </script>
</body>
</html>
