<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TallcmsContactSubmission extends Model
{
    protected $table = 'tallcms_contact_submissions';

    protected $fillable = [
        'name',
        'email',
        'form_data',
        'page_url',
        'is_read',
    ];

    protected $casts = [
        'form_data' => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Scope to get only unread submissions.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get recent submissions (last 30 days).
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Mark the submission as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark the submission as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update(['is_read' => false]);
    }

    /**
     * Get a specific field value from form_data by name.
     */
    public function getFieldValue(string $fieldName): ?string
    {
        foreach ($this->form_data ?? [] as $field) {
            if (($field['name'] ?? null) === $fieldName) {
                return $field['value'] ?? null;
            }
        }

        return null;
    }
}
