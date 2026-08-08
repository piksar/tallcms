<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class TemplateRegistry
{
    protected array $defaultTemplates = [
        'default' => [
            'label' => 'Default',
            'description' => 'Standard page layout',
            'supports' => ['content_width', 'breadcrumbs'],
            'has_sidebar' => false,
            'minimal_chrome' => false,
        ],
        'full-width' => [
            'label' => 'Full Width',
            'description' => 'Full-width layout without constraints',
            'supports' => ['breadcrumbs'],
            'has_sidebar' => false,
            'minimal_chrome' => false,
        ],
        'sidebar-left' => [
            'label' => 'Sidebar (Left)',
            'description' => 'Content with configurable left sidebar',
            'supports' => ['content_width', 'breadcrumbs', 'sidebar'],
            'has_sidebar' => true,
            'sidebar_position' => 'left',
            'minimal_chrome' => false,
        ],
        'sidebar-right' => [
            'label' => 'Sidebar (Right)',
            'description' => 'Content with configurable right sidebar',
            'supports' => ['content_width', 'breadcrumbs', 'sidebar'],
            'has_sidebar' => true,
            'sidebar_position' => 'right',
            'minimal_chrome' => false,
        ],
        'documentation' => [
            'label' => 'Documentation',
            'description' => 'Documentation layout with TOC sidebar',
            'supports' => ['content_width', 'toc'],
            'has_sidebar' => true,
            'sidebar_position' => 'left',
            'default_widgets' => [['widget' => 'toc', 'settings' => []]],
            'minimal_chrome' => false,
        ],
        'landing' => [
            'label' => 'Landing Page',
            'description' => 'Full-width with minimal header/footer',
            'supports' => [],
            'has_sidebar' => false,
            'minimal_chrome' => true,
        ],
    ];

    protected ?array $cachedTemplates = null;

    /**
     * Get all available templates, merging theme and package templates.
     * Theme templates take precedence over package defaults.
     */
    public function getAvailableTemplates(): array
    {
        if ($this->cachedTemplates === null) {
            $templates = $this->defaultTemplates;

            // Merge theme templates if available
            $themeTemplates = $this->getThemeTemplates();
            foreach ($themeTemplates as $slug => $config) {
                $templates[$slug] = array_merge($templates[$slug] ?? [], $config);
            }

            $this->cachedTemplates = $templates;
        }

        return $this->localizeTemplates($this->cachedTemplates);
    }

    /**
     * @param  array<string, array<string, mixed>>  $templates
     * @return array<string, array<string, mixed>>
     */
    protected function localizeTemplates(array $templates): array
    {
        foreach ($templates as $slug => &$config) {
            $labelKey = "tallcms::blocks.templates.{$slug}.label";
            $descriptionKey = "tallcms::blocks.templates.{$slug}.description";

            if (\Illuminate\Support\Facades\Lang::has($labelKey)) {
                $config['label'] = __($labelKey);
            }

            if (\Illuminate\Support\Facades\Lang::has($descriptionKey)) {
                $config['description'] = __($descriptionKey);
            }
        }
        unset($config);

        return $templates;
    }

    /**
     * Get template options for use in a Select field.
     */
    public function getTemplateOptions(): array
    {
        $templates = $this->getAvailableTemplates();

        return collect($templates)->mapWithKeys(function ($config, $slug) {
            return [$slug => $config['label'] ?? ucfirst(str_replace('-', ' ', $slug))];
        })->toArray();
    }

    /**
     * Resolve the Blade view path for a template.
     * Falls back through: Theme → Package → Default.
     */
    public function resolveTemplateView(string $template): string
    {
        // Check if theme has this template
        $themeView = $this->getThemeTemplateView($template);
        if ($themeView) {
            return $themeView;
        }

        // Fall back to package template
        $packageView = "tallcms::templates.{$template}";
        if (View::exists($packageView)) {
            return $packageView;
        }

        // Ultimate fallback to default template
        return 'tallcms::templates.default';
    }

    /**
     * Get the configuration for a specific template.
     */
    public function getTemplateConfig(string $template): array
    {
        $templates = $this->getAvailableTemplates();

        return $templates[$template] ?? $templates['default'];
    }

    /**
     * Get templates defined by the active theme.
     */
    protected function getThemeTemplates(): array
    {
        if (! app()->bound('theme.manager')) {
            return [];
        }

        $themeManager = app('theme.manager');
        $activeTheme = $themeManager->getActiveTheme();

        if (! $activeTheme || $activeTheme->slug === 'default') {
            return [];
        }

        $themePath = $activeTheme->path;
        $templatesDir = $themePath.'/resources/views/templates';

        if (! File::isDirectory($templatesDir)) {
            return [];
        }

        // Auto-discover templates from theme's templates directory
        $templates = [];
        $files = File::files($templatesDir);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php')) {
                $slug = str_replace('.blade.php', '', $file->getFilename());
                $templates[$slug] = [
                    'label' => ucfirst(str_replace('-', ' ', $slug)),
                    'description' => "Theme template: {$slug}",
                    'has_sidebar' => str_contains($slug, 'sidebar'),
                    'minimal_chrome' => str_contains($slug, 'landing'),
                ];
            }
        }

        // Also check theme.json for explicit template definitions
        $themeJson = $themePath.'/theme.json';
        if (File::exists($themeJson)) {
            $themeConfig = json_decode(File::get($themeJson), true);
            if (isset($themeConfig['templates']) && is_array($themeConfig['templates'])) {
                foreach ($themeConfig['templates'] as $slug => $config) {
                    $templates[$slug] = array_merge($templates[$slug] ?? [], $config);
                }
            }
        }

        return $templates;
    }

    /**
     * Get the theme's template view path if it exists.
     */
    protected function getThemeTemplateView(string $template): ?string
    {
        if (! app()->bound('theme.manager')) {
            return null;
        }

        $themeManager = app('theme.manager');
        $activeTheme = $themeManager->getActiveTheme();

        if (! $activeTheme || $activeTheme->slug === 'default') {
            return null;
        }

        $themeView = "theme.{$activeTheme->slug}::templates.{$template}";

        if (View::exists($themeView)) {
            return $themeView;
        }

        return null;
    }

    /**
     * Clear the templates cache.
     */
    public function clearCache(): void
    {
        $this->cachedTemplates = null;
    }
}
