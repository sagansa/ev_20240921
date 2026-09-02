<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\Pages;

use App\Filament\Resources\Panel\BrandVehicleResource;
use Filament\Resources\Pages\ListRecords;

class ListBrandVehicles extends ListRecords
{
    protected static string $resource = BrandVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
