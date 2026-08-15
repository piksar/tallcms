<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;

class ListCmsPages extends ListRecords
{
    use Translatable;

    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make()
                ->label(__('tallcms::fields.new_page')),
        ];
    }
}
