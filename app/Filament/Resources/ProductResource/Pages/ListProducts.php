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
            'all' => \Filament\Resources\Components\Tab::make('All')
                ->badge(\App\Models\Product::count()),
            'final' => \Filament\Resources\Components\Tab::make('Final Product')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'final'))
                ->badge(\App\Models\Product::where('type', 'final')->count()),
            'intermediate' => \Filament\Resources\Components\Tab::make('Intermediate Product')
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'intermediate'))
                ->badge(\App\Models\Product::where('type', 'intermediate')->count()),
        ];
    }
}
