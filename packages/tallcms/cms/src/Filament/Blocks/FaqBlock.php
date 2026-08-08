<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
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

class FaqBlock extends RichContentCustomBlock
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
        return 'heroicon-o-question-mark-circle';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.faq');
    }

    public static function getKeywords(): array
    {
        return ['faq', 'questions', 'answers', 'help'];
    }

    public static function getSortPriority(): int
    {
        return 20;
    }

    public static function getId(): string
    {
        return 'faq';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.faq');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_create_a_frequently_asked_questions_section_with_accordion_functiona'))
            ->modalHeading(__('tallcms::ui.t_configure_faq_block'))
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('FAQ Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label(__('tallcms::fields.section_heading'))
                                    ->placeholder(__('tallcms::ui.t_frequently_asked_questions'))
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label(__('tallcms::fields.section_subheading'))
                                    ->placeholder(__('tallcms::ui.t_find_answers_to_common_questions'))
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('items')
                                    ->label(__('tallcms::fields.questions_answers'))
                                    ->schema([
                                        TextInput::make('question')
                                            ->label(__('tallcms::fields.question'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_what_is_your_return_policy'))
                                            ->maxLength(500),

                                        Textarea::make('answer')
                                            ->label(__('tallcms::fields.answer'))
                                            ->required()
                                            ->placeholder(__('tallcms::ui.t_our_return_policy_allows_returns_within_30_days'))
                                            ->rows(4),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(20)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New Question')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make(__('tallcms::fields.settings'))
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make(__('tallcms::ui.t_display_options'))
                                    ->schema([
                                        Select::make('style')
                                            ->label(__('tallcms::fields.display_style'))
                                            ->options([
                                                'accordion' => 'Accordion (Collapsible)',
                                                'list' => 'List (Always Visible)',
                                            ])
                                            ->default('accordion'),

                                        Toggle::make('first_open')
                                            ->label(__('tallcms::fields.first_item_open_by_default'))
                                            ->helperText(__('tallcms::ui.t_only_applies_to_accordion_style'))
                                            ->default(false),

                                        Toggle::make('allow_multiple')
                                            ->label(__('tallcms::fields.allow_multiple_items_open'))
                                            ->helperText(__('tallcms::ui.t_only_applies_to_accordion_style'))
                                            ->default(false),

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

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.section_padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::fields.seo'))
                                    ->schema([
                                        Toggle::make('show_schema')
                                            ->label(__('tallcms::fields.add_faq_schema_markup'))
                                            ->helperText(__('tallcms::ui.t_adds_schema_org_faqpage_structured_data_for_seo'))
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
        $items = $config['items'] ?? self::getSampleItems();

        return static::renderBlock(array_merge($config, [
            'items' => $items,
            'heading' => $config['heading'] ?? 'Frequently Asked Questions',
            'subheading' => $config['subheading'] ?? 'Find answers to common questions about our products and services',
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

        return view('tallcms::cms.blocks.faq', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'style' => $config['style'] ?? 'accordion',
            'first_open' => $config['first_open'] ?? false,
            'allow_multiple' => $config['allow_multiple'] ?? false,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'show_schema' => $config['show_schema'] ?? true,
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
                'question' => 'What is your return policy?',
                'answer' => 'We offer a 30-day money-back guarantee on all purchases. If you\'re not satisfied, simply contact our support team for a full refund.',
            ],
            [
                'question' => 'How long does shipping take?',
                'answer' => 'Standard shipping takes 5-7 business days. Express shipping is available for 2-3 business day delivery.',
            ],
            [
                'question' => 'Do you offer customer support?',
                'answer' => 'Yes! Our support team is available 24/7 via email and live chat. We typically respond within 2 hours.',
            ],
        ];
    }
}
