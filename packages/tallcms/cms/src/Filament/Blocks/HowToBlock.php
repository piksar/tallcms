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
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class HowToBlock extends RichContentCustomBlock
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
        return 'heroicon-o-numbered-list';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.how_to');
    }

    public static function getKeywords(): array
    {
        return ['howto', 'steps', 'instructions', 'tutorial', 'guide'];
    }

    public static function getSortPriority(): int
    {
        return 21;
    }

    public static function getId(): string
    {
        return 'how-to';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.how_to');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_create_step_by_step_instructions_with_howto_schema_markup'))
            ->modalHeading(__('tallcms::ui.t_configure_how_to_block'))
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('How To Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('tallcms::fields.title'))
                                    ->placeholder(__('tallcms::ui.t_how_to'))
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label(__('tallcms::fields.description'))
                                    ->placeholder(__('tallcms::ui.t_a_brief_overview_of_what_this_guide_covers'))
                                    ->maxLength(500)
                                    ->rows(2),

                                TextInput::make('total_time')
                                    ->label(__('tallcms::fields.total_time'))
                                    ->placeholder(__('tallcms::ui.t_e_g_pt30m_or_30_minutes'))
                                    ->maxLength(100),

                                TextInput::make('estimated_cost')
                                    ->label(__('tallcms::fields.estimated_cost'))
                                    ->placeholder(__('tallcms::ui.t_e_g_50'))
                                    ->maxLength(50),

                                TextInput::make('currency')
                                    ->label(__('tallcms::fields.currency'))
                                    ->default('USD')
                                    ->maxLength(10),

                                Repeater::make('steps')
                                    ->label(__('tallcms::fields.steps'))
                                    ->schema([
                                        TextInput::make('step_name')
                                            ->label(__('tallcms::fields.step_name'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_step_title'))
                                            ->maxLength(500),

                                        Textarea::make('step_text')
                                            ->label(__('tallcms::fields.step_description'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_detailed_instructions_for_this_step'))
                                            ->rows(3),

                                        FileUpload::make('step_image')
                                            ->label(__('tallcms::fields.step_image'))
                                            ->image()
                                            ->directory('cms/blocks/howto')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->nullable(),

                                        TextInput::make('step_url')
                                            ->label(__('tallcms::fields.step_url'))
                                            ->url()
                                            ->nullable()
                                            ->placeholder('https://...')
                                            ->maxLength(500),
                                    ])
                                    ->defaultItems(2)
                                    ->minItems(2)
                                    ->maxItems(30)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['step_name'] ?? 'New Step')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make(__('tallcms::fields.settings'))
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make(__('tallcms::ui.t_display_options'))
                                    ->schema([
                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.header_alignment'))
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

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
                                            ->helperText(__('tallcms::ui.t_color_used_for_step_number_circles')),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::fields.seo'))
                                    ->schema([
                                        Toggle::make('show_schema')
                                            ->label(__('tallcms::fields.add_howto_schema_markup'))
                                            ->helperText(__('tallcms::ui.t_adds_schema_org_howto_structured_data_for_seo'))
                                            ->default(true),
                                    ]),

                                Section::make(__('tallcms::ui.t_spacing'))
                                    ->schema([
                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_padding'))
                                            ->helperText(__('tallcms::ui.t_overrides_padding_setting_above'))
                                            ->default(false),
                                    ]),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $steps = $config['steps'] ?? self::getSampleSteps();

        return static::renderBlock(array_merge($config, [
            'steps' => $steps,
            'title' => $config['title'] ?? 'How to Get Started',
            'description' => $config['description'] ?? 'Follow these simple steps to get up and running quickly',
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

        return view('tallcms::cms.blocks.how-to', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'total_time' => $config['total_time'] ?? '',
            'estimated_cost' => $config['estimated_cost'] ?? '',
            'currency' => $config['currency'] ?? 'USD',
            'steps' => $config['steps'] ?? [],
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'accent_color' => $config['accent_color'] ?? 'primary',
            'padding' => $config['padding'] ?? 'py-16',
            'show_schema' => $config['show_schema'] ?? true,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleSteps(): array
    {
        return [
            [
                'step_name' => 'Create an account',
                'step_text' => 'Sign up for a free account by clicking the registration button and filling in your details.',
                'step_image' => null,
                'step_url' => null,
            ],
            [
                'step_name' => 'Configure your settings',
                'step_text' => 'Navigate to the settings page and customize your preferences to match your needs.',
                'step_image' => null,
                'step_url' => null,
            ],
            [
                'step_name' => 'Start building',
                'step_text' => 'Use the intuitive editor to create your first page with drag-and-drop blocks.',
                'step_image' => null,
                'step_url' => null,
            ],
        ];
    }
}
