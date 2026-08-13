<?php

declare(strict_types=1);

namespace TallCms\Cms\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\SiteSetting;

/**
 * Shared installation-completeness checks used by frontend middleware.
 */
class InstallationStatus
{
    /**
     * Whether TallCMS installation is incomplete (installer not finished, or tables missing).
     */
    public static function isIncomplete(): bool
    {
        // In plugin mode, skip installer.lock check if configured
        // (host app doesn't use TallCMS's installer)
        $skipInstallerCheck = config('tallcms.plugin_mode.skip_installer_check', true);
        $isPluginMode = config('tallcms.mode') === 'plugin' ||
            (config('tallcms.mode') === null && ! File::exists(base_path('.tallcms-standalone')));

        if ($isPluginMode && $skipInstallerCheck) {
            // In plugin mode, only check if database tables exist
            try {
                return ! Schema::hasTable((new SiteSetting)->getTable());
            } catch (\Throwable) {
                // If we can't check the schema, skip feature middleware
                return true;
            }
        }

        // Standalone mode: installation is incomplete if:
        // 1. No installer lock file exists
        // 2. Database is not configured / settings table missing
        //
        // Do not require base_path('.env'): Docker/K8s inject env via the
        // process environment. A missing file does not mean install is incomplete.

        if (! File::exists(base_path('installer.lock')) && ! File::exists(storage_path('installer.lock'))) {
            return true;
        }

        try {
            // Check if database is configured
            if (empty(config('database.connections.'.config('database.default').'.database'))) {
                return true;
            }

            // Check if the settings table exists using the model's table name
            return ! Schema::hasTable((new SiteSetting)->getTable());
        } catch (\Throwable) {
            // If we can't check the schema, assume installation is incomplete
            // This handles cases where database isn't configured or accessible
            return true;
        }
    }
}
