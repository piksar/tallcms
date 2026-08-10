<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;

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
                $content = str_replace('{{'.$tag.'}}', $value, $content);
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
            'site_name' => SiteSetting::get('site_name', config('app.name', 'TallCMS')),
            'site_tagline' => SiteSetting::get('site_tagline', ''),
            'site_description' => SiteSetting::get('site_description', ''),
            'site_url' => config('app.url', url('/')),
            'current_year' => date('Y'),
            'current_date' => now()->format('F j, Y'),

            // Contact info from settings
            'contact_email' => SiteSetting::get('contact_email', config('mail.from.address', '')),
            'contact_phone' => SiteSetting::get('contact_phone', ''),
            'company_name' => SiteSetting::get('company_name', SiteSetting::get('site_name', config('app.name', 'TallCMS'))),
            'company_address' => SiteSetting::get('company_address', ''),

            // Social media from settings
            'social_facebook' => SiteSetting::get('social_facebook', ''),
            'social_twitter' => SiteSetting::get('social_twitter', ''),
            'social_linkedin' => SiteSetting::get('social_linkedin', ''),
            'social_instagram' => SiteSetting::get('social_instagram', ''),
            'social_youtube' => SiteSetting::get('social_youtube', ''),
            'social_tiktok' => SiteSetting::get('social_tiktok', ''),
            'newsletter_signup' => SiteSetting::get('newsletter_signup_url', ''),

            // SEO and branding from settings
            'logo_url' => SiteSetting::get('logo') ? Storage::disk(cms_media_disk())->url(SiteSetting::get('logo')) : '',
            'favicon_url' => SiteSetting::get('favicon') ? Storage::disk(cms_media_disk())->url(SiteSetting::get('favicon')) : '',
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
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        return [
            'page_title' => $page->title ?? '',
            'page_url' => url($prefix.'/'.ltrim($page->getFullSlug(), '/')),
            'page_author' => 'Admin', // Pages don't have authors, could be site admin
        ];
    }

    /**
     * Get post-specific merge tags
     */
    protected static function getPostTags(CmsPost $post): array
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        return [
            'post_title' => $post->title ?? '',
            'post_url' => url("{$prefix}/blog/{$post->slug}"),
            'post_excerpt' => $post->excerpt ?? '',
            'post_author' => $post->author->name ?? __('tallcms::frontend.unknown_author'),
            'post_author_email' => $post->author->email ?? '',
            'post_categories' => $post->categories->pluck('name')->implode(', ') ?: __('tallcms::frontend.uncategorized'),
            'post_published_date' => $post->published_at ? $post->published_at->format('F j, Y') : __('tallcms::frontend.not_published'),
            'post_reading_time' => __('tallcms::frontend.min_read', ['minutes' => $post->reading_time]),
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
