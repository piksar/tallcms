<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\ThemeManager;
use Illuminate\Console\Command;

class ThemeActivate extends Command
{
    protected $signature = 'theme:activate {slug : The theme slug to activate}
                            {--force : Force activation even if theme is not installed}';

    protected $description = 'Activate a TallCMS theme';

    public function __construct(
        protected ThemeManager $themeManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $force = $this->option('force');

        $this->info("Activating theme: {$slug}");

        // Check if theme exists
        $theme = Theme::find($slug);
        if (!$theme) {
            $this->error("Theme '{$slug}' not found!");
            $this->line("Run 'php artisan theme:list' to see available themes.");
            return 1;
        }

        // Check if theme is installed (has built assets)
        if (!$force && !$this->isThemeInstalled($theme)) {
            $this->warn("Theme '{$slug}' doesn't appear to be installed.");
            $this->line("Installing theme assets...");
            
            if (!$this->themeManager->installTheme($slug)) {
                $this->error("Failed to install theme assets!");
                $this->line("This could be due to:");
                $this->line("• Missing Node.js or npm");
                $this->line("• Build errors in theme assets");
                $this->line("• Permission issues with symlinks");
                $this->newLine();
                $this->line("Try running: php artisan theme:build {$slug} --install");
                return 1;
            }
        }

        // Activate the theme
        if ($this->themeManager->setActiveTheme($slug)) {
            $this->info("✅ Theme '{$theme->name}' activated successfully!");
            $this->line("Active theme: {$theme->name} (v{$theme->version})");
            
            // Show theme info
            $this->newLine();
            $this->comment("Theme Information:");
            $this->line("Name: {$theme->name}");
            $this->line("Description: {$theme->description}");
            $this->line("Author: {$theme->author}");
            $this->line("Version: {$theme->version}");
            
            return 0;
        }

        $this->error("Failed to activate theme '{$slug}'");
        return 1;
    }

    protected function isThemeInstalled(Theme $theme): bool
    {
        $publicThemePath = public_path("themes/{$theme->slug}");
        return file_exists($publicThemePath);
    }
}