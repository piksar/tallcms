<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;

class SeoService
{
    /**
     * Generate meta tags array for a page.
     */
    public static function getMetaTags(CmsPage $page): array
    {
        return [
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: $page->excerpt ?: SiteSetting::get('site_description', ''),
            'image' => $page->featured_image ? Storage::disk(cms_media_disk())->url($page->featured_image) : null,
            'type' => 'website',
            'url' => request()->url(),
        ];
    }

    /**
     * Generate meta tags array for a post (article).
     */
    public static function getPostMetaTags(CmsPost $post): array
    {
        $author = $post->author;
        $categories = $post->categories;
        $primaryCategory = $categories->first();

        return [
            'title' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: $post->excerpt ?: '',
            'image' => $post->featured_image ? Storage::disk(cms_media_disk())->url($post->featured_image) : null,
            'type' => 'article',
            'url' => request()->url(),
            // Article-specific OG tags
            'article' => [
                'published_time' => $post->published_at?->toIso8601String(),
                'modified_time' => $post->updated_at?->toIso8601String(),
                'author' => $author?->name,
                'section' => $primaryCategory?->name,
                'tags' => $categories->pluck('name')->toArray(),
            ],
            // Twitter-specific data
            'twitter' => [
                'label1' => __('tallcms::frontend.reading_time_label'),
                'data1' => __('tallcms::frontend.min_read', ['minutes' => $post->reading_time]),
                'label2' => __('tallcms::frontend.written_by'),
                'data2' => $author?->name ?? __('tallcms::frontend.unknown_author'),
            ],
        ];
    }

    /**
     * Generate meta tags for a category archive page.
     */
    public static function getCategoryMetaTags(CmsCategory $category): array
    {
        return [
            'title' => $category->name.' - '.SiteSetting::get('site_name', config('app.name')),
            'description' => $category->description ?: "Browse all posts in {$category->name}",
            'image' => null,
            'type' => 'website',
            'url' => request()->url(),
        ];
    }

    /**
     * Generate meta tags for an author archive page.
     */
    public static function getAuthorMetaTags($author): array
    {
        $siteName = SiteSetting::get('site_name', config('app.name'));

        return [
            'title' => ($author->name ?? 'Author').' - '.$siteName,
            'description' => $author->bio ?? "Browse all posts by {$author->name}",
            'image' => null,
            'type' => 'profile',
            'url' => request()->url(),
            'profile' => [
                'first_name' => explode(' ', $author->name ?? '')[0] ?? '',
                'last_name' => explode(' ', $author->name ?? '')[1] ?? '',
            ],
        ];
    }

