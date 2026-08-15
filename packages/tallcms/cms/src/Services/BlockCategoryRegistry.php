<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

/**
 * Registry defining available block categories for the Rich Editor.
 *
 * Categories help organize blocks in the enhanced block panel,
 * making it easier for users to find the block they need.
 */
class BlockCategoryRegistry
{
    /**
     * Default fallback icon if a block's icon doesn't exist.
     */
    public const FALLBACK_ICON = 'heroicon-o-cube';

    /**
     * Get all available block categories.
     *
     * @return array<string, array{label: string, icon: string, order: int}>
     */
    public static function getCategories(): array
    {
        return [
            'content' => [
                'label' => __('tallcms::blocks.categories.content'),
                'icon' => 'heroicon-o-document-text',
                'order' => 10,
            ],
            'media' => [
                'label' => __('tallcms::blocks.categories.media'),
                'icon' => 'heroicon-o-photo',
                'order' => 20,
            ],
            'social-proof' => [
                'label' => __('tallcms::blocks.categories.social-proof'),
                'icon' => 'heroicon-o-star',
                'order' => 30,
            ],
            'dynamic' => [
                'label' => __('tallcms::blocks.categories.dynamic'),
                'icon' => 'heroicon-o-newspaper',
                'order' => 40,
            ],
            'forms' => [
                'label' => __('tallcms::blocks.categories.forms'),
                'icon' => 'heroicon-o-envelope',
                'order' => 50,
            ],
            'other' => [
                'label' => __('tallcms::blocks.categories.other'),
                'icon' => 'heroicon-o-squares-plus',
                'order' => 100,
            ],
        ];
    }

    /**
     * Get a specific category by key.
     *
     * @return array{label: string, icon: string, order: int}|null
     */
    public static function getCategory(string $key): ?array
    {
        return self::getCategories()[$key] ?? null;
    }

    /**
     * Get the category order for sorting.
     */
    public static function getCategoryOrder(string $key): int
    {
        return self::getCategories()[$key]['order'] ?? 999;
    }
}
