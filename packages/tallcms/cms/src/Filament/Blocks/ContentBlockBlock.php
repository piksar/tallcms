<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class ContentBlockBlock extends RichContentCustomBlock
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
        return 'heroicon-o-document-text';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.content_block');
    }

    public static function getKeywords(): array
    {
        return ['article', 'text', 'prose', 'body'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

    public static function getId(): string
    {
        return 'content_block';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.content_block');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_add_a_content_section_with_title_and_rich_text_body'))
            ->schema([
                Tabs::make('Content Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('tallcms::fields.title'))
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_enter_section_title')),

                                TextInput::make('subtitle')
                                    ->label(__('tallcms::fields.subtitle'))
                                    ->maxLength(255)
                                    ->placeholder(__('tallcms::ui.t_optional_subtitle_or_subheading')),

                                RichEditor::make('body')
                                    ->label(__('tallcms::fields.content'))
                                    ->placeholder(__('tallcms::ui.t_write_your_content_here')),

                                static::getContentWidthField(),

                                Select::make('heading_level')
                                    ->label(__('tallcms::fields.heading_level'))
                                    ->options([
                                        'h2' => 'H2 (recommended for sections)',
                                        'h3' => 'H3 (for subsections)',
                                        'h4' => 'H4 (for smaller headings)',
                                    ])
                                    ->default('h2')
                                    ->helperText(__('tallcms::ui.t_choose_appropriate_heading_level_for_page_structure')),
                            ]),

                        Tab::make(__('tallcms::ui.t_appearance'))
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
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

                        static::getAnimationTab(supportsStagger: false),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock(array_merge($config, [
            'title' => $config['title'] ?? 'Content Block Title',
            'subtitle' => $config['subtitle'] ?? 'Optional subtitle for better content hierarchy',
            'body' => $config['body'] ?? '<p>Your content will appear here. You can use <strong>formatting</strong>, <em>emphasis</em>, and other rich text features.</p>',
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

        return view('tallcms::cms.blocks.content-block', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'subtitle' => $config['subtitle'] ?? '',
            'body' => $config['body'] ?? '',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'heading_level' => $config['heading_level'] ?? 'h2',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }
}
