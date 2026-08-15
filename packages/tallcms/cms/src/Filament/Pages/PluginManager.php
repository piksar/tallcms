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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Models\PluginLicense;
use TallCms\Cms\Services\MarketplaceCatalogService;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\PluginManager as PluginManagerService;
use TallCms\Cms\Services\PluginMigrator;
use TallCms\Cms\Services\PluginValidator;

class PluginManager extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.plugin-manager';

    public function getTitle(): string
    {
        return __('tallcms::pages.plugin_manager.title');
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-puzzle-piece';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.plugin_manager.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('system');
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(PluginLicenseService::class)->getAvailableUpdatesCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = app(PluginLicenseService::class)->getAvailableUpdatesCount();

        return $count > 0 ? "{$count} update(s) available" : null;
    }

    public array $licenseStatuses = [];

    public ?string $autoActivatePlugin = null;

    public ?string $autoActivateName = null;

    public function getSubheading(): ?string
    {
        $sessionValue = session('multisite_admin_site_id');
        if (! $sessionValue || $sessionValue === '__all_sites__') {
            return null;
        }

        try {
            $site = DB::table('tallcms_sites')
                ->where('id', $sessionValue)
                ->first();

            return $site ? __('tallcms::ui.viewing_licenses_for', ['name' => $site->name, 'domain' => $site->domain]) : null;
        } catch (QueryException) {
            return null;
        }
    }

    public function mount(): void
    {
        // Trigger automatic update check (rate-limited internally)
        app(PluginLicenseService::class)->checkForUpdatesAutomatically();

        $this->refreshLicenseStatuses();

        // Handle ?plugin= query param for auto-opening activation modal
        // Only auto-open if the plugin doesn't already have a valid license
        $requestedPlugin = request()->query('plugin');
        if ($requestedPlugin) {
            $plugin = $this->getPluginManager()->getInstalledPlugins()
                ->first(fn (Plugin $p) => $p->getLicenseSlug() === $requestedPlugin);
            if ($plugin && $plugin->requiresLicense()) {
                $status = $this->licenseStatuses[$requestedPlugin] ?? null;
                $alreadyValid = $status && ($status['has_license'] ?? false) && ($status['is_valid'] ?? false);
                if (! $alreadyValid) {
                    $this->autoActivatePlugin = $requestedPlugin;
                    $this->autoActivateName = $plugin->name;
                }
            }
        }
    }

    /**
     * Auto-mount the activation modal (called via wire:init)
     */
    public function mountAutoActivate(): void
    {
        if ($this->autoActivatePlugin) {
            $slug = $this->autoActivatePlugin;
            $name = $this->autoActivateName;
            // Clear immediately so re-renders don't retrigger
            $this->autoActivatePlugin = null;
            $this->autoActivateName = null;
            $this->mountAction('activateLicense', [
                'pluginSlug' => $slug,
                'name' => $name,
            ]);
        }
    }

    #[Url]
    public string $search = '';

    public int $pluginPage = 1;

    public int $pluginsPerPage = 12;

    public ?string $selectedPlugin = null;

    public ?array $pluginDetails = null;

    protected ?array $availableUpdates = null;

    /**
     * Get available updates (cached per request)
     */
    protected function getAvailableUpdates(): array
    {
        if ($this->availableUpdates === null) {
            $this->availableUpdates = app(PluginLicenseService::class)->getAvailableUpdates();
        }

        return $this->availableUpdates;
    }

    /**
     * Get the plugin manager service
     */
    protected function getPluginManager(): PluginManagerService
    {
        return app(PluginManagerService::class);
    }

    /**
     * Get the plugin validator service
     */
    protected function getValidator(): PluginValidator
    {
        return app(PluginValidator::class);
    }

    /**
     * Get the plugin migrator service
     */
    protected function getMigrator(): PluginMigrator
    {
        return app(PluginMigrator::class);
    }

    /**
     * Refresh license statuses for all plugins
     */
    protected function refreshLicenseStatuses(): void
    {
        $this->licenseStatuses = app(PluginLicenseService::class)->getAllStatuses();
    }

    /**
     * Refresh license-related UI state without touching update cache
     */
    protected function refreshLicenseState(): void
    {
        $this->refreshLicenseStatuses();

        // Clear computed plugin lists so licenseStatus data is refreshed
        unset($this->plugins);
        unset($this->filteredPlugins);

        // Refresh open details modal if showing a licensable plugin
        if ($this->pluginDetails && ($this->pluginDetails['requiresLicense'] ?? false)) {
            $this->showPluginDetails($this->pluginDetails['vendor'], $this->pluginDetails['slug']);
        }
    }

    /**
     * Refresh all plugin state after license changes that affect update eligibility
     * (activate/deactivate — not simple status refreshes)
     */
    protected function refreshPluginState(): void
    {
        $this->refreshLicenseStatuses();

        // Clear update cache — license changes can affect update eligibility
        app(PluginLicenseService::class)->clearUpdateCache();
        $this->availableUpdates = null;

        // Clear computed plugin lists
        unset($this->plugins);
        unset($this->filteredPlugins);

        // Refresh open details modal if showing a licensable plugin
        if ($this->pluginDetails && ($this->pluginDetails['requiresLicense'] ?? false)) {
            $this->showPluginDetails($this->pluginDetails['vendor'], $this->pluginDetails['slug']);
        }
    }

    /**
     * Get all installed plugins with metadata
     */
    #[Computed]
    public function plugins(): Collection
    {
        $availableUpdates = $this->getAvailableUpdates();

        return $this->getPluginManager()->getInstalledPlugins()
            ->map(fn (Plugin $plugin) => [
                'vendor' => $plugin->vendor,
                'slug' => $plugin->slug,
                'fullSlug' => $plugin->getFullSlug(),
                'name' => $plugin->name,
                'description' => $plugin->description,
                'version' => $plugin->version,
                'author' => $plugin->author,
                'tags' => $plugin->tags,
                'hasFilamentPlugin' => $plugin->hasFilamentPlugin(),
                'hasPublicRoutes' => $plugin->hasPublicRoutes(),
                'hasPrefixedRoutes' => $plugin->hasPrefixedRoutes(),
                'hasMigrations' => $plugin->hasMigrations(),
                'meetsRequirements' => $plugin->meetsRequirements(),
                'unmetRequirements' => $plugin->getUnmetRequirements(),
                'hasPendingMigrations' => $this->getMigrator()->hasPendingMigrations($plugin),
                'hasUpdate' => isset($availableUpdates[$plugin->getLicenseSlug()]),
                'updateInfo' => $availableUpdates[$plugin->getLicenseSlug()] ?? null,
                'requiresLicense' => $plugin->requiresLicense(),
                'licenseSlug' => $plugin->getLicenseSlug(),
                'licenseStatus' => $plugin->requiresLicense()
                    ? ($this->licenseStatuses[$plugin->getLicenseSlug()] ?? null)
                    : null,
            ])
            ->values();
    }

    /**
     * Get filtered plugins based on search query
     */
    #[Computed]
    public function filteredPlugins(): Collection
    {
        if (empty($this->search)) {
            return $this->plugins;
        }

        $search = strtolower(trim($this->search));

        return $this->plugins->filter(function ($plugin) use ($search) {
            return str_contains(strtolower($plugin['name']), $search)
                || str_contains(strtolower($plugin['description']), $search)
                || str_contains(strtolower($plugin['fullSlug']), $search)
                || str_contains(strtolower($plugin['author']), $search)
                || collect($plugin['tags'])->contains(fn ($tag) => str_contains(strtolower($tag), $search));
        });
    }

    /**
     * Get paginated plugins for the current page
     */
    #[Computed]
    public function paginatedPlugins(): Collection
    {
        return $this->filteredPlugins
            ->slice(($this->pluginPage - 1) * $this->pluginsPerPage, $this->pluginsPerPage)
            ->values();
    }

    /**
     * Get total number of plugin pages
     */
    #[Computed]
    public function pluginPageCount(): int
    {
        return (int) ceil($this->filteredPlugins->count() / $this->pluginsPerPage);
    }

    /**
     * Navigate to a plugin page
     */
    public function goToPluginPage(int $page): void
    {
        $this->pluginPage = max(1, min($page, $this->pluginPageCount));
    }

    /**
     * Reset plugin pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->pluginPage = 1;
    }

    /**
     * Get available plugins from catalog (excluding installed ones)
     */
    #[Computed]
    public function availablePlugins(): Collection
    {
        $catalog = app(MarketplaceCatalogService::class)->getPlugins();
        $installedSlugs = $this->plugins->pluck('fullSlug')->toArray();

        return collect($catalog)
            ->filter(fn ($plugin) => ! in_array($plugin['full_slug'], $installedSlugs))
            ->sortByDesc('featured')
            ->take(4)
            ->map(fn ($plugin) => array_merge($plugin, ['fullSlug' => $plugin['full_slug']]))
            ->values();
    }

    /**
     * Check if plugin uploads/updates are allowed (for Blade access)
     */
    #[Computed]
    public function uploadsAllowed(): bool
    {
        return $this->getPluginManager()->uploadsAllowed();
    }

    /**
     * Show plugin details in modal
     */
    public function showPluginDetails(string $vendor, string $slug): void
    {
        $plugin = $this->getPluginManager()->find($vendor, $slug);

        if (! $plugin) {
            return;
        }

        $this->selectedPlugin = $plugin->getFullSlug();

        $migrationStatus = $this->getMigrator()->getMigrationStatus($plugin);
        $backups = $this->getPluginManager()->getAvailableBackups($vendor, $slug);

        $availableUpdates = $this->getAvailableUpdates();

        $this->pluginDetails = [
            'vendor' => $plugin->vendor,
            'slug' => $plugin->slug,
            'fullSlug' => $plugin->getFullSlug(),
            'name' => $plugin->name,
            'description' => $plugin->description,
            'version' => $plugin->version,
            'author' => $plugin->author,
            'authorUrl' => $plugin->getAuthorUrl(),
            'homepage' => $plugin->getHomepage(),
            'license' => $plugin->getLicense(),
            'namespace' => $plugin->namespace,
            'provider' => $plugin->provider,
            'filamentPlugin' => $plugin->filamentPlugin,
            'tags' => $plugin->tags,
            'compatibility' => $plugin->getCompatibility(),
            'hasFilamentPlugin' => $plugin->hasFilamentPlugin(),
            'hasPublicRoutes' => $plugin->hasPublicRoutes(),
            'publicRoutes' => $plugin->getPublicRoutes(),
            'hasPrefixedRoutes' => $plugin->hasPrefixedRoutes(),
            'hasMigrations' => $plugin->hasMigrations(),
            'migrations' => $migrationStatus,
            'meetsRequirements' => $plugin->meetsRequirements(),
            'unmetRequirements' => $plugin->getUnmetRequirements(),
            'path' => $plugin->path,
            'backups' => $backups,
            'requiresLicense' => $plugin->requiresLicense(),
            'licenseSlug' => $plugin->getLicenseSlug(),
            'licenseStatus' => $plugin->requiresLicense()
                ? ($this->licenseStatuses[$plugin->getLicenseSlug()] ?? null)
                : null,
            'hasPendingMigrations' => $this->getMigrator()->hasPendingMigrations($plugin),
            'hasUpdate' => isset($availableUpdates[$plugin->getLicenseSlug()]),
            'updateInfo' => $availableUpdates[$plugin->getLicenseSlug()] ?? null,
        ];

        $this->dispatch('open-modal', id: 'plugin-details-modal');
    }

    /**
     * Close plugin details modal
     */
    public function closePluginDetails(): void
    {
        $this->selectedPlugin = null;
        $this->pluginDetails = null;
    }

    /**
     * Refresh plugin list
     */
    public function refreshPlugins(): void
    {
        $this->getPluginManager()->refreshCache();

        Notification::make()
            ->title(__('tallcms::ui.t_plugins_refreshed'))
            ->body(__('tallcms::ui.t_plugin_list_has_been_refreshed'))
            ->success()
            ->send();

        unset($this->plugins);
    }

    /**
     * Run pending migrations for a plugin
     */
    public function runMigrations(string $vendor, string $slug): void
    {
        $plugin = $this->getPluginManager()->find($vendor, $slug);

        if (! $plugin) {
            Notification::make()
                ->title(__('tallcms::ui.t_plugin_not_found'))
                ->danger()
                ->send();

            return;
        }

        $result = $this->getMigrator()->migrate($plugin);

        if ($result->success) {
            $count = count($result->migrations);
            Notification::make()
                ->title(__('tallcms::ui.t_migrations_completed'))
                ->body(__('tallcms::ui.n_ran_count_migration_s_for_name', ['count' => $count, 'name' => $plugin->name]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('tallcms::ui.t_migration_failed'))
                ->body(implode("\n", $result->errors))
                ->danger()
                ->send();
        }

        unset($this->plugins);
    }

    /**
     * One-click update for a plugin
     */
    public function oneClickUpdate(string $vendor, string $slug): void
    {
        // Guard: respect uploads config for one-click updates too
        if (! $this->getPluginManager()->uploadsAllowed()) {
            Notification::make()
                ->title(__('tallcms::ui.t_updates_disabled'))
                ->body(__('tallcms::ui.t_plugin_updates_are_not_enabled_in_configuration'))
                ->danger()
                ->send();

            return;
        }

        // 1. Find plugin (guard null)
        $plugin = $this->getPluginManager()->find($vendor, $slug);
        if (! $plugin) {
            Notification::make()
                ->title(__('tallcms::ui.t_plugin_not_found'))
                ->body(__('tallcms::ui.n_could_not_find_plugin_vendor_slug', ['vendor' => $vendor, 'slug' => $slug]))
                ->danger()
                ->send();

            return;
        }

        $pluginSlug = $plugin->getLicenseSlug();
        $licenseService = app(PluginLicenseService::class);

        // 2. Call checkForUpdates() which BOTH validates license AND returns fresh download URL
        //    (Anystack always returns the latest version URL - don't use cached URLs)
        $updateCheck = $licenseService->checkForUpdates($pluginSlug);

        // 3. Check if update check succeeded
        if (! ($updateCheck['success'] ?? false)) {
            // Determine if this is a license issue or network error:
            // - purchase_url present = explicit license issue from server
            // - no local license exists = license issue (user never activated)
            // - otherwise = likely network/server error
            $hasLocalLicense = PluginLicense::findByPluginSlug($pluginSlug) !== null;
            $isLicenseIssue = ! empty($updateCheck['purchase_url']) || ! $hasLocalLicense;

            if ($isLicenseIssue) {
                Notification::make()
                    ->title(__('tallcms::ui.t_license_required'))
                    ->body($updateCheck['message'] ?? 'A valid license is required to download updates.')
                    ->danger()
                    ->actions([
                        Action::make('manage_license')
                            ->label(__('tallcms::fields.manage_license'))
                            ->url(static::getUrl(['plugin' => $pluginSlug])),
                    ])
                    ->send();
            } else {
                // Network/server error (has license but check failed without purchase_url signal)
                Notification::make()
                    ->title(__('tallcms::ui.t_update_check_failed'))
                    ->body($updateCheck['message'] ?? 'Could not check for updates. Please try again.')
                    ->danger()
                    ->send();
            }

            return;
        }

        // 4. Verify update is available and download URL exists
        if (! $updateCheck['update_available']) {
            Notification::make()->title(__('tallcms::ui.t_already_up_to_date'))->success()->send();

            return;
        }

        if (empty($updateCheck['download_url'])) {
            Notification::make()
                ->title(__('tallcms::ui.t_download_unavailable'))
                ->body(__('tallcms::ui.t_could_not_get_download_url_please_try_again_or_download_manually'))
                ->warning()
                ->send();

            return;
        }

        // 5. Download ZIP from fresh URL
        $downloadUrl = $updateCheck['download_url'];
        $tempPath = storage_path('app/plugin-downloads/'.$vendor.'-'.$slug.'-'.uniqid().'.zip');

        try {
            File::ensureDirectoryExists(dirname($tempPath));

            Log::info('One-click update: Downloading', [
                'plugin' => "{$vendor}/{$slug}",
                'url' => $downloadUrl,
            ]);

            $response = Http::timeout(60)
                ->withOptions(['allow_redirects' => true])
                ->get($downloadUrl);

            if (! $response->successful()) {
                Log::error('One-click update: Download HTTP error', [
                    'plugin' => "{$vendor}/{$slug}",
                    'status' => $response->status(),
                ]);
                Notification::make()
                    ->title(__('tallcms::ui.t_download_failed'))
                    ->body('HTTP status: '.$response->status())
                    ->danger()
                    ->send();

                return;
            }

            $body = $response->body();
            $contentType = $response->header('Content-Type') ?? 'unknown';

            // Validate response is actually a ZIP file (magic bytes: PK)
            if (strlen($body) < 4 || substr($body, 0, 2) !== 'PK') {
                Log::error('One-click update: Invalid ZIP response', [
                    'plugin' => "{$vendor}/{$slug}",
                    'content_type' => $contentType,
                    'body_length' => strlen($body),
                    'body_preview' => substr($body, 0, 200),
                ]);
                Notification::make()
                    ->title(__('tallcms::ui.t_download_failed'))
                    ->body(__('tallcms::ui.t_server_returned_invalid_response_not_a_zip_file_check_logs_for_detai'))
                    ->danger()
                    ->send();

                return;
            }

            File::put($tempPath, $body);

            // 6. Apply update
            $result = $this->getPluginManager()->update($tempPath);

            if ($result->success) {
                // Clear update cache so badges refresh
                $licenseService->clearUpdateCache();
                $this->availableUpdates = null;
                unset($this->plugins);
                unset($this->filteredPlugins);

                Notification::make()
                    ->title(__('tallcms::ui.t_plugin_updated'))
                    ->body($result->message)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('tallcms::ui.t_update_failed'))
                    ->body(implode("\n", $result->errors))
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('One-click update failed', ['plugin' => "{$vendor}/{$slug}", 'error' => $e->getMessage()]);
            Notification::make()
                ->title(__('tallcms::ui.t_update_failed'))
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();
        } finally {
            // Clean up temp file
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
        }
    }

    /**
     * Refresh a single plugin's license status
     */
    public function refreshLicenseStatus(string $pluginSlug): void
    {
        $licenseService = app(PluginLicenseService::class);

        // Force revalidation by clearing cache and checking validity
        $licenseService->clearCache($pluginSlug);
        $licenseService->isValid($pluginSlug);

        $this->refreshLicenseState();

        Notification::make()
            ->title(__('tallcms::ui.t_status_refreshed'))
            ->body(__('tallcms::ui.t_license_status_has_been_refreshed_from_the_server'))
            ->success()
            ->send();
    }

    /**
     * Check for updates for a single plugin
     */
    public function checkForUpdates(string $pluginSlug): void
    {
        $licenseService = app(PluginLicenseService::class);
        $result = $licenseService->checkForUpdates($pluginSlug);

        if (! $result['success']) {
            if ($result['purchase_url'] ?? null) {
                Notification::make()
                    ->title(__('tallcms::ui.t_license_required'))
                    ->body($result['message'])
                    ->warning()
                    ->actions([
                        Action::make('purchase')
                            ->label(__('tallcms::fields.purchase_license'))
                            ->url($result['purchase_url'])
                            ->openUrlInNewTab(),
                    ])
                    ->send();
            } else {
                Notification::make()
                    ->title(__('tallcms::ui.t_update_check_failed_2'))
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }

            return;
        }

        // Surgically update the cached update list for this plugin
        $checkInterval = config('tallcms.plugins.license.update_check_interval', 86400);
        $updates = Cache::get('plugin_available_updates', []);

        if ($result['update_available'] ?? false) {
            $updates[$pluginSlug] = [
                'plugin_name' => $result['plugin_name'] ?? $pluginSlug,
                'current_version' => $result['current_version'] ?? '0.0.0',
                'latest_version' => $result['latest_version'],
                'download_url' => $result['download_url'] ?? null,
                'changelog_url' => $result['changelog_url'] ?? null,
            ];

            Notification::make()
                ->title(__('tallcms::ui.t_update_available'))
                ->body(__('tallcms::ui.n_version_latest_version_is_available_you_have_current_ve', ['latest_version' => $result['latest_version'], 'current_version' => $result['current_version']]))
                ->success()
                ->send();
        } else {
            // Remove stale entry if plugin is now up to date
            unset($updates[$pluginSlug]);

            Notification::make()
                ->title(__('tallcms::ui.t_up_to_date'))
                ->body(__('tallcms::ui.n_you_have_the_latest_version_current_version', ['current_version' => $result['current_version']]))
                ->success()
                ->send();
        }

        Cache::put('plugin_available_updates', $updates, $checkInterval);

        // Reset in-memory state so computed properties re-read the updated cache
        $this->availableUpdates = null;
        unset($this->plugins);
        unset($this->filteredPlugins);
    }

    /**
     * Activate license action with modal form
     */
    public function activateLicenseAction(): Action
    {
        return Action::make('activateLicense')
            ->label(__('tallcms::fields.activate_license'))
            ->icon('heroicon-o-key')
            ->color('primary')
            ->modalHeading(fn (array $arguments) => "Activate License — {$arguments['name']}")
            ->modalDescription(__('tallcms::ui.t_enter_your_license_key_from_your_purchase_email'))
            ->form([
                TextInput::make('license_key')
                    ->label(__('tallcms::fields.license_key'))
                    ->placeholder('XXXX-XXXX-XXXX-XXXX')
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $result = app(PluginLicenseService::class)->activate(
                    $arguments['pluginSlug'],
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
                            ->title(__('tallcms::ui.t_plugin_not_supported'))
                            ->body(__('tallcms::ui.t_this_plugin_does_not_support_license_activation'))
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

                $this->refreshPluginState();
            });
    }

    /**
     * Deactivate license action with confirmation modal
     */
    public function deactivateLicenseAction(): Action
    {
        return Action::make('deactivateLicense')
            ->label(__('tallcms::fields.deactivate'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => "Deactivate License — {$arguments['name']}")
            ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_deactivate_this_license_the_plugin_may_lose'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_deactivate'))
            ->action(function (array $arguments) {
                $result = app(PluginLicenseService::class)->deactivate($arguments['pluginSlug']);

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

                $this->refreshPluginState();
            });
    }

    /**
     * Apply update action with confirmation modal
     */
    public function applyUpdateAction(): Action
    {
        return Action::make('applyUpdate')
            ->label(__('tallcms::fields.update'))
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_update_plugin'))
            ->modalDescription(fn (array $arguments) => "Update '{$arguments['name']}' to v{$arguments['latest_version']}? A backup will be created.")
            ->action(fn (array $arguments) => $this->oneClickUpdate($arguments['vendor'], $arguments['slug']));
    }

    /**
     * Uninstall action with Filament confirmation modal
     */
    public function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->label(__('tallcms::fields.uninstall'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_uninstall_plugin'))
            ->modalDescription(function (array $arguments) {
                $message = "Are you sure you want to uninstall '{$arguments['name']}'? This will rollback all migrations and remove the plugin files. This action cannot be undone.";

                $plugin = $this->getPluginManager()->find($arguments['vendor'], $arguments['slug']);
                if ($plugin && $plugin->requiresLicense()) {
                    $status = $this->licenseStatuses[$plugin->getLicenseSlug()] ?? null;
                    if ($status && ($status['has_license'] ?? false) && ($status['is_valid'] ?? false)) {
                        $message .= "\n\n⚠️ This plugin has an active license. Uninstalling will NOT deactivate the license — it will remain allocated to this domain. Consider deactivating the license first to free the activation slot.";
                    }
                }

                return $message;
            })
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_uninstall'))
            ->action(function (array $arguments) {
                $vendor = $arguments['vendor'];
                $slug = $arguments['slug'];

                // Check if plugin has an active license before uninstalling
                $plugin = $this->getPluginManager()->find($vendor, $slug);
                $hadActiveLicense = false;
                if ($plugin && $plugin->requiresLicense()) {
                    $status = $this->licenseStatuses[$plugin->getLicenseSlug()] ?? null;
                    $hadActiveLicense = $status && ($status['has_license'] ?? false) && ($status['is_valid'] ?? false);
                }

                $result = $this->getPluginManager()->uninstall($vendor, $slug);

                if ($result->success) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_plugin_uninstalled'))
                        ->body(__('tallcms::ui.n_name_has_been_removed', ['name' => $arguments['name']]))
                        ->success()
                        ->send();

                    if ($hadActiveLicense) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_license_still_active'))
                            ->body(__('tallcms::ui.n_the_license_for_name_is_still_allocated_to_this_domain_', ['name' => $arguments['name']]))
                            ->warning()
                            ->persistent()
                            ->send();
                    }

                    $this->closePluginDetails();
                    unset($this->plugins);
                } else {
                    Notification::make()
                        ->title(__('tallcms::ui.t_uninstall_failed'))
                        ->body(implode("\n", $result->errors))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Rollback action with Filament confirmation modal
     */
    public function rollbackAction(): Action
    {
        return Action::make('rollback')
            ->label(__('tallcms::fields.rollback'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_rollback_plugin'))
            ->modalDescription(fn (array $arguments) => "Are you sure you want to rollback '{$arguments['name']}' to version {$arguments['version']}?")
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_rollback'))
            ->action(function (array $arguments) {
                $vendor = $arguments['vendor'];
                $slug = $arguments['slug'];
                $version = $arguments['version'];

                $result = $this->getPluginManager()->rollback($vendor, $slug, $version);

                if ($result->success) {
                    Notification::make()
                        ->title(__('tallcms::ui.t_rollback_successful'))
                        ->body($result->message)
                        ->success()
                        ->send();

                    $this->closePluginDetails();
                    unset($this->plugins);
                } else {
                    Notification::make()
                        ->title(__('tallcms::ui.t_rollback_failed'))
                        ->body(implode("\n", $result->errors))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshAllLicenses')
                ->label(__('tallcms::fields.refresh_licenses'))
                ->icon('heroicon-o-key')
                ->color('gray')
                ->action(function () {
                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearCache();

                    foreach ($this->licenseStatuses as $pluginSlug => $status) {
                        if ($status['has_license']) {
                            $licenseService->isValid($pluginSlug);
                        }
                    }

                    $this->refreshLicenseState();

                    Notification::make()
                        ->title(__('tallcms::ui.t_all_statuses_refreshed'))
                        ->success()
                        ->send();
                })
                ->visible(fn () => collect($this->licenseStatuses)->contains('has_license', true)),

            Action::make('checkUpdates')
                ->label(__('tallcms::fields.check_for_updates'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearUpdateCache();
                    app(MarketplaceCatalogService::class)->clearCache();

                    // Use checkAllForUpdates() to get detailed results including failures
                    $results = $licenseService->checkAllForUpdates();

                    // Build updates cache and count results
                    $updates = [];
                    $failedChecks = [];

                    foreach ($results as $pluginSlug => $result) {
                        if ($result['success'] ?? false) {
                            if ($result['update_available'] ?? false) {
                                $updates[$pluginSlug] = [
                                    'plugin_name' => $result['plugin_name'] ?? $pluginSlug,
                                    'current_version' => $result['current_version'] ?? '0.0.0',
                                    'latest_version' => $result['latest_version'],
                                    'download_url' => $result['download_url'] ?? null,
                                    'changelog_url' => $result['changelog_url'] ?? null,
                                ];
                            }
                        } else {
                            // Determine if this is a license issue or network error:
                            // - purchase_url present = explicit license issue from server/config
                            // - no local license exists = license issue (user never activated)
                            // - otherwise = likely network/server error
                            $hasLocalLicense = PluginLicense::findByPluginSlug($pluginSlug) !== null;
                            $isLicenseIssue = ! empty($result['purchase_url']) || ! $hasLocalLicense;

                            if (! $isLicenseIssue) {
                                $failedChecks[] = $result['plugin_name'] ?? $pluginSlug;
                            }
                        }
                    }

                    // Repopulate cache so badges update correctly and reset check interval
                    $checkInterval = config('tallcms.plugins.license.update_check_interval', 86400);
                    Cache::put('plugin_available_updates', $updates, $checkInterval);
                    Cache::put('plugin_updates_last_check', now(), $checkInterval);

                    // Reset all cached/computed properties to force refresh
                    $this->availableUpdates = null;
                    unset($this->plugins);
                    unset($this->filteredPlugins);

                    // Show appropriate notification
                    $updateCount = count($updates);
                    if (! empty($failedChecks)) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_update_check_completed_with_errors'))
                            ->body($updateCount > 0
                                ? "{$updateCount} update(s) found. Could not check: ".implode(', ', $failedChecks)
                                : 'Could not check: '.implode(', ', $failedChecks))
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title($updateCount > 0 ? "{$updateCount} update(s) available" : 'All plugins up to date')
                            ->success()
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label(__('tallcms::fields.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshPlugins()),

            // Combined Install/Update Plugin action
            Action::make('install')
                ->label(__('tallcms::fields.install_update_plugin'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => $this->getPluginManager()->uploadsAllowed())
                ->form([
                    FileUpload::make('plugin_zip')
                        ->label(__('tallcms::fields.plugin_package_zip'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50 * 1024) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('plugin-uploads')
                        ->helperText(__('tallcms::ui.t_upload_a_plugin_package_auto_detects_new_install_vs_update')),
                ])
                ->action(function (array $data) {
                    // Server-side guard
                    if (! $this->getPluginManager()->uploadsAllowed()) {
                        Notification::make()
                            ->title(__('tallcms::ui.t_uploads_disabled'))
                            ->body(__('tallcms::ui.t_plugin_uploads_are_not_enabled_in_configuration'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $uploadedFile = $data['plugin_zip'];
                    $zipPath = Storage::disk('local')->path($uploadedFile);

                    try {
                        // Validate ZIP and extract plugin info
                        $validationResult = $this->getValidator()->validateZip($zipPath);

                        if (! $validationResult->isValid) {
                            Notification::make()
                                ->title(__('tallcms::ui.t_invalid_plugin_package'))
                                ->body(implode("\n", $validationResult->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        $pluginInfo = $validationResult->pluginData;
                        $isUpdate = $this->getPluginManager()->isInstalled($pluginInfo['vendor'], $pluginInfo['slug']);

                        if ($isUpdate) {
                            $result = $this->getPluginManager()->update($zipPath);
                            $actionVerb = __('tallcms::ui.t_updated');
                        } else {
                            $result = $this->getPluginManager()->installFromZip($zipPath);
                            $actionVerb = __('tallcms::ui.t_installed');
                        }

                        if ($result->success) {
                            // Show warnings if any
                            foreach ($result->warnings as $warning) {
                                Notification::make()
                                    ->title(__('tallcms::ui.t_warning'))
                                    ->body($warning)
                                    ->warning()
                                    ->send();
                            }

                            $migrationCount = count($result->migrations);
                            $migrationMsg = $migrationCount > 0 ? " ({$migrationCount} migration(s) ran)" : '';

                            Notification::make()
                                ->title(__('tallcms::ui.n_plugin_actionverb', ['actionverb' => $actionVerb]))
                                ->body(__('tallcms::ui.n_name_v_version_has_been_actionverb_migrationmsg', ['name' => $result->plugin->name, 'version' => $result->plugin->version, 'actionverb' => $actionVerb, 'migrationmsg' => $migrationMsg]))
                                ->success()
                                ->send();

                            // Clear update cache so badges refresh (especially for manual updates)
                            if ($isUpdate) {
                                app(PluginLicenseService::class)->clearUpdateCache();
                                $this->availableUpdates = null;
                            }

                            unset($this->plugins);
                            unset($this->filteredPlugins);
                        } else {
                            Notification::make()
                                ->title($isUpdate ? __('tallcms::ui.t_update_failed') : __('tallcms::ui.t_installation_failed'))
                                ->body(implode("\n", $result->errors))
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Log::error('Plugin upload failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title(__('tallcms::ui.t_upload_failed'))
                            ->body('An unexpected error occurred: '.$e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($uploadedFile);
                    }
                }),
        ];
    }
}
