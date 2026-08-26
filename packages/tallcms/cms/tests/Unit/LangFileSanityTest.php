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

    public function test_demo_template_lang_files_have_matching_keys(): void
    {
        $langRoot = dirname(__DIR__, 2).'/resources/lang';
        /** @var array<string, string> $en */
        $en = include $langRoot.'/en/demo-templates.php';
        /** @var array<string, string> $de */
        $de = include $langRoot.'/de/demo-templates.php';

        $this->assertSame(array_keys($en), array_keys($de), 'demo-templates.php keys must match between en and de');
    }

    public function test_frontend_and_console_lang_files_have_matching_keys(): void
    {
        $langRoot = dirname(__DIR__, 2).'/resources/lang';

        foreach (['frontend', 'console'] as $file) {
            /** @var array<string, mixed> $en */
            $en = include $langRoot.'/en/'.$file.'.php';
            /** @var array<string, mixed> $de */
            $de = include $langRoot.'/de/'.$file.'.php';

            $this->assertSame(
                array_keys($this->flattenLangKeys($en)),
                array_keys($this->flattenLangKeys($de)),
                "{$file}.php keys must match between en and de"
            );
        }
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, null>
     */
    private function flattenLangKeys(array $translations, string $prefix = ''): array
    {
        $keys = [];

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenLangKeys($value, $fullKey));
            } else {
                $keys[$fullKey] = null;
            }
        }

        return $keys;
    }
}
