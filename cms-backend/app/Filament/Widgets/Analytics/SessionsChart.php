<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

class SessionsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Sessions & Pageviews';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Pageviews',
                    'data' => [1200, 1900, 1500, 2200, 2900, 3100, 2800],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => '#36A2EB',
                    'fill' => true,
                ],
                [
                    'label' => 'Sessions',
                    'data' => [800, 1500, 1100, 1600, 2100, 2400, 2100],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => '#FF6384',
                    'fill' => true,
                ],
            ],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
