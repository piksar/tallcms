<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Theme
{
    public string $name;
    public string $slug;
    public string $version;
    public string $description;
    public string $author;
    public array $tailwind;
    public array $supports;
    public array $build;
    public string $path;
    public ?string $parent = null;
    public array $extras = [];

    public function __construct(array $data, string $path)
    {
        $this->name = $data['name'] ?? 'Untitled Theme';
        $this->slug = $data['slug'] ?? 'untitled';
        $this->version = $data['version'] ?? '1.0.0';
        $this->description = $data['description'] ?? '';
        $this->author = $data['author'] ?? 'Unknown';
        $this->tailwind = $data['tailwind'] ?? [];
        $this->supports = $data['supports'] ?? [];
        $this->build = $data['build'] ?? [];
        $this->parent = $data['parent'] ?? null;
        $this->extras = $data['extras'] ?? [];
        $this->path = $path;
    }

    /**
     * Get theme from directory
     */
    public static function fromDirectory(string $path): ?self
    {
        $themeJsonPath = $path . '/theme.json';
        
        if (!File::exists($themeJsonPath)) {
            return null;
        }

        try {
            $themeData = json_decode(File::get($themeJsonPath), true);
            return new self($themeData, $path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available themes
     */
    public static function all(): Collection
    {
        // Use ThemeManager for cached discovery if available
        if (app()->bound('theme.manager')) {
            return app('theme.manager')->getAvailableThemes();
        }

        // Fallback to direct filesystem discovery
        return self::discoverFromFilesystem();
    }

    /**
     * Discover themes directly from filesystem
     */
    protected static function discoverFromFilesystem(): Collection
    {
        $themesPath = base_path('themes');
        
        if (!File::exists($themesPath)) {
            return collect();
        }

        $themes = collect();
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $theme = self::fromDirectory($directory);
            if ($theme) {
                $themes->push($theme);
            }
        }

        return $themes;
    }

    /**
     * Find theme by slug
     */
    public static function find(string $slug): ?self
    {
        return self::all()->firstWhere('slug', $slug);
    }


    /**
     * Get theme's view path
     */
    public function getViewPath(string $viewPath = ''): string
    {
        return $this->path . '/resources/views' . ($viewPath ? '/' . $viewPath : '');
    }

    /**
     * Get theme's public path
     */
    public function getPublicPath(string $assetPath = ''): string
    {
        return $this->path . '/public' . ($assetPath ? '/' . $assetPath : '');
    }

    /**
     * Get theme's resource path
     */
    public function getResourcePath(string $resourcePath = ''): string
    {
        return $this->path . '/resources' . ($resourcePath ? '/' . $resourcePath : '');
    }

    /**
     * Check if theme supports a feature
     */
    public function supports(string $feature): bool
    {
        return isset($this->supports[$feature]) && $this->supports[$feature] === true;
    }

    /**
     * Check if theme supports a specific block
     */
    public function supportsBlock(string $blockName): bool
    {
        return isset($this->supports['blocks']) && 
               is_array($this->supports['blocks']) && 
               in_array($blockName, $this->supports['blocks']);
    }

    /**
     * Get all supported blocks
     */
    public function getSupportedBlocks(): array
    {
        return $this->supports['blocks'] ?? [];
    }

    /**
     * Get theme's Tailwind configuration
     */
    public function getTailwindConfig(): array
    {
        return $this->tailwind;
    }

    /**
     * Get theme asset URL
     */
    public function asset(string $path): string
    {
        return asset("themes/{$this->slug}/{$path}");
    }

    /**
     * Get parent theme
     */
    public function getParent(): ?self
    {
        if (!$this->parent) {
            return null;
        }

        return self::find($this->parent);
    }

    /**
     * Check if theme has parent
     */
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * Get theme hierarchy chain (including self)
     */
    public function getHierarchy(): array
    {
        $chain = [$this];
        $current = $this;

        // Build chain up to parent themes (prevent circular references)
        $visited = [$this->slug];
        while ($current->hasParent() && !in_array($current->parent, $visited)) {
            $parent = $current->getParent();
            if (!$parent) break;

            $chain[] = $parent;
            $visited[] = $parent->slug;
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Find view with parent fallback
     */
    public function findView(string $viewPath): ?string
    {
        // Try current theme first
        $fullPath = $this->path . '/resources/views/' . str_replace('.', '/', $viewPath) . '.blade.php';
        if (File::exists($fullPath)) {
            return $fullPath;
        }

        // Try parent themes
        $parent = $this->getParent();
        if ($parent) {
            return $parent->findView($viewPath);
        }

        return null;
    }

    /**
     * Check if theme (or parents) has view override
     */
    public function hasViewOverride(string $viewPath): bool
    {
        return $this->findView($viewPath) !== null;
    }
}