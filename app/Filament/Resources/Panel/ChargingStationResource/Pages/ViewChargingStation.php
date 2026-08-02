<?php

namespace App\Filament\Resources\Panel\ChargingStationResource\Pages;

use App\Filament\Resources\Panel\ChargingStationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChargingStation extends ViewRecord
{
    protected static string $resource = ChargingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
