<?php

declare(strict_types=1);

namespace TallCms\Cms;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use TallCms\Cms\Filament\Pages\ApiTokens;
use TallCms\Cms\Filament\Pages\GlobalDefaults;
use TallCms\Cms\Filament\Pages\MenuItemsManager;
use TallCms\Cms\Filament\Pages\PluginLicenses;
use TallCms\Cms\Filament\Pages\PluginManager;
use TallCms\Cms\Filament\Pages\SeoSettings;
use TallCms\Cms\Filament\Pages\SystemUpdates;
use TallCms\Cms\Filament\Pages\ThemeManager;
use TallCms\Cms\Filament\Pages\UpdateManual;
use TallCms\Cms\Filament\Pages\UpdateProgress;
use TallCms\Cms\Filament\Pages\WebhookManager;
use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;
use TallCms\Cms\Filament\Resources\SiteResource\SiteResource;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;
use TallCms\Cms\Filament\Resources\Users\UserResource;
use TallCms\Cms\Filament\Widgets\ContentHealthWidget;
use TallCms\Cms\Filament\Widgets\DashboardSitePicker;
use TallCms\Cms\Filament\Widgets\MenuOverviewWidget;
use TallCms\Cms\Filament\Widgets\PluginUpdatesWidget;
use TallCms\Cms\Services\LocaleRegistry;

class TallCmsPlugin implements Plugin
{
    protected bool $hasCategories = true;

    protected bool $hasPages = true;

    protected bool $hasPosts = true;

    protected bool $hasComments = true;

    protected bool $hasContactSubmissions = true;

    protected bool $hasMedia = true;

    protected bool $hasMenus = true;

    protected bool $hasUsers = true;

    protected bool $hasSiteSettings = true;

    protected bool $hasGlobalDefaults = true;

    protected bool $hasSeoSettings = true;

    protected bool $hasPluginManager = true;

    protected bool $hasThemeManager = true;

    protected bool $hasSystemUpdates = true;

    protected bool $hasShieldPlugin = true;

    protected ?string $shieldNavigationGroup = null; // Set dynamically from config

