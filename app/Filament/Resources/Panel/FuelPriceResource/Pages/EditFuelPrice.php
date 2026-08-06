<?php

namespace App\Filament\Resources\Panel\FuelPriceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\FuelPriceResource;

class EditFuelPrice extends EditRecord
{
    protected static string $resource = FuelPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
