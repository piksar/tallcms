<?php

namespace App\Filament\Resources\TallcmsMenus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TallcmsMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Menu Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Header Menu')
                    ->helperText('Internal name for this menu'),

                Select::make('location')
                    ->label('Menu Location')
                    ->options([
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'sidebar' => 'Sidebar',
                        'mobile' => 'Mobile',
                    ])
                    ->required()
                    ->searchable()
                    ->helperText('Where this menu will appear on the site'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder('Brief description of this menu')
                    ->helperText('Optional description for administrative purposes')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Enable or disable this menu'),
            ]);
    }
}
