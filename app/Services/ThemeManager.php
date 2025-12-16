<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Events\ThemeActivating;
use App\Events\ThemeActivated;
use App\Events\ThemeInstalling;
use App\Events\ThemeInstalled;

class ThemeManager
{
    protected ?Theme $activeTheme = null;
    protected const CACHE_KEY = 'themes.discovered';

    /**
     * Get the currently active theme with safety fallback
     */
    public function getActiveTheme(): Theme
    {
        if ($this->activeTheme) {
            return $this->activeTheme;
        }

        $activeSlug = Config::get('theme.active', 'default');
        $requestedTheme = Theme::find($activeSlug);
        
        // Safety check: ensure the theme actually exists on disk
        if ($requestedTheme && $this->validateThemeExists($requestedTheme)) {
            $this->activeTheme = $requestedTheme;
        } else {
            // Active theme is missing or invalid - fallback safely
            $this->activeTheme = $this->getSafeDefaultTheme($activeSlug);
        }
        
        return $this->activeTheme;
    }

    /**
     * Validate that a theme exists and is functional
     */
    protected function validateThemeExists(Theme $theme): bool
    {
        return File::exists($theme->path) && 
               File::exists($theme->path . '/theme.json') &&
               is_readable($theme->path . '/theme.json');
    }

    /**
     * Get a safe default theme, preferring 'default' but falling back to any available theme
     */
    protected function getSafeDefaultTheme(string $requestedSlug): Theme
    {
        // Try to find a 'default' theme first
        $defaultTheme = Theme::find('default');
        if ($defaultTheme && $this->validateThemeExists($defaultTheme)) {
            $this->logThemeFallback($requestedSlug, 'default');
            return $defaultTheme;
        }
        
        // If no 'default' theme, use the first available theme
        $availableThemes = $this->getAvailableThemes();
        if ($availableThemes->isNotEmpty()) {
            $fallbackTheme = $availableThemes->first();
            $this->logThemeFallback($requestedSlug, $fallbackTheme->slug);
            return $fallbackTheme;
        }
        
        // Last resort: create a virtual default theme
        $this->logThemeFallback($requestedSlug, 'virtual-default');
        return $this->getDefaultTheme();
    }

    /**
     * Log theme fallback for debugging
     */
    protected function logThemeFallback(string $requestedSlug, string $fallbackSlug): void
    {
        Log::warning("Theme '{$requestedSlug}' not found or invalid. Falling back to '{$fallbackSlug}'.", [
            'requested_theme' => $requestedSlug,
            'fallback_theme' => $fallbackSlug,
            'themes_path' => base_path('themes'),
        ]);
    }

    /**
     * Set the active theme
     */
    public function setActiveTheme(string $slug): bool
    {
        $theme = Theme::find($slug);
        
        if (!$theme) {
            return false;
        }

        // Get previous theme for events
        $previousTheme = $this->activeTheme;

        // Dispatch theme activating event
        Event::dispatch(new ThemeActivating($theme, $previousTheme));

        // Update configuration file
        $configPath = config_path('theme.php');
        $config = File::exists($configPath) ? include $configPath : [];
        $config['active'] = $slug;
        
        File::put($configPath, "<?php\n\nreturn " . var_export($config, true) . ";\n");

        // Update in-memory config immediately
        Config::set('theme.active', $slug);
        
        // Clear config cache if it exists
        if (app()->configurationIsCached()) {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        }

        // Clear cached theme instance
        $this->activeTheme = null;
        
        // Bind file-based theme to ThemeInterface for color/text/padding presets
        $this->bindFileBasedTheme($theme);
        
        // Reset and refresh view paths with new theme
        $this->resetViewPaths();
        $this->registerThemeViewPaths();

        // Dispatch theme activated event
        Event::dispatch(new ThemeActivated($theme, $previousTheme));

        return true;
    }

