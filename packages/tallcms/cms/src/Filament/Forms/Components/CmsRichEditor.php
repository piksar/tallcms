<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components;

use Filament\Forms\Components\RichEditor;
use TallCms\Cms\Filament\Forms\Components\Plugins\BlockChromePlugin;
use TallCms\Cms\Filament\Forms\Components\Plugins\MediaLibraryPlugin;
use TallCms\Cms\Services\BlockCategoryRegistry;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

/**
 * Custom RichEditor component for CMS pages and posts.
 *
 * Extends Filament's RichEditor with an enhanced block panel featuring:
 * - Search functionality for finding blocks quickly
 * - Blocks grouped by category (Content, Media, Social Proof, etc.)
 * - Icons displayed alongside block names
 *
 * This component is scoped to CMS editors only - other RichEditors in the
 * admin are unaffected.
 *
 * Falls back to standard RichEditor when Filament version is incompatible.
 */
class CmsRichEditor extends RichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins([
            MediaLibraryPlugin::make(),
            BlockChromePlugin::make(),
        ]);

        // Hint at slash commands on a fresh editor. TipTap's Placeholder
        // shows on the first empty node only, so this greets newcomers
        // without nagging anyone with content. Users can override by
        // calling ->placeholder() on the field.
        $this->placeholder(__('tallcms::editor.type_for_blocks'));

        // Use enhanced view for Filament v4.x/v5.x
        if (static::isFilamentCompatible()) {
            $this->view = 'tallcms::filament.forms.components.cms-rich-editor';
        }
    }

    public function getDefaultToolbarButtons(): array
    {
        $buttons = parent::getDefaultToolbarButtons();

        return array_map(function ($group) {
            if (is_array($group) && in_array('attachFiles', $group)) {
                $pos = array_search('attachFiles', $group);
                array_splice($group, $pos + 1, 0, ['insertMedia']);
            }

            return $group;
        }, $buttons);
    }

    /**
     * Get blocks grouped by category for the enhanced block panel.
     *
     * @return array<string, array<int, array{id: string, label: string, icon: string, iconHtml: string, searchable: string}>>
     */
    public function getGroupedBlocks(): array
    {
        return CustomBlockDiscoveryService::getBlocksGroupedByCategory()->toArray();
    }

    /**
     * Get the block category definitions.
     *
     * @return array<string, array{label: string, icon: string, order: int}>
     */
    public function getBlockCategories(): array
    {
        return BlockCategoryRegistry::getCategories();
    }

    /**
     * Check if the current Filament version is compatible with the enhanced panel.
     *
     * We require Filament v4.x or v5.x for the enhanced block panel features.
     * If incompatible, the view will fall back to the standard panel.
     */
    public static function isFilamentCompatible(): bool
    {
        if (! class_exists(\Composer\InstalledVersions::class)) {
            return false;
        }

        try {
            $version = \Composer\InstalledVersions::getVersion('filament/forms');

            return $version
                && version_compare($version, '4.0.0', '>=')
                && version_compare($version, '6.0.0', '<');
        } catch (\Throwable) {
            return false;
        }
    }
}
