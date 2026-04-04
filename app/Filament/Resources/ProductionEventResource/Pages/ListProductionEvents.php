<?php

namespace App\Filament\Resources\ProductionEventResource\Pages;

use App\Filament\Resources\ProductionEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionEvents extends ListRecords
{
    protected static string $resource = ProductionEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
