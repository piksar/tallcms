<?php

namespace TallCms\Cms\Filament\Blocks\Concerns;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Provides reusable daisyUI-compatible form schema components for blocks.
 *
 * All blocks should use these standardized options to ensure consistent
 * styling that works with daisyUI theme switching.
 */
trait HasDaisyUIOptions
{
    /**
     * Get button variant options (maps to daisyUI btn-* classes)
     */
    protected static function getButtonVariantOptions(): array
    {
        return [
            'btn-primary' => __('tallcms::blocks.options.primary'),
            'btn-secondary' => __('tallcms::blocks.options.secondary'),
            'btn-accent' => __('tallcms::blocks.options.accent'),
            'btn-neutral' => __('tallcms::blocks.options.neutral'),
            'btn-info' => __('tallcms::blocks.options.info'),
            'btn-success' => __('tallcms::blocks.options.success'),
            'btn-warning' => __('tallcms::blocks.options.warning'),
            'btn-error' => __('tallcms::blocks.options.error'),
        ];
    }

    /**
     * Get secondary/outline button variant options
     */
    protected static function getSecondaryButtonVariantOptions(): array
    {
        return [
            'btn-outline btn-primary' => __('tallcms::blocks.options.primary_outline'),
            'btn-outline btn-secondary' => __('tallcms::blocks.options.secondary_outline'),
            'btn-outline btn-accent' => __('tallcms::blocks.options.accent_outline'),
            'btn-outline btn-neutral' => __('tallcms::blocks.options.neutral_outline'),
            'btn-ghost' => __('tallcms::blocks.options.ghost'),
            'btn-link' => __('tallcms::blocks.options.link'),
        ];
    }

    /**
     * Get button size options
     */
    protected static function getButtonSizeOptions(): array
    {
        return [
            'btn-xs' => __('tallcms::blocks.options.extra_small'),
            'btn-sm' => __('tallcms::blocks.options.small'),
            'btn-md' => __('tallcms::blocks.options.medium'),
            'btn-lg' => __('tallcms::blocks.options.large'),
        ];
    }

    /**
     * Get background color options (semantic daisyUI colors)
     */
    protected static function getBackgroundOptions(): array
    {
        return [
            'bg-base-100' => __('tallcms::blocks.options.base_default'),
            'bg-base-200' => __('tallcms::blocks.options.base_subtle'),
            'bg-base-300' => __('tallcms::blocks.options.base_strong'),
            'bg-primary' => __('tallcms::blocks.options.primary'),
            'bg-secondary' => __('tallcms::blocks.options.secondary'),
            'bg-accent' => __('tallcms::blocks.options.accent'),
            'bg-neutral' => __('tallcms::blocks.options.neutral'),
            'bg-info' => __('tallcms::blocks.options.info'),
            'bg-success' => __('tallcms::blocks.options.success'),
            'bg-warning' => __('tallcms::blocks.options.warning'),
            'bg-error' => __('tallcms::blocks.options.error'),
        ];
    }

    /**
     * Get card style options
     */
    protected static function getCardStyleOptions(): array
    {
        return [
            'card bg-base-100' => __('tallcms::blocks.options.default_card'),
            'card bg-base-100 shadow-md' => __('tallcms::blocks.options.card_shadow'),
            'card bg-base-100 shadow-xl' => __('tallcms::blocks.options.card_large_shadow'),
            'card bg-base-200' => __('tallcms::blocks.options.subtle_card'),
            'card bordered' => __('tallcms::blocks.options.bordered_card'),
            'card bg-primary text-primary-content' => __('tallcms::blocks.options.primary_card'),
            'card bg-secondary text-secondary-content' => __('tallcms::blocks.options.secondary_card'),
        ];
    }

    /**
     * Get accent color options (semantic daisyUI tokens, no `text-`/`bg-` prefix).
     *
     * Returned values are bare token names — block views pair them with the
     * AccentColor helper (or the @accent Blade directive) to map to concrete
     * Tailwind classes for icons, markers, popular indicators, etc.
     */
    protected static function getAccentColorOptions(): array
    {
        return [
            'primary' => __('tallcms::blocks.options.primary'),
            'secondary' => __('tallcms::blocks.options.secondary'),
            'accent' => __('tallcms::blocks.options.accent'),
            'neutral' => __('tallcms::blocks.options.neutral'),
            'info' => __('tallcms::blocks.options.info'),
            'success' => __('tallcms::blocks.options.success'),
            'warning' => __('tallcms::blocks.options.warning'),
            'error' => __('tallcms::blocks.options.error'),
        ];
    }

    /**
     * Get text alignment options
     */
    protected static function getTextAlignmentOptions(): array
    {
        return [
            'text-left' => __('tallcms::blocks.options.align_left'),
            'text-center' => __('tallcms::blocks.options.align_center'),
            'text-right' => __('tallcms::blocks.options.align_right'),
        ];
    }

