<?php

namespace TallCms\Cms\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use TallCms\Cms\Tests\TestCase;

/**
 * PR #60 regression guard — category labels inside CmsPostForm, CmsPostsTable,
 * and CmsCategoryForm must read via `tallcms_label('categories', …)` so a rename
 * (e.g. "Categories" → "Tags") propagates into form section headings, helper
 * text, table columns, and filter labels — not only into the resource-level
 * labels covered by ResourceLabelOverrideTest.
 *
 * These assertions intentionally operate on source strings rather than
 * mounting the Filament schema. The alternative — instantiating a full
 * Schema with a stub Livewire component — costs more setup than the value
 * added for a defensive-consistency change. Source assertions catch the
 * regression we actually care about: someone re-hardcoding "Category" /
 * "Categories" in these exact spots.
 */
class CategoryLabelPropagationTest extends TestCase
{
    #[DataProvider('configReadCallsites')]
    public function test_source_file_reads_category_label_from_helper(
        string $relativePath,
        string $expectedHelperCall,
        string $context,
    ): void {
        $source = file_get_contents(self::packagePath($relativePath));

        $this->assertStringContainsString(
            $expectedHelperCall,
            $source,
            "{$context} must read the category label via tallcms_label() so the label "
            .'override API (PR #57) covers renames deep in forms and tables, '
            .'not only at the resource level.',
        );
    }

    public static function configReadCallsites(): array
    {
        return [
            'CmsPostForm categories section heading' => [
                'src/Filament/Resources/CmsPosts/Schemas/CmsPostForm.php',
                "tallcms_label('categories', 'plural')",
                'The Categories section heading in CmsPostForm',
            ],
            'CmsPostsTable categories column' => [
                'src/Filament/Resources/CmsPosts/Tables/CmsPostsTable.php',
                "tallcms_label('categories', 'plural')",
                'The categories column label in CmsPostsTable',
            ],
            'CmsCategoryForm parent field' => [
                'src/Filament/Resources/CmsCategories/Schemas/CmsCategoryForm.php',
                "tallcms_label('categories', 'singular')",
                'The Parent field label in CmsCategoryForm',
            ],
        ];
    }

    public function test_cms_post_form_does_not_hardcode_categories_section_heading(): void
    {
        // Belt-and-braces: assert the specific hardcoded strings we replaced
        // are NOT present, so a partial revert that restores the literal would
        // fail this test even if the helper call is kept elsewhere.
        $source = file_get_contents(self::packagePath(
            'src/Filament/Resources/CmsPosts/Schemas/CmsPostForm.php'
        ));

        $this->assertStringNotContainsString(
            "Section::make('Categories')",
            $source,
            'CmsPostForm must not hardcode the Categories section heading — '
            .'use tallcms_label(\'categories\', \'plural\') instead.',
        );
    }

    public function test_author_dropdown_uses_relationship_not_plucked_options(): void
    {
        // The PR #60 change switched author dropdowns from
        //   ->options(fn () => $userModel::query()->pluck('name', 'id'))
        // to
        //   ->relationship(name: 'author', titleAttribute: 'name', ...)
        //      ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->name)
        // so host-app Users with first_name/last_name + a `name` accessor
        // get the correct label without a form override.
        foreach (['CmsPostForm', 'CmsPageForm'] as $form) {
            $source = file_get_contents(self::packagePath(
                'src/Filament/Resources/'.($form === 'CmsPostForm' ? 'CmsPosts' : 'CmsPages')
                ."/Schemas/{$form}.php"
            ));

            // Positive: must call the record-aware label resolver so a
            // hydrated User flows through its name accessor.
            $this->assertStringContainsString(
                'getOptionLabelFromRecordUsing',
                $source,
                "{$form} must use ->getOptionLabelFromRecordUsing() on the "
                .'author dropdown so a hydrated User model flows through any '
                .'accessor (e.g. first_name + last_name => name).',
            );

            // Positive: must bind the author dropdown to the relationship,
            // not a statically-plucked options array.
            $this->assertMatchesRegularExpression(
                "/->relationship\(\s*name:\s*'author'/",
                $source,
                "{$form} must bind the author dropdown to the `author` "
                .'relationship with ->relationship(name: \'author\', ...) '
                .'so Filament hydrates User records for the label callback.',
            );
        }
    }

    protected static function packagePath(string $relative): string
    {
        return dirname(__DIR__, 2).'/'.$relative;
    }
}
