<?php

declare(strict_types=1);

namespace TallCms\Cms\Notifications;

use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;

class ContentRejectedNotification extends Notification
{
    public function __construct(
        protected Model $content,
        protected string $reason
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return array_filter(config('tallcms.publishing.notification_channels', ['mail', 'database']));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $contentType = $this->getContentTypeName();

        return (new MailMessage)
            ->subject("Your {$contentType} Needs Revision: {$this->content->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your {$contentType} has been returned for revision.")
            ->line("**Title:** {$this->content->title}")
            ->line('**Reason for rejection:**')
            ->line($this->reason)
            ->action('Edit Content', $this->getEditUrl())
            ->line('Please address the feedback and resubmit for review.');
    }

    /**
     * Get the database representation for Filament notifications.
     */
    public function toDatabase(object $notifiable): array
    {
        $contentType = $this->getContentTypeName();

        return FilamentNotification::make()
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->title(__('tallcms::ui.n_content_rejected', [
                'type' => $contentType,
                'title' => $this->content->title,
            ]))
            ->body($this->reason)
            ->actions([
                FilamentAction::make('edit')
                    ->label(__('tallcms::fields.edit'))
                    ->url($this->getEditUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    /**
     * Get the content type name for display
     */
    protected function getContentTypeName(): string
    {
        if ($this->content instanceof CmsPost) {
            return 'Post';
        }

        if ($this->content instanceof CmsPage) {
            return 'Page';
        }

        return class_basename($this->content);
    }

    /**
     * Get the content type identifier
     */
    protected function getContentType(): string
    {
        if ($this->content instanceof CmsPost) {
            return 'post';
        }

        if ($this->content instanceof CmsPage) {
            return 'page';
        }

        return strtolower(class_basename($this->content));
    }

    /**
     * Get the edit URL for this content
     */
    protected function getEditUrl(): string
    {
        $panelId = config('tallcms.filament.panel_id', 'admin');

        if ($this->content instanceof CmsPost) {
            return route("filament.{$panelId}.resources.cms-posts.edit", $this->content);
        }

        if ($this->content instanceof CmsPage) {
            return route("filament.{$panelId}.resources.cms-pages.edit", $this->content);
        }

        return tallcms_panel_url();
    }
}
