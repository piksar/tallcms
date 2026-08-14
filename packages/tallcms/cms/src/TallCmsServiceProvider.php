<?php

declare(strict_types=1);

namespace TallCms\Cms;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TallCms\Cms\Providers\PluginServiceProvider;
use TallCms\Cms\Providers\ThemeServiceProvider;

class TallCmsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'tallcms';

    /**
     * Class aliases for backwards compatibility.
     * Maps App\* classes to their new TallCms\Cms\* locations.
     */
    protected array $classAliases = [
        // Contracts
        'App\\Contracts\\ThemeInterface' => Contracts\ThemeInterface::class,

        // Support
        'App\\Support\\ThemeColors' => Support\ThemeColors::class,

        // Exceptions
        'App\\Exceptions\\ConfigurationException' => Exceptions\ConfigurationException::class,
        'App\\Exceptions\\DownloadException' => Exceptions\DownloadException::class,
        'App\\Exceptions\\ExtractionException' => Exceptions\ExtractionException::class,
        'App\\Exceptions\\IncompatiblePlatformException' => Exceptions\IncompatiblePlatformException::class,
        'App\\Exceptions\\IncompatibleVersionException' => Exceptions\IncompatibleVersionException::class,
        'App\\Exceptions\\InsufficientDiskSpaceException' => Exceptions\InsufficientDiskSpaceException::class,
        'App\\Exceptions\\IntegrityException' => Exceptions\IntegrityException::class,
        'App\\Exceptions\\InvalidReleaseException' => Exceptions\InvalidReleaseException::class,
        'App\\Exceptions\\MissingDependencyException' => Exceptions\MissingDependencyException::class,
        'App\\Exceptions\\SecurityException' => Exceptions\SecurityException::class,
        'App\\Exceptions\\SignatureException' => Exceptions\SignatureException::class,
        'App\\Exceptions\\UpdateException' => Exceptions\UpdateException::class,
        'App\\Exceptions\\UpdateInProgressException' => Exceptions\UpdateInProgressException::class,

        // Models
        'App\\Models\\CmsCategory' => Models\CmsCategory::class,
        'App\\Models\\CmsPage' => Models\CmsPage::class,
        'App\\Models\\CmsPost' => Models\CmsPost::class,
        'App\\Models\\Plugin' => Models\Plugin::class,
        'App\\Models\\PluginLicense' => Models\PluginLicense::class,
        'App\\Models\\Theme' => Models\Theme::class,
        'App\\Models\\TallcmsMenu' => Models\TallcmsMenu::class,
        'App\\Models\\TallcmsMenuItem' => Models\TallcmsMenuItem::class,
        'App\\Models\\SiteSetting' => Models\SiteSetting::class,
        'App\\Models\\TallcmsContactSubmission' => Models\TallcmsContactSubmission::class,
        'App\\Models\\CmsComment' => Models\CmsComment::class,
        'App\\Models\\CmsRevision' => Models\CmsRevision::class,
        'App\\Models\\CmsPreviewToken' => Models\CmsPreviewToken::class,
        'App\\Models\\TallcmsMedia' => Models\TallcmsMedia::class,
        'App\\Models\\MediaCollection' => Models\MediaCollection::class,

        // Model Concerns
        'App\\Models\\Concerns\\HasPublishingWorkflow' => Models\Concerns\HasPublishingWorkflow::class,
        'App\\Models\\Concerns\\HasPreviewTokens' => Models\Concerns\HasPreviewTokens::class,
        'App\\Models\\Concerns\\HasRevisions' => Models\Concerns\HasRevisions::class,

        // Services
        'App\\Services\\BlockLinkResolver' => Services\BlockLinkResolver::class,
        'App\\Services\\ContentDiffService' => Services\ContentDiffService::class,
        'App\\Services\\CustomBlockDiscoveryService' => Services\CustomBlockDiscoveryService::class,
        'App\\Services\\EnvWriter' => Services\EnvWriter::class,
        'App\\Services\\EnvironmentChecker' => Services\EnvironmentChecker::class,
        'App\\Services\\FileBasedTheme' => Services\FileBasedTheme::class,
        'App\\Services\\HtmlSanitizerService' => Services\HtmlSanitizerService::class,
        'App\\Services\\InstallerRunner' => Services\InstallerRunner::class,
        'App\\Services\\LicenseProxyClient' => Services\LicenseProxyClient::class,
        'App\\Services\\MenuUrlResolver' => Services\MenuUrlResolver::class,
        'App\\Services\\MergeTagService' => Services\MergeTagService::class,
        'App\\Services\\PluginLicenseService' => Services\PluginLicenseService::class,
        'App\\Services\\PluginManager' => Services\PluginManager::class,
        'App\\Services\\PluginMigrationRepository' => Services\PluginMigrationRepository::class,
        'App\\Services\\PluginMigrator' => Services\PluginMigrator::class,
        'App\\Services\\PluginValidator' => Services\PluginValidator::class,
        'App\\Services\\PublishingWorkflowService' => Services\PublishingWorkflowService::class,
        'App\\Services\\TallCmsUpdater' => Services\TallCmsUpdater::class,
        'App\\Services\\ThemeManager' => Services\ThemeManager::class,
        'App\\Services\\ThemeResolver' => Services\ThemeResolver::class,
        'App\\Services\\ThemeValidator' => Services\ThemeValidator::class,
        'App\\Services\\TemplateRegistry' => Services\TemplateRegistry::class,
        'App\\Services\\WidgetRegistry' => Services\WidgetRegistry::class,

        // Providers
        'App\\Providers\\PluginServiceProvider' => Providers\PluginServiceProvider::class,
        'App\\Providers\\ThemeServiceProvider' => Providers\ThemeServiceProvider::class,

        // Events
        'App\\Events\\PluginInstalled' => Events\PluginInstalled::class,
        'App\\Events\\PluginInstalling' => Events\PluginInstalling::class,
        'App\\Events\\PluginUninstalled' => Events\PluginUninstalled::class,
        'App\\Events\\PluginUninstalling' => Events\PluginUninstalling::class,
        'App\\Events\\ThemeActivated' => Events\ThemeActivated::class,
        'App\\Events\\ThemeActivating' => Events\ThemeActivating::class,
        'App\\Events\\ThemeInstalled' => Events\ThemeInstalled::class,
        'App\\Events\\ThemeInstalling' => Events\ThemeInstalling::class,
        'App\\Events\\ThemeRollback' => Events\ThemeRollback::class,

        // Notifications
        'App\\Notifications\\ContentApprovedNotification' => Notifications\ContentApprovedNotification::class,
        'App\\Notifications\\ContentRejectedNotification' => Notifications\ContentRejectedNotification::class,
        'App\\Notifications\\ContentSubmittedForReviewNotification' => Notifications\ContentSubmittedForReviewNotification::class,
        'App\\Notifications\\NewCommentNotification' => Notifications\NewCommentNotification::class,
        'App\\Notifications\\CommentApprovedNotification' => Notifications\CommentApprovedNotification::class,

        // Enums
        'App\\Enums\\ContentStatus' => Enums\ContentStatus::class,

        // Controllers
        'App\\Http\\Controllers\\CommentController' => Http\Controllers\CommentController::class,
        'App\\Http\\Controllers\\ContactFormController' => Http\Controllers\ContactFormController::class,
        'App\\Http\\Controllers\\PreviewController' => Http\Controllers\PreviewController::class,

        // Middleware
        'App\\Http\\Middleware\\MaintenanceModeMiddleware' => Http\Middleware\MaintenanceModeMiddleware::class,
        'App\\Http\\Middleware\\ThemePreviewMiddleware' => Http\Middleware\ThemePreviewMiddleware::class,

        // Livewire Components
        'App\\Livewire\\CmsPageRenderer' => Livewire\CmsPageRenderer::class,

        // Mail
        'App\\Mail\\ContactFormAdminNotification' => Mail\ContactFormAdminNotification::class,
        'App\\Mail\\ContactFormAutoReply' => Mail\ContactFormAutoReply::class,

        // Console Commands
        'App\\Console\\Commands\\CleanExpiredPreviewTokens' => Console\Commands\CleanExpiredPreviewTokens::class,
        'App\\Console\\Commands\\LicenseTestCommand' => Console\Commands\LicenseTestCommand::class,
        'App\\Console\\Commands\\MakePluginCommand' => Console\Commands\MakePluginCommand::class,
        'App\\Console\\Commands\\MakeTallCmsBlock' => Console\Commands\MakeTallCmsBlock::class,
        'App\\Console\\Commands\\MakeTheme' => Console\Commands\MakeTheme::class,
        'App\\Console\\Commands\\PluginCleanupBackupsCommand' => Console\Commands\PluginCleanupBackupsCommand::class,
        'App\\Console\\Commands\\PluginInstallCommand' => Console\Commands\PluginInstallCommand::class,
        'App\\Console\\Commands\\PluginListCommand' => Console\Commands\PluginListCommand::class,
        'App\\Console\\Commands\\PluginMigrateCommand' => Console\Commands\PluginMigrateCommand::class,
        'App\\Console\\Commands\\PluginUninstallCommand' => Console\Commands\PluginUninstallCommand::class,
        'App\\Console\\Commands\\TallCmsGenerateKeypair' => Console\Commands\TallCmsGenerateKeypair::class,
        'App\\Console\\Commands\\TallCmsGenerateManifest' => Console\Commands\TallCmsGenerateManifest::class,
        'App\\Console\\Commands\\TallCmsSetup' => Console\Commands\TallCmsSetup::class,
        'App\\Console\\Commands\\TallCmsSignRelease' => Console\Commands\TallCmsSignRelease::class,
        'App\\Console\\Commands\\TallCmsUpdate' => Console\Commands\TallCmsUpdate::class,
        'App\\Console\\Commands\\TallCmsVersion' => Console\Commands\TallCmsVersion::class,
        'App\\Console\\Commands\\ThemeActivate' => Console\Commands\ThemeActivate::class,
        'App\\Console\\Commands\\ThemeBuild' => Console\Commands\ThemeBuild::class,
        'App\\Console\\Commands\\ThemeCacheClear' => Console\Commands\ThemeCacheClear::class,
        'App\\Console\\Commands\\ThemeInstallCommand' => Console\Commands\ThemeInstallCommand::class,
        'App\\Console\\Commands\\ThemeList' => Console\Commands\ThemeList::class,

        // Filament Blocks
        'App\\Filament\\Blocks\\CallToActionBlock' => Filament\Blocks\CallToActionBlock::class,
        'App\\Filament\\Blocks\\ContactFormBlock' => Filament\Blocks\ContactFormBlock::class,
        'App\\Filament\\Blocks\\ContentBlockBlock' => Filament\Blocks\ContentBlockBlock::class,
        'App\\Filament\\Blocks\\DividerBlock' => Filament\Blocks\DividerBlock::class,
        'App\\Filament\\Blocks\\FaqBlock' => Filament\Blocks\FaqBlock::class,
        'App\\Filament\\Blocks\\FeaturesBlock' => Filament\Blocks\FeaturesBlock::class,
        'App\\Filament\\Blocks\\HeroBlock' => Filament\Blocks\HeroBlock::class,
        'App\\Filament\\Blocks\\HowToBlock' => Filament\Blocks\HowToBlock::class,
        'App\\Filament\\Blocks\\ImageGalleryBlock' => Filament\Blocks\ImageGalleryBlock::class,
        'App\\Filament\\Blocks\\LogosBlock' => Filament\Blocks\LogosBlock::class,
        'App\\Filament\\Blocks\\ParallaxBlock' => Filament\Blocks\ParallaxBlock::class,
        'App\\Filament\\Blocks\\PostsBlock' => Filament\Blocks\PostsBlock::class,
        'App\\Filament\\Blocks\\PricingBlock' => Filament\Blocks\PricingBlock::class,
        'App\\Filament\\Blocks\\StatsBlock' => Filament\Blocks\StatsBlock::class,
        'App\\Filament\\Blocks\\TeamBlock' => Filament\Blocks\TeamBlock::class,
        'App\\Filament\\Blocks\\TestimonialsBlock' => Filament\Blocks\TestimonialsBlock::class,
        'App\\Filament\\Blocks\\TimelineBlock' => Filament\Blocks\TimelineBlock::class,

        // Filament Block Concerns
        'App\\Filament\\Blocks\\Concerns\\HasDaisyUIOptions' => Filament\Blocks\Concerns\HasDaisyUIOptions::class,

        // Filament Resources - CmsCategories
        'App\\Filament\\Resources\\CmsCategories\\CmsCategoryResource' => Filament\Resources\CmsCategories\CmsCategoryResource::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\CreateCmsCategory' => Filament\Resources\CmsCategories\Pages\CreateCmsCategory::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\EditCmsCategory' => Filament\Resources\CmsCategories\Pages\EditCmsCategory::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\ListCmsCategories' => Filament\Resources\CmsCategories\Pages\ListCmsCategories::class,
        'App\\Filament\\Resources\\CmsCategories\\Schemas\\CmsCategoryForm' => Filament\Resources\CmsCategories\Schemas\CmsCategoryForm::class,
        'App\\Filament\\Resources\\CmsCategories\\Tables\\CmsCategoriesTable' => Filament\Resources\CmsCategories\Tables\CmsCategoriesTable::class,

        // Filament Resources - CmsPages
        'App\\Filament\\Resources\\CmsPages\\CmsPageResource' => Filament\Resources\CmsPages\CmsPageResource::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\CreateCmsPage' => Filament\Resources\CmsPages\Pages\CreateCmsPage::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\EditCmsPage' => Filament\Resources\CmsPages\Pages\EditCmsPage::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\ListCmsPages' => Filament\Resources\CmsPages\Pages\ListCmsPages::class,
        'App\\Filament\\Resources\\CmsPages\\Schemas\\CmsPageForm' => Filament\Resources\CmsPages\Schemas\CmsPageForm::class,
        'App\\Filament\\Resources\\CmsPages\\Tables\\CmsPagesTable' => Filament\Resources\CmsPages\Tables\CmsPagesTable::class,

        // Filament Resources - CmsPosts
        'App\\Filament\\Resources\\CmsPosts\\CmsPostResource' => Filament\Resources\CmsPosts\CmsPostResource::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\CreateCmsPost' => Filament\Resources\CmsPosts\Pages\CreateCmsPost::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\EditCmsPost' => Filament\Resources\CmsPosts\Pages\EditCmsPost::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\ListCmsPosts' => Filament\Resources\CmsPosts\Pages\ListCmsPosts::class,
        'App\\Filament\\Resources\\CmsPosts\\Schemas\\CmsPostForm' => Filament\Resources\CmsPosts\Schemas\CmsPostForm::class,
        'App\\Filament\\Resources\\CmsPosts\\Tables\\CmsPostsTable' => Filament\Resources\CmsPosts\Tables\CmsPostsTable::class,

        // Filament Resources - CmsComments
        'App\\Filament\\Resources\\CmsComments\\CmsCommentResource' => Filament\Resources\CmsComments\CmsCommentResource::class,
        'App\\Filament\\Resources\\CmsComments\\Pages\\ListCmsComments' => Filament\Resources\CmsComments\Pages\ListCmsComments::class,
        'App\\Filament\\Resources\\CmsComments\\Pages\\ViewCmsComment' => Filament\Resources\CmsComments\Pages\ViewCmsComment::class,
        'App\\Filament\\Resources\\CmsComments\\Tables\\CmsCommentsTable' => Filament\Resources\CmsComments\Tables\CmsCommentsTable::class,

        // Filament Resources - TallcmsContactSubmissions
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\TallcmsContactSubmissionResource' => Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Pages\\ListTallcmsContactSubmissions' => Filament\Resources\TallcmsContactSubmissions\Pages\ListTallcmsContactSubmissions::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Pages\\ViewTallcmsContactSubmission' => Filament\Resources\TallcmsContactSubmissions\Pages\ViewTallcmsContactSubmission::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Tables\\TallcmsContactSubmissionsTable' => Filament\Resources\TallcmsContactSubmissions\Tables\TallcmsContactSubmissionsTable::class,

        // Filament Resources - TallcmsMedia
        'App\\Filament\\Resources\\TallcmsMedia\\TallcmsMediaResource' => Filament\Resources\TallcmsMedia\TallcmsMediaResource::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\CreateTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\EditTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\ListTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Schemas\\TallcmsMediaForm' => Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Tables\\TallcmsMediaTable' => Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable::class,

        // Filament Resources - TallcmsMenus
        'App\\Filament\\Resources\\TallcmsMenus\\TallcmsMenuResource' => Filament\Resources\TallcmsMenus\TallcmsMenuResource::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\CreateTallcmsMenu' => Filament\Resources\TallcmsMenus\Pages\CreateTallcmsMenu::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\EditTallcmsMenu' => Filament\Resources\TallcmsMenus\Pages\EditTallcmsMenu::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\ListTallcmsMenus' => Filament\Resources\TallcmsMenus\Pages\ListTallcmsMenus::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Schemas\\TallcmsMenuForm' => Filament\Resources\TallcmsMenus\Schemas\TallcmsMenuForm::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Tables\\TallcmsMenusTable' => Filament\Resources\TallcmsMenus\Tables\TallcmsMenusTable::class,

        // Livewire Components
        'App\\Livewire\\RevisionHistory' => Livewire\RevisionHistory::class,

        // Policies
        'App\\Policies\\CmsCategoryPolicy' => Policies\CmsCategoryPolicy::class,
        'App\\Policies\\CmsPagePolicy' => Policies\CmsPagePolicy::class,
        'App\\Policies\\CmsPostPolicy' => Policies\CmsPostPolicy::class,
        'App\\Policies\\RolePolicy' => Policies\RolePolicy::class,
        'App\\Policies\\CmsCommentPolicy' => Policies\CmsCommentPolicy::class,
        'App\\Policies\\TallcmsContactSubmissionPolicy' => Policies\TallcmsContactSubmissionPolicy::class,
        'App\\Policies\\TallcmsMediaPolicy' => Policies\TallcmsMediaPolicy::class,
        'App\\Policies\\TallcmsMenuPolicy' => Policies\TallcmsMenuPolicy::class,
        'App\\Policies\\UserPolicy' => Policies\UserPolicy::class,

        // Filament Pages
        'App\\Filament\\Pages\\MenuItemsManager' => Filament\Pages\MenuItemsManager::class,
        'App\\Filament\\Pages\\PluginLicenses' => Filament\Pages\PluginLicenses::class,
        'App\\Filament\\Pages\\PluginManager' => Filament\Pages\PluginManager::class,
        'App\\Filament\\Pages\\SeoSettings' => Filament\Pages\SeoSettings::class,
        'App\\Filament\\Pages\\SystemUpdates' => Filament\Pages\SystemUpdates::class,
        'App\\Filament\\Pages\\ThemeManager' => Filament\Pages\ThemeManager::class,
        'App\\Filament\\Pages\\UpdateManual' => Filament\Pages\UpdateManual::class,
        'App\\Filament\\Pages\\UpdateProgress' => Filament\Pages\UpdateProgress::class,

        // Filament Widgets
        'App\\Filament\\Widgets\\MenuOverviewWidget' => Filament\Widgets\MenuOverviewWidget::class,
        'App\\Filament\\Widgets\\PluginUpdatesWidget' => Filament\Widgets\PluginUpdatesWidget::class,

        // Jobs
        'App\\Jobs\\TallCmsUpdateJob' => Jobs\TallCmsUpdateJob::class,

        // Search
        'App\\Services\\ContentIndexer' => Services\ContentIndexer::class,
        'App\\Services\\SearchHighlighter' => Services\SearchHighlighter::class,
        'App\\Observers\\SearchContentObserver' => Observers\SearchContentObserver::class,
        'App\\Livewire\\SearchResults' => Livewire\SearchResults::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile(['tallcms', 'theme'])
            ->hasViews('tallcms')
            ->hasMigrations($this->getMigrations())
            ->runsMigrations()
            ->hasTranslations()
            ->hasAssets()
            ->hasCommands($this->getCommands());
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        // Load UI translations during register() so nested providers / Filament
        // plugins that boot before TallCmsServiceProvider::boot() can resolve
        // __('tallcms::…') without poisoning the translator cache with [].
        $this->registerPackageTranslations();

        // Register class aliases for backwards compatibility
        $this->registerClassAliases();

        // Register MenuUrlResolver singleton (needed for menu helper functions)
        $this->app->singleton('menu.url.resolver', Services\MenuUrlResolver::class);

        // Register LocaleRegistry singleton (needed for i18n features)
        $this->app->singleton(Services\LocaleRegistry::class);
        $this->app->alias(Services\LocaleRegistry::class, 'tallcms.locales');

        // Register TemplateRegistry singleton (needed for page templates)
        $this->app->singleton(Services\TemplateRegistry::class);

        // Register WidgetRegistry singleton (needed for sidebar widgets)
        $this->app->singleton(Services\WidgetRegistry::class);

        // Register search services
        $this->app->singleton(Services\ContentIndexer::class);
        $this->app->singleton(Services\SearchHighlighter::class);

        // Register the PluginServiceProvider and ThemeServiceProvider
        $this->app->register(PluginServiceProvider::class);
        $this->app->register(ThemeServiceProvider::class);

        // Only bind TallCmsUpdater service in standalone mode
        if ($this->isStandaloneMode()) {
            $this->app->singleton(Services\TallCmsUpdater::class);
        }

        // Register API/Webhook services
        $this->app->singleton(Services\WebhookDispatcher::class);
        $this->app->singleton(Services\WebhookUrlValidator::class);
        $this->app->singleton(Validation\TokenAbilityValidator::class);

        // Register custom CMS permissions with Filament Shield so they
        // appear in the role editor UI and aren't wiped on role save
        $this->mergeShieldCustomPermissions();
    }

    /**
     * Register package lang files early (register phase).
     *
     * Spatie Package Tools also loads these in boot() via hasTranslations();
     * calling loadTranslationsFrom again is safe (namespace hint is replaced).
     * Early registration avoids empty translator cache entries when other
     * providers resolve tallcms:: keys before TallCmsServiceProvider::boot().
     */
    protected function registerPackageTranslations(): void
    {
        $this->loadTranslationsFrom(
            $this->package->basePath('/../resources/lang'),
            $this->package->shortName(),
        );
    }

    /**
     * Merge CMS workflow permissions into Shield's custom_permissions config.
     * This ensures they appear in Shield's role editor UI alongside
     * auto-generated resource permissions, preventing accidental removal.
     */
    protected function mergeShieldCustomPermissions(): void
    {
        $existing = config('filament-shield.custom_permissions', []);

        // Source of truth for CMS permissions at runtime — also listed in TallCmsSetup and standalone Shield config
        $cmsPermissions = [
            'Approve:CmsPage',
            'Approve:CmsPost',
            'SubmitForReview:CmsPage',
            'SubmitForReview:CmsPost',
            'ViewRevisions:CmsPage',
            'ViewRevisions:CmsPost',
            'RestoreRevisions:CmsPage',
            'RestoreRevisions:CmsPost',
            'GeneratePreviewLink:CmsPage',
            'GeneratePreviewLink:CmsPost',
        ];

        config(['filament-shield.custom_permissions' => array_values(array_unique(array_merge($existing, $cmsPermissions)))]);

        // Ensure the custom permissions tab is visible
        if (! config('filament-shield.shield_resource.tabs.custom_permissions', false)) {
            config(['filament-shield.shield_resource.tabs.custom_permissions' => true]);
        }
    }

    /**
     * Register class aliases for backwards compatibility.
     * This allows existing code using App\* namespaces to continue working.
     * Skips aliasing if the original class/interface/trait already exists
     * (e.g., in a monorepo or when app/ classes haven't been removed).
     */
    protected function registerClassAliases(): void
    {
        foreach ($this->classAliases as $alias => $class) {
            // Check if alias target already exists as class, interface, or trait
            // Allow autoloading (no false param) to properly detect existing types
            if (! class_exists($alias) && ! interface_exists($alias) && ! trait_exists($alias)) {
                class_alias($class, $alias);
            }
        }
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Override version from composer.json (ensures correct version even with published config)
        $composerJson = __DIR__.'/../composer.json';
        if (file_exists($composerJson)) {
            $data = json_decode(file_get_contents($composerJson), true);
            if (isset($data['version'])) {
                config(['tallcms.version' => $data['version']]);
            }
        }

        // Publish Scribe config for API documentation
        $this->publishes([
            __DIR__.'/../config/scribe.php' => config_path('scribe.php'),
        ], 'tallcms-scribe-config');

        // Also register the package's compiled assets under Laravel's
        // conventional `laravel-assets` tag. The standalone scaffold's
        // composer post-update-cmd runs `vendor:publish --tag=laravel-assets
        // --force`, so any cms upgrade that ships a new tallcms.js / .css
        // refreshes public/vendor/tallcms/ without operators having to
        // remember the package-specific tag. Plugin-mode hosts that follow
        // the standard Laravel scaffold get the same auto-republish for
        // free. This was the path that broke push.sg's contact form when
        // _pageUrl naming changed in the runtime but public/ still held
        // the older _page_url copy.
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/tallcms'),
        ], ['laravel-assets', 'tallcms-assets-laravel']);

        // Register Livewire components
        $this->registerLivewireComponents();

        // Register middleware aliases before loading routes
        $this->registerMiddlewareAliases();
        $this->registerRootLocaleRedirectMiddleware();

        // Register Blade component aliases for theme compatibility
        $this->registerBladeComponentAliases();

        // Register Blade component namespace for <x-tallcms::*> syntax
        Blade::componentNamespace('TallCms\\Cms\\View\\Components', 'tallcms');

        // Register @accent Blade directive: maps a daisyUI semantic token to
        // a literal Tailwind class via AccentColor::resolve(). Used by blocks
        // to surface accent-color choice without hardcoding text-primary etc.
        Blade::directive(
            'accent',
            fn ($expression) => "<?php echo \\TallCms\\Cms\\Filament\\Blocks\\Support\\AccentColor::resolve({$expression}); ?>"
        );

        // Register admin CSS for block previews (DaisyUI components)
        // This is loaded from the package directly, no publishing required
        $adminCssPath = __DIR__.'/../resources/dist/tallcms-admin.css';
        if (file_exists($adminCssPath)) {
            FilamentAsset::register([
                Css::make('tallcms-admin', $adminCssPath),
            ], 'tallcms/cms');
        }

        // Block chrome (drag handle, reorder, duplicate) TipTap extension.
        // Loaded on demand via getTipTapJsExtensions() in BlockChromePlugin —
        // not as a global admin script.
        $blockChromeJsPath = __DIR__.'/../resources/dist/block-chrome.js';
        if (file_exists($blockChromeJsPath)) {
            FilamentAsset::register([
                Js::make('block-chrome', $blockChromeJsPath)->loadedOnRequest(),
            ], 'tallcms/cms');
        }

        // Block-preview daisyUI scoping (.cms-blocks). Standalone gets this
        // via @vite() in cms-rich-editor.blade.php; plugin-mode adopters
        // don't have the source file in their host vite.config.js, so the
        // package ships a pre-built copy and registers it here. Gated on
        // !standalone to avoid double-loading alongside the @vite-built copy.
        if (! $this->isStandaloneMode()) {
            $previewCssPath = __DIR__.'/../resources/dist/tallcms-preview.css';
            if (file_exists($previewCssPath)) {
                FilamentAsset::register([
                    Css::make('tallcms-preview', $previewCssPath),
                ], 'tallcms/cms');
            }
        }

        // Register frontend assets if published (optional)
        $cssPath = public_path('vendor/tallcms/tallcms.css');
        $jsPath = public_path('vendor/tallcms/tallcms.js');

        if (file_exists($cssPath) && file_exists($jsPath)) {
            FilamentAsset::register([
                Css::make('tallcms-styles', $cssPath),
                Js::make('tallcms-scripts', $jsPath),
            ], 'tallcms/cms');
        }

        // Boot mode-specific features
        if ($this->isStandaloneMode()) {
            $this->bootStandaloneFeatures();
        } else {
            $this->bootPluginFeatures();
        }

        // Register search observer and warn if Scout not configured
        $this->bootSearchFeatures();

        // Register webhook observers for model events
        $this->bootWebhookFeatures();
    }

    /**
     * Boot search-related features.
     */
    protected function bootSearchFeatures(): void
    {
        if (! config('tallcms.search.enabled', true)) {
            return;
        }

        // Register search content observer
        Models\CmsPage::observe(Observers\SearchContentObserver::class);
        Models\CmsPost::observe(Observers\SearchContentObserver::class);

        // Register search route respecting mode and i18n settings
        $this->registerSearchRoute();

    }

    /**
     * Boot webhook-related features.
     */
    protected function bootWebhookFeatures(): void
    {
        if (! config('tallcms.webhooks.enabled', false)) {
            return;
        }

        // Register webhook observer for content models
        $observer = Observers\WebhookObserver::class;

        Models\CmsPage::observe($observer);
        Models\CmsPost::observe($observer);
        Models\CmsCategory::observe($observer);
        Models\TallcmsMedia::observe($observer);
        Models\MediaCollection::observe($observer);
    }

    /**
     * Register search route respecting plugin mode prefix and i18n locale prefixes.
     */
    protected function registerSearchRoute(): void
    {
        $i18nEnabled = config('tallcms.i18n.enabled', false);
        $urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');

        // Use configurable route name prefix (defaults to 'tallcms.')
        $namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

        // Determine base prefix (plugin mode routes prefix)
        $basePrefix = '';
        if (! $this->isStandaloneMode()) {
            $basePrefix = config('tallcms.plugin_mode.routes_prefix', '');
        }

        $middleware = ['web', 'tallcms.maintenance', 'tallcms.set-locale'];

        if ($i18nEnabled && $urlStrategy === 'prefix') {
            // Register search route for each locale
            $registry = app(Services\LocaleRegistry::class);
            $locales = $registry->getLocaleCodes();
            $default = $registry->getDefaultLocale();
            $hideDefault = config('tallcms.i18n.hide_default_locale', true);

            foreach ($locales as $locale) {
                $publicPrefix = Services\LocaleRegistry::toBcp47($locale);
                $isDefault = ($locale === $default);

                // Build full prefix: base + locale (if not hidden default)
                $localePrefix = ($hideDefault && $isDefault) ? '' : $publicPrefix;
                $fullPrefix = trim($basePrefix.'/'.$localePrefix, '/');

                $nameSuffix = ($hideDefault && $isDefault) ? '' : ".{$locale}";

                Route::middleware($middleware)
                    ->prefix($fullPrefix)
                    ->group(function () use ($locale, $nameSuffix, $namePrefix) {
                        Route::get('/search', Livewire\SearchResults::class)
                            ->defaults('locale', $locale)
                            ->name($namePrefix.'search'.$nameSuffix);
                    });
            }

            // When hide_default_locale=false, also register an unsuffixed route
            // that aliases to the default locale, so route('prefix.search') still works
            if (! $hideDefault) {
                $defaultPublicPrefix = Services\LocaleRegistry::toBcp47($default);
                $fullPrefix = trim($basePrefix.'/'.$defaultPublicPrefix, '/');

                Route::middleware($middleware)
                    ->prefix($fullPrefix)
                    ->group(function () use ($default, $namePrefix) {
                        Route::get('/search', Livewire\SearchResults::class)
                            ->defaults('locale', $default)
                            ->name($namePrefix.'search');
                    });
            }
        } else {
            // Non-i18n: single search route with base prefix only
            Route::middleware($middleware)
                ->prefix($basePrefix)
                ->group(function () use ($namePrefix) {
                    Route::get('/search', Livewire\SearchResults::class)
                        ->name($namePrefix.'search');
                });
        }
    }

    /**
     * Register Livewire components from the package.
     *
     * Component names must match Livewire's class-to-name conversion:
     * TallCms\Cms\Livewire\RevisionHistory -> tall-cms.cms.livewire.revision-history
     * This is required because Filament's Livewire::make() uses the class name.
     */
    protected function registerLivewireComponents(): void
    {
        if (class_exists(\Livewire\Livewire::class) && $this->app->bound('livewire')) {
            // Register with the exact names Livewire generates from class names
            \Livewire\Livewire::component('tall-cms.cms.livewire.revision-history', Livewire\RevisionHistory::class);
            \Livewire\Livewire::component('tall-cms.cms.livewire.cms-page-renderer', Livewire\CmsPageRenderer::class);

            // Register search results component
            if (config('tallcms.search.enabled', true)) {
                \Livewire\Livewire::component('tall-cms.cms.livewire.search-results', Livewire\SearchResults::class);
            }
        }
    }

    /**
     * Register middleware aliases used by package routes.
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('tallcms.maintenance', Http\Middleware\MaintenanceModeMiddleware::class);
        $router->aliasMiddleware('tallcms.theme-preview', Http\Middleware\ThemePreviewMiddleware::class);
        $router->aliasMiddleware('tallcms.preview-auth', Http\Middleware\PreviewAuthMiddleware::class);
        $router->aliasMiddleware('tallcms.set-locale', Http\Middleware\SetLocaleMiddleware::class);
        $router->aliasMiddleware('tallcms.redirect-root-locale', Http\Middleware\RedirectRootToLocaleMiddleware::class);

        // API middleware
        $router->aliasMiddleware('tallcms.api-enabled', Http\Middleware\EnsureApiEnabled::class);
        $router->aliasMiddleware('tallcms.token-expiry', Http\Middleware\CheckTokenExpiry::class);
        $router->aliasMiddleware('tallcms.abilities', Http\Middleware\CheckTokenAbilities::class);
    }

    /**
     * Register root-to-locale redirect as global middleware so it runs even
     * when no unprefixed / route exists (hide_default_locale=false).
     */
    protected function registerRootLocaleRedirectMiddleware(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->pushMiddleware(Http\Middleware\RedirectRootToLocaleMiddleware::class);
    }

    /**
     * Register Blade component aliases for theme compatibility.
     *
     * This allows themes to use <x-menu> instead of <x-tallcms::menu>
     * making themes portable between standalone and plugin modes.
     *
     * Only registered when themes are enabled to avoid overriding
     * host app components with the same names.
     */
    protected function registerBladeComponentAliases(): void
    {
        // Only register aliases in plugin mode when themes are enabled
        // This prevents overriding host app components of the same name
        if ($this->isStandaloneMode()) {
            return;
        }

        if (! config('tallcms.plugin_mode.themes_enabled', true)) {
            return;
        }

        $blade = $this->app['blade.compiler'];

        // Register anonymous component aliases that point to package views
        // <x-menu> -> tallcms::components.menu
        $blade->component('tallcms::components.menu', 'menu');
        $blade->component('tallcms::components.menu-item', 'menu-item');

        // Form components used by contact form block
        // <x-form.dynamic-field> -> tallcms::components.form.dynamic-field
        $blade->component('tallcms::components.form.dynamic-field', 'form.dynamic-field');
    }

    /**
     * Determine if running in standalone mode (full TallCMS skeleton)
     * vs plugin mode (installed in existing Filament app)
     */
    public function isStandaloneMode(): bool
    {
        // 1. Explicit config takes precedence
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        // 2. Auto-detect: Check if installed via skeleton vs require
        // Standalone: tallcms/tallcms skeleton (has .tallcms-standalone marker)
        // Plugin: composer require tallcms/cms (no marker)
        return file_exists(base_path('.tallcms-standalone'));
    }

    /**
     * Boot features for standalone mode (full TallCMS installation)
     */
    protected function bootStandaloneFeatures(): void
    {
        // Standalone mode: frontend routes are defined in the app's routes/web.php
        // using App wrapper classes for full customization.
        // But essential routes (media download, preview) are still loaded from package.
        $this->loadEssentialRoutes();
    }

    /**
     * Boot features for plugin mode (installed in existing Filament app)
     */
    protected function bootPluginFeatures(): void
    {
        // Check if TallCmsPlugin is registered (deferred to after app boots)
        $this->app->booted(function () {
            $this->checkPluginRegistration();
        });

        // Always load essential admin routes (preview, contact API)
        // These are needed for admin panel functionality regardless of frontend routes
        $this->loadEssentialRoutes();

        // Plugin mode: frontend routes are OPT-IN
        if (config('tallcms.plugin_mode.routes_enabled', false)) {
            $prefix = config('tallcms.plugin_mode.routes_prefix', '');

            // Log warning if assets aren't published (frontend styling may be incomplete)
            if (! file_exists(public_path('vendor/tallcms/tallcms.css'))) {
                \Illuminate\Support\Facades\Log::warning(
                    'TallCMS: Package assets not published. Frontend styling may be incomplete. '.
                    'Run: php artisan vendor:publish --tag=tallcms-assets'
                );
            }

            Route::prefix($prefix)
                ->middleware(['web'])
                ->group(__DIR__.'/../routes/frontend.php');
        }
    }

    /**
     * Load essential routes that are always needed (preview, contact API)
     * These are required for admin functionality but can be prefixed to avoid conflicts
     */
    protected function loadEssentialRoutes(): void
    {
        // Optional prefix for essential routes to avoid conflicts
        $prefix = config('tallcms.plugin_mode.essential_routes_prefix', '');

        // Preview routes (needed for admin preview buttons)
        if (config('tallcms.plugin_mode.preview_routes_enabled', true)) {
            Route::middleware(['web'])->prefix($prefix)->group(function () {
                Route::get('/preview/share/{token}', [Http\Controllers\PreviewController::class, 'tokenPreview'])
                    ->middleware('throttle:60,1')
                    ->name('tallcms.preview.token');

                Route::middleware('tallcms.preview-auth')->group(function () {
                    // Use explicit ID binding since models use slug as route key name
                    Route::get('/preview/page/{page:id}', [Http\Controllers\PreviewController::class, 'page'])
                        ->name('tallcms.preview.page');
                    Route::get('/preview/post/{post:id}', [Http\Controllers\PreviewController::class, 'post'])
                        ->name('tallcms.preview.post');
                });
            });
        }

        // Contact form API (needed for contact form blocks)
        if (config('tallcms.plugin_mode.api_routes_enabled', true)) {
            Route::middleware(['web'])->prefix($prefix)->group(function () {
                Route::post('/api/tallcms/contact', [Http\Controllers\ContactFormController::class, 'submit'])
                    ->name('tallcms.contact.submit');
            });
        }

        // Comment submission route (needed for comment forms on posts)
        if (config('tallcms.comments.enabled', true)) {
            Route::middleware(['web'])->prefix($prefix)->group(function () {
                Route::post('/api/tallcms/comments', [Http\Controllers\CommentController::class, 'submit'])
                    ->name('tallcms.comments.submit');
            });
        }

        // Media download route (forces download instead of browser display)
        Route::middleware(['web'])->prefix($prefix)->group(function () {
            Route::get('/media/download/{media}', Http\Controllers\MediaDownloadController::class)
                ->name('tallcms.media.download');
        });

        // Core SEO routes (sitemap.xml, robots.txt)
        // Always at root level - no prefix - search engines expect standard locations
        if (config('tallcms.plugin_mode.seo_routes_enabled', true)) {
            Route::middleware(['web'])->group(__DIR__.'/../routes/seo.php');
        }

        // Archive routes (RSS feed, category/author archives)
        // Optional prefix to avoid conflicts with host app routes
        if (config('tallcms.plugin_mode.archive_routes_enabled', false)) {
            $archivePrefix = config('tallcms.plugin_mode.archive_routes_prefix', '');
            Route::middleware(['web'])->prefix($archivePrefix)->group(__DIR__.'/../routes/archives.php');
        }

        // REST API routes
        $this->loadApiRoutes();
    }

    /**
     * Load REST API routes when enabled.
     */
    protected function loadApiRoutes(): void
    {
        if (! config('tallcms.api.enabled', false)) {
            return;
        }

        $prefix = config('tallcms.api.prefix', 'api/v1/tallcms');
        $rateLimit = config('tallcms.api.rate_limit', 60);

        Route::prefix($prefix)
            ->middleware(['throttle:'.$rateLimit.',1'])
            ->name('tallcms.api.')
            ->group(__DIR__.'/../routes/api.php');

        // API documentation is handled by Scribe (laravel type)
        // Run: php artisan scribe:generate
        // Access at: /api/docs
    }

    /**
     * Check if TallCmsPlugin is registered with any Filament panel.
     * Logs a warning if not registered to help plugin mode users.
     */
    protected function checkPluginRegistration(): void
    {
        // Skip check if running in console (artisan commands)
        if ($this->app->runningInConsole()) {
            return;
        }

        // Skip if Filament isn't available
        if (! class_exists(\Filament\Facades\Filament::class)) {
            return;
        }

        try {
            $panels = \Filament\Facades\Filament::getPanels();
            $pluginRegistered = false;

            foreach ($panels as $panel) {
                if ($panel->hasPlugin('tallcms')) {
                    $pluginRegistered = true;
                    break;
                }
            }

            if (! $pluginRegistered) {
                \Illuminate\Support\Facades\Log::warning(
                    'TallCMS: Plugin not registered with any Filament panel. '.
                    'Add TallCmsPlugin::make() to your panel provider. '.
                    'Example: $panel->plugin(TallCmsPlugin::make())'
                );
            }
        } catch (\Throwable $e) {
            // Silently ignore - Filament may not be fully booted yet
        }
    }

    /**
     * Get the migrations that should be published.
     *
     * These are CMS-specific migrations. App-specific migrations
     * (users, cache, jobs, permissions) remain in the skeleton.
     */
    protected function getMigrations(): array
    {
        return [
            // Core CMS tables
            '2025_12_08_082455_create_tall_cms_pages_table',
            '2025_12_08_082458_create_tall_cms_categories_table',
            '2025_12_08_085200_create_tall_cms_posts_table',
            '2025_12_08_085204_create_tall_cms_post_category_table',
            '2025_12_08_094007_add_is_homepage_to_tallcms_pages_table',

            // Site settings
            '2025_12_09_045501_create_site_settings_table',
            '2025_12_09_062630_rename_site_settings_to_tallcms_site_settings',

            // Media library
            '2025_12_09_062914_create_tallcms_media_table',
            '2025_12_09_063854_create_tallcms_media_collections_table',
            '2025_12_09_063922_modify_tallcms_media_table_for_collections',
            '2025_12_09_064048_create_tallcms_media_collection_pivot_table',
            '2025_12_09_064058_remove_collection_id_from_tallcms_media',

            // Menus
            '2025_12_09_092513_create_tallcms_menus_table',
            '2025_12_09_092514_create_tallcms_menu_items_table',

            // Contact submissions
            '2025_12_21_150937_create_tallcms_contact_submissions_table',

            // Publishing workflow & revisions
            '2026_01_03_164841_add_publishing_workflow_fields',
            '2026_01_03_164842_create_tallcms_revisions_table',
            '2026_01_03_164843_create_tallcms_preview_tokens_table',
            '2026_01_07_012959_add_revision_metadata_fields_to_tallcms_revisions_table',

            // Plugin system
            '2026_01_07_064340_create_tallcms_plugin_migrations_table',
            '2026_01_09_133136_create_tallcms_plugin_licenses_table',
            '2026_01_09_214045_migrate_pro_licenses_to_core',

            // SEO & Author features
            '2026_01_22_000001_add_author_fields_to_users_table',

            // Multilingual support
            '2026_01_23_000001_add_multilingual_support',

            // Page settings
            '2026_01_25_000001_add_content_width_to_tallcms_pages_table',
            '2026_01_26_000001_add_show_breadcrumbs_to_tallcms_pages_table',

            // Full-text search
            '2026_01_27_000001_add_search_content_to_cms_tables',

            // Media optimization
            '2026_01_27_100000_add_variants_to_tallcms_media',

            // REST API & Webhooks
            '2026_01_27_200001_add_expires_at_to_personal_access_tokens',
            '2026_01_27_200002_create_tallcms_webhooks_table',
            '2026_01_27_200003_create_tallcms_webhook_deliveries_table',
            '2026_01_27_200004_change_webhook_deliveries_unique_key',

            // Page templates & widgets
            '2026_02_01_000001_add_sidebar_widgets_to_tallcms_pages',

            // Comments
            '2026_02_21_000001_create_tallcms_comments_table',

            // GEO Phase 1: Author profiles & review metadata
            '2026_04_07_000001_add_author_profile_fields_to_users_table',
            '2026_04_07_000002_add_review_metadata_to_content_tables',

            // Site as core model (v4.0)
            '2026_04_20_000001_create_tallcms_sites_core_table',
            '2026_04_20_000002_create_tallcms_site_setting_overrides_core_table',
            '2026_04_20_000003_migrate_site_name_to_site_model',

            // v4.0.1 patch: add user_id for installs that ran v4.0.0 without it
            '2026_04_21_000001_add_user_id_to_tallcms_sites_table',
        ];
    }

    /**
     * Get the commands that should be registered.
     */
    protected function getCommands(): array
    {
        $commands = [
            // Core commands available in all modes
            Console\Commands\BackfillAuthorSlugs::class,
            Console\Commands\CleanExpiredPreviewTokens::class,
            Console\Commands\MakeTallCmsBlock::class,
            Console\Commands\SearchIndex::class,
            Console\Commands\SeedCounselTemplate::class,
            Console\Commands\SeedInkTemplate::class,
            Console\Commands\SeedKeystoneTemplate::class,
            Console\Commands\SeedLaunchpadTemplate::class,
            Console\Commands\SeedPushsgTemplate::class,
            Console\Commands\SeedSpotlightTemplate::class,
            Console\Commands\ShieldSyncSiteOwner::class,
            Console\Commands\TallCmsDiagnose::class,
            Console\Commands\TallCmsInstall::class,
            Console\Commands\TallCmsPostInstall::class,
            Console\Commands\TallCmsSetup::class,

            // Theme commands
            Console\Commands\MakeTheme::class,
            Console\Commands\ThemeActivate::class,
            Console\Commands\ThemeBuild::class,
            Console\Commands\ThemeCacheClear::class,
            Console\Commands\ThemeInstallCommand::class,
            Console\Commands\ThemeList::class,
            Console\Commands\ThemePublishAll::class,

            // Plugin commands
            Console\Commands\MakePluginCommand::class,
            Console\Commands\PluginCleanupBackupsCommand::class,
            Console\Commands\PluginInstallCommand::class,
            Console\Commands\PluginListCommand::class,
            Console\Commands\PluginMigrateCommand::class,
            Console\Commands\PluginPackageCommand::class,
            Console\Commands\PluginUninstallCommand::class,
        ];

        // Standalone-only commands (updater, release signing)
        if ($this->isStandaloneMode()) {
            $commands = array_merge($commands, [
                Console\Commands\TallCmsUpdate::class,
                Console\Commands\TallCmsVersion::class,
                Console\Commands\TallCmsGenerateKeypair::class,
                Console\Commands\TallCmsGenerateManifest::class,
                Console\Commands\TallCmsSignRelease::class,
                Console\Commands\LicenseTestCommand::class,
            ]);
        }

        return $commands;
    }
}
