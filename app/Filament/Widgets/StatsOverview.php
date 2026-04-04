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
        return [
            Stat::make('Total Produk', Product::count())
                ->description('Produk siap jual')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),
            Stat::make('Stok Bahan Baku', RawMaterial::count())
                ->description('Jenis bahan tersedia')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
            Stat::make('Total Penjualan', 'Rp ' . number_format(Sale::where('status', 'paid')->sum('total_price'), 0, ',', '.'))
                ->description('Pendapatan lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
