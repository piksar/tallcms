<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Filament\Blocks\SplitBlock;
use TallCms\Cms\Tests\TestCase;

class SplitBlockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_pack_cells_builds_json_safe_cells_array(): void
    {
        $packed = SplitBlock::packCells([
            'cell_count' => 2,
            'preset' => '33/67',
            'cell_1_type' => 'image',
            'cell_1_image' => 'cms/split-blocks/logo.jpg',
            'cell_1_alt' => 'Logo',
            'cell_1_image_shape' => 'circle',
            'cell_2_type' => 'rich_text',
            'cell_2_body' => '<p>Hello</p>',
        ]);

        $this->assertArrayHasKey('cells', $packed);
        $this->assertArrayNotHasKey('cell_1_type', $packed);
        $this->assertCount(2, $packed['cells']);
        $this->assertSame('image', $packed['cells'][0]['type']);
        $this->assertSame('circle', $packed['cells'][0]['image_shape']);
        $this->assertSame('m', $packed['cells'][0]['image_size']);
        $this->assertSame('center', $packed['cells'][0]['image_align']);
        $this->assertSame('inherit', $packed['cells'][0]['image_vertical']);
        $this->assertSame('33/67', $packed['preset']);
        $this->assertNotFalse(json_encode($packed, JSON_THROW_ON_ERROR));
    }

    public function test_to_html_reads_cells_array(): void
    {
        $html = SplitBlock::toHtml([
            'preset' => '33/67',
            'cells' => [
                ['type' => 'image', 'image' => null, 'image_shape' => 'circle', 'alt' => 'Logo'],
                ['type' => 'rich_text', 'body' => '<p>Hello</p>'],
            ],
        ], []);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('split-block', $html);
        $this->assertStringContainsString('data-split-layout="33/67"', $html);
        $this->assertStringContainsString('data-split-cell-type="image"', $html);
        $this->assertStringContainsString('data-split-cell-type="rich_text"', $html);
        $this->assertStringContainsString('lg:col-span-2', $html);
    }

    public function test_block_metadata(): void
    {
        $this->assertSame('split', SplitBlock::getId());
        $this->assertSame('content', SplitBlock::getCategory());
        $this->assertSame(12, SplitBlock::getSortPriority());
        $this->assertSame('Split', SplitBlock::getLabel());
    }

    public function test_two_cell_50_50_uses_two_column_grid(): void
    {
        $html = $this->renderPreset('50/50');

        $this->assertStringContainsString('lg:grid-cols-2', $html);
        $this->assertStringNotContainsString('lg:grid-cols-3', $html);
        $this->assertStringNotContainsString('lg:col-span-2', $html);
        $this->assertStringContainsString('data-split-layout="50/50"', $html);
    }

    public function test_two_cell_33_67_spans_one_and_two(): void
    {
        $html = $this->renderPreset('33/67');
        $layout = SplitBlock::resolveLayout('33/67', 2);

        $this->assertSame('33/67', $layout['preset']);
        $this->assertStringContainsString('lg:grid-cols-3', $layout['container']);
        $this->assertStringContainsString('lg:col-span-1', $layout['cells'][0]);
        $this->assertStringContainsString('lg:col-span-2', $layout['cells'][1]);
        $this->assertStringContainsString('lg:grid-cols-3', $html);
        $this->assertStringContainsString('lg:col-span-2', $html);
        $this->assertStringNotContainsString('lg:grid-cols-2', $html);
    }

    public function test_two_cell_67_33_spans_two_and_one(): void
    {
        $layout = SplitBlock::resolveLayout('67/33', 2);

        $this->assertStringContainsString('lg:col-span-2', $layout['cells'][0]);
        $this->assertStringContainsString('lg:col-span-1', $layout['cells'][1]);
        $this->assertStringContainsString('lg:grid-cols-3', $this->renderPreset('67/33'));
    }

    public function test_two_cell_25_75_spans_one_and_three(): void
    {
        $layout = SplitBlock::resolveLayout('25/75', 2);

        $this->assertStringContainsString('lg:grid-cols-4', $layout['container']);
        $this->assertStringContainsString('lg:col-span-1', $layout['cells'][0]);
        $this->assertStringContainsString('lg:col-span-3', $layout['cells'][1]);
        $this->assertStringContainsString('lg:grid-cols-4', $this->renderPreset('25/75'));
    }

    public function test_two_cell_75_25_spans_three_and_one(): void
    {
        $layout = SplitBlock::resolveLayout('75/25', 2);

        $this->assertStringContainsString('lg:col-span-3', $layout['cells'][0]);
        $this->assertStringContainsString('lg:col-span-1', $layout['cells'][1]);
    }

    public function test_three_cell_equal_has_no_two_cell_ratio_classes(): void
    {
        $html = $this->renderPreset('equal', $this->textCells(3));
        $layout = SplitBlock::resolveLayout('equal', 3);

        $this->assertSame('equal', $layout['preset']);
        $this->assertStringContainsString('lg:grid-cols-3', $layout['container']);
        $this->assertStringContainsString('lg:grid-cols-3', $html);
        $this->assertStringNotContainsString('lg:col-span-2', $html);
        $this->assertStringNotContainsString('lg:col-span-3', $html);
        $this->assertStringNotContainsString('lg:grid-cols-2', $html);
        $this->assertStringNotContainsString('lg:max-w-sm', $html);
    }

    public function test_four_cell_equal_uses_four_columns_without_spans(): void
    {
        $html = $this->renderPreset('equal', $this->textCells(4));
        $layout = SplitBlock::resolveLayout('equal', 4);

        $this->assertStringContainsString('lg:grid-cols-4', $layout['container']);
        $this->assertStringContainsString('lg:grid-cols-4', $html);
        $this->assertStringNotContainsString('lg:col-span-3', $html);
        $this->assertStringNotContainsString('lg:col-span-2', $html);
    }

    public function test_three_cells_with_33_67_normalizes_to_equal(): void
    {
        $html = $this->renderPreset('33/67', $this->textCells(3));
        $layout = SplitBlock::resolveLayout('33/67', 3);

        $this->assertSame('equal', $layout['preset']);
        $this->assertStringContainsString('data-split-layout="equal"', $html);
        $this->assertStringNotContainsString('lg:col-span-2', $html);
        $this->assertStringContainsString('lg:grid-cols-3', $html);
    }

    public function test_sidebar_start_with_two_cells(): void
    {
        $html = $this->renderPreset('sidebar-start');
        $layout = SplitBlock::resolveLayout('sidebar-start', 2);

        $this->assertStringContainsString('lg:flex-row', $layout['container']);
        $this->assertStringContainsString('lg:max-w-sm', $layout['cells'][0]);
        $this->assertStringContainsString('flex-1', $layout['cells'][1]);
        $this->assertStringNotContainsString('lg:max-w-sm', $layout['cells'][1]);
        $this->assertStringContainsString('lg:max-w-sm', $html);
        $this->assertStringContainsString('lg:flex-row', $html);
        $this->assertStringNotContainsString('lg:grid-cols-', $html);
    }

    public function test_sidebar_end_with_two_cells(): void
    {
        $layout = SplitBlock::resolveLayout('sidebar-end', 2);

        $this->assertStringContainsString('flex-1', $layout['cells'][0]);
        $this->assertStringContainsString('lg:max-w-sm', $layout['cells'][1]);
        $this->assertStringContainsString('lg:max-w-sm', $this->renderPreset('sidebar-end'));
    }

    public function test_sidebar_start_with_three_cells(): void
    {
        $layout = SplitBlock::resolveLayout('sidebar-start', 3);

        $this->assertStringContainsString('lg:max-w-sm', $layout['cells'][0]);
        $this->assertStringContainsString('flex-1', $layout['cells'][1]);
        $this->assertStringContainsString('flex-1', $layout['cells'][2]);
        $this->assertCount(3, $layout['cells']);
        $this->assertStringContainsString('lg:max-w-sm', $this->renderPreset('sidebar-start', $this->textCells(3)));
    }

    public function test_sidebar_end_with_three_cells(): void
    {
        $layout = SplitBlock::resolveLayout('sidebar-end', 3);

        $this->assertStringContainsString('flex-1', $layout['cells'][0]);
        $this->assertStringContainsString('flex-1', $layout['cells'][1]);
        $this->assertStringContainsString('lg:max-w-sm', $layout['cells'][2]);
    }

    public function test_circle_shape_renders_rounded_full(): void
    {
        $path = $this->createFakeImage('cms/split-blocks/logo.jpg');

        $html = $this->renderPreset('50/50', [
            [
                'type' => 'image',
                'image' => $path,
                'image_shape' => 'circle',
                'alt' => 'Club logo',
            ],
            [
                'type' => 'rich_text',
                'body' => '<p>Hello</p>',
            ],
        ]);

        $this->assertStringContainsString('rounded-full', $html);
        $this->assertStringContainsString('w-1/2 max-w-sm lg:w-full', $html);
        $this->assertStringContainsString('flex justify-center', $html);
        $this->assertStringContainsString('alt="Club logo"', $html);
    }

    public function test_empty_image_and_body_still_emit_grid_slots(): void
    {
        $html = $this->renderPreset('50/50', [
            ['type' => 'image', 'image' => null, 'alt' => 'Missing', 'image_shape' => 'none'],
            ['type' => 'rich_text', 'body' => ''],
        ]);

        $this->assertSame(2, substr_count($html, 'data-split-cell='));
        $this->assertStringContainsString('lg:grid-cols-2', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_rich_text_is_not_inside_not_prose(): void
    {
        $html = $this->renderPreset('50/50', [
            [
                'type' => 'rich_text',
                'body' => '<h2>Heading</h2><ul><li>One</li><li>Two</li></ul>',
            ],
            [
                'type' => 'rich_text',
                'body' => '<p>Other</p>',
            ],
        ]);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>One</li>', $html);
        $this->assertStringContainsString('prose prose-lg', $html);
        $this->assertDoesNotMatchRegularExpression('/<section\b[^>]*\bnot-prose\b/', $html);
    }

    public function test_image_cells_opt_out_of_prose(): void
    {
        $path = $this->createFakeImage('cms/split-blocks/logo.jpg');

        $html = $this->renderPreset('50/50', [
            [
                'type' => 'image',
                'image' => $path,
                'image_shape' => 'circle',
                'alt' => 'Club logo',
            ],
            [
                'type' => 'rich_text',
                'body' => '<ul><li>One</li></ul>',
            ],
        ]);

        $this->assertMatchesRegularExpression('/<div\b[^>]*\bnot-prose\b[^>]*data-split-cell="0"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<div\b[^>]*\bnot-prose\b[^>]*data-split-cell="1"/', $html);
    }

    public function test_rich_text_is_sanitized(): void
    {
        $html = $this->renderPreset('50/50', [
            ['type' => 'rich_text', 'body' => '<p>Safe</p>'],
            ['type' => 'rich_text', 'body' => '<p>Hello</p><script>alert(1)</script><p onclick="alert(1)">Click</p>'],
        ]);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_preview_supplies_sample_content(): void
    {
        $html = SplitBlock::toPreviewHtml([
            'preset' => '33/67',
            'background' => 'bg-neutral',
        ]);

        $this->assertStringContainsString('Split heading', $html);
        $this->assertStringContainsString('data-split-layout="33/67"', $html);
        $this->assertStringContainsString('bg-neutral', $html);
        $this->assertStringNotContainsString('x-data', $html);
    }

    public function test_reverse_stack_uses_flex_col_reverse(): void
    {
        $html = $this->renderPreset('50/50', $this->textCells(2), [
            'stack_order' => 'reverse',
        ]);
        $layout = SplitBlock::resolveLayout('50/50', 2, 'reverse');

        $this->assertStringContainsString('flex-col-reverse', $layout['container']);
        $this->assertStringContainsString('flex-col-reverse', $html);
    }

    public function test_vertical_center_uses_items_center(): void
    {
        $layout = SplitBlock::resolveLayout('50/50', 2, 'as-is', 'center');

        $this->assertStringContainsString('items-center', $layout['container']);
        $this->assertStringNotContainsString('items-start', $layout['container']);
        $this->assertStringNotContainsString('items-end', $layout['container']);
    }

    public function test_vertical_end_uses_items_end(): void
    {
        $layout = SplitBlock::resolveLayout('50/50', 2, 'as-is', 'end');
        $html = $this->renderPreset('50/50', $this->textCells(2), [
            'vertical_align' => 'end',
        ]);

        $this->assertStringContainsString('items-end', $layout['container']);
        $this->assertStringNotContainsString('items-start', $layout['container']);
        $this->assertStringContainsString('items-end', $html);
    }

    public function test_unpack_defaults_circle_size_to_medium(): void
    {
        $unpacked = SplitBlock::unpackCells([
            'cells' => [
                ['type' => 'image', 'image_shape' => 'circle'],
                ['type' => 'rich_text'],
            ],
        ]);

        $this->assertSame('m', $unpacked['cell_1_image_size']);
        $this->assertSame('center', $unpacked['cell_1_image_align']);
        $this->assertSame('inherit', $unpacked['cell_1_image_vertical']);
        $this->assertSame('fill', $unpacked['cell_2_image_size']);
        $this->assertSame('left', $unpacked['cell_2_image_align']);
    }

    public function test_unpack_defaults_non_circle_size_to_fill(): void
    {
        $unpacked = SplitBlock::unpackCells([
            'cells' => [
                ['type' => 'image', 'image_shape' => 'none'],
                ['type' => 'rich_text'],
            ],
        ]);

        $this->assertSame('fill', $unpacked['cell_1_image_size']);
        $this->assertSame('left', $unpacked['cell_1_image_align']);
    }

    public function test_image_size_class_literals(): void
    {
        $this->assertSame('w-1/3 max-w-xs lg:w-full h-auto', SplitBlock::imageSizeClass('s'));
        $this->assertSame('w-1/2 max-w-sm lg:w-full h-auto', SplitBlock::imageSizeClass('m'));
        $this->assertSame('w-2/3 max-w-md lg:w-full h-auto', SplitBlock::imageSizeClass('l'));
        $this->assertSame('w-full h-auto', SplitBlock::imageSizeClass('fill'));
        $this->assertSame('flex justify-center', SplitBlock::imageAlignClass('center'));
        $this->assertSame('flex justify-end', SplitBlock::imageAlignClass('right'));
        $this->assertSame('flex justify-start', SplitBlock::imageAlignClass('left'));
        $this->assertSame('lg:self-end', SplitBlock::imageVerticalClass('end'));
        $this->assertSame('lg:self-start', SplitBlock::imageVerticalClass('start'));
        $this->assertSame('lg:self-center', SplitBlock::imageVerticalClass('center'));
        $this->assertSame('', SplitBlock::imageVerticalClass('inherit'));
    }

    public function test_explicit_fill_on_circle_keeps_full_width(): void
    {
        $path = $this->createFakeImage('cms/split-blocks/logo.jpg');

        $html = $this->renderPreset('50/50', [
            [
                'type' => 'image',
                'image' => $path,
                'image_shape' => 'circle',
                'image_size' => 'fill',
                'image_align' => 'left',
                'alt' => 'Club logo',
            ],
            [
                'type' => 'rich_text',
                'body' => '<p>Hello</p>',
            ],
        ]);

        $this->assertMatchesRegularExpression('/<img\b[^>]*\bw-full h-auto\b/', $html);
        $this->assertDoesNotMatchRegularExpression('/<img\b[^>]*\bw-1\/2\b/', $html);
        $this->assertStringContainsString('flex justify-start', $html);
    }

    public function test_image_cell_vertical_end_uses_self_end(): void
    {
        $path = $this->createFakeImage('cms/split-blocks/logo.jpg');

        $html = $this->renderPreset('50/50', [
            [
                'type' => 'image',
                'image' => $path,
                'image_shape' => 'none',
                'image_size' => 's',
                'image_align' => 'right',
                'image_vertical' => 'end',
                'alt' => 'Photo',
            ],
            [
                'type' => 'rich_text',
                'body' => '<p>Hello</p>',
            ],
        ]);

        $this->assertStringContainsString('w-1/3 max-w-xs lg:w-full', $html);
        $this->assertStringContainsString('flex justify-end', $html);
        $this->assertStringContainsString('lg:self-end', $html);
    }

    public function test_pack_persists_image_size_and_align(): void
    {
        $packed = SplitBlock::packCells([
            'cell_count' => 2,
            'preset' => '50/50',
            'cell_1_type' => 'image',
            'cell_1_image_shape' => 'rounded',
            'cell_1_image_size' => 'l',
            'cell_1_image_align' => 'right',
            'cell_1_image_vertical' => 'end',
            'cell_2_type' => 'rich_text',
            'cell_2_body' => '<p>Hello</p>',
        ]);

        $this->assertSame('l', $packed['cells'][0]['image_size']);
        $this->assertSame('right', $packed['cells'][0]['image_align']);
        $this->assertSame('end', $packed['cells'][0]['image_vertical']);

        $unpacked = SplitBlock::unpackCells($packed);

        $this->assertSame('l', $unpacked['cell_1_image_size']);
        $this->assertSame('right', $unpacked['cell_1_image_align']);
        $this->assertSame('end', $unpacked['cell_1_image_vertical']);
    }

    public function test_preset_options_hide_two_cell_ratios_for_three_cells(): void
    {
        $two = SplitBlock::presetOptions(2);
        $three = SplitBlock::presetOptions(3);

        $this->assertArrayHasKey('33/67', $two);
        $this->assertArrayNotHasKey('33/67', $three);
        $this->assertArrayHasKey('equal', $three);
        $this->assertArrayNotHasKey('equal', $two);
    }

    /**
     * @param  list<array<string, mixed>>|null  $cells
     * @param  array<string, mixed>  $overrides
     */
    protected function renderPreset(string $preset, ?array $cells = null, array $overrides = []): string
    {
        $config = array_merge([
            'preset' => $preset,
            'cells' => $cells ?? $this->textCells(2),
            'stack_order' => 'as-is',
            'vertical_align' => 'start',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
        ], $overrides);

        return SplitBlock::toHtml($config, []);
    }

    /**
     * @return list<array{type: string, body: string}>
     */
    protected function textCells(int $count): array
    {
        $cells = [];

        for ($i = 0; $i < $count; $i++) {
            $cells[] = [
                'type' => 'rich_text',
                'body' => '<p>Cell '.($i + 1).'</p>',
            ];
        }

        return $cells;
    }

    protected function createFakeImage(string $path): string
    {
        Storage::disk('public')->put($path, 'fake-image-content');

        return $path;
    }
}
