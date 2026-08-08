<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Tests\TestCase;

class TallcmsLabelHelperTest extends TestCase
{
    public function test_override_wins_over_translation(): void
    {
        config([
            'tallcms.labels.pages.singular' => 'Listing',
            'app.locale' => 'de',
        ]);
        app()->setLocale('de');

        $this->assertSame('Listing', tallcms_label('pages', 'singular'));
    }

    public function test_null_override_uses_english_translation(): void
    {
        config([
            'tallcms.labels.pages.singular' => null,
            'tallcms.labels.pages.plural' => null,
            'tallcms.labels.pages.navigation' => null,
        ]);
        app()->setLocale('en');

        $this->assertSame('Page', tallcms_label('pages', 'singular'));
        $this->assertSame('Pages', tallcms_label('pages', 'plural'));
        $this->assertSame('Pages', tallcms_label('pages', 'navigation'));
    }

    public function test_null_override_uses_german_translation(): void
    {
        config([
            'tallcms.labels.pages.singular' => null,
            'tallcms.labels.media.navigation' => null,
            'tallcms.navigation.groups.content' => null,
        ]);
        app()->setLocale('de');

        $this->assertSame('Seite', tallcms_label('pages', 'singular'));
        $this->assertSame('Mediathek', tallcms_label('media', 'navigation'));
        $this->assertSame('Inhalt', tallcms_nav_group('content'));
    }

    public function test_empty_string_override_falls_through_to_translation(): void
    {
        config(['tallcms.labels.posts.singular' => '']);
        app()->setLocale('en');

        $this->assertSame('Post', tallcms_label('posts', 'singular'));
    }

    public function test_nav_group_override_wins(): void
    {
        config(['tallcms.navigation.groups.system' => 'Ops']);
        app()->setLocale('de');

        $this->assertSame('Ops', tallcms_nav_group('system'));
    }

    public function test_platform_nav_group_resolves_german_without_returning_key(): void
    {
        config(['tallcms.navigation.groups.platform' => null]);
        app()->setLocale('de');

        $label = tallcms_nav_group('platform');

        $this->assertSame('Websites', $label);
        $this->assertStringNotContainsString('tallcms::', $label);
    }

    public function test_translator_namespace_is_registered_for_tallcms(): void
    {
        $namespaces = app('translator')->getLoader()->namespaces();

        $this->assertArrayHasKey('tallcms', $namespaces);
        $this->assertTrue(
            is_dir($namespaces['tallcms']) || is_dir(realpath($namespaces['tallcms']) ?: ''),
            'tallcms translation path should exist on disk',
        );
    }
}
