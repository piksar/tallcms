<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance mode check if installation is not complete
        if ($this->installationIncomplete()) {
            return $next($request);
        }

        // Skip maintenance mode for admin and installer routes
        if ($request->is('admin*') || $request->is('admin') || $request->is('install*')) {
            return $next($request);
        }

        try {
            // Check if maintenance mode is enabled
            $maintenanceMode = SiteSetting::get('maintenance_mode', false);
            
            if ($maintenanceMode) {
                $maintenanceMessage = SiteSetting::get('maintenance_message', 'We\'re currently performing scheduled maintenance. Please check back soon!');
                $siteName = SiteSetting::get('site_name');
                
                return response()->view('maintenance', [
                    'maintenanceMessage' => $maintenanceMessage,
                    'siteName' => $siteName,
                ], 503);
            }
        } catch (\Exception $e) {
            // If database query fails, skip maintenance check
            // This handles cases where tables don't exist yet
        }

        return $next($request);
    }

    /**
     * Check if installation is incomplete
     */
    private function installationIncomplete(): bool
    {
        // Installation is incomplete if:
        // 1. No installer lock file exists
        // 2. Database tables don't exist
        // 3. .env doesn't exist
        
        if (!File::exists(storage_path('installer.lock'))) {
            return true;
        }

        if (!File::exists(base_path('.env'))) {
            return true;
        }

        try {
            // Check if database is configured
            if (empty(config('database.connections.' . config('database.default') . '.database'))) {
                return true;
            }
            
            // Check if the settings table exists
            return !Schema::hasTable('tallcms_site_settings');
        } catch (\Exception $e) {
            // If we can't check the schema, assume installation is incomplete
            // This handles cases where database isn't configured or accessible
            return true;
        }
    }
}