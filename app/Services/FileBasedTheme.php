<?php

namespace App\Services;

use App\Contracts\ThemeInterface;
use App\Models\Theme;

class FileBasedTheme implements ThemeInterface
{
    protected Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    public function getId(): string
    {
        return $this->theme->slug;
    }

    public function getName(): string
    {
        return $this->theme->name;
    }

    public function getColorPalette(): array
    {
        $tailwindColors = $this->theme->getTailwindConfig()['colors'] ?? [];
        $primaryColors = $tailwindColors['primary'] ?? [];
        
        // Build palette in the expected format
        return [
            'primary' => $this->buildColorShades($primaryColors, '#2563eb'),
            'secondary' => $this->buildColorShades($tailwindColors['secondary'] ?? [], '#6b7280'),
            'success' => $this->buildColorShades($tailwindColors['success'] ?? [], '#10b981'),
            'warning' => $this->buildColorShades($tailwindColors['warning'] ?? [], '#f59e0b'),
            'danger' => $this->buildColorShades($tailwindColors['danger'] ?? [], '#ef4444'),
            'neutral' => $this->buildColorShades($tailwindColors['neutral'] ?? [], '#6b7280'),
        ];
    }

    protected function buildColorShades(array $shades, string $fallback): array
    {
        // Define the complete scale we need
        $requiredShades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
        
        if (empty($shades)) {
            // Generate complete shades from fallback color
            return [
                50 => $this->lighten($fallback, 0.95),
                100 => $this->lighten($fallback, 0.9),
                200 => $this->lighten($fallback, 0.8),
                300 => $this->lighten($fallback, 0.6),
                400 => $this->lighten($fallback, 0.4),
                500 => $fallback,
                600 => $this->darken($fallback, 0.1),
                700 => $this->darken($fallback, 0.2),
                800 => $this->darken($fallback, 0.3),
                900 => $this->darken($fallback, 0.4),
                950 => $this->darken($fallback, 0.5),
            ];
        }

        // Normalize partial shade sets to complete scale
        $normalizedShades = [];
        
        // Find the base color (500 or closest available)
        $baseColor = $shades[500] ?? $shades[600] ?? $shades[400] ?? $fallback;
        if (!$baseColor && !empty($shades)) {
            // Use the middle value from available shades
            $availableShades = array_keys($shades);
            sort($availableShades);
            $middleIndex = floor(count($availableShades) / 2);
            $baseColor = $shades[$availableShades[$middleIndex]];
        }
        
        // Build complete scale, using provided shades where available
        foreach ($requiredShades as $shade) {
            if (isset($shades[$shade])) {
                // Use provided shade
                $normalizedShades[$shade] = $shades[$shade];
            } else {
                // Generate missing shade based on position relative to 500
                if ($shade < 500) {
                    // Lighter shades
                    $amount = (500 - $shade) / 500 * 0.95; // Scale from 0 to 0.95
                    $normalizedShades[$shade] = $this->lighten($baseColor, $amount);
                } elseif ($shade > 500) {
                    // Darker shades
                    $amount = ($shade - 500) / 450 * 0.5; // Scale from 0 to 0.5
                    $normalizedShades[$shade] = $this->darken($baseColor, $amount);
                } else {
                    // 500 shade
                    $normalizedShades[$shade] = $baseColor;
                }
            }
        }

        return $normalizedShades;
    }

    protected function lighten(string $color, float $amount): string
    {
        // Convert hex to RGB
        $rgb = $this->hexToRgb($color);
        if (!$rgb) return $color;
        
        // Lighten by mixing with white
        $rgb[0] = min(255, $rgb[0] + (255 - $rgb[0]) * $amount);
        $rgb[1] = min(255, $rgb[1] + (255 - $rgb[1]) * $amount);
        $rgb[2] = min(255, $rgb[2] + (255 - $rgb[2]) * $amount);
        
        return $this->rgbToHex($rgb);
    }

