<?php

namespace App\Filament\Resources\ProductionEventResource\Pages;

use App\Filament\Resources\ProductionEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionEvent extends CreateRecord
{
    protected static string $resource = ProductionEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(fn () => $this->getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-m-arrow-left'),
        ];
    }
}
