<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ThemeCacheClear extends Command
{
    protected $signature = 'theme:cache:clear';
    protected $description = 'Clear the theme discovery cache';

    public function handle()
    {
        $themeManager = app('theme.manager');
        
        if ($themeManager->clearCache()) {
            $this->info('Theme cache cleared successfully.');
        } else {
            $this->error('Failed to clear theme cache.');
        }
    }
}