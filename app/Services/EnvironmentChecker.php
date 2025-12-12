<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

class EnvironmentChecker
{
    /**
     * Check all system requirements
     */
    public function checkAll(): array
    {
        return [
            'php' => $this->checkPhpVersion(),
            'extensions' => $this->checkPhpExtensions(),
            'directories' => $this->checkWritableDirectories(),
            'database' => $this->checkDatabaseConnection(),
            'overall' => $this->getOverallStatus(),
        ];
    }

    /**
     * Check PHP version requirement
     */
    public function checkPhpVersion(): array
    {
        $currentVersion = PHP_VERSION;
        $requiredVersion = '8.2.0';
        $passed = version_compare($currentVersion, $requiredVersion, '>=');

        return [
            'name' => 'PHP Version',
            'required' => ">= {$requiredVersion}",
            'current' => $currentVersion,
            'passed' => $passed,
            'message' => $passed 
                ? "PHP {$currentVersion} meets requirements"
                : "PHP {$requiredVersion} or higher is required. Current: {$currentVersion}"
        ];
    }

    /**
     * Check required PHP extensions
     */
    public function checkPhpExtensions(): array
    {
        $requiredExtensions = [
            'openssl' => 'OpenSSL for encryption',
            'pdo' => 'PDO for database access', 
            'mbstring' => 'Mbstring for string handling',
            'tokenizer' => 'Tokenizer for Laravel',
            'xml' => 'XML for parsing',
            'ctype' => 'Ctype for character type checking',
            'json' => 'JSON for data exchange',
            'bcmath' => 'BCMath for precision mathematics',
            'curl' => 'cURL for HTTP requests',
            'fileinfo' => 'Fileinfo for file type detection',
            'gd' => 'GD for image processing',
            'zip' => 'Zip for archive handling'
        ];

        $extensions = [];
        $allPassed = true;

        foreach ($requiredExtensions as $extension => $description) {
            $installed = extension_loaded($extension);
            if (!$installed) {
                $allPassed = false;
            }

            $extensions[] = [
                'name' => $extension,
                'description' => $description,
                'installed' => $installed,
                'passed' => $installed
            ];
        }

        return [
            'name' => 'PHP Extensions',
            'extensions' => $extensions,
            'passed' => $allPassed,
            'message' => $allPassed 
                ? 'All required PHP extensions are installed'
                : 'Some required PHP extensions are missing'
        ];
    }

    /**
     * Check writable directories
     */
    public function checkWritableDirectories(): array
    {
        $requiredDirectories = [
            storage_path() => 'Storage directory for application data',
            storage_path('app') => 'Storage app directory',
            storage_path('framework') => 'Storage framework directory',
            storage_path('logs') => 'Storage logs directory',
            base_path('bootstrap/cache') => 'Bootstrap cache directory',
            public_path('storage') => 'Public storage symlink (will be created if missing)'
        ];

        $directories = [];
        $allPassed = true;

        foreach ($requiredDirectories as $directory => $description) {
            // Create directory if it doesn't exist
            if (!File::exists($directory)) {
                try {
                    File::makeDirectory($directory, 0755, true);
                } catch (\Exception $e) {
                    // Directory creation failed
                }
            }

            $exists = File::exists($directory);
            $writable = $exists && is_writable($directory);
            
            if (!$writable) {
                $allPassed = false;
            }

            $directories[] = [
                'path' => $directory,
                'description' => $description,
                'exists' => $exists,
                'writable' => $writable,
                'passed' => $writable
            ];
        }

        return [
            'name' => 'Directory Permissions',
            'directories' => $directories,
            'passed' => $allPassed,
            'message' => $allPassed 
                ? 'All required directories are writable'
                : 'Some directories are not writable'
        ];
    }

    /**
     * Check database connection with provided credentials
     */
    public function checkDatabaseConnection(array $config = null): array
    {
        if (!$config) {
            // Use existing .env config if no config provided
            $config = [
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD')
            ];
        }

        try {
            if (empty($config['database'])) {
                return [
                    'name' => 'Database Connection',
                    'passed' => false,
                    'message' => 'Database configuration not provided'
                ];
            }

            // Create PDO connection to test
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            // Check if database exists
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$config['database']]);
            $databaseExists = (bool) $stmt->fetch();

            return [
                'name' => 'Database Connection',
                'passed' => true,
                'database_exists' => $databaseExists,
                'message' => $databaseExists 
                    ? "Successfully connected to database '{$config['database']}'"
                    : "Connected to MySQL server, but database '{$config['database']}' doesn't exist (will be created)"
            ];

        } catch (\PDOException $e) {
            return [
                'name' => 'Database Connection',
                'passed' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get overall status of all checks
     */
    public function getOverallStatus(): bool
    {
        $checks = [
            $this->checkPhpVersion(),
            $this->checkPhpExtensions(),
            $this->checkWritableDirectories()
        ];

        foreach ($checks as $check) {
            if (!$check['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if environment is ready for installation
     */
    public function isReady(): bool
    {
        return $this->getOverallStatus();
    }
}