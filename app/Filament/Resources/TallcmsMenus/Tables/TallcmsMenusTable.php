<?php

namespace App\Filament\Resources\TallcmsMenus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;

class TallcmsMenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('allItems_count')
                    ->label('Menu Items')
                    ->counts('allItems')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state . ' items'),
                    
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('manage_items')
                    ->label('Manage Items')
                    ->icon('heroicon-o-bars-3')
                    ->color('primary')
                    ->url(fn ($record): string => "/admin/menu-items-manager?activeTab={$record->id}")
                    ->openUrlInNewTab(false),
                    
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Preview: ' . $record->name)
                    ->modalContent(fn ($record) => view('admin.menu-preview', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                    
                EditAction::make()
                    ->label('Settings'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
