<?php

namespace App\Events;

use App\Models\Theme;

class ThemeInstalled
{
    public Theme $theme;
    public bool $success;

    public function __construct(Theme $theme, bool $success = true)
    {
        $this->theme = $theme;
        $this->success = $success;
    }
}