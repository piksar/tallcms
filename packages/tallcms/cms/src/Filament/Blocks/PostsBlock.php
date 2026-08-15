<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class PostsBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'dynamic';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.posts');
    }

    public static function getKeywords(): array
    {
        return ['blog', 'articles', 'posts', 'recent'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

    public static function getId(): string
    {
        return 'posts';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.posts');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('5xl')
            ->modalDescription(__('tallcms::ui.t_display_a_list_of_posts_filtered_by_category'))
            ->schema([
                // Hidden block UUID for stable pagination parameters
                Hidden::make('block_uuid')
                    ->default(fn () => Str::uuid()->toString()),

                Tabs::make('Posts Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-newspaper')
                            ->schema([
                                Section::make(__('tallcms::ui.t_filtering'))
                                    ->schema([
                                        Select::make('categories')
                                            ->label(__('tallcms::fields.filter_by_categories'))
                                            ->multiple()
                                            ->options(function () {
                                                $query = \TallCms\Cms\Models\CmsCategory::query();
                                                if (auth()->check() && ! auth()->user()->hasRole('super_admin')
                                                    && \Illuminate\Support\Facades\Schema::hasColumn('tallcms_categories', 'user_id')) {
                                                    $query->where('user_id', auth()->id());
                                                }

                                                return $query->pluck('name', 'id')->toArray();
                                            })
                                            ->placeholder(__('tallcms::ui.t_all_categories'))
                                            ->helperText(__('tallcms::ui.t_leave_empty_to_show_posts_from_all_categories')),

                                        Toggle::make('featured_only')
                                            ->label(__('tallcms::fields.featured_posts_only'))
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_quantity_sorting'))
                                    ->schema([
                                        Select::make('posts_count')
                                            ->label(__('tallcms::fields.number_of_posts'))
                                            ->options([
                                                '3' => '3 posts',
                                                '6' => '6 posts',
                                                '9' => '9 posts',
                                                '12' => '12 posts',
                                                '24' => '24 posts',
                                            ])
                                            ->default('6'),

                                        TextInput::make('offset')
                                            ->label(__('tallcms::fields.offset_skip_posts'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText(__('tallcms::ui.t_skip_first_n_posts_incompatible_with_pagination')),

                                        Select::make('sort_by')
                                            ->label(__('tallcms::fields.sort_by'))
                                            ->options([
                                                'newest' => 'Newest First',
                                                'oldest' => 'Oldest First',
                                                'title_asc' => 'Title (A-Z)',
                                                'title_desc' => 'Title (Z-A)',
                                                'featured_first' => 'Featured First, then Newest',
                                                'manual' => 'Manual Selection',
                                            ])
                                            ->default('newest')
                                            ->live(),

                                        Select::make('pinned_posts')
                                            ->label(__('tallcms::fields.select_posts_manual_order'))
                                            ->multiple()
                                            ->options(function () {
                                                $query = \TallCms\Cms\Models\CmsPost::published();
                                                if (auth()->check() && ! auth()->user()->hasRole('super_admin')
                                                    && \Illuminate\Support\Facades\Schema::hasColumn('tallcms_posts', 'user_id')) {
                                                    $query->where('user_id', auth()->id());
                                                }

                                                return $query->pluck('title', 'id')->toArray();
                                            })
                                            ->visible(fn (Get $get) => $get('sort_by') === 'manual')
                                            ->helperText(__('tallcms::ui.t_select_and_order_posts_manually')),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_display_options'))
                                    ->schema([
                                        Toggle::make('show_image')
                                            ->label(__('tallcms::fields.show_featured_image'))
                                            ->default(true),

                                        Toggle::make('show_excerpt')
                                            ->label(__('tallcms::fields.show_excerpt'))
                                            ->default(true),

                                        Toggle::make('show_date')
                                            ->label(__('tallcms::fields.show_date'))
                                            ->default(true),

                                        Toggle::make('show_author')
                                            ->label(__('tallcms::fields.show_author'))
                                            ->default(false),

                                        Toggle::make('show_categories')
                                            ->label(__('tallcms::fields.show_categories'))
                                            ->default(true),

                                        Toggle::make('show_read_more')
                                            ->label(__('tallcms::fields.show_read_more_link'))
                                            ->default(true),

                                        Toggle::make('show_comments')
                                            ->label(__('tallcms::fields.show_comments'))
                                            ->default(config('tallcms.comments.enabled', true)),
                                    ])
                                    ->columns(3),

                                TextInput::make('empty_message')
                                    ->label(__('tallcms::fields.empty_state_message'))
                                    ->placeholder(__('tallcms::ui.t_no_posts_found'))
                                    ->helperText(__('tallcms::ui.t_message_shown_when_no_posts_match_the_filters')),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')
                                    ->label(__('tallcms::fields.layout_style'))
                                    ->options([
                                        'grid' => 'Grid (cards)',
                                        'list' => 'List (horizontal)',
                                        'compact-list' => 'Compact List (minimal)',
                                        'featured-hero' => 'Featured Hero + Grid',
                                    ])
                                    ->default('grid')
                                    ->live(),

                                Select::make('columns')
                                    ->label(__('tallcms::fields.grid_columns'))
                                    ->options([
                                        '2' => '2 Columns',
                                        '3' => '3 Columns',
                                        '4' => '4 Columns',
                                    ])
                                    ->default('3')
                                    ->visible(fn (Get $get) => in_array($get('layout'), ['grid', 'featured-hero']))
                                    ->helperText(__('tallcms::ui.t_responsive_1_on_mobile_2_on_tablet_selected_on_desktop')),

                                Section::make(__('tallcms::ui.t_pagination'))
                                    ->schema([
                                        Toggle::make('enable_pagination')
                                            ->label(__('tallcms::fields.enable_pagination'))
                                            ->helperText(__('tallcms::ui.t_show_pagination_controls_when_there_are_more_posts_than_displayed'))
                                            ->default(false)
                                            ->live(),

                                        Select::make('per_page')
                                            ->label(__('tallcms::fields.posts_per_page'))
                                            ->options([
                                                '6' => '6 posts',
                                                '9' => '9 posts',
                                                '12' => '12 posts',
                                                '18' => '18 posts',
                                                '24' => '24 posts',
                                            ])
                                            ->default('12')
                                            ->visible(fn (Get $get) => $get('enable_pagination'))
                                            ->helperText(__('tallcms::ui.t_number_of_posts_to_show_per_page')),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_featured_posts'))
                                    ->schema([
                                        Toggle::make('show_featured_badge')
                                            ->label(__('tallcms::fields.show_featured_badge'))
                                            ->helperText(__('tallcms::ui.t_display_a_badge_on_featured_posts'))
                                            ->default(false)
                                            ->live(),

                                        Select::make('featured_badge_style')
                                            ->label(__('tallcms::fields.badge_style'))
                                            ->options([
                                                'badge' => 'Text Badge ("Featured")',
                                                'star' => 'Star Icon',
                                                'ribbon' => 'Corner Ribbon',
                                            ])
                                            ->default('badge')
                                            ->visible(fn (Get $get) => $get('show_featured_badge')),

                                        Select::make('featured_badge_color')
                                            ->label(__('tallcms::fields.badge_color'))
                                            ->options([
                                                'primary' => 'Primary',
                                                'secondary' => 'Secondary',
                                                'accent' => 'Accent',
                                                'warning' => 'Warning (Gold)',
                                            ])
                                            ->default('warning')
                                            ->visible(fn (Get $get) => $get('show_featured_badge')),

                                        Select::make('featured_card_style')
                                            ->label(__('tallcms::fields.featured_card_style'))
                                            ->options([
                                                'default' => 'Same as Regular',
                                                'border' => 'Accent Border',
                                                'gradient' => 'Gradient Background',
                                                'elevated' => 'Elevated Shadow',
                                            ])
                                            ->default('default'),
                                    ])
                                    ->columns(4),

                                Section::make(__('tallcms::ui.t_appearance'))
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label(__('tallcms::fields.background'))
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_padding'))
                                            ->helperText(__('tallcms::ui.t_overrides_padding_setting_above'))
                                            ->default(false),
                                    ])
                                    ->columns(4),
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
                                    ->helperText(__('tallcms::ui.t_animate_post_cards_sequentially_instead_of_all_at_once'))
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
        return static::renderBlock($config, true, 'preview');
    }

    public static function toHtml(array $config, array $data): string
    {
        // Don't pass parentSlug - let the template resolve it from request()->route('slug')
        // This ensures correct URL generation when viewing the page
        return static::renderBlock($config, false);
    }

    protected static function renderBlock(array $config, bool $isPreview, ?string $parentSlug = null): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        $params = [
            ...$config,
            'isPreview' => $isPreview,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ];

        if ($parentSlug !== null) {
            $params['parentSlug'] = $parentSlug;
        }

        return view('tallcms::cms.blocks.posts', $params)->render();
    }
}
