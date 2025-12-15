<?php

namespace App\Contracts;

interface ThemeInterface
{
    /**
     * Get the theme's unique identifier
     */
    public function getId(): string;

    /**
     * Get the theme's display name
     */
    public function getName(): string;

    /**
     * Get the theme's color palette for components
     * 
     * @return array{
     *     primary: array<int, string>,
     *     secondary: array<int, string>,
     *     success: array<int, string>,
     *     warning: array<int, string>,
     *     danger: array<int, string>,
     *     neutral: array<int, string>
     * }
     */
    public function getColorPalette(): array;

    /**
     * Get preset button colors for rich editor components
     * 
     * @return array{
     *     primary: array{bg: string, text: string, hover: string},
     *     secondary: array{bg: string, text: string, hover: string},
     *     success: array{bg: string, text: string, hover: string},
     *     warning: array{bg: string, text: string, hover: string},
     *     danger: array{bg: string, text: string, hover: string},
     *     neutral: array{bg: string, text: string, hover: string}
     * }
     */
    public function getButtonPresets(): array;

    /**
     * Get text color presets for different contrast levels
     * 
     * @return array{
     *     primary: array{heading: string, description: string},
     *     secondary: array{heading: string, description: string},
     *     muted: array{heading: string, description: string},
     *     inverse: array{heading: string, description: string}
     * }
     */
    public function getTextPresets(): array;

    /**
     * Get the theme's CSS custom properties as a string
     */
    public function getCssCustomProperties(): string;

    /**
     * Get the theme's CSS file path (relative to public)
     */
    public function getCssFilePath(): string;

    /**
     * Get padding/spacing presets for components
     * 
     * @return array{
     *     small: array{padding: string, classes: string},
     *     medium: array{padding: string, classes: string},
     *     large: array{padding: string, classes: string},
     *     xl: array{padding: string, classes: string}
     * }
     */
    public function getPaddingPresets(): array;

    /**
     * Check if this theme supports dark mode
     */
    public function supportsDarkMode(): bool;

    /**
     * Get theme configuration options for users
     */
    public function getConfigurationSchema(): array;
}