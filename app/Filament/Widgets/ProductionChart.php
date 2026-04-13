<?php

namespace App\Filament\Widgets;

use App\Models\ProductionEvent;
use Filament\Widgets\ChartWidget;

class ProductionChart extends ChartWidget
{
    protected static ?string $heading = 'Volume Produksi (7 Hari Terakhir)';
    protected static string $color = 'info';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        
        $productionData = ProductionEvent::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(quantity_produced) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateString = $dateObj->format('Y-m-d');
            
            $labels[] = $dateObj->format('d M');
            $data[] = (float) ($productionData[$dateString] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Risol Diproduksi (Pcs)',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}