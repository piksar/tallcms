<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeActivateCommand extends Command
{
    protected $signature = 'theme:activate {slug : The theme slug to activate}';
    protected $description = 'Activate a theme';

    public function handle()
    {
        $slug = $this->argument('slug');
        $themeManager = app('theme.manager');

        // Check if theme exists
        $theme = Theme::find($slug);
        if (!$theme) {
            $this->error("Theme '{$slug}' not found.");
            
            // Show available themes
            $availableThemes = $themeManager->getAvailableThemes();
            if ($availableThemes->isNotEmpty()) {
                $this->line('');
                $this->line('Available themes:');
                foreach ($availableThemes as $availableTheme) {
                    $this->line("- {$availableTheme->slug} ({$availableTheme->name})");
                }
            }
            
            return 1;
        }

        // Check if theme is already active
        $activeTheme = $themeManager->getActiveTheme();
        if ($activeTheme->slug === $slug) {
            $this->warn("Theme '{$slug}' is already active.");
            return 0;
        }

        $this->info("Activating theme: {$theme->name} ({$theme->slug})");

        // Activate the theme
        if ($themeManager->setActiveTheme($slug)) {
            $this->info('Theme activated successfully!');
            
            // Show theme info
            $this->line('');
            $this->line("Name: {$theme->name}");
            $this->line("Version: {$theme->version}");
            $this->line("Author: {$theme->author}");
            if ($theme->hasParent()) {
                $this->line("Parent: {$theme->parent}");
            }
            
        } else {
            $this->error('Failed to activate theme.');
            return 1;
        }

        return 0;
    }
}