<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeInstallCommand extends Command
{
    protected $signature = 'theme:install {slug : The theme slug to install}';
    protected $description = 'Install a theme (publish assets and build)';

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

        $this->info("Installing theme: {$theme->name} ({$theme->slug})");

        // Install the theme
        $this->line('Publishing assets...');
        if ($themeManager->installTheme($slug)) {
            $this->info('Theme installed successfully!');
        } else {
            $this->error('Failed to install theme.');
            return 1;
        }

        return 0;
    }
}