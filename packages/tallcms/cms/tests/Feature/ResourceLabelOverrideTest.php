<?php

namespace TallCms\Cms\Tests\Feature;

use Filament\Panel;
use PHPUnit\Framework\Attributes\DataProvider;
use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;
use TallCms\Cms\Filament\Resources\SiteResource\SiteResource;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;
use TallCms\Cms\Filament\Resources\Users\UserResource;
use TallCms\Cms\TallCmsPlugin;
use TallCms\Cms\Tests\TestCase;

/**
 * Contract tests for the configurable resource labels feature (PR #57).
 *
 * Plugin-mode adopters need to rename admin resources to match their
 * domain — "Articles" instead of "Posts", "Tags" instead of "Categories",
 * etc. Each resource's singular/plural/navigation labels are exposed via
 * `tallcms.labels.<key>.<facet>` config, with a fluent API on TallCmsPlugin
 * for convenience. These tests lock the contract so a future refactor
 * doesn't silently break renaming.
 */
class ResourceLabelOverrideTest extends TestCase
{
    #[DataProvider('resourceMatrix')]
    public function test_resource_reads_all_three_labels_from_config(
        string $configKey,
        string $resource,
        string $singular,
        string $plural,
        string $navigation,
    ): void {
        config(['tallcms.labels.'.$configKey => [
            'singular' => $singular,
            'plural' => $plural,
            'navigation' => $navigation,
        ]]);

        $this->assertSame($singular, $resource::getModelLabel());
        $this->assertSame($plural, $resource::getPluralModelLabel());
        $this->assertSame($navigation, $resource::getNavigationLabel());
    }

    public static function resourceMatrix(): array
    {
        return [
            'categories' => ['categories', CmsCategoryResource::class, 'Tag', 'Tags', 'Tags'],
            'pages' => ['pages', CmsPageResource::class, 'Listing', 'Listings', 'Listings'],
            'posts' => ['posts', CmsPostResource::class, 'Article', 'Articles', 'Articles'],
            'menus' => ['menus', TallcmsMenuResource::class, 'Nav', 'Nav Menus', 'Nav Menus'],
            'media' => ['media', TallcmsMediaResource::class, 'Asset', 'Assets', 'Asset Library'],
            'media_collections' => ['media_collections', MediaCollectionResource::class, 'Album', 'Albums', 'Albums'],
            'comments' => ['comments', CmsCommentResource::class, 'Reply', 'Replies', 'Replies'],
            'contact_submissions' => ['contact_submissions', TallcmsContactSubmissionResource::class, 'Lead', 'Leads', 'Leads'],
            'users' => ['users', UserResource::class, 'Member', 'Members', 'Members'],
            'site_settings' => ['site_settings', SiteResource::class, 'Property', 'Properties', 'Property Settings'],
        ];
    }

    public function test_defaults_apply_when_no_override_is_configured(): void
    {
        // Null overrides fall through to package translations (APP_LOCALE).
        config(['tallcms.labels.categories' => [
            'singular' => null,
            'plural' => null,
            'navigation' => null,
        ]]);

        app()->setLocale('en');

        $this->assertSame('Category', CmsCategoryResource::getModelLabel());
        $this->assertSame('Categories', CmsCategoryResource::getPluralModelLabel());
        $this->assertSame('Categories', CmsCategoryResource::getNavigationLabel());
    }

    public function test_defaults_follow_app_locale_when_no_override_is_configured(): void
    {
        config(['tallcms.labels.categories' => [
            'singular' => null,
            'plural' => null,
            'navigation' => null,
        ]]);

        app()->setLocale('de');

        $this->assertSame('Kategorie', CmsCategoryResource::getModelLabel());
        $this->assertSame('Kategorien', CmsCategoryResource::getPluralModelLabel());
        $this->assertSame('Kategorien', CmsCategoryResource::getNavigationLabel());
    }

