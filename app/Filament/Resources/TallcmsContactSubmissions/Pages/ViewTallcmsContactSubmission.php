<?php

namespace App\Filament\Resources\TallcmsContactSubmissions\Pages;

use App\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use App\Models\TallcmsContactSubmission;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Page;

class ViewTallcmsContactSubmission extends Page
{
    protected static string $resource = TallcmsContactSubmissionResource::class;

    protected string $view = 'filament.resources.tallcms-contact-submissions.pages.view-tallcms-contact-submission';

    public TallcmsContactSubmission $record;

    public function mount(int|string $record): void
    {
        $this->record = TallcmsContactSubmission::findOrFail($record);

        // Mark as read when viewing
        if (! $this->record->is_read) {
            $this->record->markAsRead();
        }
    }

    public function getTitle(): string
    {
        $name = $this->record->name ?? 'Unknown';

        return "Submission from {$name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_read')
                ->label(fn () => $this->record->is_read ? 'Mark as Unread' : 'Mark as Read')
                ->icon(fn () => $this->record->is_read ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
                ->action(function () {
                    $this->record->is_read
                        ? $this->record->markAsUnread()
                        : $this->record->markAsRead();

                    $this->record = $this->record->fresh();
                }),

            DeleteAction::make()
                ->record($this->record)
                ->successRedirectUrl(TallcmsContactSubmissionResource::getUrl()),
        ];
    }
}
