<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('tallcms::fields.full_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('tallcms::fields.email_address'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email address copied'),

                TagsColumn::make('roles.name')
                    ->label(__('tallcms::fields.roles'))
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('created_at')
                    ->label(__('tallcms::fields.joined'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M j, Y g:i A')),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('tallcms::fields.role'))
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
