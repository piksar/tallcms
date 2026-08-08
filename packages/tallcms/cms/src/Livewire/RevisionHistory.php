<?php

namespace TallCms\Cms\Livewire;

use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Models\CmsRevision;
use TallCms\Cms\Services\ContentDiffService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

class RevisionHistory extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Model $record;

    public ?int $selectedRevision = null;

    public ?int $compareRevision = null;

    public ?array $diff = null;

    // Cached revisions collection
    protected ?Collection $revisionsCache = null;

    public function mount(Model $record): void
    {
        $this->record = $record;
    }

    /**
     * Get revisions for this record (cached)
     */
    public function getRevisionsProperty(): Collection
    {
        if ($this->revisionsCache === null) {
            $this->revisionsCache = $this->record->revisions()
                ->with('user')
                ->orderByDesc('revision_number')
                ->get();
        }

        return $this->revisionsCache;
    }

    /**
     * Find a revision that belongs to this record (security: scoped lookup)
     */
    protected function findRevision(int $id): ?CmsRevision
    {
        return $this->revisions->firstWhere('id', $id);
    }

    public function selectRevision(int $revisionId): void
    {
        // Validate revision belongs to this record
        if (! $this->findRevision($revisionId)) {
            return;
        }

        // Toggle off if clicking same one
        if ($this->selectedRevision === $revisionId) {
            $this->selectedRevision = null;
            $this->compareRevision = null;
            $this->diff = null;

            return;
        }

        if ($this->selectedRevision === null) {
            $this->selectedRevision = $revisionId;
        } else {
            $this->compareRevision = $revisionId;
            $this->calculateDiff();
        }
    }

    public function compareToLatest(int $revisionId): void
    {
        if (! $this->findRevision($revisionId)) {
            return;
        }

        $latest = $this->revisions->first();
        if (! $latest || $latest->id === $revisionId) {
            return;
        }

        $this->selectedRevision = $revisionId;
        $this->compareRevision = $latest->id;
        $this->calculateDiff();
    }

    public function compareToPrevious(int $revisionId): void
    {
        $revision = $this->findRevision($revisionId);
        if (! $revision) {
            return;
        }

        // Find the previous revision from cached collection
        $previous = $this->revisions
            ->where('revision_number', '<', $revision->revision_number)
            ->sortByDesc('revision_number')
            ->first();

        if ($previous) {
            $this->selectedRevision = $previous->id;
            $this->compareRevision = $revisionId;
            $this->calculateDiff();
        }
    }

    public function clearSelection(): void
    {
        $this->selectedRevision = null;
        $this->compareRevision = null;
        $this->diff = null;
    }

    protected function calculateDiff(): void
    {
        if ($this->selectedRevision === null || $this->compareRevision === null) {
            $this->diff = null;

            return;
        }

        $diffService = app(ContentDiffService::class);

        // Get content for both revisions
        $firstContent = $this->getContentForRevision($this->selectedRevision);
        $secondContent = $this->getContentForRevision($this->compareRevision);

        if ($firstContent === null && $secondContent === null) {
            $this->diff = null;

            return;
        }

        // Determine older/newer without mutating user selections
        $firstId = $this->selectedRevision;
        $secondId = $this->compareRevision;
        $firstLabel = $this->getLabelForRevision($firstId);
        $secondLabel = $this->getLabelForRevision($secondId);

        // Determine which is older (for display order only)
        $shouldSwap = $this->isNewer($firstId, $secondId);

        if ($shouldSwap) {
            $olderId = $secondId;
            $newerId = $firstId;
            $olderLabel = $secondLabel;
            $newerLabel = $firstLabel;
            $olderContent = $secondContent;
            $newerContent = $firstContent;
        } else {
            $olderId = $firstId;
            $newerId = $secondId;
            $olderLabel = $firstLabel;
            $newerLabel = $secondLabel;
            $olderContent = $firstContent;
            $newerContent = $secondContent;
        }

        // The older revision is restorable (not the latest/current)
        $latest = $this->revisions->first();
        $restorableId = ($latest && $olderId !== $latest->id) ? $olderId : null;

        $this->diff = [
            'older_label' => $olderLabel,
            'newer_label' => $newerLabel,
            'older_id' => $olderId,
            'newer_id' => $newerId,
            'restorable_id' => $restorableId,
            'restorable_label' => $restorableId ? $this->getLabelForRevision($restorableId) : null,
            'title' => $this->diffField('title', $olderContent, $newerContent),
            'excerpt' => $this->diffField('excerpt', $olderContent, $newerContent),
            'meta_title' => $this->diffField('meta_title', $olderContent, $newerContent),
            'meta_description' => $this->diffField('meta_description', $olderContent, $newerContent),
            'content' => $diffService->diff(
                $olderContent['content'] ?? null,
                $newerContent['content'] ?? null
            ),
        ];
    }

    /**
     * Check if first revision is newer than second (for ordering display)
     */
    protected function isNewer(int $first, int $second): bool
    {
        $rev1 = $this->findRevision($first);
        $rev2 = $this->findRevision($second);

        if (! $rev1 || ! $rev2) {
            return false;
        }

        return $rev1->revision_number > $rev2->revision_number;
    }

    protected function getContentForRevision(int $revisionId): ?array
    {
        $revision = $this->findRevision($revisionId);
        if (! $revision) {
            return null;
        }

        return [
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'content' => $revision->content,
            'meta_title' => $revision->meta_title,
            'meta_description' => $revision->meta_description,
        ];
    }

    protected function getLabelForRevision(int $revisionId): string
    {
        $revision = $this->findRevision($revisionId);
        if (! $revision) {
            return 'Unknown';
        }

        $latest = $this->revisions->first();
        if ($latest && $revision->id === $latest->id) {
            return "Revision #{$revision->revision_number} (Current)";
        }

        return "Revision #{$revision->revision_number}";
    }

    protected function diffField(string $field, ?array $older, ?array $newer): ?array
    {
        $oldValue = $older[$field] ?? null;
        $newValue = $newer[$field] ?? null;

        if ($oldValue === $newValue) {
            return null;
        }

        return [
            'old' => $oldValue,
            'new' => $newValue,
        ];
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(fn (array $arguments) => 'Restore '.$this->getLabelForRevision($arguments['revisionId'] ?? 0))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_restore_revision'))
            ->modalDescription(fn (array $arguments) => 'Are you sure you want to restore '.$this->getLabelForRevision($arguments['revisionId'] ?? 0).'? This will create a new revision with the restored content.')
            ->modalSubmitActionLabel(__('tallcms::ui.t_restore'))
            ->action(function (array $arguments) {
                $revisionId = $arguments['revisionId'] ?? null;
                if (! $revisionId) {
                    return;
                }

                $this->performRestore($revisionId);
            });
    }

    protected function performRestore(int $revisionId): void
    {
        try {
            // Security: verify revision belongs to this record
            $revision = $this->findRevision($revisionId);
            if (! $revision) {
                Notification::make()
                    ->danger()
                    ->title(__('tallcms::ui.t_error'))
                    ->body(__('tallcms::ui.t_revision_not_found_or_does_not_belong_to_this_record'))
                    ->send();

                return;
            }

            $permissionName = $this->record instanceof \TallCms\Cms\Models\CmsPost
                ? 'RestoreRevision:CmsPost'
                : 'RestoreRevision:CmsPage';

            if (! auth()->user()?->can($permissionName)) {
                Notification::make()
                    ->danger()
                    ->title(__('tallcms::ui.t_permission_denied'))
                    ->body(__('tallcms::ui.t_you_do_not_have_permission_to_restore_revisions'))
                    ->send();

                return;
            }

            $this->record->restoreRevision($revision);

            // Refresh the record to get updated data
            $this->record->refresh();

            Notification::make()
                ->success()
                ->title(__('tallcms::ui.t_revision_restored'))
                ->body(__('tallcms::ui.n_content_restored_revision', ['number' => $revision->revision_number]))
                ->send();

            $this->clearSelection();
            $this->revisionsCache = null; // Clear cache to refresh

            // Redirect to the edit page to refresh the form with restored content
            // Use navigate: false to force full page reload and bypass Livewire SPA cache
            // Use resource's getUrl() for multi-panel compatibility
            $editUrl = $this->record instanceof \TallCms\Cms\Models\CmsPost
                ? CmsPostResource::getUrl('edit', ['record' => $this->record])
                : CmsPageResource::getUrl('edit', ['record' => $this->record]);

            $this->redirect($editUrl, navigate: false);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('tallcms::ui.t_error'))
                ->body('Failed to restore revision: '.$e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('tallcms::livewire.revision-history');
    }
}
