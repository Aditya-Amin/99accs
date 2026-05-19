<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Analytics extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $title = 'Analytics';

    protected static string $routePath = 'analytics';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Analytics\TrafficStatsOverview::class,
            \App\Filament\Widgets\Analytics\SessionsChart::class,
            \App\Filament\Widgets\Analytics\TrafficSourcesChart::class,
            \App\Filament\Widgets\Analytics\DeviceUsageChart::class,
        ];
    }
}
