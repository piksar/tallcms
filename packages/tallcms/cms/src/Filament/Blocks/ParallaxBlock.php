<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class ParallaxBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'media';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.parallax');
    }

    public static function getKeywords(): array
    {
        return ['scroll', 'background', 'parallax', 'effect'];
    }

    public static function getSortPriority(): int
    {
        return 20;
    }

    public static function getId(): string
    {
        return 'parallax';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.parallax');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_create_a_full_width_parallax_image_section_with_overlay_content'))
            ->modalHeading(__('tallcms::ui.t_configure_parallax_section'))
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Parallax Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('tallcms::fields.background_image'))
                                    ->required()
                                    ->image()
                                    ->disk(\cms_media_disk())
                                    ->directory('parallax')
                                    ->visibility(\cms_media_visibility())
                                    ->imageResizeMode('cover')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080'),

                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.heading'))
                                    ->placeholder(__('tallcms::ui.t_your_inspiring_message'))
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label(__('tallcms::fields.subheading'))
                                    ->placeholder(__('tallcms::ui.t_a_brief_description_or_call_to_action'))
                                    ->maxLength(500)
                                    ->rows(2),

                                TextInput::make('cta_text')
                                    ->label(__('tallcms::fields.button_text_optional'))
                                    ->placeholder(__('tallcms::ui.t_get_started'))
                                    ->maxLength(50),

                                TextInput::make('cta_url')
                                    ->label(__('tallcms::fields.button_url'))
                                    ->placeholder('https://example.com or /page')
                                    ->maxLength(500),
                            ]),

                        Tab::make(__('tallcms::ui.t_appearance'))
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make(__('tallcms::fields.layout'))
                                    ->schema([
                                        Select::make('height')
                                            ->label(__('tallcms::fields.section_height'))
                                            ->options([
                                                'small' => 'Small (300px)',
                                                'medium' => 'Medium (500px)',
                                                'large' => 'Large (700px)',
                                                'full' => 'Full Viewport',
                                            ])
                                            ->default('medium'),

                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.text_alignment'))
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::ui.t_overlay'))
                                    ->schema([
                                        ColorPicker::make('overlay_color')
                                            ->label(__('tallcms::fields.overlay_color'))
                                            ->default('#000000'),

                                        Select::make('overlay_opacity')
                                            ->label(__('tallcms::fields.overlay_opacity'))
                                            ->options([
                                                '0' => '0% (No Overlay)',
                                                '10' => '10%',
                                                '20' => '20%',
                                                '30' => '30%',
                                                '40' => '40%',
                                                '50' => '50%',
                                                '60' => '60%',
                                                '70' => '70%',
                                                '80' => '80%',
                                            ])
                                            ->default('50'),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock(array_merge($config, [
            'heading' => $config['heading'] ?? 'Inspiring Parallax Section',
            'subheading' => $config['subheading'] ?? 'Create beautiful, immersive experiences with parallax scrolling',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        // Don't render if no image
        if (empty($config['image'])) {
            return '';
        }

        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('tallcms::cms.blocks.parallax', [
            'id' => static::getId(),
            'image' => $config['image'] ?? null,
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'cta_text' => $config['cta_text'] ?? '',
            'cta_url' => $config['cta_url'] ?? '',
            'height' => $config['height'] ?? 'medium',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'overlay_color' => $config['overlay_color'] ?? '#000000',
            'overlay_opacity' => $config['overlay_opacity'] ?? '50',
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }
}
