<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TallcmsMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('tallcms::fields.menu_name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('tallcms::ui.t_header_menu'))
                    ->helperText(__('tallcms::ui.t_internal_name_for_this_menu')),

                Select::make('location')
                    ->label(__('tallcms::fields.menu_location'))
                    ->options([
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'sidebar' => 'Sidebar',
                        'mobile' => 'Mobile',
                    ])
                    ->required()
                    ->searchable()
                    ->helperText(__('tallcms::ui.t_where_this_menu_will_appear_on_the_site')),

                Textarea::make('description')
                    ->label(__('tallcms::fields.description'))
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder(__('tallcms::ui.t_brief_description_of_this_menu'))
                    ->helperText(__('tallcms::ui.t_optional_description_for_administrative_purposes'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label(__('tallcms::fields.active'))
                    ->default(true)
                    ->helperText(__('tallcms::ui.t_enable_or_disable_this_menu')),
            ]);
    }
}
