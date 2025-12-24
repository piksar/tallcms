<?php

/*
|--------------------------------------------------------------------------
| Installer Bootstrap Check
|--------------------------------------------------------------------------
|
| This file runs before Laravel fully boots to handle missing .env files
| and APP_KEYs that would prevent the installer from being accessible.
|
*/

use Illuminate\Support\Facades\File;

/**
 * Check if .env file has a valid APP_KEY
 */
if (!function_exists('hasValidAppKey')) {
    function hasValidAppKey(string $envPath): bool
    {
        if (!file_exists($envPath)) {
            return false;
        }
        
        $content = file_get_contents($envPath);
        
        // Check if APP_KEY exists and is not empty
        if (preg_match('/^APP_KEY=(.+)$/m', $content, $matches)) {
            $key = trim($matches[1], '"\'');
            return !empty($key) && $key !== 'base64:';
        }
        
        return false;
    }
}

/**
 * Check if .env file has placeholder database credentials
 */
if (!function_exists('hasPlaceholderDbCredentials')) {
    function hasPlaceholderDbCredentials(string $envPath): bool
    {
        if (!file_exists($envPath)) {
            return true;
        }
        
        $content = file_get_contents($envPath);
        
        // Check for placeholder values
        $placeholders = [
            'your_database_name',
            'your_username', 
            'your_password'
        ];
        
        foreach ($placeholders as $placeholder) {
            if (str_contains($content, $placeholder)) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Create minimal .env file for installer
 */
if (!function_exists('createMinimalEnvForInstaller')) {
    function createMinimalEnvForInstaller(string $envPath, string $envExamplePath): void
    {
        try {
            // Try to copy from .env.example first
            if (file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
                
                // Verify it has a valid APP_KEY
                if (hasValidAppKey($envPath)) {
                    return;
                }
            }
            
            // Generate minimal .env with proper APP_KEY (no DB config - user will set in installer)
            $appKey = 'base64:' . base64_encode(random_bytes(32));
            $content = "APP_NAME=TallCMS\n";
            $content .= "APP_ENV=production\n";
            $content .= "APP_KEY={$appKey}\n";
            $content .= "APP_DEBUG=false\n";
            $content .= "APP_URL=http://localhost\n\n";
            $content .= "# Use file sessions during installation to avoid database dependency\n";
            $content .= "SESSION_DRIVER=file\n";
            $content .= "SESSION_LIFETIME=120\n";
            $content .= "SESSION_ENCRYPT=false\n";
            $content .= "SESSION_PATH=/\n";
            $content .= "SESSION_DOMAIN=null\n\n";
            $content .= "BROADCAST_CONNECTION=log\n";
            $content .= "FILESYSTEM_DISK=local\n";
            $content .= "QUEUE_CONNECTION=sync\n\n";
            $content .= "# Use file cache during installation to avoid database dependency\n";
            $content .= "CACHE_STORE=file\n\n";
            $content .= "# Database settings will be configured during installation\n";
            $content .= "DB_CONNECTION=\n";
            $content .= "DB_HOST=\n";
            $content .= "DB_PORT=\n";
            $content .= "DB_DATABASE=\n";
            $content .= "DB_USERNAME=\n";
            $content .= "DB_PASSWORD=\n\n";
            $content .= "INSTALLER_ENABLED=true\n";
            
            file_put_contents($envPath, $content);
            
        } catch (Exception $e) {
            // If we can't write .env file, show error
            http_response_code(500);
            echo "<!DOCTYPE html>
<html>
<head>
    <title>TallCMS Installation Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; }
        .code { background: #f5f5f5; padding: 10px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='error'>
        <h2>TallCMS Installation Error</h2>
        <p>Cannot create .env configuration file. Please check file permissions.</p>
        <p><strong>Manual Fix:</strong></p>
        <div class='code'>cp .env.example .env</div>
        <p>Then refresh this page to continue installation.</p>
    </div>
</body>
</html>";
            exit;
        }
    }
}

// Check if we're in installer mode
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isInstallerRequest = str_starts_with($requestUri, '/install');

// Path to .env file
$envPath = __DIR__ . '/../.env';
$envExamplePath = __DIR__ . '/../.env.example';
$lockPath = __DIR__ . '/../installer.lock';

// Check if installation is complete
$installationComplete = file_exists($lockPath);

// Also check for .env-based lock (fallback method)
$envContents = file_exists($envPath) ? file_get_contents($envPath) : '';
$installerDisabledInEnv = str_contains($envContents, 'INSTALLER_ENABLED=false');

// If installation is complete (either method) and not explicitly enabled, skip installer logic
if ($installationComplete || $installerDisabledInEnv) {
    $installerEnabled = str_contains($envContents, 'INSTALLER_ENABLED=true');
    
    if (!$installerEnabled && $isInstallerRequest) {
        // Redirect away from installer if installation is complete
        header('Location: /');
        exit;
    }
    
    // Let Laravel handle normal requests
    return;
}

// Handle missing or incomplete .env for installer
if (!file_exists($envPath) || !hasValidAppKey($envPath) || hasPlaceholderDbCredentials($envPath)) {
    
    // If not an installer request, redirect to installer
    if (!$isInstallerRequest) {
        header('Location: /install');
        exit;
    }
    
    // For installer requests, ensure we have a minimal .env with APP_KEY
    createMinimalEnvForInstaller($envPath, $envExamplePath);
}