    /**
     * Get text color options (semantic daisyUI colors)
     */
    protected static function getTextColorOptions(): array
    {
        return [
            'text-base-content' => __('tallcms::blocks.options.base_default'),
            'text-primary' => __('tallcms::blocks.options.primary'),
            'text-secondary' => __('tallcms::blocks.options.secondary'),
            'text-accent' => __('tallcms::blocks.options.accent'),
            'text-neutral' => __('tallcms::blocks.options.neutral'),
            'text-info' => __('tallcms::blocks.options.info'),
            'text-success' => __('tallcms::blocks.options.success'),
            'text-warning' => __('tallcms::blocks.options.warning'),
            'text-error' => __('tallcms::blocks.options.error'),
            'text-white' => __('tallcms::blocks.options.white'),
            'text-white/90' => __('tallcms::blocks.options.white_90'),
            'text-white/80' => __('tallcms::blocks.options.white_80'),
            'text-white/70' => __('tallcms::blocks.options.white_70'),
            'text-primary-content' => __('tallcms::blocks.options.primary_content'),
            'text-secondary-content' => __('tallcms::blocks.options.secondary_content'),
            'text-accent-content' => __('tallcms::blocks.options.accent_content'),
            'text-neutral-content' => __('tallcms::blocks.options.neutral_content'),
        ];
    }

    /**
     * Get padding/spacing options
     */
    protected static function getPaddingOptions(): array
    {
        return [
            'py-8' => __('tallcms::blocks.options.small'),
            'py-12' => __('tallcms::blocks.options.medium'),
            'py-16' => __('tallcms::blocks.options.large'),
            'py-24' => __('tallcms::blocks.options.extra_large'),
        ];
    }

    /**
     * Get a reusable button styling section for block schemas
     */
    protected static function getButtonStyleSection(
        string $prefix = '',
        ?string $label = null,
        string $defaultVariant = 'btn-primary',
        string $defaultSize = 'btn-md',
        bool $showSize = true
    ): Section {
        $label ??= __('tallcms::blocks.options.primary_button');
        $variantField = $prefix ? "{$prefix}_variant" : 'button_variant';
        $sizeField = $prefix ? "{$prefix}_size" : 'button_size';

        $schema = [
            Select::make($variantField)
                ->label(__('tallcms::blocks.options.button_style'))
                ->options(static::getButtonVariantOptions())
                ->default($defaultVariant),
        ];

        if ($showSize) {
            $schema[] = Select::make($sizeField)
                ->label(__('tallcms::blocks.options.button_size'))
                ->options(static::getButtonSizeOptions())
                ->default($defaultSize);
        }

        return Section::make($label)
            ->schema($schema)
            ->columns(2)
            ->compact();
    }

    /**
     * Get a reusable secondary button styling section
     */
    protected static function getSecondaryButtonStyleSection(
        string $prefix = 'secondary_button',
        ?string $label = null,
        string $defaultVariant = 'btn-outline btn-primary'
    ): Section {
        $label ??= __('tallcms::blocks.options.secondary_button');

        return Section::make($label)
            ->schema([
                Select::make("{$prefix}_variant")
                    ->label(__('tallcms::blocks.options.button_style'))
                    ->options(static::getSecondaryButtonVariantOptions())
                    ->default($defaultVariant),
            ])
            ->compact();
    }

    /**
     * Get a reusable background styling section
     */
    protected static function getBackgroundSection(
        string $defaultBg = 'bg-base-200',
        bool $showGradient = false
    ): Section {
        $schema = [
            Select::make('background')
                ->label(__('tallcms::blocks.options.background_color'))
                ->options(static::getBackgroundOptions())
                ->default($defaultBg),
        ];

        if ($showGradient) {
            $schema[] = Toggle::make('use_gradient')
                ->label(__('tallcms::blocks.options.use_gradient'))
                ->default(false);
        }

        return Section::make(__('tallcms::blocks.options.background'))
            ->schema($schema)
            ->compact();
    }

    /**
     * Get a reusable layout section
     */
    protected static function getLayoutSection(
        string $defaultAlignment = 'text-center',
        string $defaultPadding = 'py-12'
    ): Section {
        return Section::make(__('tallcms::blocks.options.layout'))
            ->schema([
                Select::make('text_alignment')
                    ->label(__('tallcms::blocks.options.text_alignment'))
                    ->options(static::getTextAlignmentOptions())
                    ->default($defaultAlignment),

                Select::make('padding')
                    ->label(__('tallcms::blocks.options.section_padding'))
                    ->options(static::getPaddingOptions())
                    ->default($defaultPadding),
            ])
            ->columns(2)
            ->compact();
    }

    /**
     * Build button CSS classes from config
     */
    public static function buildButtonClasses(array $config, string $prefix = ''): string
    {
        $variantKey = $prefix ? "{$prefix}_variant" : 'button_variant';
        $sizeKey = $prefix ? "{$prefix}_size" : 'button_size';

        $variant = $config[$variantKey] ?? 'btn-primary';
        $size = $config[$sizeKey] ?? '';

        return trim("btn {$variant} {$size}");
    }

    /**
     * Build background CSS classes from config
     */
    public static function buildBackgroundClasses(array $config): string
    {
        return $config['background'] ?? 'bg-base-200';
    }

    /**
     * Build layout CSS classes from config
     */
    public static function buildLayoutClasses(array $config): string
    {
        $alignment = $config['text_alignment'] ?? 'text-center';
        $padding = $config['padding'] ?? 'py-12';

        return "{$alignment} {$padding}";
    }
}
