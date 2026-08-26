<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class SplitBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    /**
     * @return list<string>
     */
    public static function twoCellOnlyPresets(): array
    {
        return ['50/50', '33/67', '67/33', '25/75', '75/25'];
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-view-columns';
    }

    public static function getDescription(): string
    {
        return __('tallcms::blocks.descriptions.split');
    }

    public static function getKeywords(): array
    {
        return ['split', 'columns', 'two column', 'media', 'text', 'layout', 'image'];
    }

    public static function getSortPriority(): int
    {
        return 12;
    }

    public static function getId(): string
    {
        return 'split';
    }

    public static function getLabel(): string
    {
        return __('tallcms::blocks.labels.split');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_add_image_and_rich_text_side_by_side'))
            ->modalHeading(__('tallcms::ui.t_configure_split_block'))
            ->modalWidth('6xl')
            ->fillForm(fn (array $arguments): array => static::unpackCells($arguments['config'] ?? []))
            ->mutateDataUsing(fn (array $data): array => static::packCells($data))
            ->schema([
                Tabs::make('Split Configuration')
                    ->tabs([
                        Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Select::make('cell_count')
                                    ->label(__('tallcms::fields.cells'))
                                    ->options([
                                        2 => '2',
                                        3 => '3',
                                        4 => '4',
                                    ])
                                    ->default(2)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                        $set('preset', static::normalizePreset(
                                            (string) ($get('preset') ?? '50/50'),
                                            (int) $state,
                                        ));
                                    }),

                                ...static::cellSectionSchemas(),
                            ]),

                        Tab::make(__('tallcms::fields.layout'))
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('preset')
                                    ->label(__('tallcms::fields.split_preset'))
                                    ->options(function (Get $get): array {
                                        return static::presetOptions((int) ($get('cell_count') ?? 2));
                                    })
                                    ->default('50/50')
                                    ->live()
                                    ->helperText(__('tallcms::ui.t_named_column_ratios_two_cell_ratios_reset_when_you_add_a_third_cell')),

                                Select::make('stack_order')
                                    ->label(__('tallcms::fields.stack_order'))
                                    ->options([
                                        'as-is' => __('tallcms::blocks.options.split_stack_as_is'),
                                        'reverse' => __('tallcms::blocks.options.split_stack_reverse'),
                                    ])
                                    ->default('as-is')
                                    ->helperText(__('tallcms::ui.t_mobile_stack_order_below_large_screens')),

                                Select::make('vertical_align')
                                    ->label(__('tallcms::fields.vertical_align'))
                                    ->options([
                                        'start' => __('tallcms::blocks.options.split_align_start'),
                                        'center' => __('tallcms::blocks.options.split_align_center'),
                                        'end' => __('tallcms::blocks.options.split_align_end'),
                                    ])
                                    ->default('start'),
                            ]),

                        Tab::make(__('tallcms::ui.t_appearance'))
                            ->icon('heroicon-m-paint-brush')
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
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $cells = static::cellsFromConfig($config);

        if (count($cells) < 2) {
            $config['cells'] = static::sampleCells();
        } else {
            $config['cells'] = static::fillPreviewCells($cells);
        }

        return static::renderBlock($config, isPreview: true);
    }

    /**
     * @return list<Section>
     */
    protected static function cellSectionSchemas(): array
    {
        $sections = [];

        for ($index = 1; $index <= 4; $index++) {
            $typeField = "cell_{$index}_type";
            $imageField = "cell_{$index}_image";
            $altField = "cell_{$index}_alt";
            $shapeField = "cell_{$index}_image_shape";
            $sizeField = "cell_{$index}_image_size";
            $alignField = "cell_{$index}_image_align";
            $verticalField = "cell_{$index}_image_vertical";
            $bodyField = "cell_{$index}_body";

            $sections[] = Section::make(__('tallcms::fields.cells').' '.$index)
                ->visible(fn (Get $get): bool => (int) ($get('cell_count') ?? 2) >= $index)
                ->schema([
                    Select::make($typeField)
                        ->label(__('tallcms::fields.cell_type'))
                        ->options([
                            'image' => __('tallcms::fields.image'),
                            'rich_text' => __('tallcms::fields.rich_text'),
                        ])
                        ->default($index === 1 ? 'image' : 'rich_text')
                        ->required()
                        ->live(),

                    FileUpload::make($imageField)
                        ->label(__('tallcms::fields.image'))
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk(\cms_media_disk())
                        ->directory('cms/split-blocks')
                        ->visibility(\cms_media_visibility())
                        ->nullable()
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    TextInput::make($altField)
                        ->label(__('tallcms::fields.alt_text'))
                        ->maxLength(255)
                        ->placeholder(__('tallcms::ui.t_describe_the_image_for_accessibility'))
                        ->required(fn (Get $get): bool => $get($typeField) === 'image' && filled($get($imageField)))
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    Select::make($shapeField)
                        ->label(__('tallcms::fields.image_shape'))
                        ->options([
                            'none' => __('tallcms::blocks.options.split_shape_none'),
                            'rounded' => __('tallcms::blocks.options.split_shape_rounded'),
                            'circle' => __('tallcms::blocks.options.split_shape_circle'),
                        ])
                        ->default('none')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $state) use ($sizeField, $alignField): void {
                            if ($state !== 'circle') {
                                return;
                            }

                            $size = $get($sizeField);

                            if ($size === null || $size === '' || $size === 'fill') {
                                $set($sizeField, 'm');
                            }

                            $align = $get($alignField);

                            if ($align === null || $align === '' || $align === 'left') {
                                $set($alignField, 'center');
                            }
                        })
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    Select::make($sizeField)
                        ->label(__('tallcms::fields.image_size'))
                        ->options([
                            's' => __('tallcms::blocks.options.split_size_s'),
                            'm' => __('tallcms::blocks.options.split_size_m'),
                            'l' => __('tallcms::blocks.options.split_size_l'),
                            'fill' => __('tallcms::blocks.options.split_size_fill'),
                        ])
                        ->default('fill')
                        ->helperText(__('tallcms::ui.t_split_image_size_fraction_on_small_screens_max_width_from_large'))
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    Select::make($alignField)
                        ->label(__('tallcms::fields.image_align'))
                        ->options([
                            'left' => __('tallcms::blocks.options.split_align_left'),
                            'center' => __('tallcms::blocks.options.split_align_center'),
                            'right' => __('tallcms::blocks.options.split_align_right'),
                        ])
                        ->default('left')
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    Select::make($verticalField)
                        ->label(__('tallcms::fields.image_vertical_align'))
                        ->options([
                            'inherit' => __('tallcms::blocks.options.split_align_inherit'),
                            'start' => __('tallcms::blocks.options.split_align_start'),
                            'center' => __('tallcms::blocks.options.split_align_center'),
                            'end' => __('tallcms::blocks.options.split_align_end'),
                        ])
                        ->default('inherit')
                        ->helperText(__('tallcms::ui.t_overrides_the_row_vertical_alignment_for_this_image'))
                        ->visible(fn (Get $get): bool => $get($typeField) === 'image'),

                    RichEditor::make($bodyField)
                        ->label(__('tallcms::fields.rich_text'))
                        ->placeholder(__('tallcms::ui.t_write_your_content_here'))
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'link'],
                            ['h2', 'h3'],
                            ['blockquote', 'bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ])
                        ->visible(fn (Get $get): bool => $get($typeField) === 'rich_text'),
                ])
                ->columns(2)
                ->compact();
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function unpackCells(array $config): array
    {
        $cells = static::cellsFromConfig($config);

        if ($cells === []) {
            $cells = [
                ['type' => 'image', 'image_shape' => 'none'],
                ['type' => 'rich_text'],
            ];
        }

        $count = max(2, min(4, count($cells)));
        $flat = $config;
        unset($flat['cells']);
        $flat['cell_count'] = $count;

        for ($index = 1; $index <= 4; $index++) {
            $cell = $cells[$index - 1] ?? [];
            $shape = static::normalizeImageShape($cell['image_shape'] ?? null);
            $flat["cell_{$index}_type"] = $cell['type'] ?? ($index === 1 ? 'image' : 'rich_text');
            $flat["cell_{$index}_image"] = $cell['image'] ?? null;
            $flat["cell_{$index}_alt"] = $cell['alt'] ?? '';
            $flat["cell_{$index}_image_shape"] = $shape;
            $flat["cell_{$index}_image_size"] = static::normalizeImageSize($cell['image_size'] ?? null, $shape);
            $flat["cell_{$index}_image_align"] = static::normalizeImageAlign($cell['image_align'] ?? null, $shape);
            $flat["cell_{$index}_image_vertical"] = static::normalizeImageVertical($cell['image_vertical'] ?? null);
            $flat["cell_{$index}_body"] = static::bodyToHtml($cell['body'] ?? '');
        }

        return $flat;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function packCells(array $data): array
    {
        $count = max(2, min(4, (int) ($data['cell_count'] ?? 2)));
        $cells = [];

        for ($index = 1; $index <= $count; $index++) {
            $shape = static::normalizeImageShape($data["cell_{$index}_image_shape"] ?? null);
            $cells[] = [
                'type' => ($data["cell_{$index}_type"] ?? 'rich_text') === 'image' ? 'image' : 'rich_text',
                'image' => static::resolveImagePath($data["cell_{$index}_image"] ?? null),
                'alt' => (string) ($data["cell_{$index}_alt"] ?? ''),
                'image_shape' => $shape,
                'image_size' => static::normalizeImageSize($data["cell_{$index}_image_size"] ?? null, $shape),
                'image_align' => static::normalizeImageAlign($data["cell_{$index}_image_align"] ?? null, $shape),
                'image_vertical' => static::normalizeImageVertical($data["cell_{$index}_image_vertical"] ?? null),
                'body' => static::bodyToHtml($data["cell_{$index}_body"] ?? ''),
            ];
        }

        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, 'cell_')) {
                unset($data[$key]);
            }
        }

        $data['cells'] = $cells;
        $data['preset'] = static::normalizePreset((string) ($data['preset'] ?? '50/50'), $count);

        $encoded = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        $decoded = is_string($encoded) ? json_decode($encoded, true) : null;

        return is_array($decoded) ? $decoded : $data;
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    /**
     * @return array{preset: string, container: string, cells: list<string>}
     */
    public static function resolveLayout(
        string $preset,
        int $cellCount,
        string $stackOrder = 'as-is',
        string $verticalAlign = 'start',
    ): array {
        $count = max(2, min(4, $cellCount));
        $preset = static::normalizePreset($preset, $count);
        $align = match ($verticalAlign) {
            'center' => 'items-center',
            'end' => 'items-end',
            default => 'items-start',
        };
        $reverse = $stackOrder === 'reverse';
        $gap = 'gap-8 lg:gap-12';

        if (in_array($preset, ['sidebar-start', 'sidebar-end'], true)) {
            $stack = $reverse ? 'flex flex-col-reverse' : 'flex flex-col';
            $container = trim("{$stack} lg:flex-row {$align} {$gap}");
            $cells = [];

            for ($i = 0; $i < $count; $i++) {
                $isSidebar = ($preset === 'sidebar-start' && $i === 0)
                    || ($preset === 'sidebar-end' && $i === $count - 1);

                $cells[] = $isSidebar
                    ? 'min-w-0 w-full lg:max-w-sm lg:shrink-0'
                    : 'min-w-0 w-full flex-1';
            }

            return [
                'preset' => $preset,
                'container' => $container,
                'cells' => $cells,
            ];
        }

        $lgGrid = match (true) {
            $preset === '33/67', $preset === '67/33' => 'lg:grid-cols-3',
            $preset === '25/75', $preset === '75/25' => 'lg:grid-cols-4',
            $preset === 'equal' && $count === 3 => 'lg:grid-cols-3',
            $preset === 'equal' && $count === 4 => 'lg:grid-cols-4',
            default => 'lg:grid-cols-2',
        };

        $container = $reverse
            ? trim("flex flex-col-reverse lg:grid {$lgGrid} {$align} {$gap}")
            : trim("grid grid-cols-1 {$lgGrid} {$align} {$gap}");

        $cells = [];

        for ($i = 0; $i < $count; $i++) {
            $span = match ($preset) {
                '33/67' => $i === 1 ? 'lg:col-span-2' : 'lg:col-span-1',
                '67/33' => $i === 0 ? 'lg:col-span-2' : 'lg:col-span-1',
                '25/75' => $i === 1 ? 'lg:col-span-3' : 'lg:col-span-1',
                '75/25' => $i === 0 ? 'lg:col-span-3' : 'lg:col-span-1',
                default => '',
            };

            $cells[] = trim('min-w-0 '.$span);
        }

        return [
            'preset' => $preset,
            'container' => $container,
            'cells' => $cells,
        ];
    }

    public static function normalizePreset(string $preset, int $cellCount): string
    {
        $count = max(2, min(4, $cellCount));

        if ($count > 2 && in_array($preset, self::twoCellOnlyPresets(), true)) {
            return 'equal';
        }

        $allowed = $count > 2
            ? ['equal', 'sidebar-start', 'sidebar-end']
            : [...self::twoCellOnlyPresets(), 'sidebar-start', 'sidebar-end', 'equal'];

        if (! in_array($preset, $allowed, true)) {
            return $count > 2 ? 'equal' : '50/50';
        }

        return $preset;
    }

    public static function normalizeImageShape(mixed $shape): string
    {
        return in_array($shape, ['none', 'rounded', 'circle'], true) ? $shape : 'none';
    }

    public static function normalizeImageSize(mixed $size, string $shape = 'none'): string
    {
        if (in_array($size, ['s', 'm', 'l', 'fill'], true)) {
            return $size;
        }

        return $shape === 'circle' ? 'm' : 'fill';
    }

    public static function normalizeImageAlign(mixed $align, string $shape = 'none'): string
    {
        if (in_array($align, ['left', 'center', 'right'], true)) {
            return $align;
        }

        return $shape === 'circle' ? 'center' : 'left';
    }

    public static function normalizeImageVertical(mixed $vertical): string
    {
        return in_array($vertical, ['inherit', 'start', 'center', 'end'], true) ? $vertical : 'inherit';
    }

    public static function imageSizeClass(string $size): string
    {
        return match ($size) {
            's' => 'w-1/3 max-w-xs lg:w-full h-auto',
            'm' => 'w-1/2 max-w-sm lg:w-full h-auto',
            'l' => 'w-2/3 max-w-md lg:w-full h-auto',
            default => 'w-full h-auto',
        };
    }

    public static function imageAlignClass(string $align): string
    {
        return match ($align) {
            'center' => 'flex justify-center',
            'right' => 'flex justify-end',
            default => 'flex justify-start',
        };
    }

    public static function imageVerticalClass(string $vertical): string
    {
        return match ($vertical) {
            'start' => 'lg:self-start',
            'center' => 'lg:self-center',
            'end' => 'lg:self-end',
            default => '',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function presetOptions(int $cellCount): array
    {
        $count = max(2, min(4, $cellCount > 0 ? $cellCount : 2));

        if ($count > 2) {
            return [
                'equal' => __('tallcms::blocks.options.split_equal'),
                'sidebar-start' => __('tallcms::blocks.options.split_sidebar_start'),
                'sidebar-end' => __('tallcms::blocks.options.split_sidebar_end'),
            ];
        }

        return [
            '50/50' => __('tallcms::blocks.options.split_50_50'),
            '33/67' => __('tallcms::blocks.options.split_33_67'),
            '67/33' => __('tallcms::blocks.options.split_67_33'),
            '25/75' => __('tallcms::blocks.options.split_25_75'),
            '75/25' => __('tallcms::blocks.options.split_75_25'),
            'sidebar-start' => __('tallcms::blocks.options.split_sidebar_start'),
            'sidebar-end' => __('tallcms::blocks.options.split_sidebar_end'),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    public static function cellsFromConfig(array $config): array
    {
        if (! isset($config['cells']) || ! is_array($config['cells'])) {
            return [];
        }

        return array_values($config['cells']);
    }

    protected static function renderBlock(array $config, bool $isPreview = false): string
    {
        $cells = static::cellsFromConfig($config);

        if (count($cells) < 2) {
            $cells = array_pad($cells, 2, ['type' => 'rich_text', 'body' => '']);
        }

        $cells = array_slice($cells, 0, 4);
        $layout = static::resolveLayout(
            (string) ($config['preset'] ?? '50/50'),
            count($cells),
            (string) ($config['stack_order'] ?? 'as-is'),
            (string) ($config['vertical_align'] ?? 'start'),
        );

        $prepared = [];
        $disk = \cms_media_disk();

        foreach ($cells as $index => $cell) {
            $type = ($cell['type'] ?? 'rich_text') === 'image' ? 'image' : 'rich_text';
            $path = static::resolveImagePath($cell['image'] ?? null);
            $hasFile = $path !== null && Storage::disk($disk)->exists($path);
            $shape = static::normalizeImageShape($cell['image_shape'] ?? null);
            $size = static::normalizeImageSize($cell['image_size'] ?? null, $shape);
            $align = static::normalizeImageAlign($cell['image_align'] ?? null, $shape);
            $vertical = static::normalizeImageVertical($cell['image_vertical'] ?? null);
            $cellClass = $layout['cells'][$index] ?? 'min-w-0';

            if ($type === 'image') {
                $cellClass = trim($cellClass.' '.static::imageAlignClass($align).' '.static::imageVerticalClass($vertical));
            }

            $body = static::bodyToHtml($cell['body'] ?? '');

            $prepared[] = [
                'type' => $type,
                'class' => $cellClass,
                'image_url' => $hasFile ? Storage::disk($disk)->url($path) : null,
                'alt' => (string) ($cell['alt'] ?? ''),
                'image_shape' => $shape,
                'image_class' => static::imageSizeClass($size),
                'body' => is_string($body) ? $body : '',
                'placeholder' => $isPreview && $type === 'image' && ! $hasFile,
            ];
        }

        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.split', [
            'id' => static::getId(),
            'cells' => $prepared,
            'layoutPreset' => $layout['preset'],
            'containerClass' => $layout['container'],
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $isPreview ? '' : $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $isPreview ? false : $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
            'isPreview' => $isPreview,
        ])->render();
    }

    protected static function bodyToHtml(mixed $body): string
    {
        if (is_string($body)) {
            return $body;
        }

        if (! is_array($body) || $body === []) {
            return '';
        }

        try {
            return (string) RichContentRenderer::make($body)->toHtml();
        } catch (\Throwable) {
            return '';
        }
    }

    protected static function resolveImagePath(mixed $image): ?string
    {
        if (is_array($image)) {
            $image = reset($image) ?: null;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        return $image;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function sampleCells(): array
    {
        return [
            [
                'type' => 'image',
                'image' => null,
                'image_shape' => 'circle',
                'image_size' => 'm',
                'image_align' => 'center',
                'image_vertical' => 'inherit',
                'alt' => 'Sample image',
            ],
            [
                'type' => 'rich_text',
                'body' => '<h2>Split heading</h2><p>Rich text beside an image. Use headings, paragraphs, and lists.</p><ul><li>First point</li><li>Second point</li></ul>',
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cells
     * @return list<array<string, mixed>>
     */
    protected static function fillPreviewCells(array $cells): array
    {
        $sample = static::sampleCells();

        foreach ($cells as $index => $cell) {
            $type = $cell['type'] ?? 'rich_text';

            if ($type === 'rich_text' && blank($cell['body'] ?? null)) {
                $cells[$index]['body'] = $sample[1]['body'];
            }

            if ($type === 'image' && blank($cell['alt'] ?? null)) {
                $cells[$index]['alt'] = $sample[0]['alt'];
            }
        }

        return $cells;
    }
}