    /**
     * Bind file-based theme to ThemeInterface for preset integration
     */
    protected function bindFileBasedTheme(Theme $theme): void
    {
        $fileBasedTheme = new \App\Services\FileBasedTheme($theme);
        
        // Rebind the ThemeInterface to use our file-based theme
        app()->bind(\App\Contracts\ThemeInterface::class, function () use ($fileBasedTheme) {
            return $fileBasedTheme;
        });
        
        // Update ThemeResolver to use the new theme
        app()->when(\App\Services\ThemeResolver::class)
            ->needs(\App\Contracts\ThemeInterface::class)
            ->give(function () use ($fileBasedTheme) {
                return $fileBasedTheme;
            });
    }

    /**
     * Get all available themes with caching and stale cache protection
     */
    public function getAvailableThemes(): Collection
    {
        // Check if caching is enabled
        if (Config::get('theme.cache_enabled', true)) {
            $cacheTtl = Config::get('theme.cache_ttl', 3600);
            $cachedThemes = Cache::remember(self::CACHE_KEY, $cacheTtl, function () {
                return $this->discoverThemes();
            });
            
            // Prune missing directories to prevent phantom themes
            return $this->pruneMissingThemes($cachedThemes);
        }
        
        return $this->discoverThemes();
    }

    /**
     * Remove themes from cache whose directories no longer exist
     */
    protected function pruneMissingThemes(Collection $themes): Collection
    {
        $validThemes = $themes->filter(function ($theme) {
            return File::exists($theme->path) && File::exists($theme->path . '/theme.json');
        });
        
        // If we pruned themes, update the cache
        if ($validThemes->count() !== $themes->count()) {
            $cacheTtl = Config::get('theme.cache_ttl', 3600);
            Cache::put(self::CACHE_KEY, $validThemes, $cacheTtl);
        }
        
        return $validThemes;
    }

    /**
     * Discover themes from filesystem
     */
    protected function discoverThemes(): Collection
    {
        $themesPath = base_path('themes');
        
        if (!File::exists($themesPath)) {
            return collect();
        }

        $themes = collect();
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $theme = Theme::fromDirectory($directory);
            if ($theme) {
                $themes->push($theme);
            }
        }