    /**
     * Generate JSON-LD structured data for a page.
     */
    public static function getPageJsonLd(CmsPage $page): array
    {
        $siteName = SiteSetting::get('site_name', config('app.name'));
        $siteUrl = tallcms_base_url($page->site_id ?? null);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: $page->excerpt ?: '',
            'url' => request()->url(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl,
            ],
        ];
    }

    /**
     * Generate JSON-LD structured data for a post (Article/BlogPosting).
     */
    public static function getPostJsonLd(CmsPost $post): array
    {
        $siteName = SiteSetting::get('site_name', config('app.name'));
        $siteUrl = tallcms_base_url($post->site_id ?? null);
        $author = $post->author;
        $logoUrl = SiteSetting::get('logo')
            ? Storage::disk(cms_media_disk())->url(SiteSetting::get('logo'))
            : null;

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: $post->excerpt ?: '',
            'url' => request()->url(),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => $siteUrl,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => request()->url(),
            ],
        ];

        // Add logo if available
        if ($logoUrl) {
            $data['publisher']['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ];
        }

        // Add author if available (enriched with E-E-A-T signals)
        if ($author) {
            $authorData = [
                '@type' => 'Person',
                'name' => $author->name,
            ];

            if (! empty($author->slug)) {
                $prefix = config('tallcms.plugin_mode.routes_prefix', '');
                $prefix = $prefix ? "/{$prefix}" : '';
                $authorData['url'] = url("{$prefix}/author/{$author->slug}");
            }

            if (! empty($author->job_title)) {
                $authorData['jobTitle'] = $author->job_title;
            }

            if (! empty($author->company)) {
                $authorData['worksFor'] = [
                    '@type' => 'Organization',
                    'name' => $author->company,
                ];
            }

            $sameAs = [];
            if (! empty($author->linkedin_url)) {
                $sameAs[] = $author->linkedin_url;
            }
            if (! empty($author->twitter_handle)) {
                $handle = ltrim($author->twitter_handle, '@');
                $sameAs[] = "https://x.com/{$handle}";
            }
            if (! empty($sameAs)) {
                $authorData['sameAs'] = $sameAs;
            }

            $data['author'] = $authorData;
        }

        // Add expert reviewer if available
        if (! empty($post->expert_reviewer_name)) {
            $reviewerData = [
                '@type' => 'Person',
                'name' => $post->expert_reviewer_name,
            ];
            if (! empty($post->expert_reviewer_title)) {
                $reviewerData['jobTitle'] = $post->expert_reviewer_title;
            }
            if (! empty($post->expert_reviewer_url)) {
                $reviewerData['url'] = $post->expert_reviewer_url;
            }
            $data['reviewedBy'] = $reviewerData;
        }

        // Add last reviewed date
        if ($post->last_reviewed_at) {
            $data['lastReviewed'] = $post->last_reviewed_at->toIso8601String();
        }

        // Add featured image if available
        if ($post->featured_image) {
            $data['image'] = Storage::disk(cms_media_disk())->url($post->featured_image);
        }

        // Add categories as keywords
        $categories = $post->categories;
        if ($categories->isNotEmpty()) {
            $data['keywords'] = $categories->pluck('name')->implode(', ');
            $data['articleSection'] = $categories->first()->name;
        }

        // Add citation sources
        if (! empty($post->sources) && is_array($post->sources)) {
            $citations = [];
            foreach ($post->sources as $source) {
                $citation = ['@type' => 'CreativeWork'];
                if (! empty($source['title'])) {
                    $citation['name'] = $source['title'];
                }
                if (! empty($source['url'])) {
                    $citation['url'] = $source['url'];
                }
                $citations[] = $citation;
            }
            if (! empty($citations)) {
                $data['citation'] = $citations;
            }
        }

        // Add word count from full content (not excerpt)
        $contentText = app(ContentIndexer::class)->extractSearchableText($post->content);
        $wordCount = str_word_count($contentText);
        if ($wordCount > 0) {
            $data['wordCount'] = $wordCount;
        }

        return $data;
    }

    /**
     * Generate JSON-LD BreadcrumbList for navigation.
     */
    public static function getBreadcrumbJsonLd(array $breadcrumbs): array
    {
        $items = [];
        $position = 1;

        foreach ($breadcrumbs as $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'],
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate JSON-LD WebSite schema for homepage.
     */
    public static function getWebsiteJsonLd(): array
    {
        $siteName = SiteSetting::get('site_name', config('app.name'));
        $siteUrl = tallcms_base_url();
        $description = SiteSetting::get('site_description', '');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $siteUrl,
            'description' => $description,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl.'/?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Get the default OG image from site settings.
     */
    public static function getDefaultOgImage(): ?string
    {
        $defaultImage = SiteSetting::get('seo_default_og_image');

        if ($defaultImage) {
            return Storage::disk(cms_media_disk())->url($defaultImage);
        }

        // Fallback to logo
        $logo = SiteSetting::get('logo');
        if ($logo) {
            return Storage::disk(cms_media_disk())->url($logo);
        }

        return null;
    }

    /**
     * Get the Twitter site handle from settings.
     */
    public static function getTwitterSite(): ?string
    {
        $twitterUrl = SiteSetting::get('social_twitter');

        if (! $twitterUrl) {
            return null;
        }

        // Extract handle from URL
        if (preg_match('/(?:twitter\.com|x\.com)\/([^\/\?]+)/', $twitterUrl, $matches)) {
            return '@'.$matches[1];
        }

        return null;
    }

    /**
     * Get the canonical URL for a post.
     *
     * Posts can be nested under a page containing a PostsBlock (e.g., /blog/post-slug)
     * or at the root if the homepage contains a PostsBlock (e.g., /post-slug).
     */
    public static function getPostUrl(CmsPost $post, ?int $siteId = null): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';
        $baseUrl = tallcms_base_url($siteId ?? $post->site_id ?? null);

        $blogParent = static::getBlogParentSlug($siteId ?? $post->site_id ?? null);

        if ($blogParent) {
            return $baseUrl.$prefix.'/'.$blogParent.'/'.$post->slug;
        }

        return $baseUrl.$prefix.'/'.$post->slug;
    }

    /**
     * Get the blog parent page slug (the page containing a PostsBlock).
     *
     * Returns null if the homepage has the PostsBlock (posts at root),
     * or the slug of the first non-homepage page with a PostsBlock.
     *
     * Note: When multiple pages contain PostsBlocks, the first page by
     * sort_order is selected. This provides deterministic canonical URLs
     * but may not match user expectations if posts appear on multiple pages.
     *
     * Results are cached for performance within a single request.
     */
    /**
     * Get the blog parent page slug for the given site context.
     *
     * Accepts an optional site_id for queued/console contexts where
     * no request exists. In web context, SiteScope handles filtering.
     * Results are cached per-site for the process lifetime.
     */
    public static function getBlogParentSlug(?int $siteId = null): ?string
    {
        static $cache = [];

        $cacheKey = $siteId ?? 'default';

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        // When explicit siteId is given (queue context), bypass SiteScope
        $pageQuery = $siteId
            ? CmsPage::withoutGlobalScopes()->where('site_id', $siteId)
            : CmsPage::query();

        // First check homepage
        $homepage = (clone $pageQuery)->where('is_homepage', true)->published()->first();
        if ($homepage && static::pageHasPostsBlock($homepage)) {
            return $cache[$cacheKey] = null; // Posts at root
        }

        // Find first non-homepage page with a PostsBlock
        $pages = (clone $pageQuery)->where('is_homepage', false)
            ->published()
            ->orderBy('sort_order')
            ->get();

        foreach ($pages as $page) {
            if (static::pageHasPostsBlock($page)) {
                // getFullSlug() returns the hierarchical path when
                // tallcms.pages.hierarchical_urls is on, and the leaf slug
                // when off — keeps post URLs consistent with the page tree.
                return $cache[$cacheKey] = $page->getFullSlug();
            }
        }

        return $cache[$cacheKey] = null;
    }

    /**
     * Check if a page contains a PostsBlock.
     *
     * Detects PostsBlock in both JSON content structures and rendered HTML.
     */
    protected static function pageHasPostsBlock(CmsPage $page): bool
    {
        if (empty($page->content)) {
            return false;
        }

        $content = $page->content;

        if (is_string($content)) {
            // Check for JSON patterns
            if (str_contains($content, '"id":"posts"') || str_contains($content, "'id':'posts'")) {
                return true;
            }

            // Check for rendered HTML pattern (data-id="posts" attribute)
            if (str_contains($content, 'data-id="posts"') || str_contains($content, "data-id='posts'")) {
                return true;
            }

            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return static::searchForPostsBlock($decoded);
            }
        }

        if (is_array($content)) {
            return static::searchForPostsBlock($content);
        }

        return false;
    }

    /**
     * Recursively search for posts block in content structure.
     */
    protected static function searchForPostsBlock(array $content): bool
    {
        foreach ($content as $key => $value) {
            if ($key === 'id' && $value === 'posts') {
                return true;
            }

            if (is_array($value) && static::searchForPostsBlock($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the CMS base URL (respects plugin mode prefix).
     */
    public static function getCmsBaseUrl(): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        return tallcms_base_url().$prefix;
    }
}
