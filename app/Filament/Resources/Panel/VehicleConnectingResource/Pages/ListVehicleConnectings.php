<?php

namespace App\Filament\Resources\Panel\VehicleConnectingResource\Pages;

use App\Filament\Resources\Panel\VehicleConnectingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleConnectings extends ListRecords
{
    protected static string $resource = VehicleConnectingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
