<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\ThemeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeBuild extends Command
{
    protected $signature = 'theme:build {slug? : The theme slug to build (optional, builds active theme if not specified)}
                            {--force : Force rebuild even if assets exist}
                            {--install : Install theme dependencies first}';

    protected $description = 'Build TallCMS theme assets';

    public function __construct(
        protected ThemeManager $themeManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $force = $this->option('force');
        $install = $this->option('install');

        // Determine which theme to build
        if ($slug) {
            $theme = Theme::find($slug);
            if (!$theme) {
                $this->error("Theme '{$slug}' not found!");
                return 1;
            }
        } else {
            $theme = $this->themeManager->getActiveTheme();
            $this->info("Building active theme: {$theme->name}");
        }

        return $this->buildTheme($theme, $force, $install);
    }

    protected function buildTheme(Theme $theme, bool $force = false, bool $install = false): int
    {
        $this->info("Building theme: {$theme->name} ({$theme->slug})");
        
        // Check if theme directory exists
        if (!File::exists($theme->path)) {
            $this->error("Theme directory not found: {$theme->path}");
            return 1;
        }

        // Check if package.json exists
        $packageJsonPath = $theme->path . '/package.json';
        if (!File::exists($packageJsonPath)) {
            $this->error("No package.json found in theme directory!");
            $this->line("Theme may not have build configuration.");
            return 1;
        }

        // Install dependencies if requested or node_modules doesn't exist
        if ($install || !File::exists($theme->path . '/node_modules')) {
            $this->line("Installing dependencies...");
            if (!$this->installDependencies($theme)) {
                return 1;
            }
        }

        // Create symlinks for public assets
        $this->line("Publishing theme assets...");
        $this->themeManager->publishThemeAssets($theme);

        // Build assets
        $this->line("Building theme assets...");
        if ($this->themeManager->buildThemeAssets($theme)) {
            $this->info("✅ Theme '{$theme->name}' built successfully!");
            
            // Show build output information
            $this->showBuildInfo($theme);
            
            return 0;
        }

        $this->error("❌ Failed to build theme assets!");
        $this->line("Check the theme's build configuration and try again.");
        return 1;
    }

    protected function installDependencies(Theme $theme): bool
    {
        $this->withProgressBar(range(1, 3), function ($step) use ($theme) {
            match($step) {
                1 => $this->line("  Checking package.json..."),
                2 => $this->line("  Running npm install..."),
                3 => $this->line("  Finalizing installation..."),
            };
            sleep(1);
        });

        $this->newLine(2);

        // Run npm install in theme directory
        $process = \Illuminate\Support\Facades\Process::path($theme->path)->run('npm install');
        
        if ($process->failed()) {
            $this->error("Failed to install dependencies!");
            $this->line("Error output:");
            $this->line($process->errorOutput());
            return false;
        }

        $this->info("Dependencies installed successfully!");
        return true;
    }

    protected function showBuildInfo(Theme $theme): void
    {
        $this->newLine();
        $this->comment("Build Information:");
        
        // Check for built assets
        $publicPath = public_path("themes/{$theme->slug}");
        if (File::exists($publicPath)) {
            $this->line("Public assets: {$publicPath}");
        }

        $buildPath = $theme->path . '/public/build';
        if (File::exists($buildPath)) {
            $this->line("Built assets: {$buildPath}");
            
            // Show asset files
            $assetFiles = File::files($buildPath);
            if ($assetFiles) {
                $this->line("Generated files:");
                foreach ($assetFiles as $file) {
                    $size = $this->formatBytes(File::size($file));
                    $this->line("  • {$file->getFilename()} ({$size})");
                }
            }
        }

        $this->newLine();
        $this->line("Theme is ready to use!");
        $this->line("Activate with: php artisan theme:activate {$theme->slug}");
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}