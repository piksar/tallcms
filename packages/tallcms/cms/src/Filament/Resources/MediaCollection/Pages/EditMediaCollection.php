<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;

class EditMediaCollection extends EditRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewMedia')
                ->label(__('tallcms::fields.view_media'))
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn () => TallcmsMediaResource::getUrl('index', [
                    'tableFilters' => [
                        'collections' => ['value' => $this->record->id],
                    ],
                    'filters' => [
                        'collections' => ['value' => $this->record->id],
                        'recent' => ['isActive' => false],
                    ],
                ])),
            DeleteAction::make()
                ->modalHeading(__('tallcms::ui.t_delete_collection'))
                ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_delete_this_collection_media_files_will_not')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
