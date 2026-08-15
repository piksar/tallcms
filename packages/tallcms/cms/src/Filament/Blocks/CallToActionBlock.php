<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\BlockLinkResolver;

class CallToActionBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cursor-arrow-ripple';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.call_to_action');
    }

    public static function getKeywords(): array
    {
        return ['cta', 'button', 'action', 'call to action'];
    }

    public static function getSortPriority(): int
    {
        return 20;
    }

    public static function getId(): string
    {
        return 'call_to_action';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.call_to_action');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_create_a_compelling_call_to_action_section'))
            ->schema([
                Tabs::make('CTA Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')->label(__('tallcms::fields.title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_enter_cta_title')),

                                Textarea::make('description')->label(__('tallcms::fields.description'))
                                    ->maxLength(500)
                                    ->placeholder(__('tallcms::ui.t_enter_cta_description')),

                                Section::make(__('tallcms::fields.primary_button'))
                                    ->schema([
                                        TextInput::make('button_text')->label(__('tallcms::fields.button_text'))
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder(__('tallcms::ui.t_get_started'))
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
                                            ->placeholder(__('tallcms::ui.t_e_g_no_credit_card_required'))
                                            ->helperText(__('tallcms::ui.t_short_reassurance_text_below_the_button'))
                                            ->maxLength(50)
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
                                            ->placeholder(__('tallcms::ui.t_e_g_free_to_try'))
                                            ->helperText(__('tallcms::ui.t_short_reassurance_text_below_the_button'))
                                            ->maxLength(50)
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact()
                                    ->collapsible(),
                            ]),

                        Tab::make(__('tallcms::ui.t_styling'))
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make(__('tallcms::ui.t_button_styles'))
                                    ->description(__('tallcms::ui.t_choose_button_styles_from_daisyui_presets'))
                                    ->schema([
                                        Select::make('button_variant')
                                            ->label(__('tallcms::fields.primary_button'))
                                            ->options(static::getButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('button_size')
                                            ->label(__('tallcms::fields.button_size'))
                                            ->options(static::getButtonSizeOptions())
                                            ->default('btn-lg'),

                                        Select::make('secondary_button_variant')
                                            ->label(__('tallcms::fields.secondary_button'))
                                            ->options(static::getSecondaryButtonVariantOptions())
                                            ->default('btn-ghost'),
                                    ])
                                    ->columns(3),

                                Section::make(__('tallcms::ui.t_section_appearance'))
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label(__('tallcms::fields.background'))
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-200'),

                                        Select::make('text_alignment')
                                            ->label(__('tallcms::fields.text_alignment'))
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),

                                        Select::make('padding')
                                            ->label(__('tallcms::fields.padding'))
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
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
        return static::renderBlock($config);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.call-to-action', [
            'id' => static::getId(),
            'title' => $config['title'] ?? 'Call to Action Title',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? 'Get Started',
            'button_url' => $buttonUrl,
            'button_classes' => static::buildButtonClasses($config),
            'button_microcopy' => $config['button_microcopy'] ?? null,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'secondary_button_classes' => static::buildButtonClasses($config, 'secondary_button'),
            'secondary_button_microcopy' => $config['secondary_button_microcopy'] ?? null,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-200',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'padding' => $config['padding'] ?? 'py-16',
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }
}
