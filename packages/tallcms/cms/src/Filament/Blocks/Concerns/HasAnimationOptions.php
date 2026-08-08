<?php

namespace TallCms\Cms\Filament\Blocks\Concerns;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Provides configurable entrance animations for blocks.
 *
 * Core (free) animations: Fade In, Fade In Up
 * Pro animations: Fade In Down/Left/Right, Zoom In, Zoom In Up
 *
 * Animations use CSS keyframes + Alpine.js x-intersect for scroll-triggered
 * animations that respect prefers-reduced-motion.
 */
trait HasAnimationOptions
{
    /**
     * Check if TallCMS Pro is installed.
     */
    protected static function hasPro(): bool
    {
        return class_exists(\Tallcms\Pro\Filament\TallcmsProPlugin::class);
    }

    /**
     * Get animation type options.
     * Core: None, Fade In, Fade In Up
     * Pro: + Fade In Down/Left/Right, Zoom In, Zoom In Up
     */
    protected static function getAnimationTypeOptions(): array
    {
        $options = [
            '' => 'None',
            'fade-in' => 'Fade In',
            'fade-in-up' => 'Fade In Up',
        ];

        if (static::hasPro()) {
            $options['fade-in-down'] = 'Fade In Down';
            $options['fade-in-left'] = 'Fade In Left';
            $options['fade-in-right'] = 'Fade In Right';
            $options['zoom-in'] = 'Zoom In';
            $options['zoom-in-up'] = 'Zoom In Up';
        }

        return $options;
    }

    /**
     * Get animation duration options.
     * Core: Normal (0.7s), Relaxed (1s), Dramatic (1.5s)
     * Pro: + Snappy (0.3s), Quick (0.5s)
     */
    protected static function getAnimationDurationOptions(): array
    {
        $options = [
            'anim-duration-700' => 'Normal (0.7s)',
            'anim-duration-1000' => 'Relaxed (1s)',
            'anim-duration-1500' => 'Dramatic (1.5s)',
        ];

        if (static::hasPro()) {
            // Insert faster options at beginning
            $options = [
                'anim-duration-300' => 'Snappy (0.3s)',
                'anim-duration-500' => 'Quick (0.5s)',
            ] + $options;
        }

        return $options;
    }

    /**
     * Get stagger delay options (Pro only).
     */
    protected static function getStaggerDelayOptions(): array
    {
        return [
            '0' => 'None (0ms)',
            '100' => 'Short (100ms)',
            '200' => 'Medium (200ms)',
            '300' => 'Long (300ms)',
        ];
    }

    /**
     * Get the animation configuration section for the block editor.
     *
     * @param  bool  $supportsStagger  Whether this block supports staggered item animations
     */
    protected static function getAnimationSection(bool $supportsStagger = false): Section
    {
        $schema = [
            Select::make('animation_type')
                ->label(__('tallcms::fields.entrance_animation'))
                ->options(static::getAnimationTypeOptions())
                ->default('')
                ->helperText(__('tallcms::ui.t_animation_plays_when_block_scrolls_into_view')),

            Select::make('animation_duration')
                ->label(__('tallcms::fields.animation_speed'))
                ->options(static::getAnimationDurationOptions())
                ->default('anim-duration-700'),
        ];

        // Add stagger options for Pro users on blocks that support it
        if ($supportsStagger && static::hasPro()) {
            $schema[] = Toggle::make('animation_stagger')
                ->label(__('tallcms::fields.stagger_items'))
                ->helperText(__('tallcms::ui.t_animate_items_sequentially_instead_of_all_at_once'))
                ->default(false)
                ->live();

            $schema[] = Select::make('animation_stagger_delay')
                ->label(__('tallcms::fields.stagger_delay'))
                ->options(static::getStaggerDelayOptions())
                ->default('100')
                ->visible(fn ($get): bool => $get('animation_stagger') === true);
        }

        return Section::make(__('tallcms::ui.t_animation'))
            ->schema($schema)
            ->columns(2)
            ->collapsed()
            ->compact();
    }

    /**
     * Get the animation tab for the block editor.
     *
     * @param  bool  $supportsStagger  Whether this block supports staggered item animations
     */
    protected static function getAnimationTab(bool $supportsStagger = false): Tab
    {
        $schema = [
            Select::make('animation_type')
                ->label(__('tallcms::fields.entrance_animation'))
                ->options(static::getAnimationTypeOptions())
                ->default('')
                ->helperText(__('tallcms::ui.t_animation_plays_when_block_scrolls_into_view')),

            Select::make('animation_duration')
                ->label(__('tallcms::fields.animation_speed'))
                ->options(static::getAnimationDurationOptions())
                ->default('anim-duration-700'),
        ];

        // Add stagger options for Pro users on blocks that support it
        if ($supportsStagger && static::hasPro()) {
            $schema[] = Toggle::make('animation_stagger')
                ->label(__('tallcms::fields.stagger_items'))
                ->helperText(__('tallcms::ui.t_animate_items_sequentially_instead_of_all_at_once'))
                ->default(false)
                ->live();

            $schema[] = Select::make('animation_stagger_delay')
                ->label(__('tallcms::fields.stagger_delay'))
                ->options(static::getStaggerDelayOptions())
                ->default('100')
                ->visible(fn ($get): bool => $get('animation_stagger') === true);
        }

        return Tab::make(__('tallcms::ui.t_animation'))
            ->icon('heroicon-m-sparkles')
            ->schema($schema);
    }

    /**
     * Get sanitized animation configuration for rendering.
     *
     * This ensures Pro values are stripped when Pro is not installed,
     * preventing bypass via manual JSON/DB edits.
     */
    public static function getAnimationConfig(array $config): array
    {
        $hasPro = static::hasPro();

        $type = $config['animation_type'] ?? '';
        $duration = $config['animation_duration'] ?? 'anim-duration-700';

        // Valid animation types
        $coreTypes = ['fade-in', 'fade-in-up'];
        $proTypes = ['fade-in-down', 'fade-in-left', 'fade-in-right', 'zoom-in', 'zoom-in-up'];
        $allValidTypes = array_merge([''], $coreTypes, $proTypes);

        // Note: anim-duration-500 included in Core for backwards compatibility
        // (was Core in earlier versions, now Pro-only in UI but still valid)
        $coreDurations = ['anim-duration-500', 'anim-duration-700', 'anim-duration-1000', 'anim-duration-1500'];
        $proDurations = ['anim-duration-300'];
        $allValidDurations = array_merge($coreDurations, $proDurations);

        // Validate type - invalid/unknown values become '' (None)
        if (! in_array($type, $allValidTypes)) {
            $type = '';
        }

        // If valid Pro type but no Pro license → no animation
        if (! $hasPro && in_array($type, $proTypes)) {
            $type = '';
        }

        // Validate duration
        if (! in_array($duration, $allValidDurations)) {
            $duration = 'anim-duration-700';
        }
        if (! $hasPro && in_array($duration, $proDurations)) {
            $duration = 'anim-duration-700';
        }

        // Validate stagger delay to allowed values
        $allowedDelays = [0, 100, 200, 300];
        $staggerDelay = (int) ($config['animation_stagger_delay'] ?? 100);
        if (! in_array($staggerDelay, $allowedDelays, true)) {
            $staggerDelay = 100;
        }

        return [
            'animation_type' => $type,
            'animation_duration' => $duration,
            'animation_stagger' => $hasPro ? (bool) ($config['animation_stagger'] ?? false) : false,
            'animation_stagger_delay' => $hasPro ? $staggerDelay : 100,
        ];
    }
}
