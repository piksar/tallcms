<?php

namespace App\Filament\Resources\CmsCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CmsCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Color'),
                    
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->limit(30)
                    ->color('gray'),
                    
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—')
                    ->limit(20),
                    
                TextColumn::make('posts_count')
                    ->label('Articles')
                    ->counts('posts')
                    ->badge(),
                    
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
