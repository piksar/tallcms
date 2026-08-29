<?php

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Services\MarketplaceCatalogService;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\SiteSettingsService;
use TallCms\Cms\Services\ThemeManager as ThemeManagerService;
use TallCms\Cms\Services\ThemeValidator;

class ThemeManager extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.theme-manager';

    public function getTitle(): string
    {
        return __('tallcms::pages.theme_manager.title');
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-paint-brush';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.theme_manager.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('appearance');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public ?string $selectedTheme = null;

    public ?array $themeDetails = null;

    #[Url]
    public string $search = '';

    public int $themePage = 1;

    public int $themesPerPage = 12;

    public bool $filterDarkMode = false;

    public bool $filterThemeController = false;

    public bool $filterResponsive = false;

    public bool $filterAnimations = false;

    #[Url]
    public string $sort = 'active';

    public array $licenseStatuses = [];

    /**
     * Display Options toggles, hydrated only when the slide-over is opened
     * for the *active* theme. See showThemeDetails() and the updated* hooks below.
     */
    public bool $showThemeSwitcher = true;

    public bool $showSearch = true;

    public bool $showLanguageDropdown = true;

    public function mount(): void
    {
        $this->refreshLicenseStatuses();
    }

    public function updatedShowThemeSwitcher(bool $value): void
    {
        $this->writeScopedSetting('show_theme_switcher', $value, 'boolean', 'branding');
    }

    public function updatedShowSearch(bool $value): void
    {
        $this->writeScopedSetting('show_search', $value, 'boolean', 'branding');
    }

    public function updatedShowLanguageDropdown(bool $value): void
    {
        $this->writeScopedSetting('show_language_dropdown', $value, 'boolean', 'branding');
    }

    /**
     * Get the theme manager service
     */
    protected function getThemeManager(): ThemeManagerService
    {
        return app(ThemeManagerService::class);
    }

    protected function getSiteSettingsService(): SiteSettingsService
    {
        return app(SiteSettingsService::class);
    }

    /**
     * Persist a Theme Manager setting to the site shown in the page dropdown
     * (getMultisiteContext), not ambient SiteSetting::set().
     *
     * Livewire updates hit /livewire/update without MarkAdminContext, so
     * SiteSetting::set() can follow the admin hostname (e.g. 127.0.0.1.nip.io)
     * instead of the tenant selected in Theme Manager.
     */
    protected function writeScopedSetting(string $key, mixed $value, string $type, string $group): void
    {
        $context = $this->getMultisiteContext();

        if ($context) {
            $this->getSiteSettingsService()->setForSite((int) $context->id, $key, $value, $type);
        } else {
            SiteSetting::setGlobal($key, $value, $type, $group);
        }

        SiteSetting::clearCache();
    }

    protected function readScopedSetting(string $key, mixed $default = null): mixed
    {
        $context = $this->getMultisiteContext();

        if ($context) {
            return $this->getSiteSettingsService()->getForSite((int) $context->id, $key, $default);
        }

        return SiteSetting::getGlobal($key, $default);
    }

    /**
     * Get the theme validator service
     */
    protected function getValidator(): ThemeValidator
    {
        return app(ThemeValidator::class);
    }

    /**
     * Get multisite admin context by reading the session, with a role-based
     * fallback for users who haven't explicitly switched sites.
     *
     * Resolution order:
     *   1. Explicit session site selected via the Site Switcher.
     *   2. "__all_sites__" sentinel → null (caller treats as global).
     *   3. No session value AND user is not super_admin → their first owned
     *      site. Critical for site_owners on SaaS: they own exactly one
     *      (or a handful of) sites and would never touch the Site Switcher.
     *      Without this fallback, theme activation, preset changes, etc.
     *      all write to the GLOBAL config/theme.php instead of scoping to
     *      their own site.
     *   4. No session value and user is super_admin → null (caller treats
     *      as global — super_admins manage installation-wide defaults).
     *
     * @return ?stdClass with columns: id, name, domain, theme, etc.
     */
    protected function getMultisiteContext(): ?object
    {
        $sessionValue = session('multisite_admin_site_id');

        // Explicit "All Sites" selection
        if ($sessionValue === '__all_sites__') {
            return null;
        }

        try {
            if ($sessionValue && is_numeric($sessionValue)) {
                $site = DB::table('tallcms_sites')
                    ->where('id', $sessionValue)
                    ->where('is_active', true)
                    ->first();

                return $site ?: null;
            }

            // No session value — fall back based on role.
            $user = auth()->user();
            if (! $user) {
                return null;
            }

            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                // Super-admins manage global defaults; null = global.
                return null;
            }

            $site = DB::table('tallcms_sites')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('is_active', true)
                ->orderBy('created_at')
                ->first();

            return $site ?: null;
        } catch (QueryException) {
            // Table doesn't exist (multisite plugin not installed)
            return null;
        }
    }

    /**
     * Sites the current user can manage themes for, as a list for the site
     * switcher dropdown. super_admins see every active site; non-super-admins
     * see only their own.
     *
     * @return array<int, string> [site_id => "Name (domain)"]
     */
    public function manageableSites(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        try {
            $query = DB::table('tallcms_sites')->where('is_active', true);

            if (! (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))) {
                $query->where('user_id', $user->getAuthIdentifier());
            }

            return $query->orderBy('name')
                ->get(['id', 'name', 'domain'])
                ->mapWithKeys(fn ($site) => [
                    (int) $site->id => "{$site->name} ({$site->domain})",
                ])
                ->all();
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * Set the current session's multisite context to the given site.
     *
     * Wired to the site-switcher dropdown on the Theme Manager page. Validates
     * that the user is actually allowed to manage that site (super_admin or
     * the owner). Full reload after switching so the page re-evaluates every
     * cached computed property against the new context.
     */
    public function switchSite(?string $siteId): void
    {
        $siteId = $siteId !== null && $siteId !== '' ? (int) $siteId : null;
        $user = auth()->user();

        if (! $user || ! $siteId) {
            return;
        }

        $siteQuery = DB::table('tallcms_sites')->where('id', $siteId)->where('is_active', true);

        if (! (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))) {
            $siteQuery->where('user_id', $user->getAuthIdentifier());
        }

        if (! $siteQuery->exists()) {
            Notification::make()
                ->title(__('tallcms::ui.t_cannot_switch_to_that_site'))
                ->danger()
                ->send();

            return;
        }

        session(['multisite_admin_site_id' => $siteId]);

        $this->redirect(static::getUrl(), navigate: false);
    }

    public function getSubheading(): ?string
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return null;
        }

        return __('tallcms::ui.managing_theme_for_full', ['name' => $context->name, 'domain' => $context->domain]);
    }

    /**
     * Get all available themes with metadata
     */
    #[Computed]
    public function themes(): Collection
    {
        return $this->getThemeManager()->getAvailableThemes()
            ->map(fn (Theme $theme) => [
                'slug' => $theme->slug,
                'name' => $theme->name,
                'description' => $theme->description,
                'version' => $theme->version,
                'author' => $theme->author,
                'screenshot' => $theme->getScreenshotUrl(),
                'isActive' => $theme->slug === $this->getActiveThemeSlug(),
                'supports' => $theme->supports,
                'parent' => $theme->parent,
                'isBuilt' => $theme->isBuilt(),
                'isPrebuilt' => $theme->isPrebuilt(),
                'meetsRequirements' => $theme->meetsRequirements(),
                'unmetRequirements' => $theme->getUnmetRequirements(),
                'daisyuiPreset' => $theme->getDaisyUIPreset(),
                'daisyuiColors' => $this->getPresetColors($theme),
                'hasDarkMode' => $theme->supports('dark_mode'),
                'hasThemeController' => $theme->supportsThemeController(),
                'hasResponsive' => $theme->supports('responsive'),
                'hasAnimations' => $theme->supports('animations'),
                'requiresLicense' => $theme->requiresLicense(),
                'licenseSlug' => $theme->getLicenseSlug(),
                'licenseStatus' => $theme->requiresLicense()
                    ? ($this->licenseStatuses[$theme->getLicenseSlug()] ?? null)
                    : null,
                'presets' => $theme->getDaisyUIPresets(),
                'tags' => $theme->getTags(),
                'purchaseUrl' => $theme->getPurchaseUrl(),
            ])
            ->values();
    }

    /**
     * Get themes filtered by search and feature toggles
     */
    #[Computed]
    public function filteredThemes(): Collection
    {
        $themes = $this->themes;

        if (! empty($this->search)) {
            $search = strtolower(trim($this->search));
            $themes = $themes->filter(fn ($t) => str_contains(strtolower($t['name']), $search)
                || str_contains(strtolower($t['description'] ?? ''), $search)
                || str_contains(strtolower($t['author']), $search)
                || str_contains(strtolower($t['daisyuiPreset'] ?? ''), $search)
                || collect($t['tags'] ?? [])->contains(fn ($tag) => str_contains(strtolower($tag), $search))
            );
        }

        if ($this->filterDarkMode) {
            $themes = $themes->where('hasDarkMode', true);
        }
        if ($this->filterThemeController) {
            $themes = $themes->where('hasThemeController', true);
        }
        if ($this->filterResponsive) {
            $themes = $themes->where('hasResponsive', true);
        }
        if ($this->filterAnimations) {
            $themes = $themes->where('hasAnimations', true);
        }

        $themes = $themes->sortBy(fn ($t) => match ($this->sort) {
            'name' => [0, $t['name']],
            'preset' => [$t['daisyuiPreset'], $t['isActive'] ? 0 : 1, $t['name']],
            default => [$t['isActive'] ? 0 : 1, $t['name']],
        });

        return $themes->values();
    }

    /**
     * Get paginated themes for the current page
     */
    #[Computed]
    public function paginatedThemes(): Collection
    {
        return $this->filteredThemes
            ->slice(($this->themePage - 1) * $this->themesPerPage, $this->themesPerPage)
            ->values();
    }

    /**
     * Get total number of theme pages
     */
    #[Computed]
    public function themePageCount(): int
    {
        return (int) ceil($this->filteredThemes->count() / $this->themesPerPage);
    }

    /**
     * Navigate to a theme page
     */
    public function goToThemePage(int $page): void
    {
        $this->themePage = max(1, min($page, $this->themePageCount));
    }

    /**
     * Reset theme pagination when search or filters change
     */
    public function updatedSearch(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterDarkMode(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterThemeController(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterResponsive(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterAnimations(): void
    {
        $this->themePage = 1;
    }

    /**
     * Get available themes from the remote marketplace catalog
     */
    #[Computed]
    public function availableMarketplaceThemes(): Collection
    {
        $catalog = app(MarketplaceCatalogService::class)->getThemes();

        // Use licenseSlug as the canonical identifier for installed themes.
        // Theme::getLicenseSlug() defaults to "tallcms/theme-{slug}".
        // Marketplace theme entries must use the same convention.
        $installedSlugs = $this->themes
            ->pluck('licenseSlug')
            ->filter()
            ->toArray();

        return collect($catalog)
            ->filter(fn ($theme) => ! in_array($theme['full_slug'], $installedSlugs))
            ->sortByDesc('featured')
            ->take(4)
            ->values();
    }

    /**
     * Get daisyUI preset colors for a theme
     */
    protected function getPresetColors(Theme $theme): array
    {
        $customColors = $theme->getDaisyUIColors();
        if (! empty($customColors)) {
            return $customColors;
        }

        $preset = $theme->getDaisyUIPreset();
        if ($preset) {
            return Theme::DAISYUI_PRESET_COLORS[$preset] ?? [];
        }

        return [];
    }

    /**
     * Clear cached theme computed properties
     */
    /**
     * Get the currently active theme data
     */
    #[Computed]
    public function activeTheme(): ?array
    {
        $theme = $this->themes->firstWhere('isActive', true);

        if ($theme) {
            // Resolve theme model directly from slug (not from global ThemeManager state)
            $activeThemeModel = Theme::find($this->getActiveThemeSlug());
            $fallback = $activeThemeModel?->getDaisyUIPreset() ?? 'light';
            $presets = $activeThemeModel?->getDaisyUIPresets() ?? [];

            // SiteSetting::get() follows the request host / admin_context, which
            // on Livewire updates is often the platform domain rather than the
            // site selected in Theme Manager. Read the dropdown site explicitly.
            $stored = $this->readScopedSetting('theme_default_preset');

            $theme['defaultPreset'] = ($stored && in_array($stored, $presets)) ? $stored : $fallback;
        }

        return $theme;
    }

    protected function clearThemeCache(): void
    {
        unset($this->themes);
        unset($this->filteredThemes);
        unset($this->activeTheme);
    }

    /**
     * Get the active theme slug (site-aware when multisite is active).
     * When a site is selected, returns the site's theme. Falls back to global.
     */
    public function getActiveThemeSlug(): string
    {
        $context = $this->getMultisiteContext();
        if ($context && $context->theme) {
            return $context->theme;
        }

        return $this->getThemeManager()->getActiveTheme()->slug;
    }

    /**
     * Check if rollback is available.
     * Rollback only applies to global theme changes (config/theme.php writes).
     * Per-site theme changes are direct column updates with no rollback state.
     */
    public function canRollback(): bool
    {
        if ($this->getMultisiteContext()) {
            return false;
        }

        return $this->getThemeManager()->canRollback();
    }

    /**
     * Get the rollback theme slug
     */
    public function getRollbackSlug(): ?string
    {
        return $this->getThemeManager()->getRollbackSlug();
    }

    /**
     * Activate a theme
     */
    public function activateTheme(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            Notification::make()
                ->title(__('tallcms::ui.t_theme_not_found'))
                ->body(__('tallcms::ui.n_the_theme_slug_could_not_be_found', ['slug' => $slug]))
                ->danger()
                ->send();

            return;
        }

        // Run preflight validation
        $validation = $this->getValidator()->preflightCheck($theme);

        if (! $validation->isValid) {
            Notification::make()
                ->title(__('tallcms::ui.t_theme_activation_failed'))
                ->body(implode("\n", $validation->errors))
                ->danger()
                ->send();

            return;
        }

        // Show warnings if any
        if ($validation->hasWarnings()) {
            foreach ($validation->warnings as $warning) {
                Notification::make()
                    ->title(__('tallcms::ui.t_warning'))
                    ->body($warning)
                    ->warning()
                    ->send();
            }
        }

        $context = $this->getMultisiteContext();

        if ($context) {
            // Per-site: update the site's theme column
            DB::table('tallcms_sites')
                ->where('id', $context->id)
                ->update(['theme' => $slug, 'updated_at' => now()]);

            // Run essential activation steps (same as setActiveTheme minus config file write)
            $manager = $this->getThemeManager();
            $manager->publishThemeAssets($theme);

            // Clear compiled views to prevent stale cached templates
            $compiledViewPath = config('view.compiled');
            if ($compiledViewPath && File::isDirectory($compiledViewPath)) {
                foreach (File::glob($compiledViewPath.'/*.php') as $view) {
                    File::delete($view);
                }
            }

            $this->writeScopedSetting('theme_default_preset', '', 'text', 'theme');

            Notification::make()
                ->title(__('tallcms::ui.t_site_theme_updated'))
                ->body(__('tallcms::ui.n_theme_name_is_now_active_for_context_name', ['theme_name' => $theme->name, 'context_name' => $context->name]))
                ->success()
                ->send();

            $this->clearThemeCache();
        } elseif ($this->getThemeManager()->activateWithRollback($slug)) {
            // Global: write to config/theme.php with rollback support
            $this->writeScopedSetting('theme_default_preset', '', 'text', 'theme');

            Notification::make()
                ->title(__('tallcms::ui.t_theme_activated'))
                ->body(__('tallcms::ui.n_theme_name_is_now_the_default_theme', ['theme_name' => $theme->name]))
                ->success()
                ->send();

            $this->clearThemeCache();
        } else {
            Notification::make()
                ->title(__('tallcms::ui.t_activation_failed'))
                ->body(__('tallcms::ui.t_failed_to_activate_theme_please_check_the_logs'))
                ->danger()
                ->send();
        }
    }

    /**
     * Rollback to the previous theme
     */
    public function rollbackTheme(): void
    {
        $rollbackSlug = $this->getRollbackSlug();

        if (! $rollbackSlug) {
            Notification::make()
                ->title(__('tallcms::ui.t_no_rollback_available'))
                ->body(__('tallcms::ui.t_there_is_no_previous_theme_to_rollback_to'))
                ->warning()
                ->send();

            return;
        }

        if ($this->getThemeManager()->rollbackToPrevious()) {
            Notification::make()
                ->title(__('tallcms::ui.t_rollback_successful'))
                ->body(__('tallcms::ui.n_reverted_to_previous_theme_rollbackslug', ['rollbackslug' => $rollbackSlug]))
                ->success()
                ->send();

            // Clear the computed property cache so themes list re-evaluates
            $this->clearThemeCache();
        } else {
            Notification::make()
                ->title(__('tallcms::ui.t_rollback_failed'))
                ->body(__('tallcms::ui.t_failed_to_rollback_to_previous_theme'))
                ->danger()
                ->send();
        }
    }

    /**
     * Preview a theme in a new tab
     */
    public function previewTheme(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            Notification::make()
                ->title(__('tallcms::ui.t_theme_not_found'))
                ->danger()
                ->send();

            return;
        }

        // Validate theme can be previewed (same checks as middleware)
        if ($theme->isPrebuilt() && ! $theme->isBuilt()) {
            Notification::make()
                ->title(__('tallcms::ui.t_theme_not_built'))
                ->body(__('tallcms::ui.n_theme_theme_name_has_not_been_built_run_npm_run_build_i', ['theme_name' => $theme->name]))
                ->danger()
                ->send();

            return;
        }

        if (! $theme->meetsRequirements()) {
            $unmet = $theme->getUnmetRequirements();
            Notification::make()
                ->title(__('tallcms::ui.t_theme_requirements_not_met'))
                ->body(implode("\n", $unmet))
                ->danger()
                ->send();

            return;
        }

        // Generate preview URL
        $previewUrl = url('/').'?theme_preview='.$slug;

        // Dispatch event to open in new tab
        $this->dispatch('open-preview', url: $previewUrl);

        Notification::make()
            ->title(__('tallcms::ui.t_preview_opened'))
            ->body(__('tallcms::ui.n_preview_of_theme_name_opened_in_new_tab', ['theme_name' => $theme->name]))
            ->info()
            ->send();
    }

    /**
     * Change the default daisyUI preset for the active theme
     */
    public function changeDefaultPreset(string $preset): void
    {
        // Resolve theme directly from site-aware slug, not from global ThemeManager
        $activeTheme = Theme::find($this->getActiveThemeSlug());

        if (! $activeTheme || ! $activeTheme->supportsThemeController()) {
            return;
        }

        $availablePresets = $activeTheme->getDaisyUIPresets();
        if (! in_array($preset, $availablePresets)) {
            Notification::make()
                ->title(__('tallcms::ui.t_invalid_preset'))
                ->body(__('tallcms::ui.n_the_preset_preset_is_not_available_for_this_theme', ['preset' => $preset]))
                ->danger()
                ->send();

            return;
        }

        $this->writeScopedSetting('theme_default_preset', $preset, 'text', 'theme');

        Notification::make()
            ->title(__('tallcms::ui.t_default_preset_updated'))
            ->body(__('tallcms::ui.n_default_preset_changed', ['preset' => ucfirst($preset)]))
            ->success()
            ->send();

        $this->clearThemeCache();
    }

    /**
     * Show theme details in modal
     */
    public function showThemeDetails(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            return;
        }

        $this->selectedTheme = $slug;
        $activeSlug = $this->getActiveThemeSlug();

        $this->themeDetails = [
            'name' => $theme->name,
            'slug' => $theme->slug,
            'version' => $theme->version,
            'description' => $theme->description,
            'author' => $theme->author,
            'authorUrl' => $theme->getAuthorUrl(),
            'homepage' => $theme->getHomepage(),
            'license' => $theme->getLicense(),
            'parent' => $theme->parent,
            'supports' => $theme->supports,
            'tailwind' => $theme->tailwind,
            'daisyui' => [
                'preset' => $theme->getDaisyUIPreset(),
                'prefersDark' => $theme->getDaisyUIPrefersDark(),
                'presets' => $theme->getDaisyUIPresets(),
                'custom' => $theme->hasCustomDaisyUITheme(),
                'colors' => $theme->getDaisyUIColors(),
            ],
            'path' => $theme->path,
            'compatibility' => $theme->getCompatibility(),
            'isBuilt' => $theme->isBuilt(),
            'isPrebuilt' => $theme->isPrebuilt(),
            'isActive' => $activeSlug === $theme->slug,
            'meetsRequirements' => $theme->meetsRequirements(),
            'unmetRequirements' => $theme->getUnmetRequirements(),
            'screenshot' => $theme->getScreenshotUrl(),
            'gallery' => $theme->getGalleryScreenshots(),
            'requiresLicense' => $theme->requiresLicense(),
            'licenseSlug' => $theme->getLicenseSlug(),
            'licenseStatus' => $theme->requiresLicense()
                ? ($this->licenseStatuses[$theme->getLicenseSlug()] ?? null)
                : null,
            'tags' => $theme->getTags(),
            'hasDarkMode' => $theme->supports('dark_mode'),
            'hasThemeController' => $theme->supportsThemeController(),
            'hasResponsive' => $theme->supports('responsive'),
            'hasAnimations' => $theme->supports('animations'),
            'purchaseUrl' => $theme->getPurchaseUrl(),
        ];

        // Hydrate Display Options toggles only for the active theme — they edit
        // site-wide SiteSetting values and would be misleading when shown for
        // an inactive theme. The Blade also gates on isActive for the same reason.
        if ($activeSlug === $theme->slug) {
            $this->showThemeSwitcher = (bool) $this->readScopedSetting('show_theme_switcher', true);
            $this->showSearch = (bool) $this->readScopedSetting('show_search', true);
            $this->showLanguageDropdown = (bool) $this->readScopedSetting('show_language_dropdown', true);
        }

        $this->dispatch('open-modal', id: 'theme-details-modal');
    }

    /**
     * Close theme details modal
     */
    public function closeThemeDetails(): void
    {
        $this->selectedTheme = null;
        $this->themeDetails = null;
    }

    /**
     * Refresh theme list
     */
    public function refreshThemes(): void
    {
        $this->getThemeManager()->refreshCache();

        Notification::make()
            ->title(__('tallcms::ui.t_themes_refreshed'))
            ->body(__('tallcms::ui.t_theme_list_has_been_refreshed'))
            ->success()
            ->send();

        // Clear the computed property cache so themes list re-evaluates
        $this->clearThemeCache();
    }

    /**
     * Delete theme action with Filament confirmation modal
     */
    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('tallcms::fields.delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_delete_theme'))
            ->modalDescription(fn (array $arguments) => "Are you sure you want to delete the theme '{$arguments['name']}'? This action cannot be undone.")
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_delete_theme'))
            ->action(function (array $arguments) {
                // Server-side guard: themes are installation-wide, super_admin only.
                if (! auth()->user()?->hasRole('super_admin')) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_not_authorized'))
                        ->body(__('tallcms::ui.t_only_super_admins_can_delete_themes'))
                        ->danger()
                        ->send();

                    return;
                }

                $slug = $arguments['slug'];
                $theme = Theme::find($slug);

                if (! $theme) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_theme_not_found'))
                        ->body(__('tallcms::ui.n_the_theme_slug_could_not_be_found', ['slug' => $slug]))
                        ->danger()
                        ->send();

                    return;
                }

                // Use the service method to delete
                $result = $this->getThemeManager()->deleteTheme($slug);

                if (! $result['success']) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_delete_failed'))
                        ->body($result['error'])
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('tallcms::ui.t_theme_deleted'))
                    ->body(__('tallcms::ui.n_theme_name_has_been_removed', ['theme_name' => $theme->name]))
                    ->success()
                    ->send();

                // Close the details modal
                $this->closeThemeDetails();

                // Refresh the theme list
                $this->clearThemeCache();
            });
    }

    // =========================================================================
    // License Methods
    // =========================================================================

    /**
     * Refresh all license statuses for themes that require licenses
     */
    protected function refreshLicenseStatuses(): void
    {
        $licenseService = app(PluginLicenseService::class);
        $this->licenseStatuses = [];

        $themes = $this->getThemeManager()->getAvailableThemes();

        foreach ($themes as $theme) {
            if ($theme->requiresLicense()) {
                $slug = $theme->getLicenseSlug();
                $status = $licenseService->getStatus($slug);

                // Merge purchase_url from theme.json as fallback
                if (empty($status['purchase_url']) && $theme->getPurchaseUrl()) {
                    $status['purchase_url'] = $theme->getPurchaseUrl();
                }

                $this->licenseStatuses[$slug] = $status;
            }
        }
    }

    /**
     * Refresh a single theme's license status
     */
    public function refreshLicenseStatus(string $licenseSlug): void
    {
        $licenseService = app(PluginLicenseService::class);

        $licenseService->clearCache($licenseSlug);
        $licenseService->isValid($licenseSlug);

        $this->refreshLicenseState();

        Notification::make()
            ->title(__('tallcms::ui.t_status_refreshed'))
            ->body(__('tallcms::ui.t_license_status_has_been_refreshed_from_the_server'))
            ->success()
            ->send();
    }

    /**
     * Refresh license data and clear theme caches
     */
    protected function refreshLicenseState(): void
    {
        $this->refreshLicenseStatuses();
        $this->clearThemeCache();

        // Refresh open details modal if showing a licensable theme
        if ($this->themeDetails && ($this->themeDetails['requiresLicense'] ?? false)) {
            $this->showThemeDetails($this->themeDetails['slug']);
        }
    }

    /**
     * Activate a theme license
     */
    public function activateLicenseAction(): Action
    {
        return Action::make('activateLicense')
            ->label(__('tallcms::fields.activate_license'))
            ->icon('heroicon-o-key')
            ->color('primary')
            ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->modalHeading(fn (array $arguments) => "Activate License — {$arguments['name']}")
            ->modalDescription(__('tallcms::ui.t_enter_your_license_key_from_your_purchase_email'))
            ->form([
                TextInput::make('license_key')
                    ->label(__('tallcms::fields.license_key'))
                    ->placeholder('XXXX-XXXX-XXXX-XXXX')
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                // Server-side guard: theme licenses are installation-wide, super_admin only.
                if (! auth()->user()?->hasRole('super_admin')) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_not_authorized'))
                        ->body(__('tallcms::ui.t_only_super_admins_can_activate_theme_licenses'))
                        ->danger()
                        ->send();

                    return;
                }

                $result = app(PluginLicenseService::class)->activate(
                    $arguments['licenseSlug'],
                    $data['license_key']
                );

                if ($result['valid']) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_license_activated'))
                        ->body(__('tallcms::ui.t_the_license_has_been_successfully_activated'))
                        ->success()
                        ->send();
                } else {
                    if ($result['status'] === 'not_supported') {
                        Notification::make()
                            ->title(__('tallcms::ui.t_theme_not_supported'))
                            ->body(__('tallcms::ui.t_this_theme_does_not_support_license_activation'))
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('tallcms::ui.t_activation_failed_2'))
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }

                $this->refreshLicenseState();
            });
    }

    /**
     * Deactivate a theme license
     */
    public function deactivateLicenseAction(): Action
    {
        return Action::make('deactivateLicense')
            ->label(__('tallcms::fields.deactivate'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => "Deactivate License — {$arguments['name']}")
            ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_deactivate_this_license_the_theme_may_lose_'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_deactivate'))
            ->action(function (array $arguments) {
                // Server-side guard: theme licenses are installation-wide, super_admin only.
                if (! auth()->user()?->hasRole('super_admin')) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_not_authorized'))
                        ->body(__('tallcms::ui.t_only_super_admins_can_deactivate_theme_licenses'))
                        ->danger()
                        ->send();

                    return;
                }

                $result = app(PluginLicenseService::class)->deactivate($arguments['licenseSlug']);

                if ($result['success']) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_license_deactivated'))
                        ->body(__('tallcms::ui.t_the_license_has_been_deactivated_from_this_site'))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title(__('tallcms::ui.t_deactivation_notice'))
                        ->body($result['message'])
                        ->warning()
                        ->send();
                }

                $this->refreshLicenseState();
            });
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('rollback')
                ->label(__('tallcms::fields.rollback_to_previous'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $this->canRollback())
                ->requiresConfirmation()
                ->modalHeading(__('tallcms::ui.t_rollback_theme'))
                ->modalDescription(fn () => "Are you sure you want to rollback to the previous theme ({$this->getRollbackSlug()})?")
                ->modalSubmitActionLabel(__('tallcms::ui.t_yes_rollback'))
                ->action(fn () => $this->rollbackTheme()),

            Action::make('refreshAllLicenses')
                ->label(__('tallcms::fields.refresh_licenses'))
                ->icon('heroicon-o-key')
                ->color('gray')
                ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                ->action(function () {
                    // Server-side guard: cache-busting installation-wide license state, super_admin only.
                    if (! auth()->user()?->hasRole('super_admin')) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_not_authorized'))
                            ->body(__('tallcms::ui.t_only_super_admins_can_refresh_theme_license_status'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearCache();

                    foreach ($this->licenseStatuses as $licenseSlug => $status) {
                        if ($status['has_license']) {
                            $licenseService->isValid($licenseSlug);
                        }
                    }

                    $this->refreshLicenseState();

                    Notification::make()
                        ->title(__('tallcms::ui.t_all_statuses_refreshed'))
                        ->success()
                        ->send();
                })
                ->visible(fn () => collect($this->licenseStatuses)->contains('has_license', true)),

            Action::make('refresh')
                ->label(__('tallcms::fields.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshThemes()),

            // Theme Upload action — gated to super_admin (installation-wide concern)
            // AND the theme.allow_uploads config flag.
            Action::make('upload')
                ->label(__('tallcms::fields.upload_theme'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => config('theme.allow_uploads', false) && (auth()->user()?->hasRole('super_admin') ?? false))
                ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                ->form([
                    FileUpload::make('theme_zip')
                        ->label(__('tallcms::fields.theme_package_zip'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50 * 1024) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('theme-uploads')
                        ->helperText(__('tallcms::ui.t_upload_a_theme_package_zip_file_maximum_size_50mb')),
                ])
                ->action(function (array $data) {
                    // Server-side guards: super_admin only AND uploads enabled.
                    // Both checks run regardless of UI state in case the action is
                    // invoked via direct AJAX bypassing visible().
                    if (! auth()->user()?->hasRole('super_admin')) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_not_authorized'))
                            ->body(__('tallcms::ui.t_only_super_admins_can_upload_themes'))
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! config('theme.allow_uploads', false)) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_uploads_disabled'))
                            ->body(__('tallcms::ui.t_theme_uploads_are_not_enabled_in_configuration'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $uploadedFile = $data['theme_zip'];
                    $zipPath = Storage::disk('local')->path($uploadedFile);
                    $extractedSlug = null;

                    try {
                        // Step 1: Validate ZIP file
                        $validation = $this->getValidator()->validateZip($zipPath);

                        if (! $validation->isValid) {
                            Notification::make()
                                ->title(__('tallcms::ui.t_invalid_theme_package'))
                                ->body(implode("\n", $validation->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Show warnings if any
                        foreach ($validation->warnings as $warning) {
                            Notification::make()
                                ->title(__('tallcms::ui.t_warning'))
                                ->body($warning)
                                ->warning()
                                ->send();
                        }

                        $slug = $validation->themeData['slug'];

                        // Step 2: Check if theme already exists
                        if (File::exists(base_path("themes/{$slug}"))) {
                            Notification::make()
                                ->title(__('tallcms::ui.t_theme_already_exists'))
                                ->body(__('tallcms::ui.n_a_theme_with_slug_slug_already_exists_please_remove_it_', ['slug' => $slug]))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Step 3: Extract theme
                        $extractResult = $this->getThemeManager()->extractTheme($zipPath, $slug);

                        if (! $extractResult['success']) {
                            Notification::make()
                                ->title(__('tallcms::ui.t_extraction_failed'))
                                ->body($extractResult['error'])
                                ->danger()
                                ->send();

                            return;
                        }

                        // Track extracted slug for cleanup on failure
                        $extractedSlug = $slug;

                        // Step 4: Validate extracted directory
                        $dirValidation = $this->getValidator()->validateDirectory(base_path("themes/{$slug}"));

                        if (! $dirValidation->isValid) {
                            // Cleanup extracted files on validation failure
                            File::deleteDirectory(base_path("themes/{$slug}"));
                            $extractedSlug = null;

                            Notification::make()
                                ->title(__('tallcms::ui.t_theme_validation_failed'))
                                ->body(implode("\n", $dirValidation->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Step 5: Install theme (publish assets)
                        $installResult = $this->getThemeManager()->installTheme($slug);

                        if ($installResult === false) {
                            // Cleanup on install failure (both theme dir and any published assets)
                            File::deleteDirectory(base_path("themes/{$slug}"));
                            $publicPath = public_path("themes/{$slug}");
                            if (File::exists($publicPath) || is_link($publicPath)) {
                                if (is_link($publicPath)) {
                                    unlink($publicPath);
                                } else {
                                    File::deleteDirectory($publicPath);
                                }
                            }
                            $extractedSlug = null;

                            Notification::make()
                                ->title(__('tallcms::ui.t_installation_failed'))
                                ->body(__('tallcms::ui.t_failed_to_publish_theme_assets_check_logs_for_details'))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Success - clear the slug so finally block doesn't cleanup
                        $extractedSlug = null;

                        // Step 6: Refresh theme list
                        $this->refreshThemes();

                        Notification::make()
                            ->title(__('tallcms::ui.t_theme_uploaded_successfully'))
                            ->body(__('tallcms::ui.n_theme_validation_themedata_name_has_been_installed_you_', ['validation_themedata_name' => $validation->themeData['name']]))
                            ->success()
                            ->send();

                    } catch (\Throwable $e) {
                        // Cleanup on unexpected error (both theme dir and any published assets)
                        if ($extractedSlug) {
                            if (File::exists(base_path("themes/{$extractedSlug}"))) {
                                File::deleteDirectory(base_path("themes/{$extractedSlug}"));
                            }
                            $publicPath = public_path("themes/{$extractedSlug}");
                            if (File::exists($publicPath) || is_link($publicPath)) {
                                if (is_link($publicPath)) {
                                    unlink($publicPath);
                                } else {
                                    File::deleteDirectory($publicPath);
                                }
                            }
                        }

                        // Log the error for debugging but fail gracefully for the user
                        Log::error('Theme upload failed', [
                            'error' => $e->getMessage(),
                            'slug' => $extractedSlug,
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title(__('tallcms::ui.t_upload_failed'))
                            ->body('An unexpected error occurred: '.$e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        // Always cleanup uploaded file
                        Storage::disk('local')->delete($uploadedFile);
                    }
                }),
        ];
    }
}
