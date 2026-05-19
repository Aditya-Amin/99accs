<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

class TrafficSourcesChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Traffic Sources';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Traffic Sources',
                    'data' => [55, 25, 15, 5],
                    'backgroundColor' => [
                        '#36A2EB', // Organic Search
                        '#4BC0C0', // Direct
                        '#FFCE56', // Social
                        '#FF6384', // Referral
                    ],
                ],
            ],
            'labels' => ['Organic Search', 'Direct', 'Social', 'Referral'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
