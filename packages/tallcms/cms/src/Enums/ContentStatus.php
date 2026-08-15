<?php

declare(strict_types=1);

namespace TallCms\Cms\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ContentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('tallcms::fields.status_draft'),
            self::Pending => __('tallcms::fields.status_pending_review'),
            self::Published => __('tallcms::fields.status_published'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Pending => 'heroicon-o-clock',
            self::Published => 'heroicon-o-check-circle',
        };
    }

    /**
     * Check if content with this status can be publicly visible
     * (must also check published_at)
     */
    public function isPublishable(): bool
    {
        return $this === self::Published;
    }

    /**
     * Get statuses that authors can set directly (without approval)
     */
    public static function authorOptions(): array
    {
        return [
            self::Draft->value => self::Draft->getLabel(),
        ];
    }

    /**
     * Get all status options for editors/admins
     */
    public static function editorOptions(): array
    {
        return [
            self::Draft->value => self::Draft->getLabel(),
            self::Pending->value => self::Pending->getLabel(),
            self::Published->value => self::Published->getLabel(),
        ];
    }

    /**
     * Get status options when review workflow is disabled.
     * All users can publish directly — no Pending state.
     */
    public static function directPublishOptions(): array
    {
        return [
            self::Draft->value => self::Draft->getLabel(),
            self::Published->value => self::Published->getLabel(),
        ];
    }
}
