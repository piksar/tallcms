<?php

namespace App\Filament\Widgets;

use App\Models\TallcmsMenu;
use App\Models\TallcmsMenuItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMenus = TallcmsMenu::count();
        $activeMenus = TallcmsMenu::where('is_active', true)->count();
        $totalItems = TallcmsMenuItem::count();
        $activeItems = TallcmsMenuItem::where('is_active', true)->count();

        return [
            Stat::make('Total Menus', $totalMenus)
                ->description("{$activeMenus} active")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
                
            Stat::make('Total Menu Items', $totalItems)
                ->description("{$activeItems} active items")
                ->descriptionIcon('heroicon-m-bars-3')
                ->chart([15, 4, 10, 2, 12, 4, 12])
                ->color('info'),
                
            Stat::make('Average Items per Menu', $totalMenus > 0 ? round($totalItems / $totalMenus, 1) : 0)
                ->description('Items distribution')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}