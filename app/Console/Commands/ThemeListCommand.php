<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeListCommand extends Command
{
    protected $signature = 'theme:list {--active : Show only the active theme}';
    protected $description = 'List all available themes';

    public function handle()
    {
        $themeManager = app('theme.manager');
        $themes = $themeManager->getAvailableThemes();
        $activeTheme = $themeManager->getActiveTheme();

        if ($this->option('active')) {
            $this->info("Active Theme: {$activeTheme->name} ({$activeTheme->slug})");
            $this->line("Version: {$activeTheme->version}");
            $this->line("Author: {$activeTheme->author}");
            $this->line("Description: {$activeTheme->description}");
            if ($activeTheme->hasParent()) {
                $this->line("Parent: {$activeTheme->parent}");
            }
            return;
        }

        if ($themes->isEmpty()) {
            $this->warn('No themes found.');
            return;
        }

        $this->line('Available Themes:');
        $this->line('');

        $headers = ['Name', 'Slug', 'Version', 'Author', 'Parent', 'Status'];
        $rows = [];

        foreach ($themes as $theme) {
            $rows[] = [
                $theme->name,
                $theme->slug,
                $theme->version,
                $theme->author,
                $theme->parent ?? '-',
                $theme->slug === $activeTheme->slug ? '<info>Active</info>' : 'Available'
            ];
        }

        $this->table($headers, $rows);
        
        $this->line('');
        $this->line("Active theme: <info>{$activeTheme->name}</info> ({$activeTheme->slug})");
    }
}