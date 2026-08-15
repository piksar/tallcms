<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TallCms\Cms\Filament\Pages\MenuItemsManager;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;

class MenusRelationManager extends RelationManager
{
    protected static string $relationship = 'menus';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bars-3';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return tallcms_label('menus', 'plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('tallcms::fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')->label(__('tallcms::fields.location'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label(__('tallcms::fields.items'))
                    ->state(fn ($record) => $record->allItems()->count())
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->label(__('tallcms::fields.active'))
                    ->boolean(),

                TextColumn::make('updated_at')->label(__('tallcms::fields.updated_at'))
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('create_menu')
                    ->label(__('tallcms::fields.create_menu'))
                    ->icon('heroicon-m-plus')
                    // The ?site=<id> query param tells CreateTallcmsMenu which site
                    // the new menu belongs to, so site_id is set explicitly on save.
                    ->url(fn () => TallcmsMenuResource::getUrl('create', [
                        'site' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->recordActions([
                Action::make('manage_items')
                    ->label(__('tallcms::fields.manage_items'))
                    ->icon('heroicon-o-bars-3')
                    ->color('primary')
                    ->url(fn ($record) => MenuItemsManager::getUrl(['activeTab' => $record->id])),

                Action::make('edit')
                    ->label(__('tallcms::fields.edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => TallcmsMenuResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
