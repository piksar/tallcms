<?php

namespace App\Events;

use App\Models\Theme;

class ThemeInstalling
{
    public Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }
}