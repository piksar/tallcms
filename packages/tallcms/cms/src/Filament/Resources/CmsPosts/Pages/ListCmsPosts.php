<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;

class ListCmsPosts extends ListRecords
{
    use Translatable;

    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make()
                ->label(__('tallcms::fields.new_post')),
        ];
    }
}
