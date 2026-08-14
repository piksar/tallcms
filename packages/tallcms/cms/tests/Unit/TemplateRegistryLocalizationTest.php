<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\File;
use TallCms\Cms\Services\TemplateRegistry;
use TallCms\Cms\Tests\TestCase;

class TemplateRegistryLocalizationTest extends TestCase
{
    protected string $themePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themePath = sys_get_temp_dir().'/tallcms-theme-'.uniqid('', true);
        File::ensureDirectoryExists($this->themePath.'/resources/views/templates');
        File::put($this->themePath.'/resources/views/templates/landing.blade.php', '<div>landing</div>');
        File::put($this->themePath.'/theme.json', json_encode([
            'name' => 'Test Theme',
            'templates' => [
                'landing' => [
                    'label' => 'Custom Landing Label',
                    'description' => 'Custom landing description from theme.json',
                ],
            ],
        ], JSON_PRETTY_PRINT));

        $theme = new class($this->themePath)
        {
            public string $slug = 'custom-theme';

            public string $path;

            public function __construct(string $path)
            {
                $this->path = $path;
            }
        };

        $manager = new class($theme)
        {
            public function __construct(public object $activeTheme) {}

            public function getActiveTheme(): object
            {
                return $this->activeTheme;
            }
        };

        $this->app->instance('theme.manager', $manager);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themePath);
        parent::tearDown();
    }

    public function test_theme_json_label_survives_german_localization(): void
    {
        app()->setLocale('de');

        $registry = app(TemplateRegistry::class);
        $registry->clearCache();

        $config = $registry->getTemplateConfig('landing');

        $this->assertSame('Custom Landing Label', $config['label']);
        $this->assertSame('Custom landing description from theme.json', $config['description']);
    }

    public function test_builtin_template_label_is_translated_without_theme_override(): void
    {
        app()->setLocale('de');

        // Default theme path skips theme.json merge
        $manager = new class
        {
            public function getActiveTheme(): object
            {
                return (object) ['slug' => 'default', 'path' => '/tmp'];
            }
        };
        $this->app->instance('theme.manager', $manager);

        $registry = app(TemplateRegistry::class);
        $registry->clearCache();

        $config = $registry->getTemplateConfig('landing');

        $this->assertNotSame('Landing Page', $config['label']);
        $this->assertStringNotContainsString('tallcms::', $config['label']);
    }
}
