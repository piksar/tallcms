<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance mode for admin routes
        if ($request->is('admin*') || $request->is('admin')) {
            return $next($request);
        }

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

        return $next($request);
    }
}