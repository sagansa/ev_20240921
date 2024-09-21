<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\BrandVehicleResource;

class ViewBrandVehicle extends ViewRecord
{
    protected static string $resource = BrandVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
