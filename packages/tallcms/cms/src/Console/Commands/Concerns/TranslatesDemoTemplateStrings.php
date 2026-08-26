<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands\Concerns;

trait TranslatesDemoTemplateStrings
{
    /**
     * @param  array<string, string|int|float>  $replace
     */
    protected function demo(string $key, array $replace = []): string
    {
        return (string) __("tallcms::demo-templates.{$key}", $replace);
    }

    /**
     * @param  array<string, string|int|float>  $replace
     * @return array{en: string, de: string}
     */
    protected function demoLocales(string $key, array $replace = []): array
    {
        return [
            'en' => (string) __("tallcms::demo-templates.{$key}", $replace, 'en'),
            'de' => (string) __("tallcms::demo-templates.{$key}", $replace, 'de'),
        ];
    }

    /**
     * @param  array<string, string|int|float>  $replace
     */
    protected function demoJson(string $key, array $replace = []): string
    {
        return json_encode(
            $this->demoLocales($key, $replace),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function resolveDemoBlockConfig(array $config): array
    {
        $resolved = [];

        foreach ($config as $key => $value) {
            if ($key === 'title_key' && is_string($value)) {
                $resolved['title'] = $this->demo($value);

                continue;
            }

            if (is_array($value)) {
                if (array_is_list($value)) {
                    $resolved[$key] = array_map(
                        fn ($item) => is_array($item) ? $this->resolveDemoBlockConfig($item) : $item,
                        $value
                    );
                } else {
                    $resolved[$key] = $this->resolveDemoBlockConfig($value);
                }

                continue;
            }

            $resolved[$key] = $value;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function demoBlock(string $id, array $config): string
    {
        $config = $this->resolveDemoBlockConfig($config);
        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return "<div data-type=\"customBlock\" data-config=\"{$encoded}\" data-id=\"{$id}\"></div>";
    }
}
