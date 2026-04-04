<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RisolFinanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $totalCost = Sale::whereDate('created_at', $today)->where('status', 'paid')->sum('total_cost');
        $totalProfit = Sale::whereDate('created_at', $today)->where('status', 'paid')->sum('gross_profit');
        $avgMargin = Sale::whereDate('created_at', $today)->where('status', 'paid')->avg('margin_percentage') ?? 0;

        return [
            Stat::make('Total Daily Costs', 'Rp ' . number_format($totalCost, 0, ',', '.'))
                ->description('Daily material costs')
                ->descriptionIcon('heroicon-m-shopping-cart')->color('danger'),
            Stat::make('Total Net Profit', 'Rp ' . number_format($totalProfit, 0, ',', '.'))
                ->description('Net profit today')
                ->descriptionIcon('heroicon-m-banknotes')->color('success'),
            Stat::make('Avg. Margin %', number_format($avgMargin, 1) . '%')
                ->description('Profit health')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgMargin >= 30 ? 'success' : ($avgMargin >= 10 ? 'warning' : 'danger')),
        ];
    }
}
