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
use Filament\Schemas\Components\Utilities\Get;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class PricingBlock extends RichContentCustomBlock
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
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.pricing');
    }

    public static function getKeywords(): array
    {
        return ['plans', 'pricing', 'tiers', 'subscription'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'pricing';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.pricing');
    }

    protected static function getPricingCardStyleOptions(): array
    {
        return [
            'shadow' => 'Shadow',
            'bordered' => 'Bordered',
            'elevated' => 'Elevated',
        ];
    }

    protected static function getPlanButtonStyleOptions(): array
    {
        return [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
            'btn-outline btn-primary' => 'Outline Primary',
            'btn-accent' => 'Accent',
            'btn-neutral' => 'Neutral',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_create_a_comprehensive_pricing_table_with_plans_features_and_call_to'))
            ->modalHeading(__('tallcms::ui.t_configure_pricing_block'))
            ->modalWidth('7xl')
            ->schema([
                Tabs::make('Pricing Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::ui.t_header'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('section_title')
                                    ->label(__('tallcms::fields.section_title'))
                                    ->placeholder(__('tallcms::ui.t_choose_your_plan'))
                                    ->maxLength(255),

                                Textarea::make('section_subtitle')
                                    ->label(__('tallcms::fields.section_subtitle'))
                                    ->placeholder(__('tallcms::ui.t_select_the_perfect_plan_for_your_needs'))
                                    ->maxLength(500)
                                    ->rows(2),

                                Select::make('text_alignment')
                                    ->label(__('tallcms::fields.text_alignment'))
                                    ->options(static::getTextAlignmentOptions())
                                    ->default('text-center'),
                            ]),

                        Tab::make(__('tallcms::ui.t_plans'))
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Repeater::make('plans')
                                    ->label(__('tallcms::fields.pricing_plans'))
                                    ->schema([
                                        Section::make(__('tallcms::ui.t_plan_details'))
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('tallcms::fields.plan_name'))
                                                    ->required()
                                                    ->placeholder(__('tallcms::ui.t_professional'))
                                                    ->maxLength(100),

                                                TextInput::make('description')
                                                    ->label(__('tallcms::fields.plan_description'))
                                                    ->placeholder(__('tallcms::ui.t_perfect_for_growing_teams'))
                                                    ->maxLength(200),

                                                Toggle::make('is_popular')
                                                    ->label(__('tallcms::fields.mark_as_popular_recommended'))
                                                    ->default(false)
                                                    ->live(),

                                                TextInput::make('popular_badge_text')
                                                    ->label(__('tallcms::fields.popular_badge_text'))
                                                    ->placeholder(__('tallcms::ui.t_most_popular'))
                                                    ->maxLength(50)
                                                    ->visible(fn (Get $get): bool => $get('is_popular')),
                                            ])->columns(2),

                                        Section::make(__('tallcms::ui.t_pricing'))
                                            ->schema([
                                                TextInput::make('currency_symbol')
                                                    ->label(__('tallcms::fields.currency_symbol'))
                                                    ->default('$')
                                                    ->maxLength(5),

                                                TextInput::make('price')
                                                    ->label(__('tallcms::fields.price'))
                                                    ->required()
                                                    ->placeholder(__('tallcms::ui.t_29'))
                                                    ->numeric(),

                                                Select::make('billing_period')
                                                    ->label(__('tallcms::fields.billing_period'))
                                                    ->options([
                                                        'month' => 'per month',
                                                        'year' => 'per year',
                                                        'week' => 'per week',
                                                        'day' => 'per day',
                                                        'one-time' => 'one-time payment',
                                                        'free' => 'free',
                                                    ])
                                                    ->default('month'),

                                                TextInput::make('discount_text')
                                                    ->label(__('tallcms::fields.discount_text_optional'))
                                                    ->placeholder(__('tallcms::ui.t_save_20'))
                                                    ->maxLength(50),
                                            ])->columns(4),

                                        Section::make(__('tallcms::fields.features'))
                                            ->schema([
                                                Repeater::make('features')
                                                    ->label(__('tallcms::fields.plan_features'))
                                                    ->schema([
                                                        TextInput::make('text')
                                                            ->label(__('tallcms::fields.feature_text'))
                                                            ->required()
                                                            ->placeholder(__('tallcms::ui.t_unlimited_projects'))
                                                            ->maxLength(200),

                                                        Toggle::make('included')
                                                            ->label(__('tallcms::fields.included'))
                                                            ->default(true),

                                                        TextInput::make('tooltip')
                                                            ->label(__('tallcms::fields.tooltip_optional'))
                                                            ->placeholder(__('tallcms::ui.t_additional_information_about_this_feature'))
                                                            ->maxLength(300),
                                                    ])
                                                    ->defaultItems(3)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null),
                                            ]),

                                        Section::make(__('tallcms::ui.t_call_to_action'))
                                            ->schema([
                                                TextInput::make('button_text')
                                                    ->label(__('tallcms::fields.button_text'))
                                                    ->required()
                                                    ->placeholder(__('tallcms::ui.t_get_started'))
                                                    ->maxLength(50),

                                                TextInput::make('button_url')
                                                    ->label(__('tallcms::fields.button_url'))
                                                    ->placeholder('/signup?plan=professional')
                                                    ->maxLength(500),

                                                Select::make('button_style')
                                                    ->label(__('tallcms::fields.button_style'))
                                                    ->options(static::getPlanButtonStyleOptions())
                                                    ->default('btn-primary'),

                                                TextInput::make('trial_text')
                                                    ->label(__('tallcms::fields.trial_text_optional'))
                                                    ->placeholder(__('tallcms::ui.t_14_day_free_trial'))
                                                    ->maxLength(100),
                                            ])->columns(2),
                                    ])
                                    ->defaultItems(2)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Plan')
                                    ->addActionLabel('Add Plan')
                                    ->minItems(1)
                                    ->maxItems(6),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make(__('tallcms::ui.t_grid_layout'))
                                    ->schema([
                                        Select::make('columns')
                                            ->label(__('tallcms::fields.number_of_columns'))
                                            ->options([
                                                '1' => '1 Column',
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label(__('tallcms::fields.card_style'))
                                            ->options(static::getPricingCardStyleOptions())
                                            ->default('shadow'),

                                        Select::make('spacing')
                                            ->label(__('tallcms::fields.card_spacing'))
                                            ->options([
                                                'tight' => 'Tight (gap-4)',
                                                'normal' => 'Normal (gap-6)',
                                                'relaxed' => 'Relaxed (gap-8)',
                                            ])
                                            ->default('normal'),
                                    ])
                                    ->columns(3),

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
                                            ->helperText(__('tallcms::ui.t_color_used_for_popular_plan_highlight')),

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

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])
            ->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $plans = $config['plans'] ?? self::getSamplePlans();

        return static::renderBlock(array_merge($config, [
            'plans' => $plans,
            'section_title' => $config['section_title'] ?? 'Choose Your Plan',
            'section_subtitle' => $config['section_subtitle'] ?? 'Select the perfect plan for your needs',
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

        return view('tallcms::cms.blocks.pricing', [
            'id' => static::getId(),
            'section_title' => $config['section_title'] ?? '',
            'section_subtitle' => $config['section_subtitle'] ?? '',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'plans' => $config['plans'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'shadow',
            'spacing' => $config['spacing'] ?? 'normal',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'accent_color' => $config['accent_color'] ?? 'primary',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['section_title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSamplePlans(): array
    {
        return [
            [
                'name' => 'Basic',
                'description' => 'Perfect for individuals',
                'currency_symbol' => '$',
                'price' => '9',
                'billing_period' => 'month',
                'is_popular' => false,
                'button_text' => 'Get Started',
                'button_style' => 'btn-outline btn-primary',
                'features' => [
                    ['text' => '5 Projects', 'included' => true],
                    ['text' => '10GB Storage', 'included' => true],
                    ['text' => 'Email Support', 'included' => true],
                ],
            ],
            [
                'name' => 'Professional',
                'description' => 'Perfect for growing teams',
                'currency_symbol' => '$',
                'price' => '29',
                'billing_period' => 'month',
                'is_popular' => true,
                'popular_badge_text' => 'Most Popular',
                'button_text' => 'Get Started',
                'button_style' => 'btn-primary',
                'features' => [
                    ['text' => 'Unlimited Projects', 'included' => true],
                    ['text' => '100GB Storage', 'included' => true],
                    ['text' => 'Priority Support', 'included' => true],
                    ['text' => 'Advanced Analytics', 'included' => true],
                ],
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations',
                'currency_symbol' => '$',
                'price' => '99',
                'billing_period' => 'month',
                'is_popular' => false,
                'button_text' => 'Contact Sales',
                'button_style' => 'btn-secondary',
                'features' => [
                    ['text' => 'Everything in Pro', 'included' => true],
                    ['text' => 'Unlimited Storage', 'included' => true],
                    ['text' => '24/7 Phone Support', 'included' => true],
                    ['text' => 'Custom Integrations', 'included' => true],
                    ['text' => 'Dedicated Manager', 'included' => true],
                ],
            ],
        ];
    }
}
