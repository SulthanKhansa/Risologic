<?php

namespace App\Filament\Resources\ProductionEventResource\Pages;

use App\Filament\Resources\ProductionEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionEvent extends EditRecord
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
            Actions\DeleteAction::make(),
        ];
    }
}
