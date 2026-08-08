<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;

class SiteForm
{
    /**
     * Configure a Schema instance (used by SiteResource::form()).
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::schema());
    }

    /**
     * Full form schema with tabs for the Site edit page.
     */
    public static function schema(?Site $site = null): array
    {
        return [
            Tabs::make('Site Settings')
                ->tabs([
                    static::generalTab($site),
                    static::brandingTab(),
                    static::contactTab(),
                    static::socialTab(),
                    static::embedCodeTab(),
                    static::publishingTab(),
                    static::maintenanceTab(),
                    static::languagesTab(includeSiteLocaleField: true),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ];
    }

    public static function generalTab(?Site $site = null): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_general'))
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make(__('tallcms::ui.t_site_identity'))
                    ->description(__('tallcms::ui.t_basic_site_information_visible_to_visitors'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('tallcms::fields.site_name'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('tallcms::ui.t_the_public_brand_name_shown_in_browser_tabs_and_throughout_the_site')),

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
                                'multi-page' => 'Multi-Page Website',
                                'single-page' => 'Single-Page Application (SPA)',
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText(__('tallcms::ui.help_site_type')),
                    ])
                    ->columns(2),

                Section::make(__('tallcms::ui.t_technical'))
                    ->description(__('tallcms::ui.t_domain_and_theme_configuration'))
                    ->schema([
                        TextInput::make('domain')
                            ->label(__('tallcms::fields.domain'))
                            ->disabled(! tallcms_multisite_active())
                            ->dehydrated()
                            ->helperText(tallcms_multisite_active()
                                ? 'The domain this site is served on'
                                : 'Domain is derived from your APP_URL in standalone mode'),

                        Select::make('theme')
                            ->label(__('tallcms::fields.theme'))
                            ->options(fn () => static::getThemeOptions())
                            ->helperText(__('tallcms::ui.t_visual_theme_for_this_site')),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * General tab with only site-scoped settings (no model fields).
     * Used by multisite plugin where model fields live on the Site tab.
     */
    public static function settingsGeneralTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_general'))
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make(__('tallcms::ui.t_site_identity'))
                    ->description(__('tallcms::ui.t_basic_site_information_visible_to_visitors'))
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
                                'multi-page' => 'Multi-Page Website',
                                'single-page' => 'Single-Page Application (SPA)',
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText(__('tallcms::ui.help_site_type')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function brandingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_branding'))
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Section::make(__('tallcms::ui.t_visual_identity'))
                    ->description(__('tallcms::ui.t_logo_favicon_and_visual_branding_elements'))
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
                    ])
                    ->columns(2),
            ]);
    }

    public static function contactTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_contact'))
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make(__('tallcms::ui.t_contact_information'))
                    ->description(__('tallcms::ui.t_contact_details_used_in_merge_tags_and_forms'))
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

