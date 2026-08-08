<?php

declare(strict_types=1);

/**
 * TallCMS Helper Functions
 *
 * These helper functions provide convenient access to TallCMS functionality.
 * They are designed to gracefully degrade when the theme or plugin system
 * is not configured (plugin mode with disabled themes/plugins).
 *
 * This file is autoloaded via composer.json autoload.files.
 */

// URL Helper Functions

if (! function_exists('tallcms_routes_prefix')) {
    /**
     * Get the configured routes prefix for CMS frontend routes
     *
     * In plugin mode, CMS routes can be prefixed (e.g., '/cms') to avoid
     * conflicts with the host application's routes.
     *
     * @return string The routes prefix (without leading/trailing slashes), or empty string
     */
    function tallcms_routes_prefix(): string
    {
        return trim(config('tallcms.plugin_mode.routes_prefix') ?? '', '/');
    }
}

if (! function_exists('tallcms_home_url')) {
    /**
     * Get the CMS homepage URL, respecting routes prefix
     *
     * @return string The full URL to the CMS homepage
     */
    function tallcms_home_url(): string
    {
        $prefix = tallcms_routes_prefix();

        return url($prefix ? '/'.$prefix : '/');
    }
}

if (! function_exists('tallcms_page_url')) {
    /**
     * Get a CMS page URL by slug, respecting routes prefix
     *
     * @param  string  $slug  The page slug (with or without leading slash)
     * @return string The full URL to the page
     */
    function tallcms_page_url(string $slug): string
    {
        $prefix = tallcms_routes_prefix();
        // Normalize slug: remove leading/trailing slashes
        $slug = trim($slug, '/');

        $path = $prefix ? '/'.$prefix.'/'.$slug : '/'.$slug;

        return url($path);
    }
}

// Filament Panel Helper Functions

if (! function_exists('tallcms_panel_url')) {
    /**
     * Get the full URL to the Filament admin panel, optionally with a sub-path.
     *
     * @param  string  $path  Optional sub-path to append (e.g., 'cms-pages/create')
     * @return string The full URL to the panel
     */
    function tallcms_panel_url(string $path = ''): string
    {
        $panelPath = trim(config('tallcms.filament.panel_path', 'admin'), '/');
        $path = trim($path, '/');

        $fullPath = $panelPath !== '' ? '/'.$panelPath : '';
        if ($path !== '') {
            $fullPath .= '/'.$path;
        }

        return url($fullPath ?: '/');
    }
}

if (! function_exists('tallcms_panel_route')) {
    /**
     * Build a Filament route name dynamically using the configured panel ID.
     *
     * @param  string  $suffix  The route suffix (e.g., 'pages.plugin-licenses')
     * @param  mixed  $parameters  Optional route parameters
     * @return string The resolved route URL
     */
    function tallcms_panel_route(string $suffix, mixed $parameters = []): string
    {
        $panelId = config('tallcms.filament.panel_id', 'admin');

        return route("filament.{$panelId}.{$suffix}", $parameters);
    }
}

// Theme Helper Functions

if (! function_exists('theme')) {
    /**
     * Get the current active theme instance
     */
    function theme(): \TallCms\Cms\Contracts\ThemeInterface
    {
        return \TallCms\Cms\Services\ThemeResolver::getCurrentTheme();
    }
}

if (! function_exists('theme_colors')) {
    /**
     * Get the current theme's color palette
     *
     * @return array<string, mixed>
     */
    function theme_colors(): array
    {
        return theme()->getColorPalette();
    }
}

if (! function_exists('theme_button_presets')) {
    /**
     * Get the current theme's button presets
     *
     * @return array<string, mixed>
     */
    function theme_button_presets(): array
    {
        return theme()->getButtonPresets();
    }
}

if (! function_exists('theme_text_presets')) {
    /**
     * Get the current theme's text presets
     *
     * Returns sensible defaults if no theme is active (plugin mode).
     *
     * @return array<string, mixed>
     */
    function theme_text_presets(): array
    {
        // Graceful degradation for plugin mode without themes enabled
        if (! app()->bound('theme.manager') ||
            ! config('tallcms.plugin_mode.themes_enabled', true)) {
            return [
                'primary' => [
                    'heading' => '#111827',
                    'description' => '#4b5563',
                    'link' => '#2563eb',
                    'link_hover' => '#1d4ed8',
                ],
            ];
        }

        return theme()->getTextPresets();
    }
}

if (! function_exists('theme_padding_presets')) {
    /**
     * Get the current theme's padding presets
     *
     * @return array<string, mixed>
     */
    function theme_padding_presets(): array
    {
        return theme()->getPaddingPresets();
    }
}

// Multi-Theme System Helper Functions

if (! function_exists('theme_manager')) {
    /**
     * Get the theme manager instance
     */
    function theme_manager(): \TallCms\Cms\Services\ThemeManager
    {
        return app(\TallCms\Cms\Services\ThemeManager::class);
    }
}

