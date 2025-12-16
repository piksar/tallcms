<?php

namespace App\Events;

use App\Models\Theme;

class ThemeActivated
{
    public Theme $theme;
    public ?Theme $previousTheme;

    public function __construct(Theme $theme, ?Theme $previousTheme = null)
    {
        $this->theme = $theme;
        $this->previousTheme = $previousTheme;
    }
}