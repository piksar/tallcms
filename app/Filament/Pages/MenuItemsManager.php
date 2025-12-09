<?php

namespace App\Filament\Pages;

use App\Models\CmsPage;
use App\Models\TallcmsMenuItem;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Tabs\Tab;
use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class MenuItemsManager extends NestedsetPage
{
    protected static ?string $model = TallcmsMenuItem::class;

    protected static string $recordTitleAttribute = 'label';
    
    protected static ?string $tabFieldName = 'menu_id';
    
    protected static ?int $level = 5; // Allow up to 5 levels of nesting
    
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    public function getTabs(): array
    {
        $menus = \App\Models\TallcmsMenu::all();
        $tabs = [];
        
        foreach ($menus as $menu) {
            $tabs[$menu->id] = Tab::make()
                ->label($menu->name . ' (' . $menu->allItems()->count() . ')');
        }
        
        return $tabs;
    }

    protected function schema(array $arguments): array
    {
        return [
            Hidden::make('menu_id')
                ->default(function () use ($arguments) {
                    return $arguments['tab'] ?? request()->get('activeTab');
                }),
                
            TextInput::make('label')
                ->label('Menu Label')
                ->required()
                ->maxLength(255)
                ->placeholder('Home'),

            Select::make('type')
                ->label('Link Type')
                ->options([
                    'page' => 'Page',
                    'external' => 'External URL', 
                    'custom' => 'Custom URL',
                    'header' => 'Header',
                    'separator' => 'Separator',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('page_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('url', null)),

            Select::make('page_id')
                ->label('Select Page')
                ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                ->searchable()
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === 'page'),

            TextInput::make('url')
                ->label('URL')
                ->required()
                ->placeholder('https://example.com or /contact')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['external', 'custom'])),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }
}