        return $themes;
    }

    /**
     * Clear theme discovery cache
     */
    public function clearCache(): bool
    {
        return Cache::forget(self::CACHE_KEY);
    }

    /**
     * Refresh theme cache
     */
    public function refreshCache(): Collection
    {
        $this->clearCache();
        return $this->getAvailableThemes();
    }

    /**
     * Install theme (create symlinks, build assets)
     */
    public function installTheme(string $slug): bool
    {
        $theme = Theme::find($slug);
        
        if (!$theme) {
            return false;
        }

        // Dispatch theme installing event
        Event::dispatch(new ThemeInstalling($theme));

        $success = true;

        // Create public symlink
        if (!$this->publishThemeAssets($theme)) {
            $success = false;
        }

        // Build theme assets if package.json exists
        $packageJsonPath = $theme->path . '/package.json';
        if (File::exists($packageJsonPath)) {
            if (!$this->buildThemeAssets($theme)) {
                $success = false;
            }
        }

        // Clear theme cache since we may have installed a new theme
        $this->clearCache();

        // Dispatch theme installed event
        Event::dispatch(new ThemeInstalled($theme, $success));

        return $success;
    }

    /**
     * Reset view paths to prevent bleeding from previous themes
     */
    protected function resetViewPaths(): void
    {
        $viewFinder = View::getFinder();
        $originalPaths = collect($viewFinder->getPaths())
            ->filter(function ($path) {
                // Remove any theme paths but keep original Laravel paths
                return !str_contains($path, '/themes/');
            })
            ->values()
            ->toArray();
            
        $viewFinder->setPaths($originalPaths);
    }

    /**
     * Register theme view paths with Laravel's view system
     */
    public function registerThemeViewPaths(): void
    {
        $activeTheme = $this->getActiveTheme();
        
        // Get theme hierarchy (child to parent)
        $hierarchy = $activeTheme->getHierarchy();
        
        // Register view paths in reverse order (parents first, then children)
        // This ensures child themes override parent templates
        foreach (array_reverse($hierarchy) as $theme) {
            $themeViewsPath = $theme->getViewPath();
            if (File::exists($themeViewsPath)) {
                View::prependLocation($themeViewsPath);
                
                // Also register view namespace for explicit theme loading
                $this->registerThemeNamespace($theme);
            }
        }
    }

    /**
     * Register theme view namespace
     */
    protected function registerThemeNamespace(Theme $theme): void
    {
        $namespace = "theme.{$theme->slug}";
        $viewPath = $theme->getViewPath();
        
        if (File::exists($viewPath)) {
            View::addNamespace($namespace, $viewPath);
        }
    }

    /**
     * Publish theme assets (create symlinks with fallback to copy)
     */
    public function publishThemeAssets(Theme $theme): bool
    {
        $themePublicPath = $theme->getPublicPath();
        $publicThemePath = public_path("themes/{$theme->slug}");

        // Remove existing symlink/directory
        if (File::exists($publicThemePath)) {
            if (is_link($publicThemePath)) {
                unlink($publicThemePath);
            } else {
                File::deleteDirectory($publicThemePath);
            }
        }

        // Create new symlink or copy
        if (File::exists($themePublicPath)) {
            // Ensure parent directory exists
            File::makeDirectory(dirname($publicThemePath), 0755, true, true);
            
            // Try symlink first, fallback to copy
            try {
                if (function_exists('symlink') && @symlink($themePublicPath, $publicThemePath)) {
                    return true;
                }
                
                // Fallback to recursive copy - check if it actually succeeds
                $copySuccess = File::copyDirectory($themePublicPath, $publicThemePath);
                
                if (!$copySuccess || !File::exists($publicThemePath)) {
                    \Log::warning("Failed to copy theme assets for {$theme->slug} to {$publicThemePath}");
                    return false;
                }
                
                return true;
                
            } catch (\Exception $e) {
                // Log the error but don't fail silently
                \Log::warning("Failed to publish theme assets for {$theme->slug}: " . $e->getMessage());
                return false;
            }
        }
        
        return true; // No assets to publish
    }

    /**
     * Build theme assets using npm
     */
    public function buildThemeAssets(Theme $theme): bool
    {
        $themePath = $theme->path;

        // Check if node_modules exists, if not run npm install
        if (!File::exists($themePath . '/node_modules')) {
            $result = Process::path($themePath)->run('npm install');
            if ($result->failed()) {
                return false;
            }
        }

        // Run build command
        $result = Process::path($themePath)->run('npm run build');
        return $result->successful();
    }


    /**
     * Get theme asset URL with parent fallback
     */
    public function themeAsset(string $path): string
    {
        $activeTheme = $this->getActiveTheme();
        
        // Try theme hierarchy (child to parent)
        foreach ($activeTheme->getHierarchy() as $theme) {
            $themeAssetPath = "themes/{$theme->slug}/{$path}";
            if (File::exists(public_path($themeAssetPath))) {
                return asset($themeAssetPath);
            }
        }
        
        // Fallback to default asset
        return asset($path);
    }

    /**
     * Get theme Vite assets from manifest with intelligent inheritance and dev-server support
     */
    public function getThemeViteAssets(array $entrypoints): array
    {
        $activeTheme = $this->getActiveTheme();
        
        // First check for dev server (hot reloading)
        $devServerAssets = $this->tryDevServerAssets($activeTheme, $entrypoints);
        if ($devServerAssets !== null) {
            return $devServerAssets;
        }
        
        // No dev server, use built assets with intelligent inheritance
        $assets = [];
        
        foreach ($entrypoints as $entry) {
            $assetFound = false;
            
            // Try each theme in hierarchy for this specific entry
            foreach ($activeTheme->getHierarchy() as $theme) {
                $manifestPath = public_path("themes/{$theme->slug}/build/manifest.json");
                
                if (!File::exists($manifestPath)) {
                    continue;
                }
                
                try {
                    $manifest = json_decode(File::get($manifestPath), true);
                    
                    if (isset($manifest[$entry])) {
                        // Found the entry in this theme's manifest
                        $this->addManifestEntry($assets, $manifest[$entry], $theme, $entry, $manifest);
                        $assetFound = true;
                        break; // Stop looking once we find the entry
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If not found in any theme manifest, try main app fallback
            if (!$assetFound) {
                $this->tryMainAppFallback($assets, $entry);
            }
        }
        
        return $this->deduplicateAssets($assets);
    }

    /**
     * Try to get assets from dev server (hot reloading)
     */
    protected function tryDevServerAssets(Theme $activeTheme, array $entrypoints): ?array
    {
        // Check for hot files in priority order:
        // 1. Theme-local hot file (theme dev server) - written to theme's public directory
        // 2. Main app hot file (app dev server)
        $themeHotFile = public_path("themes/{$activeTheme->slug}/hot");
        $appHotFile = storage_path('framework/vite.hot');
        
        $devServerUrl = null;
        $isThemeDevServer = false;
        
        if (File::exists($themeHotFile)) {
            $devServerUrl = trim(File::get($themeHotFile));
            $isThemeDevServer = true;
        } elseif (File::exists($appHotFile)) {
            $devServerUrl = trim(File::get($appHotFile));
            $isThemeDevServer = false;
        }
        
        if (!$devServerUrl) {
            return null; // No dev server running
        }
        
        // Build dev server assets
        $assets = [];
        
        foreach ($entrypoints as $entry) {
            $assets[] = [
                'url' => $devServerUrl . '/' . $entry,
                'type' => str_ends_with($entry, '.css') ? 'css' : 'js',
                'dev_server' => true,
                'entry' => $entry,
                'note' => $isThemeDevServer ? 'theme dev server' : 'main app dev server',
                'fallback' => !$isThemeDevServer // Mark app dev server as fallback
            ];
        }
        
        return $assets;
    }

    /**
     * Add manifest entry with all its dependencies
     */
    protected function addManifestEntry(array &$assets, array $assetInfo, Theme $theme, string $entry, array $manifest): void
    {
        // Add main asset file
        $assets[] = [
            'url' => asset("themes/{$theme->slug}/build/{$assetInfo['file']}"),
            'type' => str_ends_with($assetInfo['file'], '.css') ? 'css' : 'js',
            'entry' => $entry,
            'theme' => $theme->slug
        ];
        
        // Add any CSS imports from JS entries
        if (isset($assetInfo['css'])) {
            foreach ($assetInfo['css'] as $cssFile) {
                $assets[] = [
                    'url' => asset("themes/{$theme->slug}/build/{$cssFile}"),
                    'type' => 'css',
                    'entry' => $entry,
                    'theme' => $theme->slug
                ];
            }
        }
        
        // Add any imports - properly resolve manifest keys to actual files
        // Note: This resolves imports from the same manifest where the entry was found.
        // Split chunks defined only in parent themes won't be automatically pulled in.
        if (isset($assetInfo['imports'])) {
            foreach ($assetInfo['imports'] as $importKey) {
                // Resolve import key to actual manifest entry
                if (isset($manifest[$importKey])) {
                    $importInfo = $manifest[$importKey];
                    
                    // Add the main import file
                    $assets[] = [
                        'url' => asset("themes/{$theme->slug}/build/{$importInfo['file']}"),
                        'type' => str_ends_with($importInfo['file'], '.css') ? 'css' : 'js',
                        'entry' => $entry,
                        'theme' => $theme->slug,
                        'import' => true
                    ];
                    
                    // Also include any CSS from imports
                    if (isset($importInfo['css'])) {
                        foreach ($importInfo['css'] as $importCssFile) {
                            $assets[] = [
                                'url' => asset("themes/{$theme->slug}/build/{$importCssFile}"),
                                'type' => 'css',
                                'entry' => $entry,
                                'theme' => $theme->slug,
                                'import' => true
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Try fallback to main app for missing entries
     */
    protected function tryMainAppFallback(array &$assets, string $entry): void
    {
        $mainManifestPath = public_path('build/manifest.json');
        
        if (!File::exists($mainManifestPath)) {
            return;
        }
        
        try {
            $mainManifest = json_decode(File::get($mainManifestPath), true);
            
            // Special handling for blocks.css - fallback to app.css since blocks.css is imported there
            if ($entry === 'resources/css/blocks.css') {
                if (isset($mainManifest['resources/css/app.css'])) {
                    $assetInfo = $mainManifest['resources/css/app.css'];
                    $assets[] = [
                        'url' => asset("build/{$assetInfo['file']}"),
                        'type' => 'css',
                        'fallback' => true,
                        'entry' => $entry,
                        'note' => 'main app.css (includes blocks.css) fallback'
                    ];
                }
                return;
            }
            
            // For other entries, try direct fallback to main app manifest
            if (isset($mainManifest[$entry])) {
                $assetInfo = $mainManifest[$entry];
                
                // Add main asset file
                $assets[] = [
                    'url' => asset("build/{$assetInfo['file']}"),
                    'type' => str_ends_with($assetInfo['file'], '.css') ? 'css' : 'js',
                    'fallback' => true,
                    'entry' => $entry,
                    'note' => 'main app fallback'
                ];
                
                // Add any CSS imports from JS entries
                if (isset($assetInfo['css'])) {
                    foreach ($assetInfo['css'] as $cssFile) {
                        $assets[] = [
                            'url' => asset("build/{$cssFile}"),
                            'type' => 'css',
                            'fallback' => true,
                            'entry' => $entry,
                            'note' => 'main app CSS import fallback'
                        ];
                    }
                }
                
                // Add any imports - properly resolve manifest keys
                if (isset($assetInfo['imports'])) {
                    foreach ($assetInfo['imports'] as $importKey) {
                        if (isset($mainManifest[$importKey])) {
                            $importInfo = $mainManifest[$importKey];
                            $assets[] = [
                                'url' => asset("build/{$importInfo['file']}"),
                                'type' => str_ends_with($importInfo['file'], '.css') ? 'css' : 'js',
                                'fallback' => true,
                                'entry' => $entry,
                                'import' => true,
                                'note' => 'main app import fallback'
                            ];
                        }
                    }
                }
            }
        } catch (\Exception) {
            // Ignore manifest parsing errors
        }
    }

    /**
     * Get default theme (fallback)
     */
    protected function getDefaultTheme(): Theme
    {
        // Create a default theme instance if no theme is found
        return new Theme([
            'name' => 'Default Theme',
            'slug' => 'default',
            'version' => '1.0.0',
            'description' => 'Default TallCMS theme',
            'author' => 'TallCMS',
            'tailwind' => [],
            'supports' => [],
            'build' => []
        ], base_path('themes/default'));
    }

    /**
     * Generate theme configuration file
     */
    public function generateThemeConfig(Theme $theme): array
    {
        return [
            'colors' => $theme->getTailwindConfig()['colors'] ?? [],
            'supports' => $theme->supports,
            'assets' => [
                'css' => $this->themeAsset('css/app.css'),
                'js' => $this->themeAsset('js/app.js'),
            ]
        ];
    }


    /**
     * Get themes directory path
     */
    public function getThemesPath(): string
    {
        return base_path('themes');
    }

    /**
     * Add static CSS fallback to ensure block styles are always available
     */
    protected function addStaticCssFallback(array &$fallbackAssets, string $reason): void
    {
        $staticCssPath = glob(public_path('build/assets/app-*.css'));
        if (!empty($staticCssPath)) {
            $cssFile = basename($staticCssPath[0]);
            $fallbackAssets[] = [
                'url' => asset("build/assets/{$cssFile}"),
                'type' => 'css',
                'fallback' => true,
                'note' => "static fallback for block styles ({$reason})"
            ];
        }
    }

    /**
     * Deduplicate assets by URL and type to prevent double-injection
     */
    protected function deduplicateAssets(array $assets): array
    {
        $seen = [];
        $deduplicated = [];
        
        foreach ($assets as $asset) {
            $key = $asset['url'] . '|' . $asset['type'];
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $asset;
            }
        }
        
        return $deduplicated;
    }
}