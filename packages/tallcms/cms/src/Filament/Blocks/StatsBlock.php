<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class StatsBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'wide';
    }

    public static function getCategory(): string
    {
        return 'social-proof';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.stats');
    }

    public static function getKeywords(): array
    {
        return ['numbers', 'metrics', 'statistics', 'stats'];
    }

    public static function getSortPriority(): int
    {
        return 40;
    }

    public static function getId(): string
    {
        return 'stats';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.stats');
    }

    protected static function getStatsStyleOptions(): array
    {
        return [
            'stat' => 'Minimal',
            'stat bg-base-200 rounded-xl shadow-lg' => 'Cards with Shadow',
            'stat bg-base-100 rounded-xl border border-base-300' => 'Bordered',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_display_key_metrics_and_statistics_with_optional_count_up_animation'))
            ->modalHeading(__('tallcms::ui.t_configure_stats_block'))
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Stats Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_our_impact'))
                                    ->maxLength(255),

                                Repeater::make('stats')
                                    ->label(__('tallcms::fields.statistics'))
                                    ->schema([
                                        TextInput::make('value')
                                            ->label(__('tallcms::fields.value'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_10000'))
                                            ->helperText(__('tallcms::ui.t_the_numeric_value_numbers_only_for_animation'))
                                            ->maxLength(50),

                                        TextInput::make('label')
                                            ->label(__('tallcms::fields.label'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_happy_customers'))
                                            ->maxLength(100),

                                        TextInput::make('prefix')
                                            ->label(__('tallcms::fields.prefix'))
                                            ->placeholder('$')
                                            ->maxLength(10),

                                        TextInput::make('suffix')
                                            ->label(__('tallcms::fields.suffix'))
                                            ->placeholder('+')
                                            ->maxLength(10),

                                        Select::make('icon')
                                            ->label(__('tallcms::fields.icon_optional'))
                                            ->searchable()
                                            ->options([
                                                '' => 'No Icon',
                                                'heroicon-o-users' => 'Users',
                                                'heroicon-o-currency-dollar' => 'Currency',
                                                'heroicon-o-chart-bar' => 'Chart',
                                                'heroicon-o-globe-alt' => 'Globe',
                                                'heroicon-o-building-office' => 'Building',
                                                'heroicon-o-clock' => 'Clock',
                                                'heroicon-o-star' => 'Star',
                                                'heroicon-o-heart' => 'Heart',
                                                'heroicon-o-trophy' => 'Trophy',
                                                'heroicon-o-rocket-launch' => 'Rocket',
                                                'heroicon-o-check-badge' => 'Badge',
                                                'heroicon-o-document-text' => 'Document',
                                                'heroicon-o-shopping-cart' => 'Cart',
                                                'heroicon-o-calendar' => 'Calendar',
                                                'heroicon-o-map-pin' => 'Location',
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(4)
                                    ->minItems(2)
                                    ->maxItems(8)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'New Stat')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make(__('tallcms::ui.t_grid_layout'))
                                    ->schema([
                                        Select::make('columns')
                                            ->label(__('tallcms::fields.columns'))
                                            ->options([
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                            ])
                                            ->default('4'),

                                        Select::make('stat_style')
                                            ->label(__('tallcms::fields.stat_style'))
                                            ->options(static::getStatsStyleOptions())
                                            ->default('stat'),

                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.text_alignment'))
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::ui.t_appearance'))
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label(__('tallcms::fields.background'))
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('accent_color')
                                            ->label(__('tallcms::fields.accent_color'))
                                            ->options(static::getAccentColorOptions())
                                            ->default('primary')
                                            ->helperText(__('tallcms::ui.t_color_used_for_stat_icons_and_values')),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::ui.t_spacing'))
                                    ->schema([
                                        Toggle::make('animate')
                                            ->label(__('tallcms::fields.count_up_animation'))
                                            ->helperText(__('tallcms::ui.t_animate_numbers_when_scrolling_into_view_respects_prefers_reduced_mo'))
                                            ->default(false),

                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_padding'))
                                            ->helperText(__('tallcms::ui.t_overrides_padding_setting_above'))
                                            ->default(false),
                                    ])
                                    ->columns(2),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $stats = $config['stats'] ?? self::getSampleStats();

        return static::renderBlock(array_merge($config, ['stats' => $stats]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.stats', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'stats' => $config['stats'] ?? [],
            'columns' => $config['columns'] ?? '4',
            'stat_style' => $config['stat_style'] ?? 'stat',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'accent_color' => $config['accent_color'] ?? 'primary',
            'padding' => $config['padding'] ?? 'py-16',
            'animate' => $config['animate'] ?? false,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleStats(): array
    {
        return [
            [
                'value' => '10000',
                'label' => 'Happy Customers',
                'suffix' => '+',
                'icon' => 'heroicon-o-users',
            ],
            [
                'value' => '50',
                'label' => 'Countries Served',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'value' => '99',
                'label' => 'Uptime',
                'suffix' => '%',
                'icon' => 'heroicon-o-chart-bar',
            ],
            [
                'value' => '24',
                'label' => 'Hour Support',
                'suffix' => '/7',
                'icon' => 'heroicon-o-clock',
            ],
        ];
    }
}
