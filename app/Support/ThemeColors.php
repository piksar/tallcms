<?php

namespace App\Support;

use App\Contracts\ThemeInterface;

class ThemeColors implements ThemeInterface
{
    public function getId(): string
    {
        return 'default';
    }

    public function getName(): string
    {
        return 'Default Theme';
    }

    public function supportsDarkMode(): bool
    {
        return true;
    }

    public function getCssFilePath(): string
    {
        return 'build/assets/app.css';
    }

    public function getConfigurationSchema(): array
    {
        return [
            'primary_color' => [
                'type' => 'color',
                'label' => 'Primary Color',
                'default' => '#d97706',
            ],
            'enable_dark_mode' => [
                'type' => 'boolean', 
                'label' => 'Enable Dark Mode',
                'default' => true,
            ],
        ];
    }

    /**
     * Get the unified color palette for the application
     */
    public static function getColors(): array
    {
        return [
            'primary' => [
                50 => 'rgb(255, 251, 235)',
                100 => 'rgb(254, 243, 199)',
                200 => 'rgb(253, 230, 138)',
                300 => 'rgb(252, 211, 77)',
                400 => 'rgb(251, 191, 36)',
                500 => 'rgb(245, 158, 11)',
                600 => 'rgb(217, 119, 6)',
                700 => 'rgb(180, 83, 9)',
                800 => 'rgb(146, 64, 14)',
                900 => 'rgb(120, 53, 15)',
                950 => 'rgb(69, 26, 3)',
            ],
            'secondary' => [
                50 => 'rgb(248, 250, 252)',
                100 => 'rgb(241, 245, 249)',
                200 => 'rgb(226, 232, 240)',
                300 => 'rgb(203, 213, 225)',
                400 => 'rgb(148, 163, 184)',
                500 => 'rgb(100, 116, 139)',
                600 => 'rgb(71, 85, 105)',
                700 => 'rgb(51, 65, 85)',
                800 => 'rgb(30, 41, 59)',
                900 => 'rgb(15, 23, 42)',
                950 => 'rgb(2, 6, 23)',
            ],
            'success' => [
                50 => 'rgb(240, 253, 244)',
                100 => 'rgb(220, 252, 231)',
                200 => 'rgb(187, 247, 208)',
                300 => 'rgb(134, 239, 172)',
                400 => 'rgb(74, 222, 128)',
                500 => 'rgb(34, 197, 94)',
                600 => 'rgb(22, 163, 74)',
                700 => 'rgb(21, 128, 61)',
                800 => 'rgb(22, 101, 52)',
                900 => 'rgb(20, 83, 45)',
                950 => 'rgb(5, 46, 22)',
            ],
            'warning' => [
                50 => 'rgb(255, 251, 235)',
                100 => 'rgb(254, 243, 199)',
                200 => 'rgb(253, 230, 138)',
                300 => 'rgb(252, 211, 77)',
                400 => 'rgb(251, 191, 36)',
                500 => 'rgb(245, 158, 11)',
                600 => 'rgb(217, 119, 6)',
                700 => 'rgb(180, 83, 9)',
                800 => 'rgb(146, 64, 14)',
                900 => 'rgb(120, 53, 15)',
                950 => 'rgb(69, 26, 3)',
            ],
            'danger' => [
                50 => 'rgb(254, 242, 242)',
                100 => 'rgb(254, 226, 226)',
                200 => 'rgb(254, 202, 202)',
                300 => 'rgb(252, 165, 165)',
                400 => 'rgb(248, 113, 113)',
                500 => 'rgb(239, 68, 68)',
                600 => 'rgb(220, 38, 38)',
                700 => 'rgb(185, 28, 28)',
                800 => 'rgb(153, 27, 27)',
                900 => 'rgb(127, 29, 29)',
                950 => 'rgb(69, 10, 10)',
            ],
            'neutral' => [
                50 => 'rgb(248, 250, 252)',
                100 => 'rgb(241, 245, 249)',
                200 => 'rgb(226, 232, 240)',
                300 => 'rgb(203, 213, 225)',
                400 => 'rgb(148, 163, 184)',
                500 => 'rgb(100, 116, 139)',
                600 => 'rgb(71, 85, 105)',
                700 => 'rgb(51, 65, 85)',
                800 => 'rgb(30, 41, 59)',
                900 => 'rgb(15, 23, 42)',
                950 => 'rgb(2, 6, 23)',
            ],
        ];
    }

    /**
     * Get the theme's color palette (interface method)
     */
    public function getColorPalette(): array
    {
        return self::getColors();
    }

    /**
     * Get preset button colors for rich editor components (interface method)
     */
    public function getButtonPresets(): array
    {
        return self::getStaticButtonPresets();
    }

