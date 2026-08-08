<?php

namespace TallCms\Cms\Filament\Pages;

use Filament\Pages\Page;

/**
 * Redirect stub — preserved for backwards compatibility.
 * All license management is now handled by PluginManager.
 */
class PluginLicenses extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected string $view = 'tallcms::filament.pages.plugin-licenses';

    public function getTitle(): string
    {
        return __('tallcms::pages.plugin_licenses.title');
    }

    public function mount(): void
    {
        $params = [];
        $requestedPlugin = request()->query('plugin');
        if ($requestedPlugin) {
            $params['plugin'] = $requestedPlugin;
        }

        $this->redirect(PluginManager::getUrl($params));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
