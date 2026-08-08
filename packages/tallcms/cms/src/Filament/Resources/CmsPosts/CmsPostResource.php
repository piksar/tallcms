<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\CreateCmsPost;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\EditCmsPost;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\ListCmsPosts;
use TallCms\Cms\Filament\Resources\CmsPosts\Schemas\CmsPostForm;
use TallCms\Cms\Filament\Resources\CmsPosts\Tables\CmsPostsTable;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsPost;

class CmsPostResource extends Resource
{
    use ScopesQueryToOwnedSites;
    use Translatable;

    protected static ?string $model = CmsPost::class;

    // Title attribute enables global search automatically
    protected static ?string $recordTitleAttribute = 'title';

    // Limit results to prevent performance issues
    protected static int $globalSearchResultsLimit = 20;

    public static function form(Schema $schema): Schema
    {
        return CmsPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsPosts::route('/'),
            'create' => CreateCmsPost::route('/create'),
            'edit' => EditCmsPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('posts', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('posts', 'plural');
    }

    public static function getNavigationLabel(): string
    {
        return tallcms_label('posts', 'navigation');
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::scopeQueryToOwnedByUser($query);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getEloquentQuery()->count();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the columns that should be searched globally.
     * Filament searches these columns using LIKE queries.
     *
     * Only search_content is used because title/slug/excerpt are JSON columns
     * (Spatie Translatable), and LIKE on JSON fails on PostgreSQL and
     * causes false positives by matching locale keys like "en", "es".
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['search_content'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title ?? __('Untitled');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('tallcms::fields.type') => static::getModelLabel(),
            __('Status') => __(ucfirst($record->status ?? 'draft')),
            __('Author') => $record->author?->name ?? __('Unknown'),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    /**
     * Customize the base query for global search.
     * Eager load author to avoid N+1 queries in result details.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('author');
    }
}
