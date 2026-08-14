<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Concerns\HasTranslationCopying;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Services\PublishingWorkflowService;

class EditCmsPost extends EditRecord
{
    use HasTranslationCopying, Translatable {
        HasTranslationCopying::updatedActiveLocale insteadof Translatable;
    }

    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Locale Switcher for translations
            LocaleSwitcher::make(),

            // Copy from default locale action (for translation workflow)
            $this->getCopyFromDefaultAction(),

            // Workflow Actions Group
            // Workflow Actions Group — only visible when review workflow is enabled
            ActionGroup::make([
                $this->getSubmitForReviewAction(),
                $this->getRetractSubmissionAction(),
                $this->getApproveAction(),
                $this->getRejectAction(),
            ])
                ->label(__('tallcms::fields.workflow'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->button()
                ->visible(fn () => tallcms_review_workflow_enabled()),

            // Preview Actions Group
            ActionGroup::make([
                Action::make('preview')
                    ->label(__('tallcms::fields.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn () => route('tallcms.preview.post', ['post' => $this->record->id]))
                    ->openUrlInNewTab(),

                $this->getSharePreviewAction(),
                $this->getRevokePreviewLinksAction(),
            ])
                ->label(__('tallcms::fields.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->button(),

            $this->getSaveSnapshotAction(),
            $this->getMarkAsReviewedAction(),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getMarkAsReviewedAction(): Action
    {
        return Action::make('markAsReviewed')
            ->label(__('tallcms::fields.mark_as_reviewed'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn () => $this->record !== null && Schema::hasColumn('tallcms_posts', 'last_reviewed_at'))
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_mark_content_as_reviewed'))
            ->modalDescription(__('tallcms::ui.t_this_will_update_the_review_timestamp_and_set_you_as_the_reviewer'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_confirm_review'))
            ->action(function () {
                $this->record->update([
                    'last_reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);

                Notification::make()
                    ->success()
                    ->title(__('tallcms::ui.t_content_marked_as_reviewed'))
                    ->send();

                $this->refreshFormData(['last_reviewed_at', 'reviewed_by']);
            });
    }

    protected function getSaveSnapshotAction(): Action
    {
        return Action::make('saveSnapshot')
            ->label(__('tallcms::fields.save_snapshot'))
            ->icon('heroicon-o-camera')
            ->color('gray')
            ->visible(fn () => $this->record !== null && auth()->user()?->can('ViewRevisions:CmsPost'))
            ->form([
                Textarea::make('notes')
                    ->label(__('tallcms::fields.snapshot_notes_optional'))
                    ->placeholder(__('tallcms::ui.t_describe_this_milestone'))
                    ->rows(2),
            ])
            ->modalHeading(__('tallcms::fields.save_snapshot'))
            ->modalDescription(__('tallcms::ui.t_save_your_current_changes_and_create_a_pinned_milestone_in_the_revis'))
            ->modalSubmitActionLabel(__('tallcms::fields.save_snapshot'))
            ->action(function (array $data) {
                // Skip ALL auto revision hooks for this save
                $this->record->skipRevisions();

                // Save form first to capture unsaved changes
                $this->save();
                $this->record->refresh();

                // Create manual snapshot directly (not via hooks)
                $this->record->createManualSnapshot($data['notes'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('tallcms::ui.t_snapshot_saved'))
                    ->body(__('tallcms::ui.t_changes_saved_and_snapshot_created'))
                    ->send();
            });
    }

    protected function getSubmitForReviewAction(): Action
    {
        return Action::make('submitForReview')
            ->label(__('tallcms::fields.submit_for_review'))
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn () => $this->record->canSubmitForReview() && auth()->user()?->can('SubmitForReview:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::fields.submit_for_review'))
            ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_submit_this_post_for_review_an_editor_will_'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_submit'))
            ->action(function () {
                app(PublishingWorkflowService::class)->submitForReview($this->record);

                Notification::make()
                    ->title(__('tallcms::ui.t_submitted_for_review'))
                    ->body(__('tallcms::ui.t_your_post_has_been_submitted_for_review'))
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'submitted_at', 'submitted_by']);
            });
    }

    protected function getRetractSubmissionAction(): Action
    {
        return Action::make('retractSubmission')
            ->label(__('tallcms::fields.retract_submission'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn () => $this->record->canRetractSubmission())
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::fields.retract_submission'))
            ->modalDescription(__('tallcms::ui.t_this_will_move_the_post_back_to_draft_so_you_can_edit_it_you_can_sub'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_retract'))
            ->action(function () {
                $this->record->retractSubmission();

                Notification::make()
                    ->title(__('tallcms::ui.t_submission_retracted'))
                    ->body(__('tallcms::ui.t_the_post_has_been_moved_back_to_draft'))
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'submitted_at', 'submitted_by']);
            });
    }

    protected function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label(__('tallcms::fields.approve_publish'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn () => $this->record->canBeApproved() && auth()->user()?->can('Approve:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::fields.approve_publish'))
            ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_approve_and_publish_this_post_it_will_be_vi'))
            ->modalSubmitActionLabel(__('tallcms::fields.approve'))
            ->action(function () {
                app(PublishingWorkflowService::class)->approve($this->record);

                Notification::make()
                    ->title(__('tallcms::ui.t_post_approved'))
                    ->body($this->record->isScheduled()
                        ? __('tallcms::ui.n_post_approved_scheduled', [
                            'date' => $this->record->published_at->format('M j, Y g:i A'),
                        ])
                        : __('tallcms::ui.n_post_approved_published'))
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'approved_at', 'approved_by', 'published_at']);
            });
    }

    protected function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('tallcms::fields.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => $this->record->canBeRejected() && auth()->user()?->can('Approve:CmsPost'))
            ->form([
                Textarea::make('rejection_reason')
                    ->label(__('tallcms::fields.reason_for_rejection'))
                    ->required()
                    ->rows(4)
                    ->placeholder(__('tallcms::ui.t_please_explain_why_this_post_is_being_rejected_and_what_changes_are_')),
            ])
            ->modalHeading(__('tallcms::ui.t_reject_post'))
            ->modalDescription(__('tallcms::ui.t_please_provide_a_reason_for_rejection_the_author_will_be_notified'))
            ->modalSubmitActionLabel(__('tallcms::fields.reject'))
            ->action(function (array $data) {
                app(PublishingWorkflowService::class)->reject($this->record, $data['rejection_reason']);

                Notification::make()
                    ->title(__('tallcms::ui.t_post_rejected'))
                    ->body(__('tallcms::ui.t_the_author_has_been_notified_with_your_feedback'))
                    ->warning()
                    ->send();

                $this->refreshFormData(['status', 'rejection_reason']);
            });
    }

    protected function getSharePreviewAction(): Action
    {
        return Action::make('sharePreview')
            ->label(__('tallcms::fields.share_preview_link'))
            ->icon('heroicon-o-share')
            ->visible(fn () => auth()->user()?->can('GeneratePreviewLink:CmsPost'))
            ->form([
                Radio::make('expiry')
                    ->label(__('tallcms::fields.link_expires_in'))
                    ->options([
                        '1' => __('tallcms::ui.expiry_1_hour'),
                        '24' => __('tallcms::ui.expiry_24_hours'),
                        '168' => __('tallcms::ui.expiry_7_days'),
                        '720' => __('tallcms::ui.expiry_30_days'),
                    ])
                    ->default('24')
                    ->required(),
            ])
            ->modalHeading(__('tallcms::ui.t_generate_shareable_preview_link'))
            ->modalDescription(__('tallcms::ui.t_create_a_link_that_allows_anyone_to_preview_this_content_without_log'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_generate_link'))
            ->action(function (array $data) {
                $hours = (int) $data['expiry'];
                $token = $this->record->createPreviewToken(Carbon::now()->addHours($hours));

                $url = $token->getPreviewUrl();

                Notification::make()
                    ->title(__('tallcms::ui.t_preview_link_generated'))
                    ->body(__('tallcms::ui.n_link_expires_hours', ['hours' => $hours]))
                    ->success()
                    ->actions([
                        Action::make('copy')
                            ->label(__('tallcms::fields.copy_link'))
                            ->url($url)
                            ->openUrlInNewTab(),
                    ])
                    ->persistent()
                    ->send();
            });
    }

    protected function getRevokePreviewLinksAction(): Action
    {
        return Action::make('revokePreviewLinks')
            ->label(__('tallcms::fields.revoke_all_preview_links'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn () => $this->record->hasActivePreviewTokens() && auth()->user()?->can('GeneratePreviewLink:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_revoke_preview_links'))
            ->modalDescription(fn () => __('tallcms::ui.n_revoke_preview_links_confirm', [
                'count' => $this->record->getActivePreviewTokenCount(),
            ]))
            ->modalSubmitActionLabel(__('tallcms::ui.t_revoke_all'))
            ->action(function () {
                $count = $this->record->revokeAllPreviewTokens();

                Notification::make()
                    ->title(__('tallcms::ui.t_preview_links_revoked'))
                    ->body(__('tallcms::ui.n_preview_links_revoked', ['count' => $count]))
                    ->success()
                    ->send();
            });
    }
}