    public static function socialTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_social'))
            ->icon('heroicon-o-share')
            ->schema([
                Section::make(__('tallcms::ui.t_social_media'))
                    ->description(__('tallcms::ui.t_social_media_links_and_newsletter_signup'))
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

    /**
     * Embed code tab: per-site `<head>`/body code injection (analytics,
     * tracking pixels, chat widgets, etc.).
     *
     * Authorization model: access follows Site edit permission via SitePolicy.
     * Anyone allowed to edit a Site is allowed to set its embed code — there
     * is no separate `Manage:CodeInjection` permission. This is a deliberate
     * simplification: in a SaaS multisite context, site owners are expected
     * to be able to add scripts for the sites they own. If you need stricter
     * gating (e.g. super-admin-only embed code), gate this tab with
     * `->visible(fn() => auth()->user()->hasRole('super_admin'))`.
     */
    public static function embedCodeTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.t_embed_code'))
            ->icon('heroicon-o-code-bracket')
            ->schema([
                Section::make(__('tallcms::ui.t_warning'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger')
                    ->description(__('tallcms::ui.t_site_owners_can_add_scripts_for_this_site_only_embed_code_runs_on_ev'))
                    ->schema([]),

                Section::make(__('tallcms::fields.head_code'))
                    ->description(__('tallcms::ui.t_embedded_inside_head_before_head_analytics_meta_tags_css'))
                    ->schema([
                        Textarea::make('code_head')
                            ->label(__('tallcms::fields.head_code'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('tallcms::fields.body_start_code'))
                    ->description(__('tallcms::ui.t_embedded_right_after_the_body_open_tag_gtm_noscript_early_scripts'))
                    ->schema([
                        Textarea::make('code_body_start')
                            ->label(__('tallcms::fields.body_start_code'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('tallcms::fields.body_end_code'))
                    ->description(__('tallcms::ui.t_embedded_before_body_tracking_pixels_chat_widgets_deferred_js'))
                    ->schema([
                        Textarea::make('code_body_end')
                            ->label(__('tallcms::fields.body_end_code'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function publishingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_publishing'))
            ->icon('heroicon-o-document-check')
            ->schema([
                Section::make(__('tallcms::ui.t_publishing_workflow'))
                    ->description(__('tallcms::ui.t_control_how_content_gets_published_on_this_site'))
                    ->schema([
                        Toggle::make('review_workflow_enabled')
                            ->label(__('tallcms::fields.enable_review_workflow'))
                            ->helperText(__('tallcms::ui.help_review_workflow'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function maintenanceTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('tallcms::ui.tab_maintenance'))
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema([
                Section::make(__('tallcms::ui.t_maintenance_mode'))
                    ->description(__('tallcms::ui.t_control_site_availability_for_visitors'))
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

    public static function languagesTab(bool $includeSiteLocaleField = false): Tabs\Tab
    {
        $schema = [];

        if ($includeSiteLocaleField) {
            $schema[] = Section::make(__('tallcms::ui.t_site_language'))
                ->description(__('tallcms::ui.t_primary_language_for_this_site'))
                ->schema([
                    Select::make('locale')
                        ->label(__('tallcms::fields.locale'))
                        ->options(fn () => static::getLocaleOptions())
                        ->searchable()
                        ->helperText(__('tallcms::ui.t_used_for_content_and_as_the_redirect_target_when_redirect_is_enabled')),
                ])
                ->columns(2);
        }

        $schema[] = Section::make(__('tallcms::ui.t_url_routing'))
            ->description(__('tallcms::ui.t_locale_url_behaviour_for_this_site'))
            ->schema(static::languagesRedirectSchema());

        return Tabs\Tab::make(__('tallcms::ui.tab_languages'))
            ->icon('heroicon-o-language')
            ->schema($schema);
    }

    /**
     * Shared redirect toggle schema for standalone and multisite site edit forms.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function languagesRedirectSchema(): array
    {
        return [
            Toggle::make('redirect_root_to_locale')
                ->label(__('tallcms::fields.redirect_to_the_default_locale'))
                ->helperText(__('tallcms::ui.t_when_enabled_visitors_to_are_redirected_to_this_site_s_language_pref'))
                ->default(false)
                ->disabled(fn () => ! static::isRedirectRootToLocaleApplicable())
                ->dehydrated(fn () => static::isRedirectRootToLocaleApplicable())
                ->columnSpanFull(),
        ];
    }

    /**
     * Whether the root-to-locale redirect can apply (requires global i18n + visible default locale).
     */
    public static function isRedirectRootToLocaleApplicable(): bool
    {
        if (! (bool) SiteSetting::getGlobal('i18n_enabled', config('tallcms.i18n.enabled', false))) {
            return false;
        }

        return ! (bool) SiteSetting::getGlobal(
            'hide_default_locale',
            config('tallcms.i18n.hide_default_locale', true)
        );
    }

    /**
     * Normalize redirect toggle for forms when global URL strategy makes it inapplicable.
     */
    public static function normalizeRedirectRootToLocaleFormValue(mixed $value): bool
    {
        if (! static::isRedirectRootToLocaleApplicable()) {
            return false;
        }

        return (bool) $value;
    }

    /**
     * Get theme options for the select field.
     */
    protected static function getThemeOptions(): array
    {
        try {
            if (app()->bound('theme.manager')) {
                $themes = app('theme.manager')->getAvailableThemes();

                return $themes->mapWithKeys(fn ($theme) => [$theme->slug => $theme->name])->toArray();
            }
        } catch (\Throwable) {
        }

        return ['default' => 'Default'];
    }

    /**
     * Get locale options for the select field.
     */
    protected static function getLocaleOptions(): array
    {
        try {
            return app(LocaleRegistry::class)->getLocaleOptions();
        } catch (\Throwable) {
            return ['en' => 'English'];
        }
    }
}
