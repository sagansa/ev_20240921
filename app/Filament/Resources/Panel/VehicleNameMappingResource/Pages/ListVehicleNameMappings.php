<?php

namespace App\Filament\Resources\Panel\VehicleNameMappingResource\Pages;

use App\Filament\Resources\Panel\VehicleNameMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleNameMappings extends ListRecords
{
    protected static string $resource = VehicleNameMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