if (! function_exists('active_theme')) {
    /**
     * Get the active theme instance
     */
    function active_theme(): ?\TallCms\Cms\Models\Theme
    {
        return theme_manager()->getActiveTheme();
    }
}

if (! function_exists('daisyui_dark_preset')) {
    /**
     * Get the DaisyUI preset that represents the "dark" option
     */
    function daisyui_dark_preset(): ?string
    {
        return active_theme()?->getDaisyUIPrefersDark();
    }
}

if (! function_exists('daisyui_presets')) {
    /**
     * Get all available presets for theme-controller
     *
     * @return array<string>
     */
    function daisyui_presets(): array
    {
        return active_theme()?->getDaisyUIPresets() ?? ['light'];
    }
}

if (! function_exists('daisyui_default_preset')) {
    /**
     * Get the admin-configured default daisyUI preset for the active theme
     *
     * Validates the stored value against the active theme's available presets.
     * Falls back to the theme.json default if stored value is invalid or missing.
     */
    function daisyui_default_preset(): string
    {
        $theme = active_theme();
        $fallback = $theme?->getDaisyUIPreset() ?? 'light';

        $stored = \TallCms\Cms\Models\SiteSetting::get('theme_default_preset');
        if ($stored && $stored !== '') {
            $available = $theme?->getDaisyUIPresets() ?? [];
            if (in_array($stored, $available, true)) {
                return $stored;
            }
        }

        return $fallback;
    }
}

if (! function_exists('supports_theme_controller')) {
    /**
     * Check if theme supports runtime theme switching
     */
    function supports_theme_controller(): bool
    {
        return active_theme()?->supportsThemeController() ?? false;
    }
}

if (! function_exists('tallcms_show_theme_switcher')) {
    function tallcms_show_theme_switcher(): bool
    {
        if (! (active_theme()?->supportsThemeController() ?? false)) {
            return false;
        }

        return (bool) \TallCms\Cms\Models\SiteSetting::get('show_theme_switcher', true);
    }
}

if (! function_exists('tallcms_show_search')) {
    function tallcms_show_search(): bool
    {
        if (! (active_theme()?->supportsSearch() ?? false)) {
            return false;
        }

        if (! config('tallcms.search.enabled', true)) {
            return false;
        }

        return (bool) \TallCms\Cms\Models\SiteSetting::get('show_search', true);
    }
}