    /** @var array<string, array{singular?: string, plural?: string, navigation?: string}> */
    protected array $labelOverrides = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'tallcms';
    }

    public function register(Panel $panel): void
    {
        foreach ($this->labelOverrides as $key => $labels) {
            foreach ($labels as $type => $value) {
                Config::set("tallcms.labels.{$key}.{$type}", $value);
            }
        }

        $panel
            ->resources($this->getResources())
            ->pages($this->getPages())
            ->widgets($this->getWidgets());

        // Register FilamentShieldPlugin for role/permission management
        if ($this->hasShieldPlugin) {
            $shieldPlugin = FilamentShieldPlugin::make();

            // Place Shield under System group
            $group = $this->shieldNavigationGroup ?? tallcms_nav_group('system');
            $shieldPlugin->navigationGroup($group);

            $panel->plugin($shieldPlugin);
        }

        // Register Spatie Translatable Plugin if i18n is enabled
        $this->registerTranslatablePlugin($panel);

        // Register Filament plugins from installed TallCMS plugins
        $this->registerInstalledPluginFilamentPlugins($panel);
    }

    /**
     * Register Lara Zeus Spatie Translatable Plugin.
     *
     * The plugin provides a locale switcher in the admin panel header,
     * allowing content editors to switch between languages when editing.
     *
     * Note: The plugin is ALWAYS registered because the Translatable trait
     * on Resources requires it. When i18n is disabled, only the default
     * locale is shown (no switcher visible).
     */
    protected function registerTranslatablePlugin(Panel $panel): void
    {
        try {
            // When i18n is enabled, get all locales from registry
            // When disabled, just use the app's default locale
            if (tallcms_i18n_enabled()) {
                $registry = app(LocaleRegistry::class);
                $locales = $registry->getLocaleCodes();
            } else {
                $locales = [config('app.locale', 'en')];
            }

            $panel->plugin(
                SpatieTranslatablePlugin::make()
                    ->defaultLocales($locales)
                    ->persist() // Remember user's selected language in session
                    ->getLocaleLabelUsing(function (string $locale): ?string {
                        // Use native labels from LocaleRegistry (e.g., "简体中文" instead of "Chinese (China)")
                        if (! tallcms_i18n_enabled()) {
                            return null; // Fall back to default behavior
                        }

                        $registry = app(LocaleRegistry::class);
                        $locales = $registry->getLocales();

                        if ($locales->has($locale)) {
                            $config = $locales->get($locale);

                            return $config['native'] ?? $config['label'] ?? null;
                        }

                        return null; // Fall back to default behavior
                    })
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to register Spatie Translatable Plugin', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register Filament plugins from installed TallCMS plugins.
     */
    protected function registerInstalledPluginFilamentPlugins(Panel $panel): void
    {
        // Only register if plugin system is enabled
        if (! $this->isPluginSystemEnabled()) {
            return;
        }

        try {
            $pluginManager = app(Services\PluginManager::class);
            $filamentPlugins = $pluginManager->getFilamentPlugins();

            foreach ($filamentPlugins as $filamentPlugin) {
                $panel->plugin($filamentPlugin);
            }
        } catch (\Throwable $e) {
            // Don't let plugin registration failures break the admin panel
            Log::warning('Failed to register installed plugin Filament plugins', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        // Set default navigation group order if no plugin (e.g. Multisite) has set it.
        // Filament's navigationGroups() appends, so we check if groups are already set.
        // MultisitePlugin sets groups with Platform first during register().
        if (empty($panel->getNavigationGroups())) {
            $panel->navigationGroups([
                \Filament\Navigation\NavigationGroup::make(fn (): string => tallcms_nav_group('content')),
                \Filament\Navigation\NavigationGroup::make(fn (): string => tallcms_nav_group('appearance')),
                \Filament\Navigation\NavigationGroup::make(fn (): string => tallcms_nav_group('configuration')),
                \Filament\Navigation\NavigationGroup::make(fn (): string => tallcms_nav_group('system')),
            ]);
        }
    }

    /**
     * Get all enabled CMS pages.
     *
     * @return array<class-string>
     */
    public function getPages(): array
    {
        $pages = [];

        if ($this->hasGlobalDefaults) {
            $pages[] = GlobalDefaults::class;
        }

        if ($this->hasSeoSettings) {
            $pages[] = SeoSettings::class;
        }

        // MenuItemsManager is always included when menus are enabled
        // (it's hidden from nav, used by TallcmsMenuResource)
        if ($this->hasMenus) {
            $pages[] = MenuItemsManager::class;
        }

        // Plugin Manager: visible in standalone mode OR when explicitly enabled in plugin mode
        if ($this->hasPluginManager && $this->isPluginSystemEnabled()) {
            $pages[] = PluginManager::class;
            $pages[] = PluginLicenses::class;
        }

        // Theme Manager: visible in standalone mode OR when explicitly enabled in plugin mode
        if ($this->hasThemeManager && $this->isThemeSystemEnabled()) {
            $pages[] = ThemeManager::class;
        }

        // System Updates only available in standalone mode
        // (requires TallCmsUpdater service and GitHub release infrastructure)
        if ($this->isStandaloneMode() && $this->hasSystemUpdates) {
            $pages[] = SystemUpdates::class;
            $pages[] = UpdateManual::class;
            $pages[] = UpdateProgress::class;
        }

        // API Token management: only when API is enabled
        if (config('tallcms.api.enabled', false)) {
            $pages[] = ApiTokens::class;
        }

        // Webhook management: only when webhooks are enabled
        if (config('tallcms.webhooks.enabled', false)) {
            $pages[] = WebhookManager::class;
        }

        return $pages;
    }

    /**
     * Check if the plugin system is enabled.
     * Enabled by default in both standalone and plugin modes.
     */
    protected function isPluginSystemEnabled(): bool
    {
        return config('tallcms.plugin_mode.plugins_enabled', true);
    }

    /**
     * Check if the theme system is enabled.
     * Enabled by default in both standalone and plugin modes.
     */
    protected function isThemeSystemEnabled(): bool
    {
        return config('tallcms.plugin_mode.themes_enabled', true);
    }

    /**
     * Check if running in standalone mode.
     */
    protected function isStandaloneMode(): bool
    {
        // 1. Explicit config takes precedence
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        // 2. Auto-detect: standalone has .tallcms-standalone marker
        return file_exists(base_path('.tallcms-standalone'));
    }

    /**
     * Get all enabled CMS widgets.
     *
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        // Picker is registered unconditionally; its canView() guards on
        // multisite plugin presence + user-accessible sites with try/catch
        // so install / migration / DB-unavailable contexts can't blow up
        // panel boot.
        $widgets = [DashboardSitePicker::class];

        if ($this->hasPosts) {
            $widgets[] = ContentHealthWidget::class;
        }

        if ($this->hasMenus) {
            $widgets[] = MenuOverviewWidget::class;
        }

        // Plugin updates widget: only when plugin system is enabled
        if ($this->hasPluginManager && $this->isPluginSystemEnabled()) {
            $widgets[] = PluginUpdatesWidget::class;
        }

        return $widgets;
    }

    /**
     * Get all enabled CMS resources.
     *
     * @return array<class-string>
     */
    public function getResources(): array
    {
        $resources = [];

        if ($this->hasSiteSettings) {
            $resources[] = SiteResource::class;
        }

        if ($this->hasCategories) {
            $resources[] = CmsCategoryResource::class;
        }

        if ($this->hasPages) {
            $resources[] = CmsPageResource::class;
        }

        if ($this->hasPosts) {
            $resources[] = CmsPostResource::class;
        }

        if ($this->hasComments && config('tallcms.comments.enabled', true)) {
            $resources[] = CmsCommentResource::class;
        }

        if ($this->hasContactSubmissions) {
            $resources[] = TallcmsContactSubmissionResource::class;
        }

        if ($this->hasMedia) {
            $resources[] = TallcmsMediaResource::class;
            $resources[] = MediaCollectionResource::class;
        }

        if ($this->hasMenus) {
            $resources[] = TallcmsMenuResource::class;
        }

        if ($this->hasUsers) {
            $resources[] = UserResource::class;
        }

        return $resources;
    }

    /**
     * Disable categories resource.
     */
    public function withoutCategories(): static
    {
        $this->hasCategories = false;

        return $this;
    }

    /**
     * Disable pages resource.
     */
    public function withoutPages(): static
    {
        $this->hasPages = false;

        return $this;
    }

    /**
     * Disable posts resource.
     */
    public function withoutPosts(): static
    {
        $this->hasPosts = false;

        return $this;
    }

    /**
     * Disable comments resource.
     */
    public function withoutComments(): static
    {
        $this->hasComments = false;

        return $this;
    }

    /**
     * Disable contact submissions resource.
     */
    public function withoutContactSubmissions(): static
    {
        $this->hasContactSubmissions = false;

        return $this;
    }

    /**
     * Disable media resource.
     */
    public function withoutMedia(): static
    {
        $this->hasMedia = false;

        return $this;
    }

    /**
     * Disable menus resource.
     */
    public function withoutMenus(): static
    {
        $this->hasMenus = false;

        return $this;
    }

    /**
     * Disable users resource.
     *
     * Use this if your app already has a UserResource
     * or you want to manage users differently.
     */
    public function withoutUsers(): static
    {
        $this->hasUsers = false;

        return $this;
    }

    /**
     * @deprecated The standalone Code Injection page was removed in v4.4. Embed code
     *             now lives as a tab on the Site edit page (Sites → {site} → Embed Code)
     *             and authorization follows Site edit permission. This method is kept
     *             as a no-op shim so existing panel providers don't fatal on upgrade.
     */
    public function withoutCodeInjection(): static
    {
        return $this;
    }

    /**
     * Disable site settings resource.
     */
    public function withoutSiteSettings(): static
    {
        $this->hasSiteSettings = false;

        return $this;
    }

    /**
     * Disable global defaults page.
     */
    public function withoutGlobalDefaults(): static
    {
        $this->hasGlobalDefaults = false;

        return $this;
    }

    /**
     * Disable SEO settings page.
     */
    public function withoutSeoSettings(): static
    {
        $this->hasSeoSettings = false;

        return $this;
    }

    /**
     * Disable plugin manager page.
     */
    public function withoutPluginManager(): static
    {
        $this->hasPluginManager = false;

        return $this;
    }

    /**
     * Disable theme manager page.
     */
    public function withoutThemeManager(): static
    {
        $this->hasThemeManager = false;

        return $this;
    }

    /**
     * Disable system updates pages.
     */
    public function withoutSystemUpdates(): static
    {
        $this->hasSystemUpdates = false;

        return $this;
    }

    /**
     * Disable automatic FilamentShieldPlugin registration.
     *
     * Use this if you want to register FilamentShieldPlugin yourself
     * with custom configuration.
     */
    public function withoutShieldPlugin(): static
    {
        $this->hasShieldPlugin = false;

        return $this;
    }

    /**
     * Set the navigation group for Shield's role management.
     *
     * @param  string|null  $group  Navigation group name, or null to use Shield's default
     */
    public function shieldNavigationGroup(?string $group): static
    {
        $this->shieldNavigationGroup = $group;

        return $this;
    }

    /**
     * Rename the Categories resource labels.
     *
     * Example: ->categoryLabel('Tags', 'Tag')
     */
    public function categoryLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('categories', $plural, $singular, $navigation);
    }

    /**
     * Rename the Pages resource labels.
     *
     * Example: ->pageLabel('Website Pages', 'Website Page')
     */
    public function pageLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('pages', $plural, $singular, $navigation);
    }

    /**
     * Rename the Posts resource labels.
     *
     * Example: ->postLabel('Articles', 'Article')
     */
    public function postLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('posts', $plural, $singular, $navigation);
    }

    /**
     * Rename the Menus resource labels.
     */
    public function menuLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('menus', $plural, $singular, $navigation);
    }

    /**
     * Rename the Media resource labels.
     */
    public function mediaLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('media', $plural, $singular, $navigation);
    }

    /**
     * Rename the Media Collections resource labels.
     */
    public function mediaCollectionLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('media_collections', $plural, $singular, $navigation);
    }

    /**
     * Rename the Comments resource labels.
     */
    public function commentLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('comments', $plural, $singular, $navigation);
    }

    /**
     * Rename the Contact Submissions resource labels.
     */
    public function contactSubmissionLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('contact_submissions', $plural, $singular, $navigation);
    }

    /**
     * Rename the Users resource labels.
     */
    public function userLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('users', $plural, $singular, $navigation);
    }

    /**
     * Rename the Site Settings resource labels.
     */
    public function siteSettingsLabel(string $plural, ?string $singular = null, ?string $navigation = null): static
    {
        return $this->setResourceLabels('site_settings', $plural, $singular, $navigation);
    }

    /**
     * Store label overrides for a resource key.
     *
     * Only provided (non-null) values are written; omitted ones preserve
     * the existing config value (including null). When a facet stays null,
     * tallcms_label() falls back to the package translation for APP_LOCALE.
     * This matters because the default navigation label is intentionally
     * different from plural for some resources (e.g. media -> "Media Library",
     * site_settings -> "Site Settings"), and the singular form is usually
     * not the same word as the plural ("Article" vs "Articles").
     */
    protected function setResourceLabels(string $key, string $plural, ?string $singular, ?string $navigation): static
    {
        $overrides = ['plural' => $plural];

        if ($singular !== null) {
            $overrides['singular'] = $singular;
        }

        if ($navigation !== null) {
            $overrides['navigation'] = $navigation;
        }

        $this->labelOverrides[$key] = $overrides;

        return $this;
    }
}
