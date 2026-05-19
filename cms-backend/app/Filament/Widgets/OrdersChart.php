<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Orders per Month';

    protected function getData(): array
    {
        $year = now()->year;

        $monthlyCounts = Order::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        $data = collect(range(1, 12))
            ->map(fn ($m) => (int) ($monthlyCounts[$m] ?? 0))
            ->toArray();

        return [
            'datasets' => [
                [
                    'label'           => "Orders {$year}",
                    'data'            => $data,
                    'backgroundColor' => 'rgba(59,130,246,0.7)',
                    'borderColor'     => '#3b82f6',
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