if (! function_exists('tallcms_show_language_dropdown')) {
    function tallcms_show_language_dropdown(): bool
    {
        if (! (active_theme()?->supportsLanguageSwitcher() ?? false)) {
            return false;
        }

        if (! tallcms_i18n_enabled()) {
            return false;
        }

        return (bool) \TallCms\Cms\Models\SiteSetting::get('show_language_dropdown', true);
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Get theme asset URL with fallback
     *
     * @param  string  $path  Path to the asset relative to theme's public directory
     * @return string The URL to the asset
     */
    function theme_asset(string $path): string
    {
        return theme_manager()->themeAsset($path);
    }
}

if (! function_exists('theme_vite_assets')) {
    /**
     * Get theme Vite assets from manifest
     *
     * @param  array<string>  $entrypoints  List of Vite entrypoints
     * @return array<string, string> Resolved asset URLs
     */
    function theme_vite_assets(array $entrypoints): array
    {
        return theme_manager()->getThemeViteAssets($entrypoints);
    }
}

if (! function_exists('has_theme_override')) {
    /**
     * Check if current theme has override for specific view
     *
     * @param  string  $viewPath  Path to the view to check
     * @return bool True if theme has an override
     */
    function has_theme_override(string $viewPath): bool
    {
        return active_theme()->hasViewOverride($viewPath);
    }
}

// AWS / Storage Helper Functions

if (! function_exists('cms_media_disk')) {
    /**
     * Get the disk name for CMS media uploads
     *
     * Returns 's3' if S3-compatible storage is configured, otherwise 'public'.
     * Supports multiple configuration scenarios:
     * - Explicit FILESYSTEM_DISK=s3 in environment
     * - AWS S3 with static credentials
     * - IAM roles / instance profiles (no static keys)
     * - S3-compatible providers with custom endpoints
     *
     * @return string The disk name ('s3' or 'public')
     */
    function cms_media_disk(): string
    {
        // Explicit package-level override takes highest priority
        $configured = config('tallcms.media.disk');
        if (! empty($configured)) {
            return $configured;
        }

        // Auto-detect: check if FILESYSTEM_DISK is explicitly set to s3
        $defaultDisk = config('filesystems.default');
        if ($defaultDisk === 's3') {
            return 's3';
        }

        // Fallback: check if S3 bucket is configured (supports IAM roles)
        $bucket = config('filesystems.disks.s3.bucket');
        if (! empty($bucket)) {
            return 's3';
        }

        return 'public';
    }
}

if (! function_exists('cms_media_visibility')) {
    /**
     * Get the visibility setting for CMS media uploads
     *
     * @return string The visibility setting
     */
    function cms_media_visibility(): string
    {
        return 'public';
    }
}

if (! function_exists('cms_uses_s3')) {
    /**
     * Check if CMS is configured to use S3 for media storage
     *
     * @return bool True if using S3 storage
     */
    function cms_uses_s3(): bool
    {
        $disk = cms_media_disk();

        return $disk === 's3'
            || config("filesystems.disks.{$disk}.driver") === 's3';
    }
}

// Menu Helper Functions

if (! function_exists('isMenuItemActive')) {
    /**
     * Check if a menu item URL matches the current request
     */
    function isMenuItemActive(?string $itemUrl): bool
    {
        if (empty($itemUrl)) {
            return false;
        }

        // Check if item URL is external (different host)
        $itemHost = parse_url($itemUrl, PHP_URL_HOST);
        if ($itemHost && $itemHost !== request()->getHost()) {
            return false;
        }

        $currentUrl = request()->url();
        $currentPath = request()->path();

        // Normalize the item URL
        $itemUrl = rtrim($itemUrl, '/');
        $itemPath = parse_url($itemUrl, PHP_URL_PATH) ?? '/';
        $itemPath = rtrim($itemPath, '/') ?: '/';

        // Normalize current path
        $currentPath = '/'.ltrim($currentPath, '/');
        $currentPath = rtrim($currentPath, '/') ?: '/';

        // Exact match
        if ($currentUrl === $itemUrl || $currentPath === $itemPath) {
            return true;
        }

        // Homepage special case
        if ($itemPath === '/' || $itemPath === '/home') {
            return $currentPath === '/' || $currentPath === '/home';
        }

        return false;
    }
}

if (! function_exists('buildMenuItemArray')) {
    /**
     * Recursively build menu item array with children
     */
    function buildMenuItemArray($item, bool $resolveActiveState = true): array
    {
        $url = $item->getResolvedUrl();
        $children = $item->children->map(function ($child) use ($resolveActiveState) {
            return buildMenuItemArray($child, $resolveActiveState);
        })->toArray();

        // Check if this item is active
        $isActive = $resolveActiveState ? isMenuItemActive($url) : false;

        // Check if any child is active (for parent highlighting)
        $hasActiveChild = $resolveActiveState
            ? collect($children)->contains(function ($child) {
                return $child['is_active'] || $child['has_active_child'];
            })
            : false;

        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $url,
            'type' => $item->type,
            'target' => app('menu.url.resolver')->getTargetAttribute($item),
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'is_active' => $isActive,
            'has_active_child' => $hasActiveChild,
            'children' => $children,
        ];
    }
}

if (! function_exists('applyMenuActiveState')) {
    /**
     * Apply request-specific active state to a cached menu item array.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    function applyMenuActiveState(array $item): array
    {
        $children = array_map(
            fn (array $child): array => applyMenuActiveState($child),
            $item['children'] ?? [],
        );

        $item['is_active'] = isMenuItemActive($item['url'] ?? null);
        $item['has_active_child'] = collect($children)->contains(function (array $child): bool {
            return ($child['is_active'] ?? false) || ($child['has_active_child'] ?? false);
        });
        $item['children'] = $children;

        return $item;
    }
}

if (! function_exists('menu')) {
    /**
     * Get a menu by location with resolved URLs
     */
    function menu(string $location): ?array
    {
        $request = request();

        $cacheKey = implode('|', [
            $location,
            app()->getLocale(),
            $request->getHost(),
            $request->path(),
        ]);

        $resolvedMenus = $request->attributes->get('tallcms.resolved_menus', []);

        if (array_key_exists($cacheKey, $resolvedMenus)) {
            return $resolvedMenus[$cacheKey];
        }

        $persistentCacheKey = implode('|', [
            'tallcms.menu',
            $location,
            app()->getLocale(),
            $request->getHost(),
        ]);

        $cachedMenu = \TallCms\Cms\Support\MenuCache::remember($persistentCacheKey, now()->addHour(), function () use ($location): ?array {
            $menu = \TallCms\Cms\Models\TallcmsMenu::byLocation($location);

            if (! $menu) {
                return null;
            }

            // Get all menu items for this menu and build the tree structure
            $items = $menu->allItems()
                ->where('is_active', true)
                ->with('page')
                ->defaultOrder()
                ->get()
                ->toTree();

            return $items->map(function ($item) {
                return buildMenuItemArray($item, false);
            })->toArray();
        });

        $resolvedMenus[$cacheKey] = $cachedMenu === null
            ? null
            : array_map(fn (array $item): array => applyMenuActiveState($item), $cachedMenu);

        $request->attributes->set('tallcms.resolved_menus', $resolvedMenus);

        return $resolvedMenus[$cacheKey];
    }
}

