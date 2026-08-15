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

class TestimonialsBlock extends RichContentCustomBlock
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
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.testimonials');
    }

    public static function getKeywords(): array
    {
        return ['reviews', 'quotes', 'testimonials', 'customers'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

    protected static function getTestimonialCardStyleOptions(): array
    {
        return [
            'card bg-base-200 shadow-lg' => 'Cards with Shadow',
            'card bg-base-100 border border-base-300' => 'Bordered Cards',
            'card bg-base-100/50' => 'Minimal',
            'card bg-base-200 shadow-lg quote-marks' => 'Large Quote Marks',
        ];
    }

    public static function getId(): string
    {
        return 'testimonials';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.testimonials');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_display_customer_testimonials_and_social_proof'))
            ->modalHeading(__('tallcms::ui.t_configure_testimonials_block'))
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Testimonials Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_what_our_customers_say'))
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label(__('tallcms::fields.section_subheading'))
                                    ->placeholder(__('tallcms::ui.t_hear_from_people_who_love_our_product'))
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('testimonials')
                                    ->label(__('tallcms::fields.testimonials'))
                                    ->schema([
                                        Textarea::make('quote')
                                            ->label(__('tallcms::fields.testimonial_quote'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_this_product_has_completely_transformed_how_we_work'))
                                            ->rows(3),

                                        TextInput::make('author_name')
                                            ->label(__('tallcms::fields.author_name'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_jane_smith'))
                                            ->maxLength(100),

                                        TextInput::make('author_title')
                                            ->label(__('tallcms::fields.title_company'))
                                            ->placeholder(__('tallcms::ui.t_ceo_at_acme_inc'))
                                            ->maxLength(150),

                                        FileUpload::make('author_image')
                                            ->label(__('tallcms::fields.author_photo'))
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('testimonials')
                                            ->visibility(\cms_media_visibility())
                                            ->imageEditor()
                                            ->circleCropper(),

                                        FileUpload::make('company_logo')
                                            ->label(__('tallcms::fields.company_logo_optional'))
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('testimonials/logos')
                                            ->visibility(\cms_media_visibility()),

                                        Select::make('rating')
                                            ->label(__('tallcms::fields.star_rating'))
                                            ->options([
                                                '' => 'No Rating',
                                                '5' => '5 Stars',
                                                '4' => '4 Stars',
                                                '3' => '3 Stars',
                                                '2' => '2 Stars',
                                                '1' => '1 Star',
                                            ])
                                            ->default('5'),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['author_name'] ?? 'New Testimonial')
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
                                                'single' => 'Single (Large)',
                                            ])
                                            ->default('grid'),

                                        Select::make('columns')
                                            ->label(__('tallcms::fields.columns_grid_layout'))
                                            ->options([
                                                '1' => '1 Column',
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label(__('tallcms::fields.card_style'))
                                            ->options(static::getTestimonialCardStyleOptions())
                                            ->default('card bg-base-200 shadow-lg'),

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
                                            ->helperText(__('tallcms::ui.t_color_used_for_quote_marks_and_avatar_accents')),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::ui.t_content_display'))
                                    ->schema([
                                        Toggle::make('show_rating')
                                            ->label(__('tallcms::fields.show_star_ratings'))
                                            ->default(true),

                                        Toggle::make('show_company_logo')
                                            ->label(__('tallcms::fields.show_company_logos'))
                                            ->default(false),

                                        Toggle::make('first_section')
                                            ->label(__('tallcms::fields.first_section_remove_top_padding'))
                                            ->helperText(__('tallcms::ui.t_overrides_padding_setting_above'))
                                            ->default(false),
                                    ])
                                    ->columns(3),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $testimonials = $config['testimonials'] ?? self::getSampleTestimonials();

        return static::renderBlock(array_merge($config, [
            'testimonials' => $testimonials,
            'heading' => $config['heading'] ?? 'What Our Customers Say',
            'subheading' => $config['subheading'] ?? 'Trusted by thousands of happy customers worldwide',
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

        return view('tallcms::cms.blocks.testimonials', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'testimonials' => $config['testimonials'] ?? [],
            'layout' => $config['layout'] ?? 'grid',
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'card bg-base-200 shadow-lg',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'accent_color' => $config['accent_color'] ?? 'primary',
            'padding' => $config['padding'] ?? 'py-16',
            'show_rating' => $config['show_rating'] ?? true,
            'show_company_logo' => $config['show_company_logo'] ?? false,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleTestimonials(): array
    {
        return [
            [
                'quote' => 'This product has completely transformed how we work. The team is more productive than ever.',
                'author_name' => 'Sarah Johnson',
                'author_title' => 'CEO at TechCorp',
                'rating' => '5',
            ],
            [
                'quote' => 'Incredible support and an intuitive interface. We saw results from day one.',
                'author_name' => 'Michael Chen',
                'author_title' => 'Product Manager at StartupXYZ',
                'rating' => '5',
            ],
            [
                'quote' => 'The best investment we\'ve made this year. Highly recommend to any growing business.',
                'author_name' => 'Emily Rodriguez',
                'author_title' => 'Founder of GrowthLabs',
                'rating' => '5',
            ],
        ];
    }
}
