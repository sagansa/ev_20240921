<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\BrandVehicleResource;

class ListBrandVehicles extends ListRecords
{
    protected static string $resource = BrandVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
