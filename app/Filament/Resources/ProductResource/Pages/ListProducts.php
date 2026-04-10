<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => \Filament\Resources\Components\Tab::make('Semua')
                ->badge(\App\Models\Product::count()),
            'final' => \Filament\Resources\Components\Tab::make('Produk Jadi')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'final'))
                ->badge(\App\Models\Product::where('type', 'final')->count()),
            'intermediate' => \Filament\Resources\Components\Tab::make('Produk Setengah Jadi')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'intermediate'))
                ->badge(\App\Models\Product::where('type', 'intermediate')->count()),
        ];
    }
}
