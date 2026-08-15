<?php

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use TallCms\Cms\Models\Theme;

class MakeTheme extends Command
{
    protected $signature = 'make:theme {name? : The name of the theme}
                            {--preset= : DaisyUI preset (light, dark, cupcake, etc.) or "custom" for custom theme}
                            {--prefers-dark= : Dark mode preset (optional)}
                            {--all-presets : Include all daisyUI presets for theme-controller}
                            {--custom : Create a custom daisyUI theme with your own color palette}
                            {--author= : Theme author name}
                            {--description= : Theme description}
                            {--theme-version= : Theme version}
                            {--include-search : Scaffold a header search box component}
                            {--include-language-switcher : Scaffold a language dropdown component}
                            {--interactive : Force interactive mode even with arguments}';

    protected $description = 'Create a new TallCMS theme with daisyUI integration';

    public function handle(): int
    {
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

        // Create CSS files with daisyUI config
        $this->createCssFiles($themePath, $themeInfo);

        // Create starter JS files
        $this->createJsFiles($themePath);

        // Create starter view files
        $this->createViewFiles($themePath, $studlyName, $themeInfo);

        // Create .gitignore
        $this->createGitignore($themePath);

        // Clear theme cache
        if (app()->bound('theme.manager')) {
            app('theme.manager')->clearCache();
            $this->line('Theme cache cleared - theme is now discoverable');
        }

        $this->newLine();
        $this->info("Theme '{$name}' created successfully!");
        $this->line("Location: themes/{$slug}");
        $this->newLine();
        $this->comment('Next steps:');
        $this->line("1. Add a screenshot: themes/{$slug}/public/screenshot.png (1200x900px recommended)");
        $this->line("2. cd themes/{$slug} && npm install && npm run build");
        $this->line('3. cd ../../  # Back to project root');
        $this->line("4. php artisan theme:activate {$slug}");

        return 0;
    }

    protected function collectThemeInformation(): array
    {
        $useInteractive = $this->option('interactive') ||
                         (! $this->argument('name') && ! $this->option('author') && ! $this->option('description'));

        if ($useInteractive) {
            return $this->collectInteractively();
        }

        return $this->collectFromOptions();
    }

