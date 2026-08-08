<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\TagsInput;
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
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\BlockLinkResolver;

class HeroBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-home';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.hero');
    }

    public static function getKeywords(): array
    {
        return ['banner', 'header', 'landing', 'hero'];
    }

    public static function getSortPriority(): int
    {
        return 5;
    }

    public static function getId(): string
    {
        return 'hero';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.hero');
    }

    /**
     * Get hero-specific button variants (designed for dark backgrounds)
     */
    protected static function getHeroButtonVariantOptions(): array
    {
        return [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
            'btn-accent' => 'Accent',
            'btn-neutral' => 'Neutral',
            'btn-ghost text-white hover:bg-white/20' => 'Ghost (Light)',
            'btn-outline btn-primary' => 'Primary Outline',
        ];
    }

    /**
     * Get hero secondary button variants
     */
    protected static function getHeroSecondaryButtonVariantOptions(): array
    {
        return [
            'btn-ghost text-white hover:bg-white/20' => 'Ghost (Light)',
            'btn-outline border-white text-white hover:bg-white hover:text-base-content' => 'White Outline',
            'btn-link text-white' => 'Link (Light)',
            'btn-outline btn-primary' => 'Primary Outline',
        ];
    }

    /**
     * Get layout variant options
     */
    protected static function getLayoutOptions(): array
    {
        return [
            'centered' => 'Centered',
            'figure-left' => 'With Figure (Left)',
            'figure-right' => 'With Figure (Right)',
            'with-form' => 'With Form',
        ];
    }

    /**
     * Get form card style options
     */
    protected static function getFormCardStyleOptions(): array
    {
        return [
            'bg-base-100 shadow-2xl' => 'White with Large Shadow',
            'bg-base-100 shadow-md' => 'White with Shadow',
            'bg-base-200' => 'Subtle Background',
        ];
    }

    /**
     * Get hero background options (including gradient)
     */
    protected static function getHeroBackgroundOptions(): array
    {
        return [
            'bg-gradient-to-br from-primary to-secondary' => 'Primary to Secondary Gradient',
            'bg-base-200' => 'Base Subtle',
            'bg-base-300' => 'Base Strong',
            'bg-primary' => 'Primary',
            'bg-secondary' => 'Secondary',
            'bg-accent' => 'Accent',
            'bg-neutral' => 'Neutral',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_configure_the_hero_section_with_heading_subheading_and_background_im'))
            ->schema([
                Tabs::make('Hero Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                RichEditor::make('heading')->label(__('tallcms::fields.heading'))
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_enter_hero_heading'))
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'textColor',
                                    ])
                                    ->textColors([
                                        'text-primary' => 'Primary',
                                        'text-secondary' => 'Secondary',
                                        'text-accent' => 'Accent',
                                        'text-info' => 'Info',
                                        'text-success' => 'Success',
                                        'text-warning' => 'Warning',
                                        'text-error' => 'Error',
                                        'text-white' => 'White',
                                    ]),
                                RichEditor::make('subheading')->label(__('tallcms::fields.subheading'))
                                    ->maxLength(500)
                                    ->placeholder(__('tallcms::ui.t_enter_hero_subheading_or_description'))
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'textColor',
                                    ])
                                    ->textColors([
                                        'text-primary' => 'Primary',
                                        'text-secondary' => 'Secondary',
                                        'text-accent' => 'Accent',
                                        'text-info' => 'Info',
                                        'text-success' => 'Success',
                                        'text-warning' => 'Warning',
                                        'text-error' => 'Error',
                                        'text-white' => 'White',
                                    ]),

                                Section::make(__('tallcms::fields.primary_button'))
                                    ->schema([
                                        TextInput::make('button_text')->label(__('tallcms::fields.button_text'))
                                            ->maxLength(100)
                                            ->placeholder(__('tallcms::ui.t_call_to_action_button_text'))
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('button_link_type')
                                            ->label(__('tallcms::fields.link_type'))
                                            ->options([
                                                'page' => 'Page',
                                                'external' => 'External URL',
                                                'custom' => 'Custom URL',
                                            ])
                                            ->default('page')
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('button_page_id')
                                            ->label(__('tallcms::fields.select_page'))
                                            ->options(fn ($livewire) => \TallCms\Cms\Filament\Forms\OwnerSitePicker::publishedPages($livewire))
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => $get('button_link_type') === 'page')
                                            ->columnSpanFull(),

                                        TextInput::make('button_url')
                                            ->label(__('tallcms::fields.url'))
                                            ->placeholder('https://example.com or /contact')
                                            ->visible(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom']))
                                            ->columnSpanFull(),

                                        TextInput::make('button_microcopy')
                                            ->label(__('tallcms::fields.microcopy'))
                                            ->maxLength(50)
                                            ->placeholder(__('tallcms::ui.t_e_g_no_terminal_required'))
                                            ->helperText(__('tallcms::ui.t_small_supporting_text_below_button_to_reduce_hesitation'))
                                            ->visible(fn (Get $get): bool => filled($get('button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact(),

                                Section::make(__('tallcms::ui.t_secondary_button_optional'))
                                    ->schema([
                                        TextInput::make('secondary_button_text')
                                            ->label(__('tallcms::fields.button_text'))
                                            ->maxLength(100)
                                            ->placeholder(__('tallcms::ui.t_learn_more'))
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('secondary_button_link_type')
                                            ->label(__('tallcms::fields.link_type'))
                                            ->options([
                                                'page' => 'Page',
                                                'external' => 'External URL',
                                                'custom' => 'Custom URL',
                                            ])
                                            ->default('page')
                                            ->live()
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpan(1),

                                        Select::make('secondary_button_page_id')
                                            ->label(__('tallcms::fields.select_page'))
                                            ->options(fn ($livewire) => \TallCms\Cms\Filament\Forms\OwnerSitePicker::publishedPages($livewire))
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => $get('secondary_button_link_type') === 'page' && filled($get('secondary_button_text')))
                                            ->columnSpanFull(),

                                        TextInput::make('secondary_button_url')
                                            ->label(__('tallcms::fields.url'))
                                            ->placeholder('https://example.com')
                                            ->visible(fn (Get $get): bool => in_array($get('secondary_button_link_type'), ['external', 'custom']) && filled($get('secondary_button_text')))
                                            ->columnSpanFull(),

                                        TextInput::make('secondary_button_microcopy')
                                            ->label(__('tallcms::fields.microcopy'))
                                            ->maxLength(50)
                                            ->placeholder(__('tallcms::ui.t_e_g_open_source_on_github'))
                                            ->helperText(__('tallcms::ui.t_small_supporting_text_below_button'))
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact()
                                    ->collapsible(),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')
                                    ->label(__('tallcms::fields.layout_variant'))
                                    ->options(static::getLayoutOptions())
                                    ->default('centered')
                                    ->live()
                                    ->helperText(__('tallcms::ui.t_choose_how_the_hero_content_is_arranged')),

                                Select::make('height')
                                    ->label(__('tallcms::fields.section_height'))
                                    ->options([
                                        'min-h-[50vh]' => 'Small (50vh)',
                                        'min-h-[70vh]' => 'Medium (70vh)',
                                        'min-h-[90vh]' => 'Large (90vh)',
                                        'min-h-screen' => 'Full screen',
                                    ])
                                    ->default('min-h-[70vh]'),

                                Select::make('text_alignment')
                                    ->label(__('tallcms::fields.text_alignment'))
                                    ->options(static::getTextAlignmentOptions())
                                    ->default('text-center'),

                                Section::make(__('tallcms::fields.figure_image'))
                                    ->description(__('tallcms::ui.t_image_displayed_alongside_content'))
                                    ->schema([
                                        FileUpload::make('figure_image')->label(__('tallcms::fields.figure_image'))
                                            ->image()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(5120)
                                            ->directory('cms/hero-blocks')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->nullable()
                                            ->helperText(__('tallcms::ui.t_recommended_800_600px_max_5mb')),

                                        TextInput::make('figure_alt')
                                            ->label(__('tallcms::fields.alt_text'))
                                            ->maxLength(255)
                                            ->placeholder(__('tallcms::ui.t_describe_the_image_for_accessibility'))
                                            ->required(fn (Get $get) => filled($get('figure_image'))),

                                        Toggle::make('figure_rounded')
                                            ->label(__('tallcms::fields.rounded_corners'))
                                            ->default(true),

                                        Toggle::make('figure_shadow')
                                            ->label(__('tallcms::fields.drop_shadow'))
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (Get $get): bool => in_array($get('layout'), ['figure-left', 'figure-right'])),

                                Section::make(__('tallcms::ui.t_form_settings'))
                                    ->description(__('tallcms::ui.t_configure_the_lead_capture_form'))
                                    ->schema([
                                        TextInput::make('form_title')
                                            ->label(__('tallcms::fields.form_card_title'))
                                            ->maxLength(100)
                                            ->placeholder(__('tallcms::ui.t_e_g_get_started_today')),

                                        Repeater::make('form_fields')
                                            ->label(__('tallcms::fields.form_fields'))
                                            ->schema([
                                                Select::make('type')
                                                    ->label(__('tallcms::fields.field_type'))
                                                    ->options([
                                                        'text' => 'Text',
                                                        'email' => 'Email',
                                                        'tel' => 'Phone',
                                                        'textarea' => 'Text Area',
                                                        'select' => 'Dropdown',
                                                    ])
                                                    ->required()
                                                    ->live(),

                                                TextInput::make('name')
                                                    ->label(__('tallcms::fields.field_name'))
                                                    ->required()
                                                    ->alphaDash()
                                                    ->maxLength(50)
                                                    ->distinct()
                                                    ->helperText(__('tallcms::ui.t_unique_identifier_letters_numbers_underscores_or_dashes')),

                                                TextInput::make('label')
                                                    ->label(__('tallcms::fields.display_label'))
                                                    ->required()
                                                    ->maxLength(255),

                                                Toggle::make('required')
                                                    ->label(__('tallcms::fields.required'))
                                                    ->default(false)
                                                    ->inline(false),

                                                TagsInput::make('options')
                                                    ->label(__('tallcms::fields.dropdown_options'))
                                                    ->visible(fn (Get $get): bool => $get('type') === 'select')
                                                    ->helperText(__('tallcms::ui.t_press_enter_after_each_option'))
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->default(ContactFormBlock::getDefaultFields())
                                            ->minItems(1)
                                            ->maxItems(10)
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? 'Field').' ('.($state['type'] ?? 'text').')')
                                            ->addActionLabel('Add Field'),

                                        TextInput::make('form_submit_text')
                                            ->label(__('tallcms::fields.submit_button_text'))
                                            ->default('Get Started')
                                            ->maxLength(50),

                                        Textarea::make('form_success_message')
                                            ->label(__('tallcms::fields.success_message'))
                                            ->default("Thanks! We'll be in touch.")
                                            ->maxLength(500),

                                        Select::make('form_redirect_page_id')
                                            ->label(__('tallcms::fields.redirect_after_submission'))
                                            ->options(fn ($livewire) => \TallCms\Cms\Filament\Forms\OwnerSitePicker::publishedPages($livewire))
                                            ->searchable()
                                            ->placeholder(__('tallcms::ui.t_stay_on_page_show_success_message'))
                                            ->helperText(__('tallcms::ui.t_optionally_redirect_to_a_page_after_successful_submission')),

                                        Select::make('form_button_style')
                                            ->label(__('tallcms::fields.submit_button_style'))
                                            ->options(static::getButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('form_card_style')
                                            ->label(__('tallcms::fields.card_style'))
                                            ->options(static::getFormCardStyleOptions())
                                            ->default('bg-base-100 shadow-2xl'),
                                    ])
                                    ->visible(fn (Get $get): bool => $get('layout') === 'with-form'),
                            ]),

                        Tab::make(__('tallcms::fields.background'))
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('background_image')->label(__('tallcms::fields.background_image'))
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->directory('cms/hero-blocks')
                                    ->disk(\cms_media_disk())
                                    ->visibility(\cms_media_visibility())
                                    ->nullable()
                                    ->helperText(__('tallcms::ui.t_recommended_2560_1440px_16_9_keep_focal_point_centered_max_5mb'))
                                    ->live(),

                                Select::make('background_color')
                                    ->label(__('tallcms::fields.background_color'))
                                    ->options(static::getHeroBackgroundOptions())
                                    ->default('bg-gradient-to-br from-primary to-secondary')
                                    ->visible(fn (Get $get): bool => empty($get('background_image'))),

                                Toggle::make('parallax_effect')
                                    ->label(__('tallcms::fields.enable_parallax_effect'))
                                    ->default(true)
                                    ->helperText(__('tallcms::ui.t_background_moves_slower_than_content_when_scrolling')),

                                Slider::make('overlay_opacity')
                                    ->label(__('tallcms::fields.overlay_darkness'))
                                    ->range(minValue: 0, maxValue: 100)
                                    ->step(5)
                                    ->tooltips(true)
                                    ->pips(PipsMode::Positions)
                                    ->pipsValues([0, 25, 50, 75, 100])
                                    ->fillTrack()
                                    ->helperText(__('tallcms::ui.t_controls_dark_overlay_on_background_0_none_100_full_dark')),
                            ]),

                        Tab::make(__('tallcms::ui.t_styling'))
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make(__('tallcms::ui.t_button_styles'))
                                    ->description(__('tallcms::ui.t_choose_button_styles_for_the_hero_section'))
                                    ->schema([
                                        Select::make('button_variant')
                                            ->label(__('tallcms::fields.primary_button'))
                                            ->options(static::getHeroButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('secondary_button_variant')
                                            ->label(__('tallcms::fields.secondary_button'))
                                            ->options(static::getHeroSecondaryButtonVariantOptions())
                                            ->default('btn-ghost text-white hover:bg-white/20'),

                                        Select::make('button_size')
                                            ->label(__('tallcms::fields.button_size'))
                                            ->options(static::getButtonSizeOptions())
                                            ->default('btn-lg'),
                                    ])
                                    ->columns(3),
                            ]),

                        static::getAnimationTab(supportsStagger: false),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config, true);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config, false);
    }

    protected static function renderBlock(array $config, bool $isPreview = false): string
    {
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');
        $animConfig = static::getAnimationConfig($config);

        // Build button classes
        $buttonVariant = $config['button_variant'] ?? 'btn-primary';
        $buttonSize = $config['button_size'] ?? 'btn-lg';
        $buttonClasses = "btn {$buttonVariant} {$buttonSize}";

        $secondaryVariant = $config['secondary_button_variant'] ?? 'btn-ghost text-white hover:bg-white/20';
        $secondaryClasses = "btn {$secondaryVariant} {$buttonSize}";

        // Normalize form fields - use ContactFormBlock defaults if empty
        // Use array_values to ensure numeric keys (Repeater may use associative keys)
        // Also check count() because empty array [] is "set" but has no items
        $formFields = isset($config['form_fields']) && is_array($config['form_fields']) && count($config['form_fields']) > 0
            ? array_values($config['form_fields'])
            : ContactFormBlock::getDefaultFields();

        return view('tallcms::cms.blocks.hero', [
            'id' => static::getId(),
            'isPreview' => $isPreview,
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $buttonUrl,
            'button_classes' => $buttonClasses,
            'button_variant' => $buttonVariant,
            'button_size' => $buttonSize,
            'button_microcopy' => $config['button_microcopy'] ?? null,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'secondary_button_classes' => $secondaryClasses,
            'secondary_button_variant' => $secondaryVariant,
            'secondary_button_microcopy' => $config['secondary_button_microcopy'] ?? null,
            'background_image' => $config['background_image'] ?? null,
            'background_color' => $config['background_color'] ?? 'bg-gradient-to-br from-primary to-secondary',
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => ($config['overlay_opacity'] ?? 40) / 100,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'height' => $config['height'] ?? 'min-h-[70vh]',
            'layout' => $config['layout'] ?? 'centered',
            'figure_image' => $config['figure_image'] ?? null,
            'figure_alt' => $config['figure_alt'] ?? '',
            'figure_rounded' => $config['figure_rounded'] ?? true,
            'figure_shadow' => $config['figure_shadow'] ?? true,
            'form_title' => $config['form_title'] ?? null,
            'form_fields' => $formFields,
            'form_submit_text' => $config['form_submit_text'] ?? 'Get Started',
            'form_success_message' => $config['form_success_message'] ?? "Thanks! We'll be in touch.",
            'form_button_style' => $config['form_button_style'] ?? 'btn-primary',
            'form_redirect_page_id' => $config['form_redirect_page_id'] ?? null,
            'form_card_style' => $config['form_card_style'] ?? 'bg-base-100 shadow-2xl',
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }
}