    public function test_fluent_api_only_overwrites_plural_when_singular_and_navigation_are_omitted(): void
    {
        // This is the bug the review flagged: a call like
        // ->postLabel('Articles') must NOT coerce singular/navigation to
        // "Articles" too. It should only rename the plural and leave
        // singular/navigation at their configured defaults, so UI reads
        // "Create Article" / "Edit Article", not "Create Articles".
        config(['tallcms.labels.posts' => [
            'singular' => 'Post',
            'plural' => 'Posts',
            'navigation' => 'Posts',
        ]]);

        $plugin = TallCmsPlugin::make()->postLabel('Articles');

        $this->invokeRegister($plugin);

        $this->assertSame('Articles', config('tallcms.labels.posts.plural'),
            'Plural should be overwritten with the passed value.');
        $this->assertSame('Post', config('tallcms.labels.posts.singular'),
            'Singular should remain at its configured default when omitted.');
        $this->assertSame('Posts', config('tallcms.labels.posts.navigation'),
            'Navigation should remain at its configured default when omitted.');
    }

    public function test_fluent_api_persists_all_three_labels_when_fully_specified(): void
    {
        config(['tallcms.labels.posts' => [
            'singular' => 'Post',
            'plural' => 'Posts',
            'navigation' => 'Posts',
        ]]);

        $plugin = TallCmsPlugin::make()->postLabel('Articles', 'Article', 'News');

        $this->invokeRegister($plugin);

        $this->assertSame('Article', config('tallcms.labels.posts.singular'));
        $this->assertSame('Articles', config('tallcms.labels.posts.plural'));
        $this->assertSame('News', config('tallcms.labels.posts.navigation'));
    }

    public function test_fluent_api_preserves_distinct_navigation_default_for_media_resource(): void
    {
        // `media`'s default navigation label ("Media Library") is
        // intentionally different from its plural ("Media Files") —
        // a partial override must not flatten the library distinction.
        config(['tallcms.labels.media' => [
            'singular' => 'Media File',
            'plural' => 'Media Files',
            'navigation' => 'Media Library',
        ]]);

        $plugin = TallCmsPlugin::make()->mediaLabel('Assets');

        $this->invokeRegister($plugin);

        $this->assertSame('Assets', config('tallcms.labels.media.plural'));
        $this->assertSame('Media File', config('tallcms.labels.media.singular'));
        $this->assertSame('Media Library', config('tallcms.labels.media.navigation'),
            'The "Library" concept in the default navigation label must survive a partial rename.');
    }

    public function test_global_search_type_metadata_reflects_renamed_post_label(): void
    {
        config(['tallcms.labels.posts.singular' => 'Article']);

        $post = new \TallCms\Cms\Models\CmsPost;
        $post->status = 'published';

        $details = CmsPostResource::getGlobalSearchResultDetails($post);

        $this->assertSame('Article', $details[__('tallcms::fields.type')],
            'Global search "Type" metadata must reflect the renamed label, '.
            'not a hardcoded "Post" string.');
    }

    public function test_global_search_type_metadata_reflects_renamed_page_label(): void
    {
        config(['tallcms.labels.pages.singular' => 'Listing']);

        $page = new \TallCms\Cms\Models\CmsPage;
        $page->status = 'published';

        $details = CmsPageResource::getGlobalSearchResultDetails($page);

        $this->assertSame('Listing', $details[__('tallcms::fields.type')],
            'Global search "Type" metadata must reflect the renamed label, '.
            'not a hardcoded "Page" string.');
    }

    protected function invokeRegister(TallCmsPlugin $plugin): void
    {
        // The full register() pipeline touches FilamentShieldPlugin and
        // Spatie Translatable registration paths that aren't relevant
        // here. Stub Panel with self-returning methods and swallow any
        // late-stage exception — the only contract we care about is that
        // the label overrides are pushed to config early in register().
        $panel = $this->createMock(Panel::class);
        $panel->method('resources')->willReturnSelf();
        $panel->method('pages')->willReturnSelf();
        $panel->method('widgets')->willReturnSelf();
        $panel->method('plugin')->willReturnSelf();
        $panel->method('plugins')->willReturnSelf();

        try {
            $plugin->register($panel);
        } catch (\Throwable) {
            // Expected: late-stage plugin registration requires a full
            // Filament panel bootstrap. Config mutation has already run.
        }
    }
}
