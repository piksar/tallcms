<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TallCms\Cms\Filament\Resources\Users\Pages\CreateUser;
use TallCms\Cms\Filament\Resources\Users\Pages\EditUser;
use TallCms\Cms\Filament\Resources\Users\Pages\ListUsers;
use TallCms\Cms\Filament\Resources\Users\Schemas\UserForm;
use TallCms\Cms\Filament\Resources\Users\Tables\UsersTable;

class UserResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModelLabel(): string
    {
        return tallcms_label('users', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('users', 'plural');
    }

    public static function getNavigationLabel(): string
    {
        return tallcms_label('users', 'navigation');
    }

    public static function getModel(): string
    {
        return config('tallcms.plugin_mode.user_model', 'App\\Models\\User');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('system');
    }

    public static function getNavigationSort(): ?int
    {
        return 51;
    }
}