    protected function darken(string $color, float $amount): string
    {
        // Convert hex to RGB
        $rgb = $this->hexToRgb($color);
        if (!$rgb) return $color;
        
        // Darken by reducing each channel
        $rgb[0] = max(0, $rgb[0] * (1 - $amount));
        $rgb[1] = max(0, $rgb[1] * (1 - $amount));
        $rgb[2] = max(0, $rgb[2] * (1 - $amount));
        
        return $this->rgbToHex($rgb);
    }

    protected function hexToRgb(string $hex): ?array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Handle 3 or 6 character hex
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        if (strlen($hex) != 6 || !ctype_xdigit($hex)) {
            return null;
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    protected function rgbToHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', 
            (int)round($rgb[0]), 
            (int)round($rgb[1]), 
            (int)round($rgb[2])
        );
    }

    public function getButtonPresets(): array
    {
        $colors = $this->getColorPalette();
        $primary = $colors['primary'];
        $secondary = $colors['secondary']; 
        $success = $colors['success'];
        $warning = $colors['warning'];
        $danger = $colors['danger'];

        return [
            'white' => [
                'bg' => '#ffffff',
                'text' => '#374151',
                'hover' => '#f9fafb',
                'border' => '#d1d5db',
            ],
            'primary' => [
                'bg' => $primary[600] ?? '#2563eb',
                'text' => '#ffffff',
                'hover' => $primary[700] ?? '#1d4ed8',
                'border' => $primary[600] ?? '#2563eb',
            ],
            'secondary' => [
                'bg' => $secondary[500] ?? '#6b7280',
                'text' => '#ffffff',
                'hover' => $secondary[600] ?? '#4b5563',
                'border' => $secondary[500] ?? '#6b7280',
            ],
            'success' => [
                'bg' => $success[600] ?? '#059669',
                'text' => '#ffffff',
                'hover' => $success[700] ?? '#047857',
                'border' => $success[600] ?? '#059669',
            ],
            'warning' => [
                'bg' => $warning[500] ?? '#f59e0b',
                'text' => '#ffffff',
                'hover' => $warning[600] ?? '#d97706',
                'border' => $warning[500] ?? '#f59e0b',
            ],
            'danger' => [
                'bg' => $danger[600] ?? '#dc2626',
                'text' => '#ffffff',
                'hover' => $danger[700] ?? '#b91c1c',
                'border' => $danger[600] ?? '#dc2626',
            ],
            'neutral' => [
                'bg' => $colors['neutral'][500] ?? '#6b7280',
                'text' => '#ffffff',
                'hover' => $colors['neutral'][600] ?? '#4b5563',
                'border' => $colors['neutral'][500] ?? '#6b7280',
            ],
            'dark' => [
                'bg' => '#1f2937',
                'text' => '#ffffff',
                'hover' => '#111827',
                'border' => '#1f2937',
            ],
            'outline-white' => [
                'bg' => 'transparent',
                'text' => '#ffffff',
                'hover' => 'rgba(255, 255, 255, 0.1)',
                'border' => '#ffffff',
            ],
            'outline-primary' => [
                'bg' => 'transparent',
                'text' => $primary[600] ?? '#2563eb',
                'hover' => $primary[50] ?? '#eff6ff',
                'border' => $primary[600] ?? '#2563eb',
            ],
            'outline-success' => [
                'bg' => 'transparent',
                'text' => $success[600] ?? '#059669',
                'hover' => $success[50] ?? '#ecfdf5',
                'border' => $success[600] ?? '#059669',
            ],
            'outline-warning' => [
                'bg' => 'transparent',
                'text' => $warning[600] ?? '#d97706',
                'hover' => $warning[50] ?? '#fffbeb',
                'border' => $warning[600] ?? '#d97706',
            ],
            'outline-danger' => [
                'bg' => 'transparent',
                'text' => $danger[600] ?? '#dc2626',
                'hover' => $danger[50] ?? '#fef2f2',
                'border' => $danger[600] ?? '#dc2626',
            ],
            'filled-white' => [
                'bg' => '#ffffff',
                'text' => '#111827',
                'hover' => '#f9fafb',
                'border' => '#ffffff',
            ],
            'filled-primary' => [
                'bg' => $primary[600] ?? '#2563eb',
                'text' => '#ffffff',
                'hover' => $primary[700] ?? '#1d4ed8',
                'border' => $primary[600] ?? '#2563eb',
            ],
        ];
    }

    public function getTextPresets(): array
    {
        $colors = $this->getColorPalette();
        $primary = $colors['primary'];
        $neutral = $colors['neutral'];

        return [
            'primary' => [
                'heading' => '#111827',
                'description' => '#374151',
                'link' => $primary[600] ?? '#2563eb',
                'link_hover' => $primary[700] ?? '#1d4ed8',
            ],
            'secondary' => [
                'heading' => '#1f2937',
                'description' => '#4b5563',
                'link' => '#6366f1',
                'link_hover' => '#4f46e5',
            ],
            'muted' => [
                'heading' => '#374151',
                'description' => '#6b7280',
                'link' => '#6b7280',
                'link_hover' => '#374151',
            ],
            'inverse' => [
                'heading' => '#ffffff',
                'description' => '#f3f4f6',
                'link' => $primary[300] ?? '#93c5fd',
                'link_hover' => $primary[200] ?? '#bfdbfe',
            ],
        ];
    }

    public function getPaddingPresets(): array
    {
        return [
            'small' => [
                'padding' => '0.5rem 1rem',
                'classes' => 'py-2 px-4',
            ],
            'medium' => [
                'padding' => '0.75rem 1.5rem',
                'classes' => 'py-3 px-6',
            ],
            'large' => [
                'padding' => '1rem 2rem',
                'classes' => 'py-4 px-8',
            ],
            'xl' => [
                'padding' => '1.5rem 3rem',
                'classes' => 'py-6 px-12',
            ],
        ];
    }

    public function getSlug(): string
    {
        return $this->theme->slug;
    }

    public function getCssCustomProperties(): string
    {
        $colors = $this->getColorPalette();
        $primary = $colors['primary'];
        
        $properties = [
            '--color-primary-50' => $primary[50] ?? '#eff6ff',
            '--color-primary-500' => $primary[500] ?? '#3b82f6',
            '--color-primary-600' => $primary[600] ?? '#2563eb',
            '--color-primary-700' => $primary[700] ?? '#1d4ed8',
            '--color-primary-900' => $primary[900] ?? '#1e3a8a',
        ];

        return implode("\n", array_map(
            fn($key, $value) => "  {$key}: {$value};",
            array_keys($properties),
            $properties
        ));
    }

    public function getCssFilePath(): string
    {
        $manifestPath = public_path("themes/{$this->theme->slug}/build/manifest.json");
        
        // If manifest exists, read the actual hashed filename
        if (file_exists($manifestPath)) {
            try {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (isset($manifest['resources/css/app.css']['file'])) {
                    return "themes/{$this->theme->slug}/build/{$manifest['resources/css/app.css']['file']}";
                }
            } catch (\Exception) {
                // Fall through to default
            }
        }
        
        // Fallback to expected path (may 404 in production if not built)
        return "themes/{$this->theme->slug}/build/assets/app.css";
    }

    public function supportsDarkMode(): bool
    {
        return $this->theme->supports('dark_mode');
    }

    public function getConfigurationSchema(): array
    {
        return [
            'colors' => [
                'type' => 'color_palette',
                'label' => 'Color Palette',
                'default' => $this->getColorPalette(),
            ],
            'dark_mode' => [
                'type' => 'boolean',
                'label' => 'Dark Mode Support',
                'default' => $this->supportsDarkMode(),
            ],
        ];
    }
}