<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

class LogosBlock extends RichContentCustomBlock
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
        return 'heroicon-o-building-office';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.logos');
    }

    public static function getKeywords(): array
    {
        return ['clients', 'partners', 'brands', 'logos'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'logos';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.logos');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_display_client_or_partner_logos_in_a_grid_or_inline_layout'))
            ->modalHeading(__('tallcms::ui.t_configure_logos_block'))
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Logos Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-photo')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_trusted_by_leading_companies'))
                                    ->maxLength(255),

                                Select::make('source')
                                    ->label(__('tallcms::fields.logo_source'))
                                    ->options([
                                        'manual' => 'Manual Upload',
                                        'collection' => 'Media Collection',
                                    ])
                                    ->default('manual')
                                    ->live()
                                    ->helperText(__('tallcms::ui.t_use_a_media_collection_for_easier_management')),

                                Select::make('collection_id')
                                    ->label(__('tallcms::fields.media_collection'))
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
                                    ->helperText(__('tallcms::ui.t_select_a_collection_containing_logo_images')),

                                Repeater::make('logos')
                                    ->label(__('tallcms::fields.logos'))
                                    ->visible(fn (Get $get): bool => $get('source') !== 'collection')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label(__('tallcms::fields.logo_image'))
                                            ->required()
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('logos')
                                            ->visibility(\cms_media_visibility()),

                                        TextInput::make('alt')
                                            ->label(__('tallcms::fields.company_name'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_acme_inc'))
                                            ->helperText(__('tallcms::ui.t_used_for_accessibility'))
                                            ->maxLength(100),

                                        TextInput::make('url')
                                            ->label(__('tallcms::fields.link_optional'))
                                            ->placeholder('https://example.com')
                                            ->maxLength(500),
                                    ])
                                    ->defaultItems(4)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['alt'] ?? 'New Logo')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make(__('tallcms::ui.t_display_options'))
                                    ->schema([
                                        Select::make('layout')
                                            ->label(__('tallcms::fields.layout'))
                                            ->options([
                                                'grid' => 'Grid',
                                                'inline' => 'Inline (Centered)',
                                            ])
                                            ->default('grid'),

                                        Select::make('columns')
                                            ->label(__('tallcms::fields.columns_grid_layout'))
                                            ->options([
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                                '5' => '5 Columns',
                                                '6' => '6 Columns',
                                            ])
                                            ->default('5'),

                                        Select::make('size')
                                            ->label(__('tallcms::fields.logo_size'))
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium',
                                                'large' => 'Large',
                                            ])
                                            ->default('medium'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::ui.t_styling'))
                                    ->schema([
                                        Toggle::make('grayscale')
                                            ->label(__('tallcms::fields.grayscale_logos'))
                                            ->helperText(__('tallcms::ui.t_display_logos_in_grayscale'))
                                            ->default(true),

                                        Toggle::make('hover_color')
                                            ->label(__('tallcms::fields.color_on_hover'))
                                            ->helperText(__('tallcms::ui.t_show_color_when_hovering_only_if_grayscale_is_enabled'))
                                            ->default(true),
                                    ])
                                    ->columns(2),

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

                        static::getAnimationTab(supportsStagger: false),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $logos = self::resolveLogos($config, forPreview: true);

        return static::renderBlock(array_merge($config, [
            'logos' => $logos,
            'heading' => $config['heading'] ?? 'Trusted by leading companies',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        $logos = self::resolveLogos($config, forPreview: false);

        // Return empty string if no logos configured (signals misconfiguration)
        if (empty($logos)) {
            return '';
        }

        return static::renderBlock(array_merge($config, ['logos' => $logos]));
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.logos', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'logos' => $config['logos'] ?? [],
            'layout' => $config['layout'] ?? 'grid',
            'columns' => $config['columns'] ?? '5',
            'size' => $config['size'] ?? 'medium',
            'grayscale' => $config['grayscale'] ?? true,
            'hover_color' => $config['hover_color'] ?? true,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }

    private static function resolveLogos(array $config, bool $forPreview): array
    {
        $source = $config['source'] ?? 'manual';

        if ($source === 'collection') {
            if (empty($config['collection_id'])) {
                // Collection source selected but no collection chosen
                // In preview: show placeholders; in production: return empty to signal misconfiguration
                return $forPreview ? self::getSampleLogos() : [];
            }

            $collection = MediaCollection::with('media')->find($config['collection_id']);
            if (! $collection || $collection->media->isEmpty()) {
                // Collection not found or empty
                return $forPreview ? self::getSampleLogos() : [];
            }

            return $collection->media->map(fn ($media) => [
                'image' => $media->path,
                'alt' => $media->alt ?? $media->name ?? 'Logo',
                'url' => null,
            ])->toArray();
        }

        // Manual logos
        $logos = $config['logos'] ?? [];
        if (empty($logos)) {
            return $forPreview ? self::getSampleLogos() : [];
        }

        return $logos;
    }

    private static function getSampleLogos(): array
    {
        return [
            ['alt' => 'Company 1', 'image' => null, 'url' => null],
            ['alt' => 'Company 2', 'image' => null, 'url' => null],
            ['alt' => 'Company 3', 'image' => null, 'url' => null],
            ['alt' => 'Company 4', 'image' => null, 'url' => null],
            ['alt' => 'Company 5', 'image' => null, 'url' => null],
        ];
    }
}
