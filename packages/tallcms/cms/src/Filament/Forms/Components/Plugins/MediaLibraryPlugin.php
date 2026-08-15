<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components\Plugins;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;
use TallCms\Cms\Filament\Forms\Components\Actions\InsertMediaAction;

class MediaLibraryPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('insertMedia')
                ->label(__('tallcms::fields.insert_from_media_library'))
                ->action()
                ->icon(Heroicon::Photo),
        ];
    }

    public function getEditorActions(): array
    {
        return [InsertMediaAction::make()];
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [];
    }
}