    protected function collectInteractively(): array
    {
        $this->info('TallCMS Theme Creator (daisyUI)');
        $this->line('Create a theme with daisyUI presets for consistent styling.');
        $this->newLine();

        // Theme name
        $name = $this->argument('name') ?: $this->ask('What is the name of your theme?', 'My Theme');

        // Theme description
        $description = $this->option('description') ?: $this->ask(
            'Provide a brief description',
            'A modern theme for TallCMS'
        );

        // Theme author
        $author = $this->option('author') ?: $this->ask('Who is the author?', 'Theme Developer');

        // Theme version
        $version = $this->option('theme-version') ?: $this->ask('Starting version?', '1.0.0');

        // Theme type selection
        $this->newLine();
        $this->line('Theme Type:');
        $modeChoice = $this->choice(
            'What type of theme do you want to create?',
            [
                '1' => 'DaisyUI - Single preset (one color scheme)',
                '2' => 'DaisyUI - All presets (theme-controller switcher)',
                '3' => 'DaisyUI - Custom theme (define your own colors)',
            ],
            '1'
        );

        // Parse mode from choice
        $mode = match ($modeChoice) {
            'DaisyUI - Single preset (one color scheme)', '1' => 'single',
            'DaisyUI - All presets (theme-controller switcher)', '2' => 'all',
            'DaisyUI - Custom theme (define your own colors)', '3' => 'custom',
            default => 'single',
        };

        // Preset selection
        $preset = 'light';
        $prefersDark = null;
        $allPresets = false;
        $customColors = null;

        if ($mode === 'custom') {
            $this->newLine();
            $this->info('Custom daisyUI theme selected.');
            $this->line('A starter color palette will be generated in your CSS file.');
            $this->line('Customize colors using @plugin "daisyui/theme" { ... }');
            $customColors = [
                'name' => Str::slug($name),
            ];
        } elseif ($mode === 'single') {
            $this->newLine();
            $this->line('Popular presets: light, dark, cupcake, bumblebee, emerald, corporate, synthwave, retro, cyberpunk, dracula, nord');
            $preset = $this->askWithValidation(
                'Which daisyUI preset?',
                'light',
                fn ($value) => $this->validatePreset($value)
            );

            // Dark mode option
            $wantsDark = $this->confirm('Enable automatic dark mode?', false);
            if ($wantsDark) {
                $prefersDark = $this->askWithValidation(
                    'Which preset for dark mode?',
                    'dark',
                    fn ($value) => $this->validatePreset($value)
                );
            }
        } else {
            $allPresets = true;
            $preset = 'light';
            $prefersDark = 'dark';
        }

        // Optional header controls — drives supports.* in theme.json and component scaffolding.
        $this->newLine();
        $this->line('Header controls (declare which optional UI controls this theme renders):');
        $includeSearch = $this->confirm('Include a search box in the header?', false);
        $includeLanguageSwitcher = $this->confirm('Include a language dropdown in the header?', false);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description,
            'author' => $author,
            'version' => $version,
            'mode' => $mode,
            'preset' => $preset,
            'prefersDark' => $prefersDark,
            'allPresets' => $allPresets,
            'customColors' => $customColors,
            'includeSearch' => $includeSearch,
            'includeLanguageSwitcher' => $includeLanguageSwitcher,
        ];
    }

    protected function collectFromOptions(): array
    {
        $name = $this->argument('name') ?: 'My Theme';
        $preset = $this->option('preset') ?: 'light';
        $prefersDark = $this->option('prefers-dark');
        $allPresets = $this->option('all-presets');
        $isCustom = $this->option('custom');
        $mode = 'single';
        $customColors = null;

        // Check if using custom mode
        if ($isCustom || $preset === 'custom') {
            $mode = 'custom';
            $customColors = ['name' => Str::slug($name)];
            $preset = null;
        } else {
            // Validate preset
            if (! $this->validatePreset($preset)) {
                $this->error("Invalid preset: {$preset}");
                $this->line('Valid presets: '.implode(', ', Theme::ALL_DAISYUI_PRESETS));
                $this->line('Use --preset=custom for a custom Tailwind theme without daisyUI');
                exit(1);
            }

            // Validate prefersDark if provided
            if ($prefersDark && ! $this->validatePreset($prefersDark)) {
                $this->error("Invalid dark preset: {$prefersDark}");
                $this->line('Valid presets: '.implode(', ', Theme::ALL_DAISYUI_PRESETS));
                exit(1);
            }

            if ($allPresets) {
                $mode = 'all';
                $preset = 'light';
                $prefersDark = 'dark';
            }
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->option('description') ?: 'A modern TallCMS theme',
            'author' => $this->option('author') ?: 'Theme Developer',
            'version' => $this->option('theme-version') ?: '1.0.0',
            'mode' => $mode,
            'preset' => $preset,
            'prefersDark' => $prefersDark,
            'allPresets' => $allPresets,
            'customColors' => $customColors,
            'includeSearch' => (bool) $this->option('include-search'),
            'includeLanguageSwitcher' => (bool) $this->option('include-language-switcher'),
        ];
    }

    protected function validatePreset(string $preset): bool
    {
        return in_array($preset, Theme::ALL_DAISYUI_PRESETS);
    }

    protected function askWithValidation(string $question, string $default, callable $validator): string
    {
        while (true) {
            $value = $this->ask($question, $default);
            if ($validator($value)) {
                return $value;
            }
            $this->error("Invalid preset: {$value}");
            $this->line('Valid presets: '.implode(', ', Theme::ALL_DAISYUI_PRESETS));
        }
    }

    protected function createDirectoryStructure(string $themePath): void
    {
        $directories = [
            'public/build',
            'public/img',
            'resources/views/layouts',
            'resources/views/components',
            'resources/views/templates',
            'resources/css',
            'resources/js',
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$themePath}/{$dir}", 0755, true);
            $this->line("Created: {$dir}");
        }
    }

    protected function createThemeConfig(string $themePath, array $themeInfo): void
    {
        $config = [
            'name' => $themeInfo['name'],
            'slug' => $themeInfo['slug'],
            'version' => $themeInfo['version'],
            'description' => $themeInfo['description'],
            'author' => $themeInfo['author'],
        ];

        $config['tags'] = [];

        // Custom daisyUI theme
        if ($themeInfo['mode'] === 'custom') {
            $themeName = $themeInfo['customColors']['name'] ?? $themeInfo['slug'];
            $config['daisyui'] = [
                'preset' => $themeName,
                'custom' => true,
            ];
            $config['supports'] = [
                'dark_mode' => false,
                'theme_controller' => false,
                'search' => (bool) ($themeInfo['includeSearch'] ?? false),
                'language_switcher' => (bool) ($themeInfo['includeLanguageSwitcher'] ?? false),
                'responsive' => true,
                'animations' => true,
            ];
        } else {
            // DaisyUI theme
            $config['daisyui'] = [
                'preset' => $themeInfo['preset'],
            ];

            // Add prefersDark only if explicitly set
            if ($themeInfo['prefersDark']) {
                $config['daisyui']['prefersDark'] = $themeInfo['prefersDark'];
            }

            // Add presets: "all" for theme-controller mode
            if ($themeInfo['allPresets']) {
                $config['daisyui']['presets'] = 'all';
            }

            $config['supports'] = [
                'dark_mode' => $themeInfo['prefersDark'] !== null,
                'theme_controller' => $themeInfo['allPresets'],
                'search' => (bool) ($themeInfo['includeSearch'] ?? false),
                'language_switcher' => (bool) ($themeInfo['includeLanguageSwitcher'] ?? false),
                'responsive' => true,
                'animations' => true,
            ];
        }

        $config['screenshots'] = [
            'primary' => 'screenshot.png',
        ];

        $config['build'] = [
            'entries' => ['resources/css/app.css', 'resources/js/app.js'],
        ];

        // Templates section - themes can override or add page templates
        $config['templates'] = [
            'example' => [
                'label' => 'Example Template',
                'description' => 'A custom example template for this theme',
                'has_sidebar' => false,
                'minimal_chrome' => false,
            ],
        ];

        File::put("{$themePath}/theme.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('Created: theme.json');
    }

    protected function createPackageJson(string $themePath, array $themeInfo): void
    {
        // Themes share the root project's node_modules via NODE_PATH
        // This eliminates duplicate dependencies and ensures consistent versions
        $packageJson = [
            'name' => "tallcms-theme-{$themeInfo['slug']}",
            'version' => $themeInfo['version'],
            'description' => $themeInfo['description'],
            'author' => $themeInfo['author'],
            'private' => true,
            'type' => 'module',
            'scripts' => [
                'dev' => 'NODE_PATH=../../node_modules vite',
                'build' => 'NODE_PATH=../../node_modules vite build',
            ],
            'note' => "This theme uses the root project's node_modules. Run 'npm install' from the project root, not from this directory.",
        ];

        File::put("{$themePath}/package.json", json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('Created: package.json');
    }

    protected function createViteConfig(string $themePath): void
    {
        $viteConfig = <<<'JS'
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

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
        tailwindcss(),
    ],
})
JS;

        File::put("{$themePath}/vite.config.js", $viteConfig);
        $this->line('Created: vite.config.js');
    }

    protected function createCssFiles(string $themePath, array $themeInfo): void
    {
        if ($themeInfo['mode'] === 'custom') {
            $appCss = $this->buildCustomThemeCss($themeInfo);
        } else {
            $themesDirective = $this->buildThemesDirective($themeInfo);

            $appCss = <<<CSS
@import "tailwindcss";
@plugin "@tailwindcss/typography";
@plugin "daisyui" {
  {$themesDirective}
}

/* Scan paths for class discovery */
@source '../views/**/*.blade.php';
@source '../../../../resources/views/**/*.blade.php';
/* TallCMS Core package views (breadcrumbs, menu, etc.) */
@source '../../../../packages/tallcms/cms/resources/views/**/*.blade.php';
/* TallCMS Block PHP files (contains daisyUI class definitions) */
@source '../../../../packages/tallcms/cms/src/Filament/Blocks/**/*.php';
/* All plugin frontend views — auto-includes current and future plugins */
@source '../../../../plugins/**/resources/views/**/*.blade.php';

/* TallCMS Core Styles */
@import "../../../../resources/css/tallcms.css";

/* Theme-specific styles */
CSS;
        }

        File::put("{$themePath}/resources/css/app.css", $appCss);
        $this->line('Created: resources/css/app.css');
    }

    protected function buildCustomThemeCss(array $themeInfo): string
    {
        $themeName = $themeInfo['customColors']['name'] ?? $themeInfo['slug'];

        return <<<CSS
@import "tailwindcss";
@plugin "@tailwindcss/typography";
@plugin "daisyui";

/* Custom daisyUI theme - Customize these colors as needed */
/* See: https://daisyui.com/docs/themes/#custom-themes */
@plugin "daisyui/theme" {
  name: "{$themeName}";
  default: true;
  prefersdark: false;
  color-scheme: light;

  /* Base colors */
  --color-base-100: oklch(100% 0 0);
  --color-base-200: oklch(96% 0 0);
  --color-base-300: oklch(92% 0 0);
  --color-base-content: oklch(20% 0 0);

  /* Brand colors */
  --color-primary: oklch(55% 0.3 260);
  --color-primary-content: oklch(100% 0 0);
  --color-secondary: oklch(70% 0.2 330);
  --color-secondary-content: oklch(100% 0 0);
  --color-accent: oklch(75% 0.15 180);
  --color-accent-content: oklch(20% 0 0);

  /* Neutral */
  --color-neutral: oklch(30% 0.02 260);
  --color-neutral-content: oklch(98% 0 0);

  /* Status colors */
  --color-info: oklch(65% 0.2 240);
  --color-info-content: oklch(100% 0 0);
  --color-success: oklch(65% 0.2 150);
  --color-success-content: oklch(100% 0 0);
  --color-warning: oklch(75% 0.15 85);
  --color-warning-content: oklch(20% 0 0);
  --color-error: oklch(60% 0.25 25);
  --color-error-content: oklch(100% 0 0);

  /* Border radius */
  --radius-selector: 0.5rem;
  --radius-field: 0.25rem;
  --radius-box: 0.5rem;

  /* Sizing */
  --size-selector: 0.25rem;
  --size-field: 0.25rem;
  --border: 1px;
  --depth: 1;
  --noise: 0;
}

/* Scan paths for class discovery */
@source '../views/**/*.blade.php';
@source '../../../../resources/views/**/*.blade.php';
/* TallCMS Core package views (breadcrumbs, menu, etc.) */
@source '../../../../packages/tallcms/cms/resources/views/**/*.blade.php';
/* TallCMS Block PHP files (contains daisyUI class definitions) */
@source '../../../../packages/tallcms/cms/src/Filament/Blocks/**/*.php';
/* All plugin frontend views — auto-includes current and future plugins */
@source '../../../../plugins/**/resources/views/**/*.blade.php';

/* TallCMS Core Styles */
@import "../../../../resources/css/tallcms.css";

/* Theme-specific styles */
CSS;
    }

    protected function buildThemesDirective(array $themeInfo): string
    {
        if ($themeInfo['allPresets']) {
            // All presets mode - include all 35 daisyUI presets
            return 'themes: '.Theme::getPresetsCssString($themeInfo['preset'], $themeInfo['prefersDark']).';';
        }

        // Single preset mode
        $themes = $themeInfo['preset'].' --default';
        if ($themeInfo['prefersDark'] && $themeInfo['prefersDark'] !== $themeInfo['preset']) {
            $themes .= ', '.$themeInfo['prefersDark'].' --prefersdark';
        }

        return "themes: {$themes};";
    }

    protected function createJsFiles(string $themePath): void
    {
        $appJs = <<<'JS'
// Theme JavaScript
// Core CMS components (contact-form, comments, etc.) are loaded globally
// via @tallcmsCoreJs in the layout — no need to import them here.

// Theme-specific functionality here
JS;

        File::put("{$themePath}/resources/js/app.js", $appJs);
        $this->line('Created: resources/js/app.js');
    }

    protected function createViewFiles(string $themePath, string $studlyName, array $themeInfo): void
    {
        // Create layout
        $layout = $this->getLayoutTemplate($studlyName, $themeInfo);
        File::put("{$themePath}/resources/views/layouts/app.blade.php", $layout);
        $this->line('Created: resources/views/layouts/app.blade.php');

        // Create theme-switcher component if all presets mode
        if ($themeInfo['allPresets']) {
            $switcher = $this->getThemeSwitcherTemplate();
            File::put("{$themePath}/resources/views/components/theme-switcher.blade.php", $switcher);
            $this->line('Created: resources/views/components/theme-switcher.blade.php');
        }

        // Optional: search component
        if (! empty($themeInfo['includeSearch'])) {
            $search = $this->getHeaderSearchTemplate();
            File::put("{$themePath}/resources/views/components/header-search.blade.php", $search);
            $this->line('Created: resources/views/components/header-search.blade.php');
        }

        // Optional: language switcher component
        if (! empty($themeInfo['includeLanguageSwitcher'])) {
            $langSwitcher = $this->getLanguageSwitcherTemplate();
            File::put("{$themePath}/resources/views/components/language-switcher.blade.php", $langSwitcher);
            $this->line('Created: resources/views/components/language-switcher.blade.php');
        }

        // Create example template
        $exampleTemplate = $this->getExampleTemplateContent();
        File::put("{$themePath}/resources/views/templates/example.blade.php", $exampleTemplate);
        $this->line('Created: resources/views/templates/example.blade.php');
    }

    protected function getExampleTemplateContent(): string
    {
        return <<<'BLADE'
{{-- Example Custom Template --}}
{{-- Copy this file and customize to create your own page templates --}}
{{-- Templates are auto-discovered from themes/{slug}/resources/views/templates/ --}}

<div class="cms-content w-full">
    {{-- Custom header section --}}
    <div class="bg-base-200 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl font-bold">{{ $page->title }}</h1>
        </div>
    </div>

    {{-- Main content --}}
    <section id="content" class="max-w-4xl mx-auto px-4 py-8" data-content-width="{{ $page->content_width ?? 'standard' }}">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="{{ $pageData['content_width'] ?? 'standard' }}">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>
BLADE;
    }

    protected function createGitignore(string $themePath): void
    {
        $gitignore = <<<'GITIGNORE'
node_modules/
public/build/
.DS_Store
GITIGNORE;

        File::put("{$themePath}/.gitignore", $gitignore);
        $this->line('Created: .gitignore');
    }

    protected function getLayoutTemplate(string $studlyName, array $themeInfo): string
    {
        $slug = $themeInfo['slug'];
        $navbarEndParts = [];

        if (! empty($themeInfo['includeSearch'])) {
            $navbarEndParts[] = "                <!-- Search -->\n                @if(tallcms_show_search())\n                    @include('theme.{$slug}::components.header-search')\n                @endif";
        }

        if (! empty($themeInfo['includeLanguageSwitcher'])) {
            $navbarEndParts[] = "                <!-- Language Switcher -->\n                @if(tallcms_show_language_dropdown())\n                    @include('theme.{$slug}::components.language-switcher')\n                @endif";
        }

        if ($themeInfo['allPresets']) {
            $navbarEndParts[] = "                <!-- Theme Switcher -->\n                @if(tallcms_show_theme_switcher())\n                    @include('theme.{$slug}::components.theme-switcher')\n                @endif";
        } else {
            $navbarEndParts[] = '                {{-- Theme switcher: enable "all presets" mode to add theme-controller --}}';
        }

        $themeSwitcher = ltrim(implode("\n\n", $navbarEndParts));

        return <<<BLADE
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ daisyui_default_preset() }}" data-default-theme="{{ daisyui_default_preset() }}">
<head>
    @tallcmsDaisyUIBoot
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags --}}
    <x-tallcms::seo.meta-tags
        :title="\$title ?? null"
        :description="\$description ?? null"
        :image="isset(\$featuredImage) && \$featuredImage ? Storage::disk(cms_media_disk())->url(\$featuredImage) : null"
        :type="\$seoType ?? 'website'"
        :article="\$seoArticle ?? null"
        :twitter="\$seoTwitter ?? null"
        :profile="\$seoProfile ?? null"
    />

    {{-- Structured Data --}}
    <x-tallcms::seo.structured-data
        :page="\$seoPage ?? null"
        :post="\$seoPost ?? null"
        :breadcrumbs="\$seoBreadcrumbs ?? null"
        :includeWebsite="\$seoIncludeWebsite ?? false"
    />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    @if(\$favicon = \\TallCms\\Cms\\Models\\SiteSetting::get('favicon'))
        <link rel="icon" href="{{ Storage::disk(cms_media_disk())->url(\$favicon) }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- CMS Core Runtime (shared Alpine components) -->
    @tallcmsCoreJs
    <!-- Theme Assets -->
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
    <x-tallcms::code-injection zone="head" />
