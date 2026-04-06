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
            Stat::make('Omzet Hari Ini', 'Rp ' . number_format($todayOmzet, 0, ',', '.'))
                ->description('Total penjualan lunas hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Profit Hari Ini', 'Rp ' . number_format($todayProfit, 0, ',', '.'))
                ->description('Keuntungan kotor (setelah HPP)')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('info'),

            Stat::make('Bahan Perlu Belanja', $lowStockCount . ' Jenis')
                ->description('Bahan baku dengan stok <= 10')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
