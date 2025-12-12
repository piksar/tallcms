<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class InstallerGate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if installer should be accessible
        if (!$this->shouldAllowInstaller()) {
            // If installation is complete, redirect to homepage
            return redirect('/')->with('error', 'Installation is already complete.');
        }

        return $next($request);
    }

    /**
     * Determine if the installer should be accessible
     */
    private function shouldAllowInstaller(): bool
    {
        // Block if installation is complete (lock file exists)
        if (File::exists(storage_path('installer.lock'))) {
            // Allow if explicitly enabled in .env
            return env('INSTALLER_ENABLED', false);
        }

        // Allow if .env doesn't exist (fresh installation)
        if (!File::exists(base_path('.env'))) {
            return true;
        }

        // Allow if no lock file exists (installation incomplete)
        return true;
    }
}