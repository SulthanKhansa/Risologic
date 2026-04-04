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
            Stat::make('Total Products', Product::count())
                ->description('Products ready to sell')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),
            Stat::make('Raw Material Stock', RawMaterial::count())
                ->description('Types of materials available')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
            Stat::make('Total Sales', 'Rp ' . number_format(Sale::where('status', 'paid')->sum('total_price'), 0, ',', '.'))
                ->description('Cleared revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
