<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use TallCms\Cms\Tests\TestCase;

class LangFileSanityTest extends TestCase
{
    public function test_package_lang_strings_have_no_corrupted_backslash_escapes(): void
    {
        $langRoot = dirname(__DIR__, 2).'/resources/lang';
        $bad = [];

        foreach (glob($langRoot.'/*/*.php') as $file) {
            /** @var array<string, mixed> $translations */
            $translations = include $file;
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($translations));

            foreach ($iterator as $key => $value) {
                if (! is_string($value)) {
                    continue;
                }

                if (str_contains($value, '\\') && preg_match("/\\\\+'/", $value)) {
                    $bad[] = basename(dirname($file)).'/'.basename($file)."::{$key}={$value}";
                }
            }
        }

        $this->assertSame([], $bad, 'Lang values must not contain corrupted apostrophe escapes');
    }

    public function test_common_apostrophe_keys_render_cleanly(): void
    {
        app()->setLocale('en');

        $this->assertSame("You're up to date!", __('tallcms::ui.youre_up_to_date'));
        $this->assertSame("What's new:", __('tallcms::ui.whats_new'));
        $this->assertStringContainsString("don't", __('tallcms::ui.help_og_image_usage'));
        $this->assertStringNotContainsString('\\', __('tallcms::ui.help_og_image_usage'));
    }
}
