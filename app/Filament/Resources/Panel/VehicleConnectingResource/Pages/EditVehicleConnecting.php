<?php

namespace App\Filament\Resources\Panel\VehicleConnectingResource\Pages;

use App\Filament\Resources\Panel\VehicleConnectingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleConnecting extends EditRecord
{
    protected static string $resource = VehicleConnectingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
