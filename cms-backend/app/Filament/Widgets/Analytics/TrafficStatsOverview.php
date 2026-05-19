<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficStatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Users', '12.4K')
                ->description('Up 12% from last week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([2, 5, 8, 4, 12, 16, 21]),
            
            Stat::make('Sessions', '15.2K')
                ->description('Up 8% from last week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([5, 8, 4, 12, 16, 21, 25]),
                
            Stat::make('Bounce Rate', '42.3%')
                ->description('Down 2.1% from last week')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([45, 44, 46, 42, 41, 42, 42]),
                
            Stat::make('Avg. Session Duration', '2m 14s')
                ->description('Consistent with last week')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray'),
        ];
    }
}
