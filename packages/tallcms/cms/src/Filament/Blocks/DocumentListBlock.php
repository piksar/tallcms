<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
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

class DocumentListBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'standard';
    }

    public static function getCategory(): string
    {
        return 'media';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-document-arrow-down';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.document_list');
    }

    public static function getKeywords(): array
    {
        return ['documents', 'files', 'download', 'pdf', 'attachments', 'list'];
    }

    public static function getSortPriority(): int
    {
        return 15;
    }

    public static function getId(): string
    {
        return 'document_list';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.document_list');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('4xl')
            ->modalDescription(__('tallcms::ui.t_display_a_list_of_downloadable_documents'))
            ->schema([
                Tabs::make('Document List Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('tallcms::fields.section_title'))
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_e_g_downloads_resources_attachments')),

                                TextInput::make('description')
                                    ->label(__('tallcms::fields.description'))
                                    ->maxLength(500)
                                    ->placeholder(__('tallcms::ui.t_optional_description_text')),

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
                                    ->required()
                                    ->helperText(__('tallcms::ui.t_select_collections_containing_documents')),

                                Select::make('file_types')
                                    ->label(__('tallcms::fields.file_types'))
                                    ->multiple()
                                    ->options([
                                        'application/pdf' => 'PDF',
                                        'application/msword' => 'Word (DOC)',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word (DOCX)',
                                        'application/vnd.ms-excel' => 'Excel (XLS)',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel (XLSX)',
                                        'application/zip' => 'ZIP',
                                    ])
                                    ->placeholder(__('tallcms::ui.t_all_document_types'))
                                    ->helperText(__('tallcms::ui.t_leave_empty_to_show_all_documents')),

                                Select::make('order')
                                    ->label(__('tallcms::fields.order'))
                                    ->options([
                                        'newest' => 'Newest First',
                                        'oldest' => 'Oldest First',
                                        'name' => 'Alphabetical',
                                    ])
                                    ->default('newest'),

                                TextInput::make('max_items')
                                    ->label(__('tallcms::fields.maximum_items'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->placeholder(__('tallcms::ui.t_no_limit')),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')
                                    ->label(__('tallcms::fields.layout'))
                                    ->options([
                                        'list' => 'Simple List',
                                        'cards' => 'Cards',
                                        'compact' => 'Compact',
                                    ])
                                    ->default('list'),

                                Toggle::make('show_file_size')
                                    ->label(__('tallcms::fields.show_file_size'))
                                    ->default(true),

                                Toggle::make('show_file_type')
                                    ->label(__('tallcms::fields.show_file_type_badge'))
                                    ->default(true),

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
                                    ->helperText(__('tallcms::ui.t_animate_items_sequentially_instead_of_all_at_once'))
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

        return view('tallcms::cms.blocks.document-list', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'collection_ids' => $config['collection_ids'] ?? [],
            'file_types' => $config['file_types'] ?? [],
            'order' => $config['order'] ?? 'newest',
            'max_items' => isset($config['max_items']) ? (int) $config['max_items'] : null,
            'layout' => $config['layout'] ?? 'list',
            'show_file_size' => $config['show_file_size'] ?? true,
            'show_file_type' => $config['show_file_type'] ?? true,
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
