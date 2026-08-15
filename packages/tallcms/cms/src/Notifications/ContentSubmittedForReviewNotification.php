<?php

declare(strict_types=1);

namespace TallCms\Cms\Notifications;

use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;

class ContentSubmittedForReviewNotification extends Notification
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
        $submitterName = $this->content->submitter?->name ?? 'Unknown';
        $siteName = $this->getSiteName();

        $subject = $siteName
            ? "New {$contentType} for Review on {$siteName}: {$this->content->title}"
            : "New {$contentType} Submitted for Review: {$this->content->title}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$submitterName} has submitted a {$contentType} for your review.");

        if ($siteName) {
            $mail->line("**Site:** {$siteName}");
        }

        return $mail
            ->line("**Title:** {$this->content->title}")
            ->action('Review Content', $this->getEditUrl())
            ->line('Please review and approve or reject this content.');
    }

    /**
     * Get the database representation for Filament notifications.
     */
    public function toDatabase(object $notifiable): array
    {
        $contentType = $this->getLocalizedContentTypeName();
        $submitterName = $this->content->submitter?->name ?? 'Unknown';

        return FilamentNotification::make()
            ->warning()
            ->icon('heroicon-o-document-text')
            ->title(__('tallcms::ui.n_new_content_for_review', ['type' => $contentType]))
            ->body($this->getSiteName()
                ? __('tallcms::ui.n_submitted_on_site', [
                    'name' => $submitterName,
                    'site' => $this->getSiteName(),
                    'title' => $this->content->title,
                ])
                : __('tallcms::ui.n_submitted_content', [
                    'name' => $submitterName,
                    'title' => $this->content->title,
                ]))
            ->actions([
                FilamentAction::make('review')
                    ->label(__('tallcms::ui.t_review'))
                    ->url($this->getEditUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    /**
     * Get the content type name for display in the (currently English-only) mail template.
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
     * Get the localized content type name for the Filament database notification.
     */
    protected function getLocalizedContentTypeName(): string
    {
        if ($this->content instanceof CmsPost) {
            return tallcms_label('posts', 'singular');
        }

        if ($this->content instanceof CmsPage) {
            return tallcms_label('pages', 'singular');
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
        $url = null;

        if ($this->content instanceof CmsPost) {
            $url = route("filament.{$panelId}.resources.cms-posts.edit", $this->content);
        } elseif ($this->content instanceof CmsPage) {
            $url = route("filament.{$panelId}.resources.cms-pages.edit", $this->content);
        }

        if (! $url) {
            return tallcms_panel_url();
        }

        // Append site context so the admin auto-switches to the correct site
        $siteId = $this->content->site_id ?? null;
        if ($siteId) {
            $url .= (str_contains($url, '?') ? '&' : '?').'switch_site='.$siteId;
        }

        return $url;
    }

    /**
     * Get the site name for the content (multisite context).
     */
    protected function getSiteName(): ?string
    {
        $siteId = $this->content->site_id ?? null;

        if (! $siteId) {
            return null;
        }

        try {
            return DB::table('tallcms_sites')
                ->where('id', $siteId)
                ->value('name');
        } catch (\Throwable) {
            return null;
        }
    }
}
