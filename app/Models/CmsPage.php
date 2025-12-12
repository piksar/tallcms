<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    use SoftDeletes;
    
    protected $table = 'tallcms_pages';
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'status',
        'is_homepage',
        'published_at',
        'parent_id',
        'sort_order',
        'template',
    ];
    
    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
        'is_homepage' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }
            
            // Ensure only one page can be marked as homepage
            if ($page->is_homepage) {
                static::where('is_homepage', true)->update(['is_homepage' => false]);
            }
        });
        
        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }
            
            // Ensure only one page can be marked as homepage
            if ($page->is_homepage && $page->isDirty('is_homepage')) {
                static::where('id', '!=', $page->id)->update(['is_homepage' => false]);
            }
        });
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'parent_id')->orderBy('sort_order');
    }
    
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }
    
    public function scopeWithSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }
    
    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }
    
    public static function getHomepage(): ?self
    {
        return static::homepage()->published()->first();
    }
    
    /**
     * Generate a unique slug from title
     */
    public function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug already exists (excluding current record)
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);
        
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }
        
        return $query->exists();
    }
}
