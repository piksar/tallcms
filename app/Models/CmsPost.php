<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPost extends Model
{
    use SoftDeletes;
    
    protected $table = 'tallcms_posts';
    
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'is_featured',
        'views',
    ];
    
    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
            if (empty($post->author_id)) {
                $post->author_id = auth()->id();
            }
        });
        
        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
        });
    }
    
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CmsCategory::class, 'tallcms_post_category', 'post_id', 'category_id');
    }
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }
    
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    public function scopeWithSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
    
    public function scopeInCategory($query, $categorySlug)
    {
        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }
    
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->excerpt . ' ' . json_encode($this->content)));
        return (int) ceil($wordCount / 200); // Assuming 200 words per minute
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
