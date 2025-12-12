<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentChecker;
use App\Services\EnvWriter;
use App\Services\InstallerRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class InstallerController extends Controller
{
    private EnvironmentChecker $environmentChecker;
    private EnvWriter $envWriter;
    private InstallerRunner $installerRunner;

    public function __construct()
    {
        $this->environmentChecker = new EnvironmentChecker();
        $this->envWriter = new EnvWriter();
        $this->installerRunner = new InstallerRunner();
    }

    /**
     * Show welcome page
     */
    public function welcome(): View
    {
        // Bootstrap should have handled .env creation already
        // Just check if we can write to it
        $envStatus = $this->envWriter->canWriteEnv();
        
        return view('installer.welcome', [
            'envStatus' => $envStatus
        ]);
    }

    /**
     * Show environment check page
     */
    public function environment(): View
    {
        $checks = $this->environmentChecker->checkAll();
        
        return view('installer.environment', compact('checks'));
    }

    /**
     * Show configuration form
     */
    public function configuration(): View
    {
        // Ensure environment checks pass before showing configuration
        if (!$this->environmentChecker->isReady()) {
            return redirect()->route('installer.environment')
                ->with('error', 'Please resolve environment issues before proceeding');
        }

        return view('installer.configuration');
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string|max:64',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid database configuration',
                'errors' => $validator->errors()
            ], 422);
        }

        $dbConfig = [
            'host' => $request->input('db_host'),
            'port' => $request->input('db_port'),
            'database' => $request->input('db_database'),
            'username' => $request->input('db_username'),
            'password' => $request->input('db_password'),
        ];

        $result = $this->installerRunner->testDatabaseConnection($dbConfig);
        
        return response()->json($result);
    }

    /**
     * Process installation
     */
    public function install(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // Application settings
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url|max:255',
            'app_environment' => 'required|in:local,production',
            'app_debug' => 'boolean',
            
            // Database settings
            'db_host' => 'required|string|max:255',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string|max:64',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            
            // Admin user
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|max:255',
            'admin_password_confirmation' => 'required|same:admin_password',
            
            // Mail settings (optional)
            'mail_mailer' => 'nullable|in:smtp,mail,sendmail',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            
            // Options
            'seed_demo_data' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('installer.configuration')
                ->withErrors($validator)
                ->withInput();
        }

        // Backup existing .env file
        $this->envWriter->backup();

        try {
            // Update .env file
            $this->envWriter
                ->setAppConfig([
                    'name' => $request->input('app_name'),
                    'url' => $request->input('app_url'),
                    'environment' => $request->input('app_environment'),
                    'debug' => $request->boolean('app_debug'),
                ])
                ->setDatabaseConfig([
                    'host' => $request->input('db_host'),
                    'port' => $request->input('db_port'),
                    'database' => $request->input('db_database'),
                    'username' => $request->input('db_username'),
                    'password' => $request->input('db_password'),
                ])
                ->generateAppKey();

            // Set mail configuration if provided
            if ($request->filled('mail_mailer')) {
                $this->envWriter->setMailConfig([
                    'mailer' => $request->input('mail_mailer'),
                    'host' => $request->input('mail_host'),
                    'port' => $request->input('mail_port'),
                    'username' => $request->input('mail_username'),
                    'password' => $request->input('mail_password'),
                    'encryption' => $request->input('mail_encryption'),
                    'from_address' => $request->input('mail_from_address'),
                    'from_name' => $request->input('mail_from_name'),
                ]);
            }

            // Save .env file
            if (!$this->envWriter->save()) {
                throw new \Exception('Failed to write .env file');
            }

            // Clear configuration cache to pick up new .env values
            \Artisan::call('config:clear');
            
            // Force reload of database configuration
            config(['database.connections.mysql' => [
                'driver' => 'mysql',
                'host' => $request->input('db_host'),
                'port' => $request->input('db_port'),
                'database' => $request->input('db_database'),
                'username' => $request->input('db_username'),
                'password' => $request->input('db_password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]]);
            
            // Clear any existing database connections to force reconnection
            \DB::purge('mysql');

            // Run installation steps
            $config = [
                'database' => [
                    'host' => $request->input('db_host'),
                    'port' => $request->input('db_port'),
                    'database' => $request->input('db_database'),
                    'username' => $request->input('db_username'),
                    'password' => $request->input('db_password'),
                ],
                'admin' => [
                    'name' => $request->input('admin_name'),
                    'email' => $request->input('admin_email'),
                    'password' => $request->input('admin_password'),
                ],
                'seed_demo_data' => $request->boolean('seed_demo_data'),
            ];

            $result = $this->installerRunner->runInstallation($config);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Lock the installer immediately after successful installation
            $this->lockInstaller();

            // Store installation output for display
            session([
                'installation_output' => $result['output'],
                'installation_success' => true
            ]);

            // Lock installer immediately after success
            $this->lockInstaller();

            return redirect()->route('installer.complete');

        } catch (\Exception $e) {
            return redirect()->route('installer.configuration')
                ->with('error', 'Installation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show installation complete page
     */
    public function complete(): View|RedirectResponse
    {
        if (!session('installation_success')) {
            return redirect()->route('installer.welcome');
        }

        $output = session('installation_output', []);
        
        return view('installer.complete', compact('output'));
    }

    /**
     * Lock the installer to prevent re-installation
     */
    private function lockInstaller(): void
    {
        $lockPath = storage_path('installer.lock');
        
        // Check if storage directory is writable
        if (!is_writable(storage_path())) {
            throw new \Exception('Storage directory is not writable. Cannot create installer lock file.');
        }
        
        // Create lock file with timestamp
        $success = File::put($lockPath, now()->toDateTimeString());
        
        if ($success === false) {
            throw new \Exception('Failed to create installer lock file. Check permissions.');
        }
        
        \Log::info('Installer locked successfully. Lock file created at: ' . $lockPath);
        
        // Disable installer in .env (optional - lock file is primary indicator)
        try {
            $this->envWriter->disableInstaller()->save();
        } catch (\Exception $e) {
            // .env update failed, but lock file created successfully
            \Log::warning('Failed to update .env file during installer lock: ' . $e->getMessage());
        }
    }
}
