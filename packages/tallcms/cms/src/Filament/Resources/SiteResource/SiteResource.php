<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use TallCms\Cms\Filament\Resources\SiteResource\Pages\EditSite;
use TallCms\Cms\Models\Site;

/**
 * Core Site resource.
 *
 * In standalone mode: single record, direct edit, nav label "Site Settings".
 * In multisite mode: the multisite plugin extends this with list page,
 * ownership, domain verification, etc.
 */
class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return tallcms_label('site_settings', 'navigation');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('site_settings', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('site_settings', 'plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('configuration');
    }

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
            RelationManagers\MenusRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSite::route('/'),
        ];
    }
}
