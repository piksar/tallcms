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

class TimelineBlock extends RichContentCustomBlock
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
        return 'dynamic';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.timeline');
    }

    public static function getKeywords(): array
    {
        return ['history', 'events', 'timeline', 'chronology'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'timeline';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.timeline');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_display_chronological_events_process_steps_or_milestones'))
            ->modalHeading(__('tallcms::ui.t_configure_timeline_block'))
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Timeline Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_our_journey'))
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label(__('tallcms::fields.section_subheading'))
                                    ->placeholder(__('tallcms::ui.t_key_milestones_in_our_history'))
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('items')
                                    ->label(__('tallcms::fields.timeline_items'))
                                    ->schema([
                                        TextInput::make('title')
                                            ->label(__('tallcms::fields.title'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_company_founded'))
                                            ->maxLength(200),

                                        Textarea::make('description')
                                            ->label(__('tallcms::fields.description'))
                                            ->placeholder(__('tallcms::ui.t_describe_this_milestone_or_step'))
                                            ->maxLength(1000)
                                            ->rows(3),

                                        TextInput::make('date')
                                            ->label(__('tallcms::fields.date_step_label'))
                                            ->placeholder(__('tallcms::ui.t_2020_or_step_1'))
                                            ->maxLength(50),

                                        Select::make('icon')
                                            ->label(__('tallcms::fields.icon_optional'))
                                            ->searchable()
                                            ->options([
                                                '' => 'No Icon',
                                                'heroicon-o-flag' => 'Flag',
                                                'heroicon-o-rocket-launch' => 'Rocket',
                                                'heroicon-o-star' => 'Star',
                                                'heroicon-o-check-circle' => 'Check Circle',
                                                'heroicon-o-light-bulb' => 'Light Bulb',
                                                'heroicon-o-trophy' => 'Trophy',
                                                'heroicon-o-academic-cap' => 'Academic Cap',
                                                'heroicon-o-building-office' => 'Building',
                                                'heroicon-o-users' => 'Users',
                                                'heroicon-o-chart-bar' => 'Chart',
                                                'heroicon-o-globe-alt' => 'Globe',
                                                'heroicon-o-heart' => 'Heart',
                                                'heroicon-o-sparkles' => 'Sparkles',
                                                'heroicon-o-calendar' => 'Calendar',
                                                'heroicon-o-map-pin' => 'Location',
                                            ]),

                                        FileUpload::make('image')
                                            ->label(__('tallcms::fields.image_optional'))
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('timeline')
                                            ->visibility(\cms_media_visibility())
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('16:9')
                                            ->imageResizeTargetWidth('800')
                                            ->imageResizeTargetHeight('450'),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(2)
                                    ->maxItems(20)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Item')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make(__('tallcms::ui.t_display_options'))
                                    ->schema([
                                        Select::make('style')
                                            ->label(__('tallcms::fields.layout_style'))
                                            ->options([
                                                'vertical' => 'Vertical',
                                                'horizontal' => 'Horizontal',
                                            ])
                                            ->default('vertical')
                                            ->live(),

                                        Toggle::make('alternating')
                                            ->label(__('tallcms::fields.alternating_layout'))
                                            ->helperText(__('tallcms::ui.t_alternate_items_left_and_right_vertical_only'))
                                            ->default(true),

                                        Toggle::make('show_connector')
                                            ->label(__('tallcms::fields.show_connecting_line'))
                                            ->default(true),

                                        Toggle::make('numbered')
                                            ->label(__('tallcms::fields.show_step_numbers'))
                                            ->helperText(__('tallcms::ui.t_display_numbers_instead_of_icons'))
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_appearance'))
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.header_alignment'))
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),

                                        Select::make('background')
                                            ->label(__('tallcms::fields.background'))
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('accent_color')
                                            ->label(__('tallcms::fields.accent_color'))
                                            ->options(static::getAccentColorOptions())
                                            ->default('primary')
                                            ->helperText(__('tallcms::ui.t_color_used_for_timeline_node_markers_and_date_labels')),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_padding'))
                                            ->helperText(__('tallcms::ui.t_overrides_padding_setting_above'))
                                            ->default(false),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make(__('tallcms::ui.t_animation'))
                            ->icon('heroicon-m-sparkles')
                            ->schema([
                                Select::make('animation_type')
                                    ->label(__('tallcms::fields.entrance_animation'))
                                    ->options(static::getAnimationTypeOptions())
                                    ->default('')
                                    ->helperText(__('tallcms::ui.t_animation_plays_when_block_scrolls_into_view')),

                                Select::make('animation_duration')
                                    ->label(__('tallcms::fields.animation_speed'))
                                    ->options(static::getAnimationDurationOptions())
                                    ->default('anim-duration-700'),

                                Toggle::make('animation_stagger')
                                    ->label(__('tallcms::fields.stagger_items'))
                                    ->helperText(__('tallcms::ui.t_animate_timeline_items_sequentially_instead_of_all_at_once'))
                                    ->default(false)
                                    ->live()
                                    ->visible(fn (): bool => static::hasPro()),

                                Select::make('animation_stagger_delay')
                                    ->label(__('tallcms::fields.stagger_delay'))
                                    ->options(static::getStaggerDelayOptions())
                                    ->default('100')
                                    ->visible(fn (Get $get): bool => static::hasPro() && $get('animation_stagger') === true),
                            ])
                            ->columns(2),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $items = $config['items'] ?? self::getSampleItems();

        return static::renderBlock(array_merge($config, [
            'items' => $items,
            'heading' => $config['heading'] ?? 'Our Journey',
            'subheading' => $config['subheading'] ?? 'Key milestones that shaped who we are today',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.timeline', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'style' => $config['style'] ?? 'vertical',
            'alternating' => $config['alternating'] ?? true,
            'show_connector' => $config['show_connector'] ?? true,
            'numbered' => $config['numbered'] ?? false,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'accent_color' => $config['accent_color'] ?? 'primary',
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

    private static function getSampleItems(): array
    {
        return [
            [
                'title' => 'Company Founded',
                'description' => 'Started with a vision to transform the industry and create meaningful impact.',
                'date' => '2018',
                'icon' => 'heroicon-o-flag',
            ],
            [
                'title' => 'First Major Milestone',
                'description' => 'Reached 10,000 customers and expanded our team to 50 employees.',
                'date' => '2020',
                'icon' => 'heroicon-o-rocket-launch',
            ],
            [
                'title' => 'Global Expansion',
                'description' => 'Opened offices in 5 countries and launched in 20 new markets.',
                'date' => '2022',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'title' => 'Industry Recognition',
                'description' => 'Won multiple awards and became a leader in our space.',
                'date' => '2024',
                'icon' => 'heroicon-o-trophy',
            ],
        ];
    }
}
