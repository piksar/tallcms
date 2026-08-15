<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use TallCms\Cms\Filament\Pages\MenuItemsManager;

class TallcmsMenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('tallcms::fields.name'))
                    ->searchable(),
                TextColumn::make('location')->label(__('tallcms::fields.location'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('items_count')
                    ->label(__('tallcms::fields.menu_items'))
                    ->state(fn ($record) => $record->allItems()->count())
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state.' items'),

                IconColumn::make('is_active')->label(__('tallcms::fields.active'))
                    ->boolean(),
                TextColumn::make('created_at')->label(__('tallcms::fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label(__('tallcms::fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ...static::getSiteColumn(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('manage_items')
                    ->label(__('tallcms::fields.manage_items'))
                    ->icon('heroicon-o-bars-3')
                    ->color('primary')
                    ->url(fn ($record): string => MenuItemsManager::getUrl(['activeTab' => $record->id]))
                    ->openUrlInNewTab(false),

                Action::make('preview')
                    ->label(__('tallcms::fields.preview'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Preview: '.$record->name)
                    ->modalContent(fn ($record) => view('tallcms::filament.menus.preview', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                EditAction::make()
                    ->label(__('tallcms::fields.settings')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Site column for multisite. Returns empty array when plugin is absent.
     */
    protected static function getSiteColumn(): array
    {
        if (! app()->bound('tallcms.multisite.resolver')) {
            return [];
        }

        try {
            $sites = \Illuminate\Support\Facades\DB::table('tallcms_sites')
                ->pluck('name', 'id')
                ->toArray();
        } catch (QueryException) {
            return [];
        }

        if (empty($sites)) {
            return [];
        }

        return [
            TextColumn::make('site_id')
                ->label(__('tallcms::fields.site'))
                ->formatStateUsing(fn ($state) => $sites[$state] ?? 'Unassigned')
                ->badge()
                ->color(fn ($state) => $state ? 'primary' : 'gray')
                ->sortable(),
        ];
    }
}
