<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TallCmsSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tallcms:setup 
                            {--force : Force setup even if already configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup TallCMS with initial roles, permissions, and admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up TallCMS...');
        $this->newLine();

        // Check if already setup
        if (!$this->option('force') && $this->isAlreadySetup()) {
            $this->warn('TallCMS appears to be already set up.');
            $this->info('Use --force flag to force re-setup.');
            return Command::FAILURE;
        }

        // Create roles and permissions
        $this->createRolesAndPermissions();
        
        // Create or update first admin user
        $this->createAdminUser();
        
        $this->newLine();
        $this->info('✅ TallCMS setup completed successfully!');
        $this->info('You can now access the admin panel at /admin');
        
        return Command::SUCCESS;
    }

    protected function isAlreadySetup(): bool
    {
        return Role::where('name', 'super_admin')->exists() && 
               User::role('super_admin')->exists();
    }

    protected function createRolesAndPermissions(): void
    {
        $this->info('📝 Creating roles and permissions...');

        // Create roles
        $roles = [
            'super_admin' => 'Super Administrator - Complete system access',
            'administrator' => 'Administrator - Full content and limited user management', 
            'editor' => 'Editor - Full content management',
            'author' => 'Author - Create and edit own content',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(['name' => $roleName]);
            $this->line("  ✓ Created role: {$roleName}");
        }

        // Run Shield's seeder to create permissions and assign to roles
        // Credit: Using Filament Shield by Bezhan Salleh for role-based permissions
        // https://github.com/bezhanSalleh/filament-shield
        $this->info('🛡️  Running Shield seeder for permissions...');
        $this->call('db:seed', ['--class' => 'ShieldSeeder']);
        
        // Now customize the role permissions for our CMS roles
        $this->customizeRolePermissions();

        $this->info('✅ Roles and permissions created!');
        $this->newLine();
    }

    protected function createAdminUser(): void
    {
        $this->info('👤 Setting up admin user...');
        
        // Check if we have an existing user
        $existingUser = User::first();
        
        if ($existingUser && !$this->option('force')) {
            $makeAdmin = $this->confirm("Found existing user ({$existingUser->email}). Make them super admin?", true);
            
            if ($makeAdmin) {
                $existingUser->assignRole('super_admin');
                $this->info("✅ {$existingUser->email} is now a super admin!");
                return;
            }
        }

        // Create new admin user
        $name = $this->ask('Admin full name', 'Admin User');
        
        $email = $this->ask('Admin email address');
        while (!$email || !$this->isValidEmail($email) || User::where('email', $email)->exists()) {
            if (!$email) {
                $this->error('Email address is required.');
            } elseif (!$this->isValidEmail($email)) {
                $this->error('Please enter a valid email address.');
            } else {
                $this->error('This email already exists.');
            }
            $email = $this->ask('Admin email address');
        }
        
        $password = $this->secret('Admin password');
        while (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            $password = $this->secret('Admin password');
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign super admin role
        $user->assignRole('super_admin');

        $this->info("✅ Super admin user created: {$email}");
    }

    protected function customizeRolePermissions(): void
    {
        $allPermissions = Permission::all();
        
        if ($allPermissions->isEmpty()) {
            $this->error('No permissions found! Shield seeder may have failed.');
            return;
        }
        
        // Super Admin already has all permissions from Shield seeder
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->line("  ✓ Super Admin: {$superAdminRole->permissions->count()} permissions (full access)");
        
        // Administrator: Content + limited user management + some settings
        $administratorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isAdministratorPermission($permission->name);
        });
        $administratorRole = Role::where('name', 'administrator')->first();
        $administratorRole->syncPermissions($administratorPermissions);
        $this->line("  ✓ Administrator: {$administratorPermissions->count()} permissions (content + users + settings)");
        
        // Editor: Full content management, no users/settings
        $editorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isEditorPermission($permission->name);
        });
        $editorRole = Role::where('name', 'editor')->first();
        $editorRole->syncPermissions($editorPermissions);
        $this->line("  ✓ Editor: {$editorPermissions->count()} permissions (content management only)");
        
        // Author: Own content + basic operations
        $authorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isAuthorPermission($permission->name);
        });
        $authorRole = Role::where('name', 'author')->first();
        $authorRole->syncPermissions($authorPermissions);
        $this->line("  ✓ Author: {$authorPermissions->count()} permissions (basic content creation)");
    }

    protected function isAdministratorPermission(string $permission): bool
    {
        // Allow all content management (CmsPage, CmsPost, CmsCategory)
        if (str_contains($permission, 'CmsPage') || 
            str_contains($permission, 'CmsPost') || 
            str_contains($permission, 'CmsCategory') ||
            str_contains($permission, 'TallcmsMenu') ||
            str_contains($permission, 'TallcmsMedia')) {
            return true;
        }
        
        // Allow user management (but exclude Shield roles)
        if (str_contains($permission, 'User') && 
            !str_contains($permission, 'Role') && 
            !str_contains($permission, 'Shield')) {
            return true;
        }
        
        // Allow site settings page
        if (str_contains($permission, 'SiteSettings')) {
            return true;
        }
        
        return false;
    }

    protected function isEditorPermission(string $permission): bool
    {
        // Full content management
        if (str_contains($permission, 'CmsPage') || 
            str_contains($permission, 'CmsPost') || 
            str_contains($permission, 'CmsCategory') ||
            str_contains($permission, 'TallcmsMenu') ||
            str_contains($permission, 'TallcmsMedia')) {
            return true;
        }
        
        // No user management, no settings, no system features
        return false;
    }

    protected function isAuthorPermission(string $permission): bool
    {
        // Basic content permissions (view, create, update)
        if (str_contains($permission, 'CmsPage') || str_contains($permission, 'CmsPost')) {
            // Allow ViewAny, View, Create, Update
            if (str_contains($permission, 'ViewAny:') || 
                str_contains($permission, 'View:') || 
                str_contains($permission, 'Create:') || 
                str_contains($permission, 'Update:')) {
                return true;
            }
            // Exclude Delete, ForceDelete, Restore for security
        }
        
        // View categories only (but can't manage them)
        if (str_contains($permission, 'CmsCategory') && 
            (str_contains($permission, 'ViewAny:') || str_contains($permission, 'View:'))) {
            return true;
        }
        
        // Basic media operations
        if (str_contains($permission, 'TallcmsMedia') && 
            (str_contains($permission, 'ViewAny:') || 
             str_contains($permission, 'View:') || 
             str_contains($permission, 'Create:') || 
             str_contains($permission, 'Update:'))) {
            return true;
        }
        
        // Basic dashboard access
        if ($permission === 'view_dashboard') {
            return true;
        }
        
        // Menu management (view only)
        if (str_contains($permission, 'TallcmsMenu') && 
            (str_contains($permission, 'ViewAny:') || str_contains($permission, 'View:'))) {
            return true;
        }
        
        return false;
    }

    protected function isValidEmail(string $email): bool
    {
        return Validator::make(['email' => $email], [
            'email' => 'required|email'
        ])->passes();
    }
}
