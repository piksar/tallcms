<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Support\InstallationStatus;
use TallCms\Cms\Tests\TestCase;

class InstallationStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $createdPaths = [];

    /** @var array<string, string> path => temporary stash path */
    private array $stashedPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ($this->stashedPaths as $original => $stash) {
            if (is_file($stash)) {
                @rename($stash, $original);
            }
        }

        parent::tearDown();
    }

    public function test_standalone_complete_without_env_file(): void
    {
        $this->asStandalone();
        $this->ensureInstallerLock();
        $this->ensureAbsent(base_path('.env'));

        $this->assertTrue(File::exists(base_path('installer.lock')));
        $this->assertFalse(File::exists(base_path('.env')));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable((new SiteSetting)->getTable()));

        $this->assertFalse(InstallationStatus::isIncomplete());
    }

    public function test_standalone_incomplete_without_installer_lock(): void
    {
        $this->asStandalone();
        $this->ensureAbsent(base_path('installer.lock'));
        $this->ensureAbsent(storage_path('installer.lock'));

        $this->assertTrue(InstallationStatus::isIncomplete());
    }

    public function test_plugin_mode_only_requires_settings_table(): void
    {
        config([
            'tallcms.mode' => 'plugin',
            'tallcms.plugin_mode.skip_installer_check' => true,
        ]);

        $this->ensureAbsent(base_path('installer.lock'));
        $this->ensureAbsent(storage_path('installer.lock'));
        $this->ensureAbsent(base_path('.env'));

        $this->assertFalse(InstallationStatus::isIncomplete());
    }

    public function test_standalone_complete_with_env_file(): void
    {
        $this->asStandalone();
        $this->ensureInstallerLock();
        $this->ensureFile(base_path('.env'));

        $this->assertFalse(InstallationStatus::isIncomplete());
    }

    private function asStandalone(): void
    {
        config(['tallcms.mode' => 'standalone']);
    }

    private function ensureInstallerLock(): void
    {
        $this->ensureFile(base_path('installer.lock'));
    }

    private function ensureFile(string $path): void
    {
        if (File::exists($path)) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');
        $this->createdPaths[] = $path;
    }

    private function ensureAbsent(string $path): void
    {
        if (! File::exists($path)) {
            return;
        }

        $stash = $path.'.installation-status-test-stash';
        if (File::exists($stash)) {
            @unlink($stash);
        }

        rename($path, $stash);
        $this->stashedPaths[$path] = $stash;
    }
}
