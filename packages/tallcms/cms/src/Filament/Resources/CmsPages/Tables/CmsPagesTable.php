<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use TallCms\Cms\Enums\ContentStatus;

class CmsPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('tallcms::fields.image'))
                    ->square()
                    ->imageSize(50)
                    ->disk(cms_media_disk()),

                TextColumn::make('title')->label(__('tallcms::fields.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')->label(__('tallcms::fields.slug'))
                    ->searchable()
                    ->copyable()
                    ->limit(30),

                TextColumn::make('status')->label(__('tallcms::fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => ContentStatus::from($state)->getColor())
                    ->icon(fn (string $state): string => ContentStatus::from($state)->getIcon()),

                TextColumn::make('parent.title')
                    ->label(__('tallcms::fields.parent'))
                    ->limit(20),

                TextColumn::make('published_at')->label(__('tallcms::fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sort_order')->label(__('tallcms::fields.sort_order'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')->label(__('tallcms::fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Multisite: show which site each page belongs to.
                // Only visible when the multisite plugin is active.
                ...static::getSiteColumn(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ContentStatus::Draft->value => ContentStatus::Draft->getLabel(),
                        ContentStatus::Pending->value => ContentStatus::Pending->getLabel(),
                        ContentStatus::Published->value => ContentStatus::Published->getLabel(),
                    ]),

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
            ->defaultSort('created_at', 'desc');
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
