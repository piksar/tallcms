<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeTheme extends Command
{
    protected $signature = 'make:theme {name? : The name of the theme}
                            {--colors= : Primary colors (comma-separated)}
                            {--author= : Theme author name}
                            {--description= : Theme description}
                            {--theme-version= : Theme version}
                            {--interactive : Force interactive mode even with arguments}';

    protected $description = 'Create a new TallCMS theme with complete Laravel structure';

    public function handle(): int
    {
        // Collect theme information interactively or from arguments
        $themeInfo = $this->collectThemeInformation();
        
        $name = $themeInfo['name'];
        $slug = Str::slug($name);
        $studlyName = Str::studly($name);
        
        $themePath = base_path("themes/{$slug}");
        
        if (File::exists($themePath)) {
            $this->error("Theme '{$slug}' already exists!");
            return 1;
        }

        $this->info("Creating theme: {$name}");
        
        // Create theme directory structure
        $this->createDirectoryStructure($themePath);
        
        // Create theme.json
        $this->createThemeConfig($themePath, $themeInfo);
        
        // Create package.json
        $this->createPackageJson($themePath, $themeInfo);
        
        // Create vite.config.js
        $this->createViteConfig($themePath);
        
        // Create tailwind.config.js
        $this->createTailwindConfig($themePath, $themeInfo);
        
        // Create starter CSS files (app.css only, blocks.css will fallback to main app)
        $this->createCssFiles($themePath);
        
        // Create starter JS files
        $this->createJsFiles($themePath);
        
        // Create starter view files
        $this->createViewFiles($themePath, $studlyName);
        
        // Create .gitignore
        $this->createGitignore($themePath);

        // Clear theme cache so the new theme is immediately discoverable
        if (app()->bound('theme.manager')) {
            app('theme.manager')->clearCache();
            $this->line("✅ Theme cache cleared - theme is now discoverable");
        }

        $this->newLine();
        $this->info("✅ Theme '{$name}' created successfully!");
        $this->line("📁 Location: themes/{$slug}");
        $this->newLine();
        $this->comment("Next steps:");
        $this->line("1. Add a screenshot: themes/{$slug}/public/screenshot.png (1200×900px recommended)");
        $this->line("2. cd themes/{$slug} && npm install && npm run build");
        $this->line("3. cd ../../  # Back to project root");
        $this->line("4. php artisan theme:activate {$slug}");
        $this->newLine();
        $this->comment("Or run everything from project root:");
        $this->line("• cd themes/{$slug} && npm install && npm run build && cd ../../");
        $this->line("• php artisan theme:activate {$slug}");
        
        return 0;
    }

    protected function collectThemeInformation(): array
    {
        // Check if we should use interactive mode
        $useInteractive = $this->option('interactive') || 
                         (!$this->argument('name') && !$this->option('author') && !$this->option('description'));

        if ($useInteractive) {
            $this->info('🎨 TallCMS Theme Creator');
            $this->line('Let\'s create a beautiful theme! I\'ll ask you a few questions to get started.');
            $this->newLine();

            // Theme name
            $name = $this->argument('name') ?: $this->ask('What is the name of your theme?', 'My Awesome Theme');
            
            // Theme description
            $description = $this->option('description') ?: $this->ask(
                'Provide a brief description of your theme',
                'A beautiful and modern theme for TallCMS'
            );
            
            // Theme author
            $author = $this->option('author') ?: $this->ask('Who is the author of this theme?', 'Theme Developer');
            
            // Theme version
            $version = $this->option('theme-version') ?: $this->ask('What version should we start with?', '1.0.0');
            
            // Parent theme selection
            $this->newLine();
            $this->line('🔗 Parent Theme (Optional):');
            $availableThemes = \App\Models\Theme::all();
            $parentOptions = ['None' => 'None (standalone theme)'];
            
            foreach ($availableThemes as $theme) {
                if ($theme->slug !== Str::slug($name)) { // Don't allow self as parent
                    $parentOptions[$theme->slug] = "{$theme->name} ({$theme->slug})";
                }
            }
            
            $parentChoice = 'None';
            if (count($parentOptions) > 1) {
                $parentChoice = $this->choice(
                    'Select a parent theme (themes inherit views and assets from parents)',
                    array_values($parentOptions),
                    array_values($parentOptions)[0]
                );
            }
            
            $parent = array_search($parentChoice, $parentOptions);
            $parent = $parent === 'None' ? null : $parent;
            
            // Color scheme selection
            $colorSchemes = [
                'blue' => 'Blue (Professional and trustworthy)',
                'indigo' => 'Indigo (Modern and tech-focused)', 
                'purple' => 'Purple (Creative and innovative)',
                'pink' => 'Pink (Playful and approachable)',
                'red' => 'Red (Bold and energetic)',
                'orange' => 'Orange (Warm and friendly)',
                'yellow' => 'Yellow (Bright and optimistic)',
                'green' => 'Green (Natural and sustainable)',
                'teal' => 'Teal (Calm and sophisticated)',
                'cyan' => 'Cyan (Fresh and modern)',
                'gray' => 'Gray (Neutral and minimal)',
                'custom' => 'Custom (I\'ll provide my own colors)'
            ];

            $this->newLine();
            $this->line('🎨 Choose a primary color scheme for your theme:');
            $colorChoice = $this->choice(
                'Select your primary color',
                array_values($colorSchemes),
                array_values($colorSchemes)[0]
            );

            // Extract color name from choice
            $primaryColor = array_search($colorChoice, $colorSchemes);
            
            if ($primaryColor === 'custom') {
                $primaryColor = $this->ask('Enter your primary color (hex code)', '#3b82f6');
            }

            return [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'author' => $author,
                'version' => $version,
                'parent' => $parent,
                'primary_color' => $primaryColor,
            ];
        }

        // Non-interactive mode - use arguments and options
        $name = $this->argument('name') ?: 'My Theme';
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->option('description') ?: 'A beautiful TallCMS theme',
            'author' => $this->option('author') ?: 'Theme Developer',
            'version' => $this->option('theme-version') ?: '1.0.0',
            'parent' => null, // Non-interactive mode doesn't support parent selection
            'primary_color' => 'blue',
        ];
    }

    protected function createDirectoryStructure(string $themePath): void
    {
        $directories = [
            'public/css',
            'public/js', 
            'public/img',
            'public/build',
            'resources/views/layouts',
            'resources/views/cms/blocks',
            'resources/views/components',
            'resources/css',
            'resources/js',
            'resources/img'
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$themePath}/{$dir}", 0755, true);
            $this->line("Created: {$dir}");
        }
    }

    protected function createThemeConfig(string $themePath, array $themeInfo): void
    {
        $primaryColors = $this->getColorPalette($themeInfo['primary_color']);
        
        $config = [
            'name' => $themeInfo['name'],
            'slug' => $themeInfo['slug'],
            'version' => $themeInfo['version'],
            'description' => $themeInfo['description'],
            'author' => $themeInfo['author'],
            'tailwind' => [
                'colors' => [
                    'primary' => $primaryColors
                ]
            ],
            'screenshots' => [
                'primary' => 'screenshot.png',
            ],
            'supports' => [
                'dark_mode' => true,
                'responsive' => true,
                'animations' => true,
                'custom_fonts' => false
            ],
            'build' => [
                'css' => 'resources/css/app.css',
                'js' => 'resources/js/app.js',
                'output' => 'public'
            ]
        ];

        // Add parent if specified
        if (!empty($themeInfo['parent'])) {
            $config['parent'] = $themeInfo['parent'];
        }

        File::put("{$themePath}/theme.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line("Created: theme.json");
    }

    protected function createPackageJson(string $themePath, array $themeInfo): void
    {
        $packageJson = [
            'name' => "tallcms-theme-{$themeInfo['slug']}",
            'version' => $themeInfo['version'],
            'description' => $themeInfo['description'],
            'author' => $themeInfo['author'],
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build'
            ],
            'devDependencies' => [
                '@tailwindcss/vite' => '^4.1.18',
                'laravel-vite-plugin' => '^2.0.0',
                'tailwindcss' => '^4.1.18',
                'vite' => '^7.0.7'
            ]
        ];


        File::put("{$themePath}/package.json", json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line("Created: package.json");
    }

    protected function createViteConfig(string $themePath): void
    {
        $viteConfig = <<<'JS'
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    build: {
        outDir: 'public/build',
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            publicDirectory: 'public',
        }),
    ],
})
JS;

        File::put("{$themePath}/vite.config.js", $viteConfig);
        $this->line("Created: vite.config.js");
    }

    protected function createTailwindConfig(string $themePath, array $themeInfo): void
    {
        $primaryColors = $this->getColorPalette($themeInfo['primary_color']);
        
        $colorLines = [];
        foreach ($primaryColors as $shade => $color) {
            $colorLines[] = "                    {$shade}: '{$color}'";
        }
        $colorConfig = implode(",\n", $colorLines);
        
        $tailwindConfig = <<<JS
import defaultTheme from 'tailwindcss/defaultTheme'

export default {
    content: [
        './resources/views/**/*.blade.php',
        '../../resources/views/**/*.blade.php',
        '../../app/Filament/**/*.php'
    ],
    theme: {
        extend: {
            colors: {
                primary: {
{$colorConfig}
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        }
    }
}
JS;

        File::put("{$themePath}/tailwind.config.js", $tailwindConfig);
        $this->line("Created: tailwind.config.js");
    }

    protected function createCssFiles(string $themePath): void
    {
        // Main app.css
        $appCss = <<<'CSS'
@import 'tailwindcss';

/* Theme-specific styles */
:root {
    --theme-primary: theme('colors.primary.600');
    --theme-primary-hover: theme('colors.primary.700');
}

/* Custom theme utilities */
.theme-gradient {
    background: linear-gradient(135deg, theme('colors.primary.500'), theme('colors.primary.600'));
}

.theme-text-gradient {
    background: linear-gradient(135deg, theme('colors.primary.500'), theme('colors.primary.700'));
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
CSS;

        File::put("{$themePath}/resources/css/app.css", $appCss);
        $this->line("Created: resources/css/app.css");
        
        $this->comment("Note: blocks.css will automatically fallback to main app - no need to create theme-specific version");
    }

    protected function createJsFiles(string $themePath): void
    {
        $appJs = <<<'JS'
// Theme-specific JavaScript

// TallCMS Core Components - Required for native blocks (contact form, galleries, etc.)
// Do not remove this import unless you're providing your own implementations
import '../../../../resources/js/tallcms';

// Add any theme-specific functionality here
// For example: animations, interactions, custom Alpine components, etc.
JS;

        File::put("{$themePath}/resources/js/app.js", $appJs);
        $this->line("Created: resources/js/app.js");
    }

    protected function createViewFiles(string $themePath, string $studlyName): void
    {
        // Create layout override
        $layout = $this->getLayoutTemplate($studlyName);
        File::put("{$themePath}/resources/views/layouts/app.blade.php", $layout);
        $this->line("Created: resources/views/layouts/app.blade.php");

        // Create sample component (optional)
        $component = $this->getComponentTemplate($studlyName);
        File::put("{$themePath}/resources/views/components/hero-section.blade.php", $component);
        $this->line("Created: resources/views/components/hero-section.blade.php");
    }

    protected function createGitignore(string $themePath): void
    {
        $gitignore = <<<'GITIGNORE'
# Dependencies
node_modules/

# Build outputs
public/build/
public/css/
public/js/

# IDE files
.vscode/
.idea/

# OS files
.DS_Store
Thumbs.db

# Logs
npm-debug.log*
yarn-debug.log*
yarn-error.log*
GITIGNORE;

        File::put("{$themePath}/.gitignore", $gitignore);
        $this->line("Created: .gitignore");
    }

    protected function getLayoutTemplate(string $studlyName): string
    {
        return <<<BLADE
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ \$title ?? config('app.name') }}</title>
    
    @if(isset(\$description))
        <meta name="description" content="{{ \$description }}">
    @endif
    
    @if(isset(\$featuredImage))
        <meta property="og:image" content="{{ Storage::url(\$featuredImage) }}">
        <meta property="twitter:image" content="{{ Storage::url(\$featuredImage) }}">
    @endif
    
    <meta property="og:title" content="{{ \$title ?? config('app.name') }}">
    <meta property="og:description" content="{{ \$description ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ \$title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ \$description ?? '' }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Theme Assets -->
    @themeVite(['resources/css/app.css', 'resources/css/blocks.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-inter antialiased bg-white">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="absolute top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
                <div class="flex justify-between h-20">
                    
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            {{ config('app.name') }}
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-8">
                        <x-menu location="header" style="horizontal" class="flex items-center space-x-8" />
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button @click="open = !open" class="text-gray-700 hover:text-gray-900 p-2 transition-colors duration-200">
                            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 x-cloak 
                 class="md:hidden bg-white/95 backdrop-blur-md shadow-lg border-t border-gray-100">
                <div class="px-4 py-4 space-y-3">
                    <x-menu location="header" style="vertical" />
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main>
            {{ \$slot }}
        </main>
        
        <!-- Theme Footer -->
        <footer class="bg-gray-50">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-16">
                <div class="text-center">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">{{ config('app.name') }}</h4>
                    <p class="text-gray-600 text-sm mb-6">
                        Powered by TallCMS with {$studlyName} Theme
                    </p>
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <p class="text-sm text-gray-500">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    @livewireScripts
</body>
</html>
BLADE;
    }

    protected function getBlockTemplate(string $blockName, string $studlyName): string
    {
        $blockClass = Str::studly($blockName);
        
        return <<<BLADE
{{-- {$studlyName} Theme Override for {$blockClass} --}}
{{-- This template overrides the default block template --}}

@php
    // Get theme presets
    \$textPresets = theme_text_presets();
    \$textPreset = \$textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];
    
    // Theme-specific custom properties
    \$customProperties = collect([
        '--block-heading-color: ' . \$textPreset['heading'],
        '--block-text-color: ' . \$textPreset['description'],
    ])->join('; ') . ';';
@endphp

<section class="{$blockName} theme-section" style="{{ \$customProperties }}">
    <div class="theme-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- Theme-specific block content --}}
        <div class="theme-card p-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-4 theme-text-gradient">
                    {$studlyName} Theme - {$blockClass}
                </h2>
                <p class="text-lg text-gray-600">
                    This is a theme override for the {$blockClass}. 
                    Customize this template to match your {$studlyName} theme design.
                </p>
            </div>
        </div>
    </div>
</section>
BLADE;
    }

    protected function getComponentTemplate(string $studlyName): string
    {
        return <<<BLADE
{{-- {$studlyName} Theme - Hero Section Component --}}

<section class="hero-section bg-gradient-to-r from-primary-600 to-primary-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Welcome to {$studlyName}
            </h1>
            <p class="text-xl text-primary-100 mb-8">
                A beautiful theme for your TallCMS website
            </p>
            <div class="space-x-4">
                <button class="theme-button-primary bg-white text-primary-600 hover:bg-gray-50">
                    Get Started
                </button>
                <button class="theme-button-secondary bg-transparent border-2 border-white text-white hover:bg-white hover:text-primary-600">
                    Learn More
                </button>
            </div>
        </div>
    </div>
</section>
BLADE;
    }

    protected function getColorPalette(string $colorName): array
    {
        // Define color palettes for different color schemes
        $colorPalettes = [
            'blue' => [
                50 => '#eff6ff',
                100 => '#dbeafe',
                200 => '#bfdbfe',
                300 => '#93c5fd',
                400 => '#60a5fa',
                500 => '#3b82f6',
                600 => '#2563eb',
                700 => '#1d4ed8',
                800 => '#1e40af',
                900 => '#1e3a8a',
                950 => '#172554'
            ],
            'indigo' => [
                50 => '#eef2ff',
                100 => '#e0e7ff',
                200 => '#c7d2fe',
                300 => '#a5b4fc',
                400 => '#818cf8',
                500 => '#6366f1',
                600 => '#4f46e5',
                700 => '#4338ca',
                800 => '#3730a3',
                900 => '#312e81',
                950 => '#1e1b4b'
            ],
            'purple' => [
                50 => '#faf5ff',
                100 => '#f3e8ff',
                200 => '#e9d5ff',
                300 => '#d8b4fe',
                400 => '#c084fc',
                500 => '#a855f7',
                600 => '#9333ea',
                700 => '#7c3aed',
                800 => '#6b21a8',
                900 => '#581c87',
                950 => '#3b0764'
            ],
            'pink' => [
                50 => '#fdf2f8',
                100 => '#fce7f3',
                200 => '#fbcfe8',
                300 => '#f9a8d4',
                400 => '#f472b6',
                500 => '#ec4899',
                600 => '#db2777',
                700 => '#be185d',
                800 => '#9d174d',
                900 => '#831843',
                950 => '#500724'
            ],
            'red' => [
                50 => '#fef2f2',
                100 => '#fee2e2',
                200 => '#fecaca',
                300 => '#fca5a5',
                400 => '#f87171',
                500 => '#ef4444',
                600 => '#dc2626',
                700 => '#b91c1c',
                800 => '#991b1b',
                900 => '#7f1d1d',
                950 => '#450a0a'
            ],
            'orange' => [
                50 => '#fff7ed',
                100 => '#ffedd5',
                200 => '#fed7aa',
                300 => '#fdba74',
                400 => '#fb923c',
                500 => '#f97316',
                600 => '#ea580c',
                700 => '#c2410c',
                800 => '#9a3412',
                900 => '#7c2d12',
                950 => '#431407'
            ],
            'yellow' => [
                50 => '#fefce8',
                100 => '#fef9c3',
                200 => '#fef08a',
                300 => '#fde047',
                400 => '#facc15',
                500 => '#eab308',
                600 => '#ca8a04',
                700 => '#a16207',
                800 => '#854d0e',
                900 => '#713f12',
                950 => '#422006'
            ],
            'green' => [
                50 => '#f0fdf4',
                100 => '#dcfce7',
                200 => '#bbf7d0',
                300 => '#86efac',
                400 => '#4ade80',
                500 => '#22c55e',
                600 => '#16a34a',
                700 => '#15803d',
                800 => '#166534',
                900 => '#14532d',
                950 => '#052e16'
            ],
            'teal' => [
                50 => '#f0fdfa',
                100 => '#ccfbf1',
                200 => '#99f6e4',
                300 => '#5eead4',
                400 => '#2dd4bf',
                500 => '#14b8a6',
                600 => '#0d9488',
                700 => '#0f766e',
                800 => '#115e59',
                900 => '#134e4a',
                950 => '#042f2e'
            ],
            'cyan' => [
                50 => '#ecfeff',
                100 => '#cffafe',
                200 => '#a5f3fc',
                300 => '#67e8f9',
                400 => '#22d3ee',
                500 => '#06b6d4',
                600 => '#0891b2',
                700 => '#0e7490',
                800 => '#155e75',
                900 => '#164e63',
                950 => '#083344'
            ],
            'gray' => [
                50 => '#f9fafb',
                100 => '#f3f4f6',
                200 => '#e5e7eb',
                300 => '#d1d5db',
                400 => '#9ca3af',
                500 => '#6b7280',
                600 => '#4b5563',
                700 => '#374151',
                800 => '#1f2937',
                900 => '#111827',
                950 => '#030712'
            ]
        ];

        // If it's a custom hex color, generate shades
        if (str_starts_with($colorName, '#')) {
            return $this->generateShadesFromHex($colorName);
        }

        return $colorPalettes[$colorName] ?? $colorPalettes['blue'];
    }

    protected function generateShadesFromHex(string $hex): array
    {
        // Simple shade generation from a base hex color
        // This is a simplified version - in production you might want a more sophisticated algorithm
        return [
            50 => $this->adjustBrightness($hex, 0.95),
            100 => $this->adjustBrightness($hex, 0.9),
            200 => $this->adjustBrightness($hex, 0.8),
            300 => $this->adjustBrightness($hex, 0.6),
            400 => $this->adjustBrightness($hex, 0.4),
            500 => $hex,
            600 => $this->adjustBrightness($hex, -0.1),
            700 => $this->adjustBrightness($hex, -0.2),
            800 => $this->adjustBrightness($hex, -0.3),
            900 => $this->adjustBrightness($hex, -0.4),
            950 => $this->adjustBrightness($hex, -0.5)
        ];
    }

    protected function adjustBrightness(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];

        foreach ($rgb as &$channel) {
            if ($amount > 0) {
                // Lighten: mix with white
                $channel = min(255, $channel + (255 - $channel) * $amount);
            } else {
                // Darken: reduce brightness
                $channel = max(0, $channel * (1 + $amount));
            }
        }

        return sprintf('#%02x%02x%02x', round($rgb[0]), round($rgb[1]), round($rgb[2]));
    }
}