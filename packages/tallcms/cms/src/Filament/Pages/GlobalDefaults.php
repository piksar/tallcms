<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;
use TallCms\Cms\Services\SiteSettingsService;

/**
 * Global defaults for all site-scoped settings.
 *
 * These values are inherited by every site unless overridden.
 * In standalone mode (single site), these serve as the base configuration.
 * In multisite mode, per-site overrides on the Site edit page take precedence.
 */
class GlobalDefaults extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.global-defaults';

    public function getTitle(): string
    {
        return __('tallcms::pages.global_defaults.title');
    }

    public ?array $data = [];

    public static function canAccess(): bool
    {
        // Standalone (single-site) installs only have one site, so per-site
        // overrides and "global defaults to inherit from" are the same thing.
        // Showing both pages confuses users about which to edit. Only expose
        // GlobalDefaults when the multisite plugin is active and inheritance
        // actually matters across multiple sites.
        if (! app()->bound('tallcms.multisite.resolver')) {
            return false;
        }

        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.global_defaults.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 39;
    }

    /**
     * Setting keys and their types (mirrors EditSite::$settingKeys).
     */
    protected array $settingKeys = [
        // General
        'site_tagline' => 'text',
        'site_description' => 'text',
        'site_type' => 'text',
        // Contact
        'contact_email' => 'text',
        'contact_phone' => 'text',
        'company_name' => 'text',
        'company_address' => 'text',
        // Social
        'social_facebook' => 'text',
        'social_twitter' => 'text',
        'social_linkedin' => 'text',
        'social_instagram' => 'text',
        'social_youtube' => 'text',
        'social_tiktok' => 'text',
        'newsletter_signup_url' => 'text',
        // Branding
        'logo' => 'file',
        'favicon' => 'file',
        'show_powered_by' => 'boolean',
        'show_theme_switcher' => 'boolean',
        'show_search' => 'boolean',
        'show_language_dropdown' => 'boolean',
        // Publishing
        'review_workflow_enabled' => 'boolean',
        // Maintenance
        'maintenance_mode' => 'boolean',
        'maintenance_message' => 'text',
    ];

    public function mount(): void
    {
        $formData = [];

        foreach ($this->settingKeys as $key => $type) {
            $default = match ($key) {
                'show_powered_by',
                'show_theme_switcher',
                'show_search',
                'show_language_dropdown' => true,
                'site_type' => 'multi-page',
                'maintenance_message' => "We're currently performing scheduled maintenance. Please check back soon!",
                default => null,
            };

            $formData[$key] = SiteSetting::getGlobal($key, $default);
        }

        // i18n settings (installation-scoped)
        $formData['i18n_enabled'] = SiteSetting::getGlobal('i18n_enabled', config('tallcms.i18n.enabled', false));
        $formData['default_locale'] = SiteSetting::getGlobal('default_locale', config('tallcms.i18n.default_locale', 'en'));
        $formData['hide_default_locale'] = SiteSetting::getGlobal('hide_default_locale', config('tallcms.i18n.hide_default_locale', true));
        $formData['redirect_root_to_locale'] = SiteSetting::getGlobal(
            'redirect_root_to_locale',
            config('tallcms.i18n.redirect_root_to_locale', false)
        );

        if (! $formData['i18n_enabled'] || $formData['hide_default_locale']) {
            $formData['redirect_root_to_locale'] = false;
        }

        $this->form->fill($formData);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save site-scoped setting defaults
        foreach ($this->settingKeys as $key => $type) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            // File fields: null could mean "unchanged" or "deleted".
            if ($type === 'file' && $value === null) {
                $stored = SiteSetting::getGlobal($key);
                if (! $stored) {
                    continue;
                }
                $value = '';
            }

            $group = match ($key) {
                'site_tagline', 'site_description', 'site_type' => 'general',
                'contact_email', 'contact_phone', 'company_name', 'company_address' => 'contact',
                'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram',
                'social_youtube', 'social_tiktok', 'newsletter_signup_url' => 'social',
                'logo', 'favicon', 'show_powered_by',
                'show_theme_switcher', 'show_search', 'show_language_dropdown' => 'branding',
                'review_workflow_enabled' => 'publishing',
                'maintenance_mode', 'maintenance_message' => 'maintenance',
                default => 'general',
            };

            SiteSetting::setGlobal($key, $value, $type, $group);
        }

        // Save i18n settings
        if (array_key_exists('i18n_enabled', $data)) {
            SiteSetting::setGlobal('i18n_enabled', $data['i18n_enabled'], 'boolean', 'i18n');
        }
        if (array_key_exists('default_locale', $data)) {
            SiteSetting::setGlobal('default_locale', $data['default_locale'], 'text', 'i18n');
        }
        if (array_key_exists('hide_default_locale', $data)) {
            SiteSetting::setGlobal('hide_default_locale', $data['hide_default_locale'], 'boolean', 'i18n');
        }

        $i18nEnabled = (bool) ($data['i18n_enabled'] ?? false);
        $hideDefault = (bool) ($data['hide_default_locale'] ?? true);
        $redirectApplicable = $i18nEnabled && ! $hideDefault;

        if ($redirectApplicable && array_key_exists('redirect_root_to_locale', $data)) {
            SiteSetting::setGlobal('redirect_root_to_locale', $data['redirect_root_to_locale'], 'boolean', 'i18n');
        } else {
            SiteSetting::setGlobal('redirect_root_to_locale', false, 'boolean', 'i18n');
            app(SiteSettingsService::class)->resetAllSitesForKey('redirect_root_to_locale');
        }

        SiteSetting::clearCache();

        // Clear locale registry cache if i18n settings changed
        if (isset($data['i18n_enabled']) || isset($data['default_locale']) || isset($data['hide_default_locale'])) {
            try {
                app(LocaleRegistry::class)->clearCache();
            } catch (\Throwable) {
            }
        }

        Notification::make()
            ->title(__('tallcms::ui.notify_global_defaults_saved'))
            ->body(__('tallcms::ui.notify_global_defaults_saved_body'))
            ->success()
            ->send();
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('tallcms::ui.inherited_defaults'))
                ->description(__('tallcms::ui.inherited_defaults_description'))
                ->schema([]),

            Tabs::make('Global Defaults')
                ->tabs([
                    $this->generalTab(),
                    $this->brandingTab(),
                    $this->contactTab(),
                    $this->socialTab(),
                    $this->publishingTab(),
                    $this->maintenanceTab(),
                    $this->i18nTab(),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ];
    }

    protected function generalTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_general'))
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make(__('tallcms::ui.site_identity_defaults'))
                    ->description(__('tallcms::ui.site_identity_defaults_description'))
                    ->schema([
                        TextInput::make('site_tagline')
                            ->label(__('tallcms::fields.tagline'))
                            ->maxLength(255)
                            ->helperText(__('tallcms::ui.help_short_phrase_site')),

                        Textarea::make('site_description')
                            ->label(__('tallcms::fields.description'))
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText(__('tallcms::ui.help_fallback_meta_description')),

                        Select::make('site_type')
                            ->label(__('tallcms::fields.site_type'))
                            ->options([
                                'multi-page' => __('tallcms::ui.option_multi_page_website'),
                                'single-page' => __('tallcms::ui.option_spa'),
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText(__('tallcms::ui.help_site_type')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function brandingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_branding'))
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Section::make(__('tallcms::ui.visual_identity_defaults'))
                    ->description(__('tallcms::ui.visual_identity_defaults_description'))
                    ->schema([
                        FileUpload::make('logo')
                            ->label(__('tallcms::fields.site_logo'))
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->helperText(__('tallcms::ui.help_upload_logo'))
                            ->deletable()
                            ->nullable(),

                        FileUpload::make('favicon')
                            ->label(__('tallcms::fields.favicon'))
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->acceptedFileTypes(['image/x-icon', 'image/png'])
                            ->helperText(__('tallcms::ui.help_upload_favicon'))
                            ->nullable(),

                        Toggle::make('show_powered_by')
                            ->label(__('tallcms::fields.show_powered_by_tallcms_badge'))
                            ->helperText(__('tallcms::ui.help_powered_by_badge'))
                            ->default(true)
                            ->columnSpanFull(),

                        Toggle::make('show_theme_switcher')
                            ->label(__('tallcms::fields.show_theme_switcher_in_header'))
                            ->helperText(__('tallcms::ui.help_theme_switcher_default'))
                            ->default(true)
                            ->columnSpanFull(),

                        Toggle::make('show_search')
                            ->label(__('tallcms::fields.show_search_box_in_header'))
                            ->helperText(__('tallcms::ui.help_search_control_default'))
                            ->default(true)
                            ->columnSpanFull(),

                        Toggle::make('show_language_dropdown')
                            ->label(__('tallcms::fields.show_language_dropdown_in_header'))
                            ->helperText(__('tallcms::ui.help_locale_switcher_default'))
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function contactTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_contact'))
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make(__('tallcms::ui.contact_information_defaults'))
                    ->description(__('tallcms::ui.contact_information_defaults_description'))
                    ->schema([
                        TextInput::make('contact_email')
                            ->label(__('tallcms::fields.contact_email'))
                            ->email()
                            ->helperText(__('tallcms::ui.help_default_contact_email')),

                        TextInput::make('contact_phone')
                            ->label(__('tallcms::fields.contact_phone'))
                            ->tel()
                            ->helperText(__('tallcms::ui.help_business_phone')),

                        TextInput::make('company_name')
                            ->label(__('tallcms::fields.company_name'))
                            ->maxLength(255)
                            ->helperText(__('tallcms::ui.help_legal_company_name')),

                        Textarea::make('company_address')
                            ->label(__('tallcms::fields.company_address'))
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText(__('tallcms::ui.help_complete_business_address')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function socialTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_social'))
            ->icon('heroicon-o-share')
            ->schema([
                Section::make(__('tallcms::ui.social_media_defaults'))
                    ->description(__('tallcms::ui.social_media_defaults_description'))
                    ->schema([
                        TextInput::make('social_facebook')->label(__('tallcms::fields.facebook_url'))->url(),
                        TextInput::make('social_twitter')->label(__('tallcms::fields.twitter_x_url'))->url(),
                        TextInput::make('social_linkedin')->label(__('tallcms::fields.linkedin_url'))->url(),
                        TextInput::make('social_instagram')->label(__('tallcms::fields.instagram_url'))->url(),
                        TextInput::make('social_youtube')->label(__('tallcms::fields.youtube_url'))->url(),
                        TextInput::make('social_tiktok')->label(__('tallcms::fields.tiktok_url'))->url(),
                        TextInput::make('newsletter_signup_url')->label(__('tallcms::fields.newsletter_signup_url'))->url(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function publishingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_publishing'))
            ->icon('heroicon-o-document-check')
            ->schema([
                Section::make(__('tallcms::ui.publishing_workflow_default'))
                    ->description(__('tallcms::ui.publishing_workflow_default_description'))
                    ->schema([
                        Toggle::make('review_workflow_enabled')
                            ->label(__('tallcms::fields.enable_review_workflow'))
                            ->helperText(__('tallcms::ui.help_review_workflow'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function maintenanceTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_maintenance'))
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema([
                Section::make(__('tallcms::ui.maintenance_mode_default'))
                    ->description(__('tallcms::ui.maintenance_mode_default_description'))
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label(__('tallcms::fields.enable_maintenance_mode'))
                            ->helperText(__('tallcms::ui.help_maintenance_mode'))
                            ->live()
                            ->columnSpanFull(),

                        Textarea::make('maintenance_message')
                            ->label(__('tallcms::fields.maintenance_message'))
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText(__('tallcms::ui.help_maintenance_message'))
                            ->visible(fn ($get) => $get('maintenance_mode'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function i18nTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_languages'))
            ->icon('heroicon-o-language')
            ->schema([
                Section::make(__('tallcms::ui.multilingual_support'))
                    ->description(__('tallcms::ui.multilingual_support_description'))
                    ->schema([
                        Toggle::make('i18n_enabled')
                            ->label(__('tallcms::fields.enable_multilingual_support'))
                            ->helperText(__('tallcms::ui.help_i18n_enabled'))
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (! $state) {
                                    $set('redirect_root_to_locale', false);
                                }
                            }),

                        Select::make('default_locale')
                            ->label(__('tallcms::fields.default_language'))
                            ->options(fn () => $this->getLocaleOptions())
                            ->searchable()
                            ->required()
                            ->helperText(__('tallcms::ui.help_default_language'))
                            ->visible(fn ($get) => $get('i18n_enabled')),

                        Toggle::make('hide_default_locale')
                            ->label(__('tallcms::fields.hide_default_language_in_urls'))
                            ->helperText(__('tallcms::ui.help_hide_default_locale'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if ($state) {
                                    $set('redirect_root_to_locale', false);
                                }
                            })
                            ->visible(fn ($get) => $get('i18n_enabled')),

                        Toggle::make('redirect_root_to_locale')
                            ->label(__('tallcms::fields.redirect_to_the_default_locale'))
                            ->helperText(__('tallcms::ui.help_redirect_root_locale'))
                            ->default(false)
                            ->disabled(fn ($get) => ! $get('i18n_enabled') || $get('hide_default_locale'))
                            ->dehydrated(fn ($get) => $get('i18n_enabled') && ! $get('hide_default_locale'))
                            ->visible(fn ($get) => $get('i18n_enabled')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getLocaleOptions(): array
    {
        try {
            return app(LocaleRegistry::class)->getLocaleOptions();
        } catch (\Throwable) {
            return ['en' => 'English'];
        }
    }
}
