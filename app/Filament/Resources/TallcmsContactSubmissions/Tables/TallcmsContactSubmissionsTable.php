<?php

namespace App\Filament\Resources\TallcmsContactSubmissions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TallcmsContactSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('warning')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(fn ($record) => $record->is_read ? 'normal' : 'bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied'),

                TextColumn::make('page_url')
                    ->label('From Page')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->page_url)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->placeholder('All submissions')
                    ->trueLabel('Read')
                    ->falseLabel('Unread'),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('toggle_read')
                    ->label(fn ($record) => $record->is_read ? 'Mark Unread' : 'Mark Read')
                    ->icon(fn ($record) => $record->is_read ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
                    ->action(function ($record) {
                        $record->is_read ? $record->markAsUnread() : $record->markAsRead();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_read')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-envelope-open')
                        ->action(fn (Collection $records) => $records->each->markAsRead())
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_unread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-envelope')
                        ->action(fn (Collection $records) => $records->each->markAsUnread())
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No contact submissions yet')
            ->emptyStateDescription('When visitors submit your contact form, their messages will appear here.')
            ->emptyStateIcon('heroicon-o-envelope');
    }
}
