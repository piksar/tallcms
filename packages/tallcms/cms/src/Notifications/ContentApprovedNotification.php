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

class ContentApprovedNotification extends Notification
{
    public function __construct(
        protected Model $content
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
        $approverName = $this->content->approver?->name ?? 'An administrator';
        $isScheduled = $this->content->published_at?->isFuture() ?? false;

        $message = (new MailMessage)
            ->subject("Your {$contentType} Has Been Approved: {$this->content->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$approverName} has approved your {$contentType}.")
            ->line("**Title:** {$this->content->title}");

        if ($isScheduled) {
            $message->line("**Scheduled for:** {$this->content->published_at->format('F j, Y \\a\\t g:i A')}");
        } else {
            $message->line('Your content is now live!');
        }

        // Use frontend URL for published content, admin URL if scheduled
        $actionUrl = $isScheduled ? $this->getViewUrl() : $this->getFrontendUrl();
        $actionLabel = $isScheduled ? 'View in Admin' : 'View Live';

        return $message
            ->action($actionLabel, $actionUrl)
            ->line('Thank you for your contribution!');
    }

    /**
     * Get the database representation for Filament notifications.
     */
    public function toDatabase(object $notifiable): array
    {
        $contentType = $this->getContentTypeName();
        $approverName = $this->content->approver?->name ?? 'An administrator';
        $isScheduled = $this->content->published_at?->isFuture() ?? false;

        $body = $isScheduled
            ? "Scheduled for {$this->content->published_at->format('M j, Y')}"
            : 'Your content is now live!';

        // Use frontend URL for published content, admin URL if scheduled
        $actionUrl = $isScheduled ? $this->getViewUrl() : $this->getFrontendUrl();
        $actionLabel = $isScheduled ? 'View in Admin' : 'View Live';

        return FilamentNotification::make()
            ->success()
            ->icon('heroicon-o-check-circle')
            ->title(__('tallcms::ui.n_content_approved', [
                'type' => $contentType,
                'title' => $this->content->title,
            ]))
            ->body($body)
            ->actions([
                FilamentAction::make('view')
                    ->label($actionLabel)
                    ->url($actionUrl)
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
     * Get the view URL for this content (admin edit page)
     */
    protected function getViewUrl(): string
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

    /**
     * Get the public frontend URL for this content
     */
    protected function getFrontendUrl(): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        if ($this->content instanceof CmsPost) {
            return url("{$prefix}/blog/{$this->content->slug}");
        }

        if ($this->content instanceof CmsPage) {
            return $this->content->is_homepage ? url('/') : url("{$prefix}/{$this->content->slug}");
        }

        return url('/');
    }
}
