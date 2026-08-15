<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class FeaturesBlock extends RichContentCustomBlock
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
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.features');
    }

    public static function getKeywords(): array
    {
        return ['features', 'benefits', 'list', 'grid'];
    }

    public static function getSortPriority(): int
    {
        return 25;
    }

    public static function getId(): string
    {
        return 'features';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.features');
    }

    protected static function getCardStyleOptions(): array
    {
        return [
            'card shadow-xl bg-base-100' => 'Shadow',
            'card border border-base-300 bg-base-100' => 'Bordered',
            'card bg-base-200' => 'Filled',
            'card bg-base-300' => 'Filled Dark',
            'card bg-primary text-primary-content' => 'Primary',
            'card bg-secondary text-secondary-content' => 'Secondary',
            'card bg-accent text-accent-content' => 'Accent',
            'card bg-neutral text-neutral-content' => 'Neutral',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_showcase_product_or_service_features_in_a_grid_layout'))
            ->modalHeading(__('tallcms::ui.t_configure_features_block'))
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Features Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_our_features'))
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label(__('tallcms::fields.section_subheading'))
                                    ->placeholder(__('tallcms::ui.t_everything_you_need_to_succeed'))
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('features')
                                    ->label(__('tallcms::fields.features'))
                                    ->schema([
                                        Select::make('icon_type')
                                            ->label(__('tallcms::fields.icon_type'))
                                            ->options([
                                                'heroicon' => 'Heroicon',
                                                'image' => 'Custom Image',
                                                'emoji' => 'Emoji',
                                            ])
                                            ->default('heroicon')
                                            ->live(),

                                        TextInput::make('icon')
                                            ->label(__('tallcms::fields.heroicon_name'))
                                            ->placeholder('heroicon-o-check-circle')
                                            ->helperText(__('tallcms::ui.t_e_g_heroicon_o_bolt_heroicon_o_shield_check'))
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'heroicon'),

                                        FileUpload::make('icon_image')
                                            ->label(__('tallcms::fields.icon_image'))
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('features')
                                            ->visibility(\cms_media_visibility())
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'image'),

                                        TextInput::make('emoji')
                                            ->label(__('tallcms::fields.emoji'))
                                            ->placeholder('🚀')
                                            ->maxLength(10)
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'emoji'),

                                        TextInput::make('title')
                                            ->label(__('tallcms::fields.feature_title'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_fast_performance'))
                                            ->maxLength(100),

                                        Textarea::make('description')
                                            ->label(__('tallcms::fields.feature_description'))
                                            ->placeholder(__('tallcms::ui.t_lightning_fast_load_times_and_optimized_performance'))
                                            ->maxLength(500)
                                            ->rows(2),

                                        TextInput::make('link')
                                            ->label(__('tallcms::fields.link_optional'))
                                            ->placeholder('https://example.com/features')
                                            ->helperText(__('tallcms::ui.t_use_full_urls_https_or_relative_paths_page'))
                                            ->maxLength(255),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Feature')
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
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label(__('tallcms::fields.card_style'))
                                            ->options(static::getCardStyleOptions())
                                            ->default('card shadow-xl bg-base-100'),

                                        Select::make('icon_position')
                                            ->label(__('tallcms::fields.icon_position'))
                                            ->options([
                                                'top' => 'Top (Centered)',
                                                'left' => 'Left (Inline)',
                                            ])
                                            ->default('top'),

                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.text_alignment'))
                                            ->options([
                                                'text-left' => 'Left',
                                                'text-center' => 'Center',
                                            ])
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_appearance'))
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('icon_size')
                                            ->label(__('tallcms::fields.icon_size'))
                                            ->options([
                                                'w-8 h-8' => 'Small',
                                                'w-10 h-10' => 'Medium',
                                                'w-12 h-12' => 'Large',
                                            ])
                                            ->default('w-10 h-10'),

                                        Select::make('accent_color')
                                            ->label(__('tallcms::fields.accent_color'))
                                            ->options(static::getAccentColorOptions())
                                            ->default('primary')
                                            ->helperText(__('tallcms::ui.t_color_used_for_icons_and_highlights')),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_spacing'))
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $features = $config['features'] ?? self::getSampleFeatures();

        return static::renderBlock(array_merge($config, ['features' => $features]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.features', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'features' => $config['features'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'card shadow-xl bg-base-100',
            'icon_position' => $config['icon_position'] ?? 'top',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'icon_size' => $config['icon_size'] ?? 'w-10 h-10',
            'accent_color' => $config['accent_color'] ?? 'primary',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleFeatures(): array
    {
        return [
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-bolt',
                'title' => 'Lightning Fast',
                'description' => 'Optimized for speed with sub-second load times.',
            ],
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-shield-check',
                'title' => 'Secure by Default',
                'description' => 'Enterprise-grade security built into every layer.',
            ],
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-cube-transparent',
                'title' => 'Fully Customizable',
                'description' => 'Tailor every aspect to match your brand perfectly.',
            ],
        ];
    }
}
