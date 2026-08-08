<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsCategories\Pages\CreateCmsCategory;
use TallCms\Cms\Filament\Resources\CmsCategories\Pages\EditCmsCategory;
use TallCms\Cms\Filament\Resources\CmsCategories\Pages\ListCmsCategories;
use TallCms\Cms\Filament\Resources\CmsCategories\Schemas\CmsCategoryForm;
use TallCms\Cms\Filament\Resources\CmsCategories\Tables\CmsCategoriesTable;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsCategory;

class CmsCategoryResource extends Resource
{
    use ScopesQueryToOwnedSites;
    use Translatable;

    protected static ?string $model = CmsCategory::class;

    public static function form(Schema $schema): Schema
    {
        return CmsCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsCategoriesTable::configure($table);
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
            'index' => ListCmsCategories::route('/'),
            'create' => CreateCmsCategory::route('/create'),
            'edit' => EditCmsCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('categories', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('categories', 'plural');
    }

    public static function getNavigationLabel(): string
    {
        return tallcms_label('categories', 'navigation');
    }

    public static function getNavigationSort(): ?int
    {
        return 12;
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
}
