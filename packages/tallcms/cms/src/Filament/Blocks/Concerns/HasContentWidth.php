<?php

namespace TallCms\Cms\Filament\Blocks\Concerns;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\View;

trait HasContentWidth
{
    /**
     * Get the default width for this block type.
     * Override this in block classes that should have a different default.
     *
     * Options: 'inherit', 'narrow', 'standard', 'wide', 'full'
     */
    protected static function getDefaultWidth(): string
    {
        return 'inherit';
    }

    /**
     * Get the content width field for block configuration.
     */
    public static function getContentWidthField(): Select
    {
        return Select::make('content_width')
            ->label(__('tallcms::fields.content_width'))
            ->options([
                'inherit' => 'Inherit from Page/Post',
                'narrow' => 'Narrow (672px)',
                'prose' => 'Prose (896px)',
                'standard' => 'Standard (1152px)',
                'wide' => 'Wide (1280px)',
                'full' => 'Full Width',
            ])
            ->default(static::getDefaultWidth());
    }

    /**
     * Resolve the width class and padding for block rendering.
     *
     * Returns array with 'class' and 'padding' keys.
     * Full-width blocks get no horizontal padding for true full-bleed.
     */
    public static function resolveWidthClass(array $config): array
    {
        $blockWidth = $config['content_width'] ?? static::getDefaultWidth();

        // If inheriting, get the page's content width
        if ($blockWidth === 'inherit') {
            $blockWidth = View::shared('cmsPageContentWidth', 'standard');
        }

        $widthClass = match ($blockWidth) {
            'narrow' => 'max-w-2xl mx-auto',
            'prose' => 'max-w-4xl mx-auto',
            'standard' => 'max-w-6xl mx-auto',
            'wide' => 'max-w-7xl mx-auto',
            'full' => 'w-full',
            default => 'max-w-6xl mx-auto',
        };

        return [
            'class' => $widthClass,
            'padding' => $blockWidth === 'full' ? '' : 'px-4 sm:px-6 lg:px-8',
        ];
    }
}