</head>
<body class="min-h-screen flex flex-col bg-base-100 text-base-content">
    <x-tallcms::code-injection zone="body_start" />
    <!-- Header -->
    @if(function_exists('mega_menu_header_active') && mega_menu_header_active('header'))
        {{-- Mega Menu Full Header --}}
        <x-dynamic-component component="mega-menu::header" location="header" />
    @elseif(function_exists('pro_header_active') && pro_header_active('header'))
        {{-- TallCMS Pro Full Header (Mode 2) - Legacy --}}
        <x-dynamic-component component="tallcms-pro::full-header" location="header" />
    @else
        {{-- Theme's Default Navbar --}}
        <div class="navbar bg-base-100 shadow-sm sticky top-0 z-50">
            <div class="navbar-start">
                <!-- Mobile Menu -->
                <div class="dropdown lg:hidden">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52">
                        <x-menu location="header" style="vertical" />
                    </ul>
                </div>
                <!-- Logo -->
                @php
                    \$logo = \\TallCms\\Cms\\Models\\SiteSetting::get('logo');
                    \$siteName = \\TallCms\\Cms\\Models\\SiteSetting::get('site_name', config('app.name'));
                @endphp
                <a href="{{ tallcms_home_url() }}" class="btn btn-ghost text-xl font-bold">
                    @if(\$logo)
                        <img src="{{ Storage::disk(cms_media_disk())->url(\$logo) }}" alt="{{ \$siteName }}" class="h-8 w-auto">
                    @endif
                    {{ \$siteName }}
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    <x-menu location="header" style="horizontal" />
                </ul>
            </div>

            <div class="navbar-end gap-2">
{$themeSwitcher}
            </div>
        </div>
    @endif

    {{-- Breadcrumbs --}}
    @if(\$showBreadcrumbs ?? false)
        <x-tallcms::breadcrumbs :items="\$breadcrumbItems ?? []" />
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        {{ \$slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    @php \$footerSiteName = \\TallCms\\Cms\\Models\\SiteSetting::get('site_name', config('app.name')); @endphp
    <footer class="footer footer-center bg-base-200 text-base-content p-10">
        <aside>
            <p class="font-bold text-lg">{{ \$footerSiteName }}</p>
            <p>{$studlyName} theme for TallCMS</p>
        </aside>
        <nav>
            <x-menu location="footer" style="footer" />
        </nav>
        <aside>
            <p>&copy; {{ date('Y') }} {{ \$footerSiteName }}. All rights reserved.</p>
            <x-tallcms::powered-by />
        </aside>
    </footer>

    @livewireScripts
    <x-tallcms::code-injection zone="body_end" />
</body>
</html>
BLADE;
    }

    protected function getThemeSwitcherTemplate(): string
    {
        return <<<'BLADE'
@props(['class' => ''])

<div class="dropdown dropdown-end {{ $class }}">
    <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-1">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
        </svg>
        <span class="hidden sm:inline">Theme</span>
        <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    <ul tabindex="0" class="dropdown-content menu bg-base-200 rounded-box z-50 w-52 p-2 shadow-2xl max-h-96 overflow-y-auto">
        @foreach(daisyui_presets() as $preset)
            <li>
                <input type="radio"
                       name="theme-dropdown"
                       class="theme-controller btn btn-sm btn-block btn-ghost justify-start"
                       aria-label="{{ ucfirst($preset) }}"
                       value="{{ $preset }}" />
            </li>
        @endforeach
    </ul>
</div>
BLADE;
    }

    protected function getHeaderSearchTemplate(): string
    {
        return <<<'BLADE'
{{-- Minimal header search component. The visibility gate
     (theme support + admin toggle + global config) lives in tallcms_show_search();
     this partial only renders the input. --}}
<form action="{{ tallcms_search_url() }}" method="get" class="form-control">
    <label class="input input-sm input-bordered flex items-center gap-2">
        <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 105.25 5.25a7.5 7.5 0 0011.4 11.4z"/>
        </svg>
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('Search') }}"
            class="grow"
        />
    </label>
</form>
BLADE;
    }

    protected function getLanguageSwitcherTemplate(): string
    {
        return <<<'BLADE'
{{-- Minimal language dropdown. Visibility (theme support + i18n_enabled + admin toggle)
     is enforced by tallcms_show_language_dropdown() before this partial is included.
     Uses tallcms_current_slug() to strip the existing locale/route prefixes from the
     request path so tallcms_localized_url() builds a clean URL for the target locale. --}}
@php
    $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
    $current = app()->getLocale();
    $locales = $registry->getLocales();
    $cleanSlug = tallcms_current_slug();
@endphp

@if(count($locales) >= 2)
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-1" aria-label="{{ __('Change language') }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3.6 9h16.8M3.6 15h16.8M11.5 3a16 16 0 010 18M12.5 3a16 16 0 010 18"/>
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
            </svg>
            <span class="uppercase text-xs">{{ $current }}</span>
        </div>
        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 w-40 p-2 shadow">
            @foreach($locales as $code => $locale)
                <li>
                    <a href="{{ url(tallcms_localized_url($cleanSlug, $code)) }}"
                       hreflang="{{ \TallCms\Cms\Services\LocaleRegistry::toBcp47($code) }}"
                       @if($code === $current) class="active" aria-current="true" @endif>
                        {{ $locale['native'] ?? strtoupper($code) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
BLADE;
    }
}
