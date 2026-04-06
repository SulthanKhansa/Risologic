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
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $data[] = ProductionEvent::whereDate('created_at', $date)->where('status', 'completed')->sum('quantity_produced');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Risol Diproduksi (Pcs)',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
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
