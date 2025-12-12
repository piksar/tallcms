<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use Illuminate\Support\Facades\Storage;

class MergeTagService
{
    /**
     * Replace merge tags in content
     */
    public static function replaceTags(string $content, $record = null): string
    {
        $tags = self::getTagValues($record);
        
        foreach ($tags as $tag => $value) {
            // Skip empty values to avoid replacing tags with empty strings unless intended
            if ($value !== null && $value !== '') {
                $content = str_replace('{{' . $tag . '}}', $value, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Get a specific tag value
     */
    public static function getTag(string $tag, $record = null): string
    {
        $tags = self::getTagValues($record);
        return $tags[$tag] ?? '';
    }
    
    /**
     * Get all available merge tag values
     */
    protected static function getTagValues($record = null): array
    {
        $tags = [
            // Site-wide tags from settings
            'site_name' => settings('site_name', config('app.name', 'TallCMS')),
            'site_tagline' => settings('site_tagline', ''),
            'site_description' => settings('site_description', ''),
            'site_url' => config('app.url', url('/')),
            'current_year' => date('Y'),
            'current_date' => now()->format('F j, Y'),
            
            // Contact info from settings
            'contact_email' => settings('contact_email', config('mail.from.address', 'hello@example.com')),
            'contact_phone' => settings('contact_phone', ''),
            'company_name' => settings('company_name', settings('site_name', config('app.name', 'TallCMS'))),
            'company_address' => settings('company_address', ''),
            
            // Social media from settings
            'social_facebook' => settings('social_facebook', ''),
            'social_twitter' => settings('social_twitter', ''),
            'social_linkedin' => settings('social_linkedin', ''),
            'social_instagram' => settings('social_instagram', ''),
            'social_youtube' => settings('social_youtube', ''),
            'social_tiktok' => settings('social_tiktok', ''),
            'newsletter_signup' => settings('newsletter_signup_url', '#newsletter'),
            
            // SEO and branding from settings
            'logo_url' => settings('logo') ? Storage::url(settings('logo')) : '',
            'favicon_url' => settings('favicon') ? Storage::url(settings('favicon')) : '',
        ];
        
        // Add record-specific tags if record is provided
        if ($record instanceof CmsPage) {
            $tags = array_merge($tags, self::getPageTags($record));
        } elseif ($record instanceof CmsPost) {
            $tags = array_merge($tags, self::getPostTags($record));
        }
        
        return $tags;
    }
    
    /**
     * Get page-specific merge tags
     */
    protected static function getPageTags(CmsPage $page): array
    {
        return [
            'page_title' => $page->title ?? '',
            'page_url' => url('/' . ltrim($page->slug, '/')),
            'page_author' => 'Admin', // Pages don't have authors, could be site admin
        ];
    }
    
    /**
     * Get post-specific merge tags
     */
    protected static function getPostTags(CmsPost $post): array
    {
        return [
            'post_title' => $post->title ?? '',
            'post_url' => url('/post/' . $post->slug), // TODO: Implement post routing or update when blog routing is added
            'post_excerpt' => $post->excerpt ?? '',
            'post_author' => $post->author->name ?? 'Unknown Author',
            'post_author_email' => $post->author->email ?? '',
            'post_categories' => $post->categories->pluck('name')->implode(', ') ?: 'Uncategorized',
            'post_published_date' => $post->published_at ? $post->published_at->format('F j, Y') : 'Not Published',
            'post_reading_time' => $post->reading_time . ' min read',
            'related_posts' => '', // This could be implemented to show related posts HTML
        ];
    }
    
    /**
     * Get all available merge tags for documentation
     */
    public static function getAvailableTags($type = 'all'): array
    {
        $siteTags = [
            'site_name' => 'Site Name (from settings)',
            'site_tagline' => 'Site Tagline (from settings)',
            'site_description' => 'Site Description (from settings)',
            'site_url' => 'Site URL',
            'current_year' => 'Current Year',
            'current_date' => 'Current Date',
            'contact_email' => 'Contact Email (from settings)',
            'contact_phone' => 'Contact Phone (from settings)',
            'company_name' => 'Company Name (from settings)',
            'company_address' => 'Company Address (from settings)',
            'social_facebook' => 'Facebook URL (from settings)',
            'social_twitter' => 'Twitter URL (from settings)',
            'social_linkedin' => 'LinkedIn URL (from settings)',
            'social_instagram' => 'Instagram URL (from settings)',
            'social_youtube' => 'YouTube URL (from settings)',
            'social_tiktok' => 'TikTok URL (from settings)',
            'newsletter_signup' => 'Newsletter Signup URL (from settings)',
            'logo_url' => 'Logo URL (from settings)',
            'favicon_url' => 'Favicon URL (from settings)',
        ];
        
        $pageTags = [
            'page_title' => 'Page Title',
            'page_url' => 'Page URL',
            'page_author' => 'Page Author',
        ];
        
        $postTags = [
            'post_title' => 'Post Title',
            'post_url' => 'Post URL',
            'post_excerpt' => 'Post Excerpt',
            'post_author' => 'Post Author',
            'post_author_email' => 'Author Email',
            'post_categories' => 'Post Categories',
            'post_published_date' => 'Published Date',
            'post_reading_time' => 'Reading Time',
            'newsletter_signup' => 'Newsletter Signup URL',
            'related_posts' => 'Related Posts',
        ];
        
        switch ($type) {
            case 'pages':
                return array_merge($siteTags, $pageTags);
            case 'posts':
                return array_merge($siteTags, $postTags);
            default:
                return array_merge($siteTags, $pageTags, $postTags);
        }
    }
}