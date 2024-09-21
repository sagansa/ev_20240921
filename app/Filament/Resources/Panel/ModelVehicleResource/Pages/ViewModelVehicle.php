<?php

namespace App\Filament\Resources\Panel\ModelVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\ModelVehicleResource;

class ViewModelVehicle extends ViewRecord
{
    protected static string $resource = ModelVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
