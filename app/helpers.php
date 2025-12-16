<?php

if (!function_exists('theme')) {
    /**
     * Get the current active theme instance
     */
    function theme(): \App\Contracts\ThemeInterface
    {
        return \App\Services\ThemeResolver::getCurrentTheme();
    }
}

if (!function_exists('theme_colors')) {
    /**
     * Get the current theme's color palette
     */
    function theme_colors(): array
    {
        return theme()->getColorPalette();
    }
}

if (!function_exists('theme_button_presets')) {
    /**
     * Get the current theme's button presets
     */
    function theme_button_presets(): array
    {
        return theme()->getButtonPresets();
    }
}

if (!function_exists('theme_text_presets')) {
    /**
     * Get the current theme's text presets
     */
    function theme_text_presets(): array
    {
        return theme()->getTextPresets();
    }
}

if (!function_exists('theme_padding_presets')) {
    /**
     * Get the current theme's padding presets
     */
    function theme_padding_presets(): array
    {
        return theme()->getPaddingPresets();
    }
}

// Multi-Theme System Helper Functions

if (!function_exists('theme_manager')) {
    /**
     * Get the theme manager instance
     */
    function theme_manager(): \App\Services\ThemeManager
    {
        return app(\App\Services\ThemeManager::class);
    }
}

if (!function_exists('active_theme')) {
    /**
     * Get the active theme instance
     */
    function active_theme(): \App\Models\Theme
    {
        return theme_manager()->getActiveTheme();
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Get theme asset URL with fallback
     */
    function theme_asset(string $path): string
    {
        return theme_manager()->themeAsset($path);
    }
}

if (!function_exists('theme_vite_assets')) {
    /**
     * Get theme Vite assets from manifest
     */
    function theme_vite_assets(array $entrypoints): array
    {
        return theme_manager()->getThemeViteAssets($entrypoints);
    }
}


if (!function_exists('has_theme_override')) {
    /**
     * Check if current theme has override for specific view
     */
    function has_theme_override(string $viewPath): bool
    {
        return active_theme()->hasViewOverride($viewPath);
    }
}