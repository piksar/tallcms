<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallerRunner
{
    private array $output = [];
    private array $errors = [];

    /**
     * Run the complete installation process
     */
    public function runInstallation(array $config): array
    {
        $this->clearOutput();
        
        
        try {
            // Step 1: Clear configuration cache
            $this->runStep('Clearing configuration cache', function() {
                Artisan::call('config:clear');
                return 'Configuration cache cleared';
            });

            // Step 2: Generate application key if needed
            if (empty(config('app.key'))) {
                $this->runStep('Generating application key', function() {
                    Artisan::call('key:generate', ['--force' => true]);
                    return 'Application key generated';
                });
            }

            // Step 3: Run migrations
            $this->runStep('Running database migrations', function() {
                Artisan::call('migrate', ['--force' => true]);
                return 'Database migrations completed';
            });

            // Step 4: Create storage symlink
            $this->runStep('Creating storage symlink', function() {
                Artisan::call('storage:link');
                return 'Storage symlink created';
            });

            // Step 5: Seed initial data (if requested)
            if ($config['seed_demo_data'] ?? false) {
                $this->runStep('Seeding demo data', function() {
                    Artisan::call('db:seed', ['--force' => true]);
                    return 'Demo data seeded';
                });
            }

            // Step 6: Setup TallCMS (roles, permissions, admin user)
            $this->runStep('Setting up TallCMS roles and admin user', function() use ($config) {
                return $this->runTallCmsSetup($config['admin']);
            });

            // Step 7: Clear all caches
            $this->runStep('Optimizing application', function() {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
                return 'Application optimized';
            });

            return [
                'success' => true,
                'message' => 'Installation completed successfully',
                'output' => $this->output,
                'errors' => $this->errors
            ];

        } catch (\Exception $e) {
            $this->errors[] = "Installation failed: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'output' => $this->output,
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Run a single installation step
     */
    private function runStep(string $description, callable $callback): void
    {
        try {
            $this->output[] = "→ {$description}...";
            $result = $callback();
            $this->output[] = "✓ {$result}";
        } catch (\Exception $e) {
            $error = "✗ {$description} failed: " . $e->getMessage();
            $this->output[] = $error;
            $this->errors[] = $error;
            throw $e;
        }
    }


    /**
     * Run TallCMS setup command (roles/permissions/admin user)
     */
    private function runTallCmsSetup(array $adminConfig): string
    {
        try {
            Artisan::call('tallcms:setup', [
                '--force' => true,
                '--name' => $adminConfig['name'] ?? null,
                '--email' => $adminConfig['email'] ?? null,
                '--password' => $adminConfig['password'] ?? null,
                '--no-interaction' => true,
            ]);

            return 'TallCMS setup completed';
        } catch (\Exception $e) {
            throw new \Exception("TallCMS setup failed: " . $e->getMessage());
        }
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection(array $dbConfig): array
    {
        try {
            $connection = [
                'driver' => 'mysql',
                'host' => $dbConfig['host'],
                'port' => $dbConfig['port'],
                'database' => $dbConfig['database'],
                'username' => $dbConfig['username'],
                'password' => $dbConfig['password'],
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ];

            config(['database.connections.test' => $connection]);
            
            DB::connection('test')->getPdo();
            
            return [
                'success' => true,
                'message' => 'Database connection successful'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get captured output
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * Get captured errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clear output and errors
     */
    private function clearOutput(): void
    {
        $this->output = [];
        $this->errors = [];
    }
}
