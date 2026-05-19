<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

class DeviceUsageChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Device Usage';
    
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Devices',
                    'data' => [62, 35, 3],
                    'backgroundColor' => [
                        '#FF9F40', // Mobile
                        '#36A2EB', // Desktop
                        '#9966FF', // Tablet
                    ],
                ],
            ],
            'labels' => ['Mobile', 'Desktop', 'Tablet'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
