<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $todayOmzet = Sale::whereDate('created_at', $today)->where('status', 'paid')->sum('total_price');
        $todayProfit = Sale::whereDate('created_at', $today)->where('status', 'paid')->sum('gross_profit');
        $lowStockCount = RawMaterial::where('current_stock', '<=', 10)->count();

        return [
            Stat::make('Revenue Today', 'Rp ' . number_format($todayOmzet, 0, ',', '.'))
                ->description('Total paid sales today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Gross Profit Today', 'Rp ' . number_format($todayProfit, 0, ',', '.'))
                ->description('Profit after material costs (HPP)')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('info'),

            Stat::make('Low Stock Alert', $lowStockCount . ' Items')
                ->description('Materials with stock <= 10')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
