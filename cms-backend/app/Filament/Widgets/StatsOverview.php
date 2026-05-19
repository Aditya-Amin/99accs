<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now  = now();
        $year = $now->year;

        // ── Revenue ──────────────────────────────────────────────────────────
        $totalRevenue      = (float) Order::where('payment_status', 'paid')->sum('total_price');
        $thisMonthRevenue  = (float) Order::where('payment_status', 'paid')
            ->whereYear('created_at', $year)->whereMonth('created_at', $now->month)
            ->sum('total_price');
        $lastMonthRevenue  = (float) Order::where('payment_status', 'paid')
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->sum('total_price');
        $revenueChange     = $this->pctChange($thisMonthRevenue, $lastMonthRevenue);
        $revenueChart      = $this->dailyTrend(
            7, fn ($d) => Order::where('payment_status', 'paid')->whereDate('created_at', $d)->sum('total_price')
        );

        // ── Customers ────────────────────────────────────────────────────────
        $totalCustomers    = Customer::count();
        $thisMonthCust     = Customer::whereYear('created_at', $year)->whereMonth('created_at', $now->month)->count();
        $lastMonthCust     = Customer::whereYear('created_at', $now->copy()->subMonth()->year)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)->count();
        $customerChange    = $this->pctChange($thisMonthCust, $lastMonthCust);
        $customerChart     = $this->dailyTrend(7, fn ($d) => Customer::whereDate('created_at', $d)->count());

        // ── Orders ───────────────────────────────────────────────────────────
        $totalOrders       = Order::count();
        $thisMonthOrders   = Order::whereYear('created_at', $year)->whereMonth('created_at', $now->month)->count();
        $lastMonthOrders   = Order::whereYear('created_at', $now->copy()->subMonth()->year)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)->count();
        $orderChange       = $this->pctChange($thisMonthOrders, $lastMonthOrders);
        $orderChart        = $this->dailyTrend(7, fn ($d) => Order::whereDate('created_at', $d)->count());

        // ── Products ─────────────────────────────────────────────────────────
        $totalProducts     = Product::where('is_visible', true)->count();
        $outOfStock        = Product::where('is_visible', true)->where('stock_qty', 0)->count();
        $productChart      = $this->dailyTrend(7, fn ($d) => Product::whereDate('created_at', $d)->count());

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description($this->descLabel($revenueChange, 'this month'))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description($this->descLabel($customerChange, 'this month'))
                ->descriptionIcon($customerChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($customerChange >= 0 ? 'success' : 'danger')
                ->chart($customerChart),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description($this->descLabel($orderChange, 'this month'))
                ->descriptionIcon($orderChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($orderChange >= 0 ? 'success' : 'danger')
                ->chart($orderChart),

            Stat::make('Active Products', number_format($totalProducts))
                ->description($outOfStock > 0 ? "{$outOfStock} out of stock" : 'All in stock')
                ->descriptionIcon($outOfStock > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($outOfStock > 0 ? 'warning' : 'success')
                ->chart($productChart),
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function dailyTrend(int $days, callable $query): array
    {
        return collect(range($days - 1, 0))
            ->map(fn ($i) => (float) $query(now()->subDays($i)->toDateString()))
            ->toArray();
    }

    private function pctChange(float $current, float $previous): int
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function descLabel(int $pct, string $period): string
    {
        if ($pct === 0) {
            return "No change {$period}";
        }
        return $pct > 0
            ? "{$pct}% increase {$period}"
            : abs($pct) . "% decrease {$period}";
    }
}
