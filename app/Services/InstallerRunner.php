<?php

namespace App\Services;

use App\Models\CmsPage;
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

            // Step 5: Setup TallCMS (roles, permissions, admin user)
            $this->runStep('Setting up TallCMS roles and admin user', function() use ($config) {
                return $this->runTallCmsSetup($config['admin']);
            });

            // Step 6: Create default homepage
            $this->runStep('Creating default homepage', function() {
                return $this->createDefaultHomepage();
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
            $exitCode = Artisan::call('tallcms:setup', [
                '--force' => true,
                '--name' => $adminConfig['name'] ?? null,
                '--email' => $adminConfig['email'] ?? null,
                '--password' => $adminConfig['password'] ?? null,
                '--no-interaction' => true,
            ]);

            // Check if command failed
            if ($exitCode !== 0) {
                $output = Artisan::output();
                throw new \Exception("TallCMS setup command failed with exit code {$exitCode}. Output: {$output}");
            }

            return 'TallCMS setup completed';
        } catch (\Exception $e) {
            // Log the full error for debugging
            \Log::error('TallCMS setup failed', [
                'error' => $e->getMessage(),
                'admin_config' => [
                    'name' => $adminConfig['name'] ?? 'null',
                    'email' => $adminConfig['email'] ?? 'null',
                    'password_length' => isset($adminConfig['password']) ? strlen($adminConfig['password']) : 0,
                ]
            ]);
            
            throw new \Exception("TallCMS setup failed: " . $e->getMessage());
        }
    }

    /**
     * Create default homepage with hero block
     */
    private function createDefaultHomepage(): string
    {
        // Check if a homepage already exists
        if (CmsPage::where('is_homepage', true)->exists()) {
            return 'Homepage already exists, skipping';
        }

        // Hero block content showcasing TallCMS
        $heroContent = '<div data-type="customBlock" data-config="{&quot;heading&quot;:&quot;TALLcms&quot;,&quot;subheading&quot;:&quot;The CMS for Web Artisans&quot;,&quot;button_text&quot;:&quot;Get Started&quot;,&quot;button_link_type&quot;:&quot;custom&quot;,&quot;button_url&quot;:&quot;/admin&quot;,&quot;secondary_button_text&quot;:null,&quot;primary_button_style&quot;:&quot;preset&quot;,&quot;primary_button_preset&quot;:&quot;white&quot;,&quot;height&quot;:&quot;large&quot;,&quot;background_image&quot;:null,&quot;parallax_effect&quot;:true,&quot;overlay_opacity&quot;:0,&quot;text_alignment&quot;:&quot;center&quot;}" data-id="hero"></div>';

        CmsPage::create([
            'title' => 'Home',
            'slug' => 'home',
            'content' => $heroContent,
            'status' => 'published',
            'is_homepage' => true,
            'published_at' => now(),
            'meta_title' => 'Welcome to TallCMS',
            'meta_description' => 'TallCMS - The modern content management system built for web artisans.',
        ]);

        return 'Default homepage created';
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection(array $dbConfig): array
    {
        try {
            // Validate required keys exist
            $requiredKeys = ['host', 'port', 'username', 'password', 'database'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $dbConfig)) {
                    throw new \Exception("Missing required database configuration key: {$key}");
                }
            }

            // First connect to MySQL server (use 'mysql' system database to avoid dependency on user database)
            $serverConnection = [
                'driver' => 'mysql',
                'host' => $dbConfig['host'],
                'port' => $dbConfig['port'],
                'database' => 'mysql', // Use system database to test connection
                'username' => $dbConfig['username'],
                'password' => $dbConfig['password'],
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ];

            config(['database.connections.test_server' => $serverConnection]);
            DB::purge('test_server');
            DB::connection('test_server')->getPdo();

            // Check if the target database exists
            $dbName = $dbConfig['database'];
            $result = DB::connection('test_server')->select("SHOW DATABASES LIKE '" . $dbName . "'");
            $databaseExists = !empty($result);

            if (!$databaseExists) {
                return [
                    'success' => true,
                    'message' => "Connected to MySQL server, but database '{$dbName}' does not exist. It will be created during installation if permissions allow."
                ];
            }

            // Now connect with the database selected
            $fullConnection = array_merge($serverConnection, ['database' => $dbName]);
            config(['database.connections.test' => $fullConnection]);
            DB::purge('test');
            DB::connection('test')->getPdo();

            return [
                'success' => true,
                'message' => "Successfully connected to database '{$dbName}'"
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
