<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Theme
    |--------------------------------------------------------------------------
    |
    | This option determines which theme is currently active for the application.
    | The value should correspond to a theme directory name in the themes/ folder.
    |
    */
    'active' => 'grape',

    /*
    |--------------------------------------------------------------------------
    | Theme Discovery Caching
    |--------------------------------------------------------------------------
    |
    | When enabled, theme discovery will be cached to improve performance.
    | RECOMMENDED: Set to false in local development to avoid stale cache issues
    | when adding/removing themes. Set to true in production for performance.
    |
    | The system includes automatic cache pruning to remove missing themes,
    | but disabling cache in development prevents phantom themes entirely.
    |
    | Example .env settings:
    | - Local: THEME_CACHE_ENABLED=false
    | - Production: THEME_CACHE_ENABLED=true
    |
    */
    'cache_enabled' => env('THEME_CACHE_ENABLED', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | The number of seconds to cache discovered themes. Default is 1 hour.
    | Only applies when cache_enabled is true.
    |
    */
    'cache_ttl' => 3600,
];