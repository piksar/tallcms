<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use TallCms\Cms\Jobs\TallCmsUpdateJob;
use TallCms\Cms\Services\TallCmsUpdater;

class SystemUpdates extends Page
{
    use HasPageShield;

    protected string $view = 'tallcms::filament.pages.system-updates';

    public function getTitle(): string
    {
        return __('tallcms::pages.system_updates.title');
    }

    /**
     * Only register in standalone mode (not plugin mode)
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::isStandaloneMode();
    }

    /**
     * Check if running in standalone mode
     */
    protected static function isStandaloneMode(): bool
    {
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        return file_exists(base_path('.tallcms-standalone'));
    }

    public ?array $latestRelease = null;

    public ?array $preflightChecks = null;

    public ?array $dbBackupCapability = null;

    public bool $updateAvailable = false;

    public string $currentVersion = '';

    public bool $quarantineConfirmed = false;

    public bool $skipDbBackup = false;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.system_updates.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('system');
    }

    public static function getNavigationSort(): ?int
    {
        return 54;
    }

    public function mount(): void
    {
        // Block access in plugin mode
        if (! static::isStandaloneMode()) {
            abort(404);
        }

        $updater = app(TallCmsUpdater::class);

        $this->currentVersion = config('tallcms.version');
        $this->latestRelease = $updater->checkForUpdates();
        $this->updateAvailable = $updater->isUpdateAvailable();
        $this->preflightChecks = $updater->runPreflightChecks();
        $this->dbBackupCapability = $updater->checkDatabaseBackupCapability();
    }

    public function refreshUpdateCheck(): void
    {
        $updater = app(TallCmsUpdater::class);

        // Clear cache to force fresh check
        cache()->forget('tallcms_latest_release');
        cache()->forget('tallcms_last_update_check');

        $this->latestRelease = $updater->checkForUpdates();
        $this->updateAvailable = $updater->isUpdateAvailable();
        $this->preflightChecks = $updater->runPreflightChecks();
        $this->dbBackupCapability = $updater->checkDatabaseBackupCapability();

        Notification::make()
            ->title(__('tallcms::ui.t_update_check_complete'))
            ->success()
            ->send();
    }

    public function startUpdate(): void
    {
        $updater = app(TallCmsUpdater::class);

        // Verify preflight checks pass
        foreach ($this->preflightChecks as $check => $result) {
            if ($result['status'] === 'fail') {
                Notification::make()
                    ->title(__('tallcms::ui.t_preflight_check_failed'))
                    ->body($result['message'])
                    ->danger()
                    ->send();

                return;
            }
        }

        // SERVER-SIDE GUARD: Block update if backup required but not capable AND not acknowledged
        $requireDbBackup = config('tallcms.updates.require_db_backup', true);
        $dbCapable = $this->dbBackupCapability['capable'] ?? false;
        if ($requireDbBackup && ! $dbCapable && ! $this->skipDbBackup) {
            Notification::make()
                ->title(__('tallcms::ui.t_database_backup_acknowledgment_required'))
                ->body(__('tallcms::ui.t_please_acknowledge_the_database_backup_warning_before_proceeding'))
                ->danger()
                ->send();

            return;
        }

        try {
            $updater->validateNoLock();
            $updater->validateDiskSpace();
            $updater->verifySodiumAvailable();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('tallcms::ui.t_cannot_start_update'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $targetVersion = $this->latestRelease['version'] ?? null;
        if (! $targetVersion) {
            Notification::make()
                ->title(__('tallcms::ui.t_no_update_available'))
                ->warning()
                ->send();

            return;
        }

        // Create initial state
        $updater->updateState([
            'status' => 'pending',
            'version' => $targetVersion,
            'started_at' => now()->toIso8601String(),
            'steps' => [],
            'skip_db_backup' => $this->skipDbBackup,
        ]);

        // Try execution methods in order
        $method = $this->tryExecBackground($updater, $targetVersion)
            ?? $this->tryQueueJob($updater, $targetVersion)
            ?? 'manual';

        if ($method === 'manual') {
            $updater->updateState([
                'status' => 'pending_manual',
                'execution_method' => 'manual',
            ]);

            $this->redirect(UpdateManual::getUrl());

            return;
        }

        $updater->updateState(['execution_method' => $method]);

        Notification::make()
            ->title(__('tallcms::ui.t_update_started'))
            ->body(__('tallcms::ui.n_update_initiated', ['version' => $targetVersion, 'method' => $method]))
            ->success()
            ->send();

        $this->redirect(UpdateProgress::getUrl());
    }

    private function tryExecBackground(TallCmsUpdater $updater, string $targetVersion): ?string
    {
        if (! $updater->isExecAvailable()) {
            return null;
        }

        $logFile = storage_path('logs/update-'.date('Y-m-d_His').'.log');

        $php = $this->findPhpBinary();
        $artisan = base_path('artisan');
        $skipDbFlag = $this->skipDbBackup ? ' --skip-db-backup' : '';

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B {$php} {$artisan} tallcms:update --target={$targetVersion} --force{$skipDbFlag} > {$logFile} 2>&1", 'r'));
        } else {
            // Redirect stdin from /dev/null, stdout/stderr to log file, run in background
            exec("{$php} {$artisan} tallcms:update --target={$targetVersion} --force{$skipDbFlag} > {$logFile} 2>&1 < /dev/null &");
        }

        Log::info('SystemUpdates: Started update via exec', ['version' => $targetVersion]);

        return 'exec';
    }

    private function tryQueueJob(TallCmsUpdater $updater, string $targetVersion): ?string
    {
        if (! $updater->isQueueAvailable()) {
            return null;
        }

        TallCmsUpdateJob::dispatch($targetVersion, $this->skipDbBackup)
            ->onQueue('tallcms-updates');

        Log::info('SystemUpdates: Dispatched update job', ['version' => $targetVersion]);

        return 'queue';
    }

    public function clearStaleLock(): void
    {
        $updater = app(TallCmsUpdater::class);
        $updater->clearLock();
        $updater->clearState();

        Notification::make()
            ->title(__('tallcms::ui.t_lock_cleared'))
            ->body(__('tallcms::ui.t_you_can_now_retry_the_update'))
            ->success()
            ->send();

        $this->preflightChecks = $updater->runPreflightChecks();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('tallcms::fields.check_for_updates'))
                ->icon('heroicon-o-arrow-path')
                ->action('refreshUpdateCheck'),
        ];
    }

    /**
     * Find the PHP CLI binary path.
     */
    private function findPhpBinary(): string
    {
        // Check config first
        if ($configured = config('tallcms.updates.php_binary')) {
            return $configured;
        }

        // Common CLI paths to check
        $paths = [
            '/opt/homebrew/bin/php',      // macOS Homebrew ARM
            '/usr/local/bin/php',          // macOS Homebrew Intel / Linux
            '/usr/bin/php',                // System PHP
            '/usr/bin/php8.3',             // Versioned PHP
            '/usr/bin/php8.2',
            '/usr/bin/php8.1',
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Fallback: try to derive from PHP_BINARY (works if CLI, not FPM)
        $binary = PHP_BINARY;
        if (! str_contains($binary, 'fpm')) {
            return $binary;
        }

        // Last resort
        return 'php';
    }
}
