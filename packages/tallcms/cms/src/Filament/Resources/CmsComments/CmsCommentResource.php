<?php

namespace TallCms\Cms\Filament\Resources\CmsComments;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TallCms\Cms\Filament\Resources\CmsComments\Pages\ListCmsComments;
use TallCms\Cms\Filament\Resources\CmsComments\Pages\ViewCmsComment;
use TallCms\Cms\Filament\Resources\CmsComments\Tables\CmsCommentsTable;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsComment;

class CmsCommentResource extends Resource
{
    use ScopesQueryToOwnedSites;

    protected static ?string $model = CmsComment::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('comments', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('comments', 'plural');
    }

    public static function getNavigationLabel(): string
    {
        return tallcms_label('comments', 'navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getNavigationSort(): ?int
    {
        return 16;
    }

    public static function table(Table $table): Table
    {
        return CmsCommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsComments::route('/'),
            'view' => ViewCmsComment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::scopeQueryToOwnedSites($query);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::scopeQueryToOwnedSites(static::getModel()::pending())->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