    /**
     * Get text color presets (interface method)
     */
    public function getTextPresets(): array
    {
        return self::getStaticTextPresets();
    }

    /**
     * Get the theme's CSS custom properties (interface method)
     */
    public function getCssCustomProperties(): string
    {
        return self::getStaticCssCustomProperties();
    }

    /**
     * Get padding presets (interface method)
     */
    public function getPaddingPresets(): array
    {
        return self::getStaticPaddingPresets();
    }

    /**
     * Static methods for backward compatibility
     */

    /**
     * Get colors formatted for Filament
     */
    public static function getFilamentColors(): array
    {
        $colors = self::getColors();
        
        return [
            'primary' => $colors['primary'],
            'secondary' => $colors['secondary'],
            'success' => $colors['success'],
            'warning' => $colors['warning'],
            'danger' => $colors['danger'],
            'neutral' => $colors['neutral'],
        ];
    }

    /**
     * Get the primary color for Filament panel
     */
    public static function getPrimaryColor(): array
    {
        return self::getColors()['primary'];
    }

    /**
     * Get CSS custom properties for frontend
     */
    public static function getStaticCssCustomProperties(): string
    {
        $colors = self::getColors();
        $css = '';
        
        foreach ($colors as $name => $shades) {
            foreach ($shades as $shade => $value) {
                $css .= "    --color-{$name}-{$shade}: {$value};\n";
            }
        }
        
        return $css;
    }

    /**
     * Get preset button colors for components
     */
    public static function getStaticButtonPresets(): array
    {
        return [
            'primary' => [
                'bg' => self::getColors()['primary'][600],
                'text' => 'rgb(255, 255, 255)',
                'hover' => self::getColors()['primary'][700],
            ],
            'secondary' => [
                'bg' => self::getColors()['secondary'][600],
                'text' => 'rgb(255, 255, 255)',
                'hover' => self::getColors()['secondary'][700],
            ],
            'success' => [
                'bg' => self::getColors()['success'][600],
                'text' => 'rgb(255, 255, 255)',
                'hover' => self::getColors()['success'][700],
            ],
            'warning' => [
                'bg' => self::getColors()['warning'][600],
                'text' => 'rgb(0, 0, 0)',
                'hover' => self::getColors()['warning'][700],
            ],
            'danger' => [
                'bg' => self::getColors()['danger'][600],
                'text' => 'rgb(255, 255, 255)',
                'hover' => self::getColors()['danger'][700],
            ],
            'neutral' => [
                'bg' => self::getColors()['neutral'][600],
                'text' => 'rgb(255, 255, 255)',
                'hover' => self::getColors()['neutral'][700],
            ],
        ];
    }

    /**
     * Get text color presets
     */
    public static function getStaticTextPresets(): array
    {
        $colors = self::getColors();
        
        return [
            'primary' => [
                'heading' => $colors['neutral'][900],
                'description' => $colors['neutral'][600],
            ],
            'secondary' => [
                'heading' => $colors['neutral'][600],
                'description' => $colors['neutral'][500],
            ],
            'muted' => [
                'heading' => $colors['neutral'][500],
                'description' => $colors['neutral'][400],
            ],
            'inverse' => [
                'heading' => 'rgb(255, 255, 255)',
                'description' => $colors['neutral'][100],
            ],
        ];
    }

    /**
     * Get padding size presets with actual CSS values for admin preview compatibility
     */
    public static function getStaticPaddingPresets(): array
    {
        return [
            'small' => [
                'padding' => '2rem 1.5rem',
                'mobile_padding' => '2rem 1.5rem',
                'tablet_padding' => '2rem 2rem',
                'desktop_padding' => '2rem 3rem',
                'classes' => 'py-8 px-6 sm:px-8',
            ],
            'medium' => [
                'padding' => '4rem 1.5rem',
                'mobile_padding' => '4rem 1.5rem',
                'tablet_padding' => '4rem 3rem',
                'desktop_padding' => '4rem 4rem',
                'classes' => 'py-16 px-6 sm:px-12 lg:px-16',
            ],
            'large' => [
                'padding' => '6rem 1.5rem',
                'mobile_padding' => '6rem 1.5rem',
                'tablet_padding' => '6rem 3rem',
                'desktop_padding' => '6rem 5rem',
                'classes' => 'py-24 px-6 sm:px-12 lg:px-20',
            ],
            'xl' => [
                'padding' => '8rem 1.5rem',
                'mobile_padding' => '8rem 1.5rem',
                'tablet_padding' => '8rem 3rem',
                'desktop_padding' => '8rem 6rem',
                'classes' => 'py-32 px-6 sm:px-12 lg:px-24',
            ],
        ];
    }
}