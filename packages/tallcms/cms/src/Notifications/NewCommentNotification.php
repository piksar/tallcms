<?php

declare(strict_types=1);

namespace TallCms\Cms\Notifications;

use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TallCms\Cms\Models\CmsComment;

class NewCommentNotification extends Notification
{
    public function __construct(
        protected CmsComment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return array_filter(config('tallcms.comments.notification_channels', ['mail', 'database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $authorName = $this->comment->getAuthorName() ?? 'A visitor';
        $postTitle = $this->comment->post?->title ?? 'Unknown Post';

        return (new MailMessage)
            ->subject("New comment on: {$postTitle}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$authorName} has left a new comment on \"{$postTitle}\".")
            ->line('**Comment:**')
            ->line(\Illuminate\Support\Str::limit($this->comment->content, 200))
            ->action('Review Comment', $this->getViewUrl())
            ->line('Please review and approve or reject this comment.');
    }

    public function toDatabase(object $notifiable): array
    {
        $authorName = $this->comment->getAuthorName() ?? 'A visitor';
        $postTitle = $this->comment->post?->title ?? 'Unknown Post';

        return FilamentNotification::make()
            ->warning()
            ->icon('heroicon-o-chat-bubble-left-right')
            ->title(__('tallcms::ui.n_new_comment_on', ['title' => $postTitle]))
            ->body(__('tallcms::ui.n_comment_author_excerpt', [
                'author' => $authorName,
                'excerpt' => \Illuminate\Support\Str::limit($this->comment->content, 80),
            ]))
            ->actions([
                FilamentAction::make('review')
                    ->label(__('tallcms::ui.t_review'))
                    ->url($this->getViewUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function getViewUrl(): string
    {
        $panelId = config('tallcms.filament.panel_id', 'admin');

        return route("filament.{$panelId}.resources.cms-comments.view", $this->comment);
    }
}
