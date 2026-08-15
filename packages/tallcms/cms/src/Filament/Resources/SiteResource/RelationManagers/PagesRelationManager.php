<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return tallcms_label('pages', 'plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('tallcms::fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('slug')->label(__('tallcms::fields.slug'))
                    ->searchable()
                    ->limit(30)
                    ->color('gray'),

                TextColumn::make('status')->label(__('tallcms::fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => ContentStatus::from($state)->getColor()),

                IconColumn::make('is_homepage')
                    ->label(__('tallcms::fields.home'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')->label(__('tallcms::fields.updated_at'))
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Action::make('create_page')
                    ->label(__('tallcms::fields.create_page'))
                    ->icon('heroicon-m-plus')
                    // ?site=<id> sets site_id explicitly on save; ?from_site=<id>
                    // is the navigation breadcrumb that lands the user back on
                    // this Site after save instead of the global Pages index.
                    // Both query params are URL-explicit so the post-save
                    // redirect survives the Livewire round trip — session
                    // state alone wouldn't (mid-request session writes don't
                    // always reach the redirect target reliably).
                    ->url(fn () => CmsPageResource::getUrl('create', [
                        'site' => $this->getOwnerRecord()->id,
                        'from_site' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('tallcms::fields.edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => CmsPageResource::getUrl('edit', [
                        'record' => $record,
                        'from_site' => $this->getOwnerRecord()->id,
                    ])),
            ]);
    }
}
