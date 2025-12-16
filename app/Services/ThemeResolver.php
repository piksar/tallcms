<?php

namespace App\Services;

use App\Contracts\ThemeInterface;
use App\Support\ThemeColors;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ThemeResolver
{
    /**
     * Get the currently active theme instance
     * 
     * This method delegates to the file-based theme system when available,
     * falls back to legacy class-based themes for backward compatibility.
     */
    public static function getCurrentTheme(): ThemeInterface
    {
        // Try to resolve from container first (file-based themes bind here)
        if (App::bound(ThemeInterface::class)) {
            return App::make(ThemeInterface::class);
        }
        
        // Check if ThemeManager exists and has an active theme
        if (App::bound('theme.manager')) {
            $themeManager = App::make('theme.manager');
            $activeTheme = $themeManager->getActiveTheme();
            
            // If it's a file-based theme, create FileBasedTheme instance
            if ($activeTheme && isset($activeTheme->path)) {
                return new FileBasedTheme($activeTheme);
            }
        }
        
        // Fallback to default theme if no theme is bound
        return new ThemeColors();
    }
    
    /**
     * Get the active theme name from configuration
     * 
     * Uses ThemeManager as single source of truth when available
     * to prevent stale reads in long-lived processes.
     */
    public static function getActiveThemeName(): string
    {
        // Use ThemeManager as single source of truth if available
        if (App::bound('theme.manager')) {
            $themeManager = App::make('theme.manager');
            $activeTheme = $themeManager->getActiveTheme();
            return $activeTheme ? $activeTheme->slug : 'default';
        }
        
        // Fallback to config for legacy compatibility
        return Config::get('theme.active', 'default');
    }
    
    /**
     * Check if a custom theme is active (not the default)
     */
    public static function isCustomThemeActive(): bool
    {
        return static::getActiveThemeName() !== 'default';
    }
    
    /**
     * Get available themes from configuration
     * 
     * @deprecated Use ThemeManager::getAvailableThemes() for file-based themes
     */
    public static function getAvailableThemes(): array
    {
        // Delegate to ThemeManager if available (file-based themes)
        if (App::bound('theme.manager')) {
            $themeManager = App::make('theme.manager');
            $themes = $themeManager->getAvailableThemes();
            
            // Convert to legacy format
            return $themes->mapWithKeys(function ($theme) {
                return [$theme->slug => FileBasedTheme::class];
            })->toArray();
        }
        
        // Fallback to legacy config-based themes
        return Config::get('theme.legacy.available', [
            'default' => ThemeColors::class,
        ]);
    }
    
    /**
     * Bind a specific theme to the container
     * 
     * @deprecated Use ThemeManager::setActiveTheme() for file-based themes
     */
    public static function bindTheme(string $themeName): void
    {
        // Delegate to ThemeManager if available (file-based themes)
        if (App::bound('theme.manager')) {
            $themeManager = App::make('theme.manager');
            if ($themeManager->setActiveTheme($themeName)) {
                return; // Successfully activated file-based theme
            }
        }
        
        // Fallback to legacy class-based theme binding
        $themes = Config::get('theme.legacy.available', []);
        
        if (!isset($themes[$themeName])) {
            throw new \InvalidArgumentException("Theme '{$themeName}' not found in available themes.");
        }
        
        $themeClass = $themes[$themeName];
        
        if (!class_exists($themeClass)) {
            throw new \InvalidArgumentException("Theme class '{$themeClass}' does not exist.");
        }
        
        if (!is_subclass_of($themeClass, ThemeInterface::class)) {
            throw new \InvalidArgumentException("Theme class '{$themeClass}' must implement ThemeInterface.");
        }
        
        App::bind(ThemeInterface::class, $themeClass);
    }
}