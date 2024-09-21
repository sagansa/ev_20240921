<?php

namespace App\Filament\Resources\Panel\TypeVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\TypeVehicleResource;

class ViewTypeVehicle extends ViewRecord
{
    protected static string $resource = TypeVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
