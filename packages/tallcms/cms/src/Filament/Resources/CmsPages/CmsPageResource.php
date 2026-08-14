<?php

namespace TallCms\Cms\Filament\Resources\CmsPages;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPages\Pages\CreateCmsPage;
use TallCms\Cms\Filament\Resources\CmsPages\Pages\EditCmsPage;
use TallCms\Cms\Filament\Resources\CmsPages\Pages\ListCmsPages;
use TallCms\Cms\Filament\Resources\CmsPages\Schemas\CmsPageForm;
use TallCms\Cms\Filament\Resources\CmsPages\Tables\CmsPagesTable;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsPage;

class CmsPageResource extends Resource
{
    use ScopesQueryToOwnedSites;
    use Translatable;

    protected static ?string $model = CmsPage::class;

    // Title attribute enables global search automatically
    protected static ?string $recordTitleAttribute = 'title';

    // Limit results to prevent performance issues
    protected static int $globalSearchResultsLimit = 20;

    public static function form(Schema $schema): Schema
    {
        return CmsPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPagesTable::configure($table);
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
            'index' => ListCmsPages::route('/'),
            'create' => CreateCmsPage::route('/create'),
            'edit' => EditCmsPage::route('/{record}/edit'),
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

    public static function shouldRegisterNavigation(): bool
    {
        // In multisite mode, Pages are accessed through the Site resource.
        // The resource stays registered (URLs work) but nav is hidden.
        if (tallcms_multisite_active()) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('pages', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('pages', 'plural');
    }

    public static function getNavigationLabel(): string
    {
        return tallcms_label('pages', 'navigation');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getModel()::count();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the columns that should be searched globally.
     * Filament searches these columns using LIKE queries.
     *
     * Only search_content is used because title/slug are JSON columns
     * (Spatie Translatable), and LIKE on JSON fails on PostgreSQL and
     * causes false positives by matching locale keys like "en", "es".
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['search_content'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title ?? __('tallcms::ui.untitled');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $status = \TallCms\Cms\Enums\ContentStatus::tryFrom($record->status ?? 'draft');

        return [
            __('tallcms::fields.type') => static::getModelLabel(),
            __('tallcms::fields.status') => $status?->getLabel() ?? ($record->status ?? __('tallcms::fields.status_draft')),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    /**
     * Customize the base query for global search.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }
}