if (! function_exists('render_menu')) {
    /**
     * Render a menu as HTML
     */
    function render_menu(string $location, array $options = []): string
    {
        $menu = menu($location);

        if (! $menu) {
            return '';
        }

        $ulClass = $options['ul_class'] ?? 'menu';
        $liClass = $options['li_class'] ?? 'menu-item';
        $linkClass = $options['link_class'] ?? 'menu-link';

        $html = "<ul class=\"{$ulClass}\">";

        foreach ($menu as $item) {
            $html .= render_menu_item($item, $liClass, $linkClass);
        }

        $html .= '</ul>';

        return $html;
    }
}

if (! function_exists('render_menu_item')) {
    /**
     * Render a single menu item
     */
    function render_menu_item(array $item, string $liClass = '', string $linkClass = ''): string
    {
        $hasChildren = ! empty($item['children']);
        $liClass = trim($liClass.($hasChildren ? ' has-children' : ''));

        if ($item['css_class']) {
            $liClass = trim($liClass.' '.$item['css_class']);
        }

        $html = "<li class=\"{$liClass}\">";

        if ($item['url']) {
            $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $icon = $item['icon'] ? "<i class=\"{$item['icon']}\"></i> " : '';

            $html .= "<a href=\"{$item['url']}\" class=\"{$linkClass}\"{$target}>";
            $html .= $icon.htmlspecialchars($item['label']);
            $html .= '</a>';
        } else {
            // Header/separator items without links
            $html .= "<span class=\"{$linkClass}\">".htmlspecialchars($item['label']).'</span>';
        }

        if ($hasChildren) {
            $html .= '<ul class="submenu">';
            foreach ($item['children'] as $child) {
                $html .= render_menu_item($child, 'submenu-item', $linkClass);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }
}

// Plugin License Helper Functions

if (! function_exists('plugin_is_licensed')) {
    /**
     * Check if a plugin has a valid license
     *
     * This performs the full license validation check (database + API if needed).
     * Use this for gating premium features.
     *
     * @param  string  $pluginSlug  The plugin's license slug (e.g., 'tallcms-pro')
     * @return bool True if the plugin has a valid license
     */
    function plugin_is_licensed(string $pluginSlug): bool
    {
        try {
            $licenseService = app(\TallCms\Cms\Services\PluginLicenseService::class);

            return $licenseService->isValid($pluginSlug);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('plugin_has_been_licensed')) {
    /**
     * Check if a plugin has ever been licensed (for watermark logic)
     *
     * Returns true if the plugin has been licensed at any point, even if
     * the license has since expired. Use this for watermark display logic
     * where you want to hide watermarks once a user has paid.
     *
     * Note: Returns false after license deactivation (user transferred license).
     *
     * @param  string  $pluginSlug  The plugin's license slug (e.g., 'tallcms-pro')
     * @return bool True if the plugin has ever been licensed
     */
    function plugin_has_been_licensed(string $pluginSlug): bool
    {
        try {
            $licenseService = app(\TallCms\Cms\Services\PluginLicenseService::class);

            return $licenseService->hasEverBeenLicensed($pluginSlug);
        } catch (\Throwable) {
            return false;
        }
    }
}

// CMS Content Helper Functions

if (! function_exists('cms_post_url')) {
    /**
     * Generate URL for a post within a parent page context
     *
     * Automatically handles:
     * - Localized slugs when i18n is enabled
     * - Routes prefix in plugin mode
     * - Locale prefix when url_strategy is 'prefix'
     *
     * @param  \TallCms\Cms\Models\CmsPost  $post  The post to generate URL for
     * @param  string  $parentSlug  The parent page slug (e.g., 'blog')
     * @return string The full URL to the post
     */
    function cms_post_url(\TallCms\Cms\Models\CmsPost $post, string $parentSlug): string
    {
        // Get the localized post slug
        $postSlug = tallcms_i18n_enabled()
            ? ($post->getTranslation('slug', app()->getLocale(), false) ?? $post->slug)
            : $post->slug;

        $slug = trim($parentSlug, '/').'/'.$postSlug;

        return tallcms_localized_url($slug);
    }
}

// SPA Mode Helper Functions

if (! function_exists('tallcms_slug_to_anchor')) {
    /**
     * Convert a page slug to a valid HTML anchor ID for SPA mode.
     * Replaces slashes with hyphens and appends page ID for uniqueness.
     *
     * Format: {slug-with-hyphens}-{page_id}
     * Examples:
     *   - 'about' (ID 5) → 'about-5'
     *   - 'about/team' (ID 42) → 'about-team-42'
     *
     * IMPORTANT: The page ID suffix prevents anchor collisions when multiple
     * pages have similar slugs (e.g., 'services' and 'about/services').
     * Any manually created anchor links in content must use this format.
     *
     * @param  string  $slug  The page slug (e.g., 'about/team')
     * @param  int  $pageId  The page ID for collision prevention
     * @return string Valid anchor ID (e.g., 'about-team-42')
     */
    function tallcms_slug_to_anchor(string $slug, int $pageId): string
    {
        return str_replace('/', '-', $slug).'-'.$pageId;
    }
}

// Internationalization (i18n) Helper Functions

if (! function_exists('tallcms_i18n_config')) {
    /**
     * Get i18n config value, checking SiteSetting first, then config.
     * This bridges admin UI settings to runtime configuration.
     *
     * @param  string  $key  Config key (e.g., 'enabled', 'default_locale')
     * @param  mixed  $default  Default value if not found
     * @return mixed The config value
     */
    function tallcms_i18n_config(string $key, mixed $default = null): mixed
    {
        // Map config keys to SiteSetting keys
        $settingMap = [
            'enabled' => 'i18n_enabled',
            'default_locale' => 'default_locale',
            'hide_default_locale' => 'hide_default_locale',
        ];

        if (isset($settingMap[$key])) {
            $settingKey = $settingMap[$key];
            // Wrap in try-catch for when table doesn't exist (e.g., during tests)
            try {
                $dbValue = \TallCms\Cms\Models\SiteSetting::get($settingKey);

                if ($dbValue !== null) {
                    return $dbValue;
                }
            } catch (\Throwable) {
                // Table doesn't exist yet, fall through to config
            }
        }

        // Fall back to config
        return config("tallcms.i18n.{$key}", $default);
    }
}

if (! function_exists('tallcms_localized_url')) {
    /**
     * Generate a locale-aware URL.
     * - prefix strategy: /es-MX/about (BCP-47 format in path)
     * - none strategy: /about?lang=es-MX (BCP-47 format in query param)
     *
     * In plugin mode with routes_prefix set, URLs will be prefixed:
     * - /cms/es-MX/about (when routes_prefix='cms')
     *
     * IMPORTANT: This function expects a clean content slug (e.g., 'about', 'blog/post-title'),
     * NOT a pre-built URL path. Passing a URL that already contains locale or routes prefixes
     * will result in double-prefixing. Use validation rules (UniqueTranslatableSlug, reserved
     * slugs) to prevent slugs that conflict with locale codes.
     *
     * @param  string  $slug  The page/post slug (clean, without prefixes)
     * @param  string|null  $locale  Internal locale code (es_mx). Uses current if null.
     * @return string Full URL with locale indicator
     */
    function tallcms_localized_url(string $slug, ?string $locale = null): string
    {
        $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
        $locale = $locale ?? app()->getLocale();
        $default = $registry->getDefaultLocale();
        $hideDefault = tallcms_i18n_config('hide_default_locale', true);
        $urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');

        // Get routes prefix for plugin mode
        $routesPrefixRaw = config('tallcms.plugin_mode.routes_prefix', '');
        $routesPrefix = $routesPrefixRaw ? '/'.trim($routesPrefixRaw, '/') : '';

        // Normalize slug - remove leading/trailing slashes
        $slug = trim($slug, '/');
        $baseSlug = $slug === '' ? '' : '/'.$slug;

        // If i18n disabled, return simple URL with routes prefix
        if (! tallcms_i18n_config('enabled', false)) {
            return $routesPrefix.($baseSlug ?: '/');
        }

        // Strategy: 'none' - use query parameter ?lang=
        if ($urlStrategy === 'none') {
            $baseUrl = $routesPrefix.($baseSlug ?: '/');
            // Skip lang param for default locale if hideDefault enabled
            if ($hideDefault && $locale === $default) {
                return $baseUrl;
            }
            // Append ?lang= with BCP-47 format
            $bcp47 = \TallCms\Cms\Services\LocaleRegistry::toBcp47($locale);

            return $baseUrl.'?lang='.$bcp47;
        }

        // Strategy: 'prefix' - use path prefix
        $localePrefix = '';
        if (! $hideDefault || $locale !== $default) {
            // Convert internal format (es_mx) to BCP-47 (es-MX) for URL
            $localePrefix = '/'.\TallCms\Cms\Services\LocaleRegistry::toBcp47($locale);
        }

        // Build URL: routes_prefix + locale_prefix + slug
        $url = $routesPrefix.$localePrefix.$baseSlug;

        return $url ?: '/';
    }
}

if (! function_exists('tallcms_resolve_custom_url')) {
    /**
     * Resolve a custom URL, handling both clean slugs and already-prefixed paths.
     *
     * This function is designed for user-entered URLs in menus and buttons where:
     * - User may enter a clean slug (e.g., 'about', 'blog/post')
     * - User may enter an absolute path with prefixes (e.g., '/cms/zh-CN/page')
     *
     * A path is considered "fully qualified" only if it has ALL required prefixes:
     * - routes_prefix (if configured in plugin mode)
     * - locale prefix (if i18n enabled with prefix strategy and hide_default_locale=false)
     *
     * Paths missing required prefixes are normalized through tallcms_localized_url().
     *
     * @param  string  $url  The custom URL or slug
     * @return string Resolved URL
     */
    function tallcms_resolve_custom_url(string $url): string
    {
        $url = trim($url);

        if ($url === '' || $url === '/') {
            return tallcms_localized_url('/');
        }

        // Absolute paths (starting with /) may already be prefixed
        if (str_starts_with($url, '/')) {
            $routesPrefix = trim(config('tallcms.plugin_mode.routes_prefix', ''), '/');
            $pathWithoutSlash = ltrim($url, '/');
            $i18nEnabled = tallcms_i18n_config('enabled', false);
            $urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');
            $hideDefault = tallcms_i18n_config('hide_default_locale', true);

            // Track what we find in the path
            $hasRoutesPrefix = false;
            $hasLocalePrefix = false;
            $foundLocale = null;
            $slugAfterPrefixes = $pathWithoutSlash;

            // Step 1: Check for routes_prefix at the start
            if ($routesPrefix) {
                if (str_starts_with($pathWithoutSlash, $routesPrefix.'/')) {
                    $hasRoutesPrefix = true;
                    $slugAfterPrefixes = substr($pathWithoutSlash, strlen($routesPrefix) + 1);
                } elseif ($pathWithoutSlash === $routesPrefix) {
                    $hasRoutesPrefix = true;
                    $slugAfterPrefixes = '';
                }
            }

            // Step 2: Check for locale prefix (after routes_prefix if present, or at start)
            if ($i18nEnabled && $urlStrategy === 'prefix') {
                $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
                $pathToCheck = $slugAfterPrefixes;

                // Also check original path for locale-only URLs like /zh-CN/page
                if (! $hasRoutesPrefix) {
                    $pathToCheck = $pathWithoutSlash;
                }

                foreach ($registry->getLocaleCodes() as $localeCode) {
                    $bcp47 = \TallCms\Cms\Services\LocaleRegistry::toBcp47($localeCode);
                    if (str_starts_with($pathToCheck, $bcp47.'/')) {
                        $hasLocalePrefix = true;
                        $foundLocale = $localeCode;
                        $slugAfterPrefixes = substr($pathToCheck, strlen($bcp47) + 1);
                        break;
                    } elseif ($pathToCheck === $bcp47) {
                        $hasLocalePrefix = true;
                        $foundLocale = $localeCode;
                        $slugAfterPrefixes = '';
                        break;
                    }
                }
            }

            // Determine what's required
            $needsRoutesPrefix = ! empty($routesPrefix);
            $registry = $i18nEnabled ? app(\TallCms\Cms\Services\LocaleRegistry::class) : null;
            $defaultLocale = $registry?->getDefaultLocale();

            // Special case: hide_default_locale=true and URL has default locale prefix
            // These should be normalized to unprefixed URLs (e.g., /en/about → /about)
            if ($hideDefault && $hasLocalePrefix && $foundLocale === $defaultLocale) {
                // Strip the default locale prefix and rebuild
                return tallcms_localized_url($slugAfterPrefixes, $defaultLocale);
            }

            // For non-default locales, locale prefix is always needed when i18n prefix strategy is active
            $needsLocalePrefix = $i18nEnabled && $urlStrategy === 'prefix' && ! $hideDefault;

            // Case 1: Fully qualified (has all required prefixes)
            // For non-default locales with hide_default_locale=true, having locale prefix is correct
            if ($hasLocalePrefix && $foundLocale !== $defaultLocale) {
                // Non-default locale with prefix - check routes_prefix requirement
                if ($hasRoutesPrefix || ! $needsRoutesPrefix) {
                    return $url;
                }

                // Missing routes_prefix, rebuild with correct prefix
                return tallcms_localized_url($slugAfterPrefixes, $foundLocale);
            }

            // Case 2: No locale prefix and no routes prefix requirement issue
            if ((! $hasLocalePrefix || ! $needsLocalePrefix) &&
                ($hasRoutesPrefix || ! $needsRoutesPrefix)) {
                return $url;
            }

            // Case 3: Has locale but missing routes_prefix
            if ($hasLocalePrefix && $needsRoutesPrefix && ! $hasRoutesPrefix) {
                return tallcms_localized_url($slugAfterPrefixes, $foundLocale);
            }

            // Case 4: Has routes_prefix but missing required locale
            if ($hasRoutesPrefix && $needsLocalePrefix && ! $hasLocalePrefix) {
                return tallcms_localized_url($slugAfterPrefixes);
            }

            // Case 5: Neither prefix found - treat remaining as slug
            return tallcms_localized_url($slugAfterPrefixes);
        }

        // Relative path - treat as slug
        return tallcms_localized_url($url);
    }
}

if (! function_exists('tallcms_alternate_urls')) {
    /**
     * Get alternate URLs for all translations of a model.
     * Returns array keyed by internal locale code with BCP-47 formatted URLs.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model with HasTranslatableContent
     * @return array<string, string> [locale => url]
     */
    function tallcms_alternate_urls($model): array
    {
        $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
        $urls = [];

        foreach ($registry->getLocaleCodes() as $locale) {
            // Only include locales with actual translations
            $slug = $model->getTranslation('slug', $locale, false);
            if ($slug !== null) {
                $urls[$locale] = tallcms_localized_url($slug, $locale);
            }
        }

        return $urls;
    }
}

if (! function_exists('tallcms_current_locale')) {
    /**
     * Get current locale with i18n awareness.
     * Returns the app locale, which is set by SetLocaleMiddleware.
     *
     * @return string Current locale code
     */
    function tallcms_current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('tallcms_i18n_enabled')) {
    /**
     * Check if i18n is enabled.
     *
     * @return bool True if multilingual features are enabled
     */
    function tallcms_i18n_enabled(): bool
    {
        return (bool) tallcms_i18n_config('enabled', false);
    }
}

if (! function_exists('tallcms_search_url')) {
    /**
     * Get the search page URL respecting i18n and plugin mode prefixes.
     *
     * Returns the correct search URL based on current locale and route configuration.
     * Supports both url_strategy modes:
     * - 'prefix': /es/search or /cms/es/search
     * - 'none': /search?lang=es or /cms/search?lang=es
     *
     * @return string The search page URL
     */
    function tallcms_search_url(): string
    {
        // Build search path with proper prefixes
        $segments = [];

        // Add plugin mode routes prefix if applicable
        $routesPrefix = config('tallcms.plugin_mode.routes_prefix', '');
        if ($routesPrefix) {
            $segments[] = $routesPrefix;
        }

        $urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');

        // Add locale prefix if i18n is enabled with prefix strategy
        if (tallcms_i18n_enabled() && $urlStrategy === 'prefix') {
            $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
            $currentLocale = tallcms_current_locale();
            $defaultLocale = $registry->getDefaultLocale();
            $hideDefault = config('tallcms.i18n.hide_default_locale', true);

            // Add locale prefix unless it's hidden default
            if (! ($hideDefault && $currentLocale === $defaultLocale)) {
                $segments[] = \TallCms\Cms\Services\LocaleRegistry::toBcp47($currentLocale);
            }
        }

        $segments[] = 'search';

        $baseUrl = url(implode('/', $segments));

        // For 'none' strategy, append ?lang= query parameter
        if (tallcms_i18n_enabled() && $urlStrategy === 'none') {
            $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
            $currentLocale = tallcms_current_locale();
            $defaultLocale = $registry->getDefaultLocale();
            $hideDefault = config('tallcms.i18n.hide_default_locale', true);

            // Append ?lang= unless it's hidden default
            if (! ($hideDefault && $currentLocale === $defaultLocale)) {
                $bcp47 = \TallCms\Cms\Services\LocaleRegistry::toBcp47($currentLocale);

                return $baseUrl.'?lang='.$bcp47;
            }
        }

        return $baseUrl;
    }
}

if (! function_exists('tallcms_current_slug')) {
    /**
     * Extract the clean content slug from the current request path.
     *
     * Strips routes_prefix and locale prefix from the current URL to get
     * the actual content slug. Useful for language switchers and alternate URL generation.
     *
     * Examples:
     * - /cms/zh-CN/blog/post → blog/post
     * - /zh-CN/about → about
     * - /cms/about → about
     * - /about → about
     * - / or /cms or /zh-CN → '' (empty for homepage)
     *
     * @return string The clean content slug (without prefixes)
     */
    function tallcms_current_slug(): string
    {
        $path = trim(request()->path(), '/');

        if ($path === '' || $path === '/') {
            return '';
        }

        // Strip routes_prefix if present
        $routesPrefix = trim(config('tallcms.plugin_mode.routes_prefix', ''), '/');
        if ($routesPrefix && str_starts_with($path, $routesPrefix.'/')) {
            $path = substr($path, strlen($routesPrefix) + 1);
        } elseif ($routesPrefix && $path === $routesPrefix) {
            return '';
        }

        // Strip locale prefix if i18n is enabled with prefix strategy
        if (tallcms_i18n_config('enabled', false) && config('tallcms.i18n.url_strategy', 'prefix') === 'prefix') {
            $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
            foreach ($registry->getLocaleCodes() as $localeCode) {
                $bcp47 = \TallCms\Cms\Services\LocaleRegistry::toBcp47($localeCode);
                if (str_starts_with($path, $bcp47.'/')) {
                    $path = substr($path, strlen($bcp47) + 1);
                    break;
                } elseif ($path === $bcp47) {
                    return '';
                }
            }
        }

        return $path;
    }
}

if (! function_exists('tallcms_locale_label')) {
    /**
     * Get the display label (native name) for a locale code.
     *
     * @param  string  $localeCode  The locale code (internal or BCP-47 format)
     * @param  bool  $native  Whether to return native name (true) or English label (false)
     * @return string The locale label, or the code itself if not found
     */
    function tallcms_locale_label(string $localeCode, bool $native = true): string
    {
        $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
        $normalized = \TallCms\Cms\Services\LocaleRegistry::normalizeLocaleCode($localeCode);
        $locales = $registry->getLocales();

        if (isset($locales[$normalized])) {
            return $native
                ? ($locales[$normalized]['native'] ?? $locales[$normalized]['label'])
                : $locales[$normalized]['label'];
        }

        return $localeCode;
    }
}

if (! function_exists('tallcms_review_workflow_enabled')) {
    /**
     * Check if the review workflow is enabled for the current site.
     *
     * When disabled, all users can publish directly (Draft → Published).
     * When enabled, the submit → approve → publish flow applies.
     *
     * Defaults to OFF when multisite plugin is active (SaaS tenants publish directly),
     * ON when standalone (traditional editorial team).
     */
    function tallcms_review_workflow_enabled(): bool
    {
        // Determine default: multisite licensed & active → off, standalone → on.
        // Check if CmsPage has a site_id column AND the multisite scope is applied.
        // The resolver is bound unconditionally in register(), but scopes are only
        // registered in boot() after the license gate passes. Checking for the
        // scope class existence + hasGlobalScope ensures we detect actual activation.
        $multisiteActive = false;
        try {
            if (class_exists('Tallcms\Multisite\Scopes\SiteScope')) {
                $multisiteActive = \TallCms\Cms\Models\CmsPage::hasGlobalScope('Tallcms\Multisite\Scopes\SiteScope');
            }
        } catch (\Throwable) {
        }
        $default = ! $multisiteActive;

        try {
            $value = \TallCms\Cms\Models\SiteSetting::get('review_workflow_enabled');

            // If not explicitly set, use the default
            if ($value === null || $value === '') {
                return $default;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable) {
            return $default;
        }
    }
}

if (! function_exists('tallcms_base_url')) {
    /**
     * Get the canonical base URL for the current site context.
     *
     * In a web request, uses the request's scheme + host (correct for multisite).
     * In console/queue context, falls back to config('app.url').
     * Optionally accepts an explicit site_id to look up the domain.
     */
    function tallcms_base_url(?int $siteId = null): string
    {
        // Explicit site_id: look up the domain directly
        if ($siteId !== null) {
            try {
                $domain = \Illuminate\Support\Facades\DB::table('tallcms_sites')
                    ->where('id', $siteId)
                    ->value('domain');

                if ($domain) {
                    // Determine scheme: prefer HTTPS, fall back to current request scheme
                    $scheme = request()?->isSecure() ? 'https' : (str_starts_with(config('app.url', ''), 'https') ? 'https' : 'http');

                    return "{$scheme}://{$domain}";
                }
            } catch (\Throwable) {
            }
        }

        // Web request: use the actual request host (handles multisite domains)
        if (app()->runningInConsole() === false && request()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        // Console/queue fallback
        return rtrim(config('app.url', 'http://localhost'), '/');
    }
}

if (! function_exists('tallcms_multisite_active')) {
    /**
     * Check if the multisite plugin is actively running.
     *
     * Returns true only when the multisite plugin is installed, licensed,
     * and has registered its scopes (post-boot). Uses the same detection
     * pattern as tallcms_review_workflow_enabled().
     */
    function tallcms_multisite_active(): bool
    {
        try {
            if (class_exists('Tallcms\Multisite\Scopes\SiteScope')) {
                return \TallCms\Cms\Models\CmsPage::hasGlobalScope('Tallcms\Multisite\Scopes\SiteScope');
            }
        } catch (\Throwable) {
        }

        return false;
    }
}

// Admin UI localization

if (! function_exists('tallcms_label')) {
    /**
     * Resolve an admin resource label with optional config/plugin override.
     *
     * Literal overrides in config('tallcms.labels.*') win (white-label renames).
     * When the override is null/empty, the package translation is used
     * (__('tallcms::resources.{resource}.{facet}')), following APP_LOCALE.
     *
     * @param  string  $resource  Config key under tallcms.labels (e.g. pages, posts)
     * @param  string  $facet  singular|plural|navigation
     */
    function tallcms_label(string $resource, string $facet): string
    {
        $override = config("tallcms.labels.{$resource}.{$facet}");

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return (string) __("tallcms::resources.{$resource}.{$facet}");
    }
}

if (! function_exists('tallcms_nav_group')) {
    /**
     * Resolve an admin navigation group label with optional config override.
     *
     * @param  string  $group  Key under tallcms.navigation.groups (e.g. content, system)
     */
    function tallcms_nav_group(string $group): string
    {
        $override = config("tallcms.navigation.groups.{$group}");

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return (string) __("tallcms::navigation.groups.{$group}");
    }
}
