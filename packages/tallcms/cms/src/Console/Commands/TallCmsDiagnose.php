<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;

class TallCmsDiagnose extends Command
{
    protected $signature = 'tallcms:diagnose
                            {--fix : Only show problems}';

    protected $description = 'Diagnose common TallCMS configuration issues (including admin UI i18n)';

    /**
     * Stock English defaults from older published config/tallcms.php files.
     * Non-empty values matching these prevent tallcms_label() / tallcms_nav_group()
     * from using package translations for APP_LOCALE.
     *
     * @var array<string, string>
     */
    protected array $legacyNavGroupDefaults = [
        'platform' => 'Sites',
        'content' => 'Content',
        'appearance' => 'Appearance',
        'configuration' => 'Configuration',
        'system' => 'System',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $legacyLabelDefaults = [
        'pages' => [
            'singular' => 'Page',
            'plural' => 'Pages',
            'navigation' => 'Pages',
        ],
        'posts' => [
            'singular' => 'Post',
            'plural' => 'Posts',
            'navigation' => 'Posts',
        ],
        'categories' => [
            'singular' => 'Category',
            'plural' => 'Categories',
            'navigation' => 'Categories',
        ],
        'menus' => [
            'singular' => 'Menu',
            'plural' => 'Menus',
            'navigation' => 'Menus',
        ],
        'media' => [
            'singular' => 'Media File',
            'plural' => 'Media Files',
            'navigation' => 'Media Library',
        ],
        'media_collections' => [
            'singular' => 'Collection',
            'plural' => 'Collections',
            'navigation' => 'Collections',
        ],
        'comments' => [
            'singular' => 'Comment',
            'plural' => 'Comments',
            'navigation' => 'Comments',
        ],
        'contact_submissions' => [
            'singular' => 'Contact Submission',
            'plural' => 'Contact Submissions',
            'navigation' => 'Contact Submissions',
        ],
        'users' => [
            'singular' => 'User',
            'plural' => 'Users',
            'navigation' => 'Users',
        ],
        'site_settings' => [
            'singular' => 'Site',
            'plural' => 'Sites',
            'navigation' => 'Site Settings',
        ],
    ];

    public function handle(): int
    {
        $problems = 0;

        $this->newLine();
        $this->info('TallCMS diagnose');
        $this->components->twoColumnDetail('APP_LOCALE', (string) config('app.locale'));
        $this->components->twoColumnDetail('APP_FALLBACK_LOCALE', (string) config('app.fallback_locale'));
        $this->newLine();

        $problems += $this->checkPublishedLabelOverrides();

        if ($problems === 0) {
            $this->components->info('No admin UI i18n configuration issues detected.');
        } else {
            $this->newLine();
            $this->components->warn("Found {$problems} potential admin UI i18n issue(s).");
            $this->line('  See docs/ref-i18n.md → Admin UI locale → Upgrading.');
        }

        return $problems > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function checkPublishedLabelOverrides(): int
    {
        $problems = 0;
        $locale = (string) config('app.locale');

        if ($locale === 'en' || $locale === '') {
            if (! $this->option('fix')) {
                $this->components->twoColumnDetail(
                    'Published label overrides',
                    'Skipped (APP_LOCALE is en)'
                );
            }

            return 0;
        }

        foreach ($this->legacyNavGroupDefaults as $group => $english) {
            $value = config("tallcms.navigation.groups.{$group}");
            if (is_string($value) && $value === $english) {
                $problems++;
                $this->components->error(
                    "tallcms.navigation.groups.{$group} is still the legacy English default \"{$english}\""
                );
                $this->line('  Set it to null (or delete the key) so tallcms_nav_group() can use package translations.');
            }
        }

        foreach ($this->legacyLabelDefaults as $resource => $facets) {
            foreach ($facets as $facet => $english) {
                $value = config("tallcms.labels.{$resource}.{$facet}");
                if (is_string($value) && $value === $english) {
                    $problems++;
                    $this->components->error(
                        "tallcms.labels.{$resource}.{$facet} is still the legacy English default \"{$english}\""
                    );
                    $this->line('  Set it to null (or delete the key) so tallcms_label() can use package translations.');
                }
            }
        }

        if ($problems === 0 && ! $this->option('fix')) {
            $this->components->twoColumnDetail(
                'Published label overrides',
                'OK (no legacy English stock values detected)'
            );
        }

        return $problems;
    }
}
