<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SitemapService;

class SeoSettings extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.seo-settings';

    public function getTitle(): string
    {
        return __('tallcms::pages.seo_settings.title');
    }

    public ?array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-magnifying-glass-circle';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.seo_settings.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 41;
    }

    public function mount(): void
    {
        $this->form->fill([
            // RSS settings
            'seo_rss_enabled' => SiteSetting::getGlobal('seo_rss_enabled', true),
            'seo_rss_limit' => SiteSetting::getGlobal('seo_rss_limit', 20),
            'seo_rss_full_content' => SiteSetting::getGlobal('seo_rss_full_content', false),

            // Sitemap settings
            'seo_sitemap_enabled' => SiteSetting::getGlobal('seo_sitemap_enabled', true),

            // robots.txt settings
            'seo_robots_txt' => SiteSetting::getGlobal('seo_robots_txt', $this->getDefaultRobots()),
            'seo_robots_append_sitemap' => SiteSetting::getGlobal('seo_robots_append_sitemap', true),

            // Default OG image
            'seo_default_og_image' => SiteSetting::getGlobal('seo_default_og_image'),

            // llms.txt
            'seo_llms_txt_enabled' => SiteSetting::getGlobal('seo_llms_txt_enabled', false),
            'seo_llms_txt_preamble' => SiteSetting::getGlobal('seo_llms_txt_preamble', ''),
            'seo_llms_txt_include_pages' => SiteSetting::getGlobal('seo_llms_txt_include_pages', true),
            'seo_llms_txt_include_posts' => SiteSetting::getGlobal('seo_llms_txt_include_posts', true),
            'seo_llms_txt_post_limit' => SiteSetting::getGlobal('seo_llms_txt_post_limit', '0'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('tallcms::ui.rss_feed'))
                ->description(__('tallcms::ui.help_configure_rss'))
                ->schema([
                    Toggle::make('seo_rss_enabled')
                        ->label(__('tallcms::fields.enable_rss_feed'))
                        ->helperText(__('tallcms::ui.help_allow_rss_subscribe'))
                        ->live(),

                    Select::make('seo_rss_limit')
                        ->label(__('tallcms::fields.posts_in_feed'))
                        ->options([
                            '10' => __('tallcms::ui.n_posts', ['count' => 10]),
                            '20' => __('tallcms::ui.n_posts', ['count' => 20]),
                            '30' => __('tallcms::ui.n_posts', ['count' => 30]),
                            '50' => __('tallcms::ui.n_posts', ['count' => 50]),
                        ])
                        ->default('20')
                        ->visible(fn ($get) => $get('seo_rss_enabled'))
                        ->helperText(__('tallcms::ui.help_rss_post_count')),

                    Toggle::make('seo_rss_full_content')
                        ->label(__('tallcms::fields.include_full_content'))
                        ->helperText(__('tallcms::ui.help_rss_full_content'))
                        ->visible(fn ($get) => $get('seo_rss_enabled')),
                ])
                ->columns(3),

            Section::make(__('tallcms::ui.xml_sitemap'))
                ->description(__('tallcms::ui.help_configure_sitemap'))
                ->schema([
                    Toggle::make('seo_sitemap_enabled')
                        ->label(__('tallcms::fields.enable_xml_sitemap'))
                        ->helperText(__('tallcms::ui.help_generate_sitemap'))
                        ->columnSpanFull(),
                ]),

            Section::make(__('tallcms::ui.robots_txt'))
                ->description(__('tallcms::ui.help_control_robots'))
                ->schema([
                    Textarea::make('seo_robots_txt')
                        ->label(__('tallcms::fields.robots_txt_content'))
                        ->rows(8)
                        ->placeholder($this->getDefaultRobots())
                        ->helperText(__('tallcms::ui.help_customize_robots'))
                        ->columnSpanFull(),

                    Toggle::make('seo_robots_append_sitemap')
                        ->label(__('tallcms::fields.auto_append_sitemap_url'))
                        ->helperText(__('tallcms::ui.help_auto_append_sitemap'))
                        ->columnSpanFull(),
                ]),

            Section::make(__('tallcms::ui.default_og_image_section'))
                ->description(__('tallcms::ui.help_fallback_og'))
                ->schema([
                    FileUpload::make('seo_default_og_image')
                        ->label(__('tallcms::fields.default_og_image'))
                        ->image()
                        ->directory('site-assets')
                        ->disk(\cms_media_disk())
                        ->visibility(\cms_media_visibility())
                        ->helperText(__('tallcms::ui.help_og_image_usage'))
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make(__('tallcms::ui.llms_txt'))
                ->description(__('tallcms::ui.help_llms_txt'))
                ->schema([
                    Toggle::make('seo_llms_txt_enabled')
                        ->label(__('tallcms::fields.enable_llms_txt'))
                        ->helperText(__('tallcms::ui.help_publish_llms'))
                        ->live()
                        ->columnSpanFull(),

                    Textarea::make('seo_llms_txt_preamble')
                        ->label(__('tallcms::fields.preamble'))
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder(__('tallcms::ui.placeholder_llms_preamble'))
                        ->helperText(__('tallcms::ui.help_llms_preamble'))
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled'))
                        ->columnSpanFull(),

                    Toggle::make('seo_llms_txt_include_pages')
                        ->label(__('tallcms::fields.include_pages'))
                        ->helperText(__('tallcms::ui.help_list_published_pages'))
                        ->default(true)
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled')),

                    Toggle::make('seo_llms_txt_include_posts')
                        ->label(__('tallcms::fields.include_posts'))
                        ->helperText(__('tallcms::ui.help_list_published_posts'))
                        ->default(true)
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled')),

                    Select::make('seo_llms_txt_post_limit')
                        ->label(__('tallcms::fields.post_limit'))
                        ->options([
                            '0' => __('tallcms::ui.option_all_posts'),
                            '10' => __('tallcms::ui.option_n_most_recent', ['count' => 10]),
                            '25' => __('tallcms::ui.option_n_most_recent', ['count' => 25]),
                            '50' => __('tallcms::ui.option_n_most_recent', ['count' => 50]),
                            '100' => __('tallcms::ui.option_n_most_recent', ['count' => 100]),
                        ])
                        ->default('0')
                        ->helperText(__('tallcms::ui.help_limit_posts_focused'))
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled') && $get('seo_llms_txt_include_posts')),
                ])
                ->columns(3),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getDefaultRobots(): string
    {
        return "User-agent: *\nAllow: /";
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'seo_')) {
                $type = match ($key) {
                    'seo_default_og_image' => 'file',
                    'seo_rss_enabled', 'seo_rss_full_content', 'seo_sitemap_enabled', 'seo_robots_append_sitemap',
                    'seo_llms_txt_enabled', 'seo_llms_txt_include_pages', 'seo_llms_txt_include_posts' => 'boolean',
                    'seo_rss_limit', 'seo_llms_txt_post_limit' => 'integer',
                    default => 'text',
                };

                SiteSetting::setGlobal($key, $value ?? ($type === 'boolean' ? false : null), $type, 'seo');
            }
        }

        // Clear caches
        SiteSetting::clearCache();
        SitemapService::clearCache();

        Notification::make()
            ->title(__('tallcms::ui.notify_seo_saved'))
            ->success()
            ->send();
    }

    public function clearSitemapCache(): void
    {
        SitemapService::clearCache();

        Notification::make()
            ->title(__('tallcms::ui.notify_sitemap_cache_cleared'))
            ->body(__('tallcms::ui.t_the_sitemap_will_be_regenerated_on_the_next_request'))
            ->success()
            ->send();
    }
}
