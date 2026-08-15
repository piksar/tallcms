<?php

namespace TallCms\Cms\Filament\Resources\CmsComments\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;

class ViewCmsComment extends ViewRecord
{
    protected static string $resource = CmsCommentResource::class;

    public function getTitle(): string
    {
        $author = $this->record->getAuthorName() ?? 'Anonymous';

        return "Comment by {$author}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('tallcms::ui.t_comment'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('tallcms::fields.content'))
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'spam' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label(__('tallcms::fields.submitted'))
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make(__('tallcms::fields.author'))
                    ->schema([
                        TextEntry::make('author_name_display')
                            ->label(__('tallcms::fields.name'))
                            ->state(fn ($record) => $record->getAuthorName() ?? 'Anonymous'),
                        TextEntry::make('author_email_display')
                            ->label(__('tallcms::fields.email'))
                            ->state(fn ($record) => $record->getAuthorEmail()),
                        TextEntry::make('user_type')
                            ->label(__('tallcms::fields.type'))
                            ->state(fn ($record) => $record->isGuest() ? 'Guest' : 'Registered User'),
                    ])
                    ->columns(3),

                Section::make(__('tallcms::fields.post'))
                    ->schema([
                        TextEntry::make('post.title')
                            ->label(__('tallcms::fields.post_title'))
                            ->url(fn ($record) => $record->post ? route(
                                'filament.'.config('tallcms.filament.panel_id', 'admin').'.resources.cms-posts.edit',
                                $record->post
                            ) : null),
                        TextEntry::make('parent_info')
                            ->label(__('tallcms::fields.in_reply_to'))
                            ->state(fn ($record) => $record->parent ? 'Comment by '.($record->parent->getAuthorName() ?? 'Anonymous') : 'Top-level comment')
                            ->visible(fn ($record) => $record->parent_id !== null),
                    ])
                    ->columns(2),

                Section::make(__('tallcms::ui.t_moderation'))
                    ->schema([
                        TextEntry::make('approvedBy.name')
                            ->label(__('tallcms::fields.approved_by'))
                            ->visible(fn ($record) => $record->approved_by !== null),
                        TextEntry::make('approved_at')
                            ->label(__('tallcms::fields.approved_at'))
                            ->dateTime()
                            ->visible(fn ($record) => $record->approved_at !== null),
                        TextEntry::make('ip_address')
                            ->label(__('tallcms::fields.ip_address')),
                        TextEntry::make('user_agent')
                            ->label(__('tallcms::fields.user_agent'))
                            ->limit(100),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('tallcms::fields.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->isPending() && auth()->user()?->can('Approve:CmsComment'))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve(auth()->user());
                    $this->record = $this->record->fresh();
                }),

            Action::make('reject')
                ->label(__('tallcms::fields.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ($this->record->isPending() || $this->record->isApproved()) && auth()->user()?->can('Reject:CmsComment'))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject();
                    $this->record = $this->record->fresh();
                }),

            Action::make('unreject')
                ->label(__('tallcms::fields.unreject'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $this->record->isRejected() && auth()->user()?->can('Reject:CmsComment'))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->unreject();
                    $this->record = $this->record->fresh();
                }),

            Action::make('mark_spam')
                ->label(__('tallcms::fields.mark_as_spam'))
                ->icon('heroicon-o-shield-exclamation')
                ->color('gray')
                ->visible(fn () => ! $this->record->isSpam() && auth()->user()?->can('MarkAsSpam:CmsComment'))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsSpam();
                    $this->record = $this->record->fresh();
                }),

            Action::make('not_spam')
                ->label(__('tallcms::fields.not_spam'))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => $this->record->isSpam() && auth()->user()?->can('MarkAsSpam:CmsComment'))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->unmarkSpam();
                    $this->record = $this->record->fresh();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
