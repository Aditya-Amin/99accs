<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Revenue Trend';

    protected function getData(): array
    {
        $year = now()->year;

        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(total_price) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = collect(range(1, 12))
            ->map(fn ($m) => round((float) ($monthlyRevenue[$m] ?? 0), 2))
            ->toArray();

        return [
            'datasets' => [
                [
                    'label'           => "Revenue {$year} ($)",
                    'data'            => $data,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
