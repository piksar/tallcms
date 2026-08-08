<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable;
use TallCms\Cms\Models\TallcmsMedia;

class TallcmsMediaResource extends Resource
{
    use ScopesQueryToOwnedSites;

    protected static ?string $model = TallcmsMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return tallcms_label('media', 'navigation');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('media', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('media', 'plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getNavigationSort(): ?int
    {
        return 13;
    }

    public static function form(Schema $schema): Schema
    {
        return TallcmsMediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TallcmsMediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeQueryToOwnedByUser(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTallcmsMedia::route('/'),
            'create' => CreateTallcmsMedia::route('/create'),
            'edit' => EditTallcmsMedia::route('/{record}/edit'),
        ];
    }
}
