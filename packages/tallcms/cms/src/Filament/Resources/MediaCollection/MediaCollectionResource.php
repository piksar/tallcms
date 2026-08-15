<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\CreateMediaCollection;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\EditMediaCollection;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\ListMediaCollections;
use TallCms\Cms\Models\MediaCollection;
use TallCms\Cms\Rules\UserAwareUnique;

class MediaCollectionResource extends Resource
{
    use ScopesQueryToOwnedSites;

    protected static ?string $model = MediaCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    public static function getNavigationLabel(): string
    {
        return tallcms_label('media_collections', 'navigation');
    }

    public static function getModelLabel(): string
    {
        return tallcms_label('media_collections', 'singular');
    }

    public static function getPluralModelLabel(): string
    {
        return tallcms_label('media_collections', 'plural');
    }

    public static function getNavigationParentItem(): ?string
    {
        return tallcms_label('media', 'navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('content');
    }

    public static function getNavigationSort(): ?int
    {
        return 14;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('tallcms::fields.collection_name'))
                    ->required()
                    ->maxLength(255)
                    ->rules(fn (?MediaCollection $record) => [
                        UserAwareUnique::rule('tallcms_media_collections', 'name', $record?->id),
                    ])
                    ->autofocus(),

                TextInput::make('slug')
                    ->label(__('tallcms::fields.slug'))
                    ->maxLength(255)
                    ->rules(fn (?MediaCollection $record) => [
                        UserAwareUnique::rule('tallcms_media_collections', 'slug', $record?->id),
                    ])
                    ->helperText(__('tallcms::ui.t_leave_empty_to_auto_generate_from_name')),

                ColorPicker::make('color')
                    ->label(__('tallcms::fields.color'))
                    ->default('#6366f1')
                    ->helperText(__('tallcms::ui.t_used_for_badges_and_visual_identification')),

                Textarea::make('description')
                    ->label(__('tallcms::fields.description'))
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width(40),

                TextColumn::make('name')
                    ->label(__('tallcms::fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('media_count')
                    ->label(__('tallcms::fields.media'))
                    ->counts('media')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('description')
                    ->label(__('tallcms::fields.description'))
                    ->limit(50)
                    ->placeholder(__('tallcms::ui.t_no_description'))
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label(__('tallcms::fields.last_updated'))
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(__('tallcms::ui.t_delete_collection'))
                    ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_delete_this_collection_media_files_will_not')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::scopeQueryToOwnedByUser(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaCollections::route('/'),
            'create' => CreateMediaCollection::route('/create'),
            'edit' => EditMediaCollection::route('/{record}/edit'),
        ];
    }
}
