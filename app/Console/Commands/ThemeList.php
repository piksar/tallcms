<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\ThemeManager;
use Illuminate\Console\Command;

class ThemeList extends Command
{
    protected $signature = 'theme:list {--detailed : Show detailed theme information}';

    protected $description = 'List all available TallCMS themes';

    public function __construct(
        protected ThemeManager $themeManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $detailed = $this->option('detailed');
        $themes = $this->themeManager->getAvailableThemes();
        $activeTheme = $this->themeManager->getActiveTheme();

        if ($themes->isEmpty()) {
            $this->warn("No themes found!");
            $this->line("Create a new theme with: php artisan make:theme MyTheme");
            return 0;
        }

        $this->info("Available TallCMS Themes:");
        $this->newLine();

        if ($detailed) {
            $this->displayDetailedList($themes, $activeTheme);
        } else {
            $this->displaySimpleList($themes, $activeTheme);
        }

        $this->newLine();
        $this->comment("Commands:");
        $this->line("• Activate theme: php artisan theme:activate <slug>");
        $this->line("• Build theme: php artisan theme:build <slug>");
        $this->line("• Create theme: php artisan make:theme <name>");

        return 0;
    }

    protected function displaySimpleList($themes, $activeTheme): void
    {
        $headers = ['Status', 'Name', 'Slug', 'Version', 'Installed'];
        $rows = [];

        foreach ($themes as $theme) {
            $isActive = $theme->slug === $activeTheme->slug;
            $isInstalled = $this->isThemeInstalled($theme);

            $rows[] = [
                $isActive ? '🟢 Active' : '⚪',
                $theme->name,
                $theme->slug,
                $theme->version,
                $isInstalled ? '✅ Yes' : '❌ No'
            ];
        }

        $this->table($headers, $rows);
    }

    protected function displayDetailedList($themes, $activeTheme): void
    {
        foreach ($themes as $index => $theme) {
            $isActive = $theme->slug === $activeTheme->slug;
            $isInstalled = $this->isThemeInstalled($theme);

            if ($index > 0) {
                $this->newLine();
            }

            // Theme header
            $status = $isActive ? ' 🟢 (Active)' : '';
            $this->line("<fg=cyan;options=bold>{$theme->name}{$status}</>");
            
            // Theme details
            $this->line("  Slug:         {$theme->slug}");
            $this->line("  Description:  {$theme->description}");
            $this->line("  Author:       {$theme->author}");
            $this->line("  Version:      {$theme->version}");
            $this->line("  Installed:    " . ($isInstalled ? '✅ Yes' : '❌ No'));
            
            // Supported features
            if (!empty($theme->supports['blocks'])) {
                $blocks = implode(', ', $theme->supports['blocks']);
                $this->line("  Blocks:       {$blocks}");
            }
            
            // Additional features
            $features = [];
            if ($theme->supports['dark_mode'] ?? false) $features[] = 'Dark Mode';
            if ($theme->supports['responsive'] ?? false) $features[] = 'Responsive';
            
            if ($features) {
                $this->line("  Features:     " . implode(', ', $features));
            }
        }
    }

    protected function isThemeInstalled(Theme $theme): bool
    {
        $publicThemePath = public_path("themes/{$theme->slug}");
        return file_exists($publicThemePath);
    }
}