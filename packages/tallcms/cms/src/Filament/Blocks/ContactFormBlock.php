<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
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
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;

class ContactFormBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'narrow';
    }

    public static function getCategory(): string
    {
        return 'forms';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.contact_form');
    }

    public static function getKeywords(): array
    {
        return ['contact', 'email', 'form', 'message'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

    public static function getId(): string
    {
        return 'contact_form';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.contact_form');
    }

    public static function getDefaultFields(): array
    {
        return [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'options' => []],
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_configure_your_contact_form_fields_and_settings'))
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Contact Form Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                Section::make(__('tallcms::ui.t_form_header'))
                                    ->description(__('tallcms::ui.t_optional_title_and_description_shown_above_the_form'))
                                    ->schema([
                                        TextInput::make('title')->label(__('tallcms::fields.title'))
                                            ->maxLength(255)
                                            ->placeholder(__('tallcms::ui.t_e_g_get_in_touch')),

                                        Textarea::make('description')->label(__('tallcms::fields.description'))
                                            ->maxLength(500)
                                            ->placeholder(__('tallcms::ui.t_e_g_fill_out_the_form_below_and_we_ll_get_back_to_you_shortly')),
                                    ])
                                    ->collapsible(),

                                Section::make(__('tallcms::fields.form_fields'))
                                    ->description(__('tallcms::ui.t_configure_which_fields_appear_in_your_form'))
                                    ->schema([
                                        Repeater::make('fields')
                                            ->label('')
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
                                                    ->helperText(__('tallcms::ui.t_press_enter_after_each_option_max_50_options_100_chars_each'))
                                                    ->nestedRecursiveRules([
                                                        'string',
                                                        'max:100',
                                                    ])
                                                    ->rules(['array', 'max:50'])
                                                    ->dehydrateStateUsing(fn (?array $state): ?array => $state
                                                        ? array_values(array_filter(array_map('trim', $state), fn ($v) => $v !== ''))
                                                        : null
                                                    )
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->default(self::getDefaultFields())
                                            ->minItems(1)
                                            ->maxItems(20)
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? 'Field').' ('.($state['type'] ?? 'text').')')
                                            ->addActionLabel('Add Field'),
                                    ]),

                                Section::make(__('tallcms::ui.t_form_settings'))
                                    ->description(__('tallcms::ui.t_customize_button_text_and_success_message'))
                                    ->schema([
                                        TextInput::make('submit_button_text')
                                            ->label(__('tallcms::fields.submit_button_text'))
                                            ->default('Send Message')
                                            ->maxLength(50),

                                        Textarea::make('success_message')
                                            ->label(__('tallcms::fields.success_message'))
                                            ->default('Thank you for your message! We\'ll be in touch soon.')
                                            ->maxLength(500)
                                            ->helperText(__('tallcms::ui.t_shown_after_successful_form_submission')),

                                        Textarea::make('auto_reply_message')
                                            ->label(__('tallcms::fields.auto_reply_message'))
                                            ->rows(4)
                                            ->maxLength(2000)
                                            ->placeholder(__('tallcms::ui.t_thank_you_for_contacting_us_we_have_received_your_submission_and_wil'))
                                            ->helperText(__('tallcms::ui.t_custom_body_text_for_the_auto_reply_email_sent_to_submitters_leave_b')),

                                        Select::make('redirect_page_id')
                                            ->label(__('tallcms::fields.redirect_after_submission'))
                                            ->options(fn ($livewire) => \TallCms\Cms\Filament\Forms\OwnerSitePicker::publishedPages($livewire))
                                            ->searchable()
                                            ->placeholder(__('tallcms::ui.t_stay_on_page_show_success_message'))
                                            ->helperText(__('tallcms::ui.t_optionally_redirect_to_a_page_after_successful_submission')),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tab::make(__('tallcms::ui.t_appearance'))
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                static::getContentWidthField(),

                                Select::make('button_style')
                                    ->label(__('tallcms::fields.submit_button_style'))
                                    ->options(static::getButtonVariantOptions())
                                    ->default('btn-primary'),

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
                            ])
                            ->columns(2),
                    ]),

                static::getIdentifiersSection(),
            ])
            ->slideOver();
    }

    private static function normalizeConfig(array $config): array
    {
        $fields = isset($config['fields']) && is_array($config['fields'])
            ? array_values($config['fields'])
            : self::getDefaultFields();

        return array_merge([
            'title' => '',
            'description' => '',
            'fields' => self::getDefaultFields(),
            'submit_button_text' => 'Send Message',
            'success_message' => 'Thank you for your message! We\'ll be in touch soon.',
            'auto_reply_message' => null,
            'button_style' => 'btn-primary',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
            'redirect_page_id' => null,
        ], $config, ['fields' => $fields]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config, 'tallcms::cms.blocks.contact-form-preview');
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config, 'tallcms::cms.blocks.contact-form');
    }

    protected static function renderBlock(array $config, string $view): string
    {
        $normalizedConfig = self::normalizeConfig($config);
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view($view, [
            'config' => $normalizedConfig,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }
}
