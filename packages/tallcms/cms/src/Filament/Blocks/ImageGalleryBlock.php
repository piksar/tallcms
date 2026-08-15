<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
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
use TallCms\Cms\Models\MediaCollection;

class ImageGalleryBlock extends RichContentCustomBlock
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
        return 'media';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.image_gallery');
    }

    public static function getKeywords(): array
    {
        return ['images', 'photos', 'gallery', 'lightbox', 'video', 'media'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

    public static function getId(): string
    {
        return 'image_gallery';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.image_gallery');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('4xl')
            ->modalDescription(__('tallcms::ui.t_create_a_media_gallery_with_images_and_videos'))
            ->schema([
                Tabs::make('Media Gallery Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-photo')
                            ->schema([
                                TextInput::make('title')->label(__('tallcms::fields.title'))
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_gallery_title_optional')),

                                Select::make('source')
                                    ->label(__('tallcms::fields.image_source'))
                                    ->options([
                                        'manual' => 'Manual Upload',
                                        'collection' => 'Media Collection(s)',
                                    ])
                                    ->default('manual')
                                    ->live()
                                    ->helperText(__('tallcms::ui.t_use_collections_to_reuse_images_from_media_library')),

                                Select::make('collection_ids')
                                    ->label(__('tallcms::fields.collections'))
                                    ->multiple()
                                    ->options(function () {
                                        $query = MediaCollection::query();
                                        if (auth()->check() && ! auth()->user()->hasRole('super_admin')
                                            && \Illuminate\Support\Facades\Schema::hasColumn('tallcms_media_collections', 'user_id')) {
                                            $query->where('user_id', auth()->id());
                                        }

                                        return $query->pluck('name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('source') === 'collection')
                                    ->helperText(__('tallcms::ui.t_select_one_or_more_collections')),

                                Select::make('media_type')
                                    ->label(__('tallcms::fields.media_type'))
                                    ->options([
                                        'images' => 'Images Only',
                                        'videos' => 'Videos Only',
                                        'all' => 'Images & Videos',
                                    ])
                                    ->default('images')
                                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                                Select::make('collection_order')
                                    ->label(__('tallcms::fields.order'))
                                    ->options([
                                        'newest' => 'Newest First',
                                        'oldest' => 'Oldest First',
                                        'random' => 'Random',
                                    ])
                                    ->default('newest')
                                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                                TextInput::make('max_items')
                                    ->label(__('tallcms::fields.maximum_items'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->placeholder(__('tallcms::ui.t_no_limit'))
                                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                                FileUpload::make('images')->label(__('tallcms::fields.images'))
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->multiple()
                                    ->directory('cms/galleries')
                                    ->disk(\cms_media_disk())
                                    ->visibility(\cms_media_visibility())
                                    ->maxFiles(12)
                                    ->reorderable()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->requiredIf('source', 'manual')
                                    ->visible(fn (Get $get): bool => $get('source') !== 'collection')
                                    ->helperText(__('tallcms::ui.t_recommended_1200_800px_or_larger_up_to_12_images_max_5mb_each_format')),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')->label(__('tallcms::fields.layout'))
                                    ->options([
                                        'grid-1' => 'Grid (1 column)',
                                        'grid-2' => 'Grid (2 columns)',
                                        'grid-3' => 'Grid (3 columns)',
                                        'grid-4' => 'Grid (4 columns)',
                                        'masonry' => 'Masonry layout',
                                        'carousel' => 'Carousel/Slider',
                                    ])
                                    ->default('grid-3'),

                                Select::make('image_size')
                                    ->label(__('tallcms::fields.image_size'))
                                    ->options([
                                        'small' => 'Small (200px)',
                                        'medium' => 'Medium (300px)',
                                        'large' => 'Large (400px)',
                                        'full' => 'Full width',
                                    ])
                                    ->default('medium'),

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
                                    ->helperText(__('tallcms::ui.t_animate_images_sequentially_instead_of_all_at_once'))
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
        return static::renderBlock($config);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.image-gallery', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'source' => $config['source'] ?? 'manual',
            'images' => $config['images'] ?? [],
            'collection_ids' => $config['collection_ids'] ?? [],
            'collection_order' => $config['collection_order'] ?? 'newest',
            'media_type' => $config['media_type'] ?? 'images',
            'max_items' => isset($config['max_items']) ? (int) $config['max_items'] : (isset($config['max_images']) ? (int) $config['max_images'] : null),
            'layout' => $config['layout'] ?? 'grid-3',
            'image_size' => $config['image_size'] ?? 'medium',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }
}
