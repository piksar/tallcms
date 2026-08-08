<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use TallCms\Cms\Services\TallCmsUpdater;

class UpdateProgress extends Page
{
    use HasPageShield;

    protected string $view = 'tallcms::filament.pages.update-progress';

    public function getTitle(): string
    {
        return __('tallcms::pages.update_progress.title');
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.update_progress.navigation');
    }

    #[Computed]
    public function updateState(): array
    {
        $updater = app(TallCmsUpdater::class);

        return $updater->getUpdateState();
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

    public function mount(): void
    {
        // Block access in plugin mode
        if (! static::isStandaloneMode()) {
            abort(404);
        }

        $state = $this->updateState;

        // Redirect if no update in progress
        if ($state['status'] === 'no_update') {
            $this->redirect(SystemUpdates::getUrl());
        }
    }

    public function clearAndRetry(): void
    {
        $updater = app(TallCmsUpdater::class);
        $updater->clearLock();
        $updater->clearState();

        $this->redirect(SystemUpdates::getUrl());
    }

    public function backToUpdates(): void
    {
        $this->redirect(SystemUpdates::getUrl());
    }
}
