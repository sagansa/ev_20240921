<?php

namespace App\Filament\Resources\Panel\ModelVehicleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ModelVehicleResource;

class ListModelVehicles extends ListRecords
{
    protected static string $resource = ModelVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
