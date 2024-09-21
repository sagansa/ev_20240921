<?php

namespace App\Filament\Resources\Panel\ModelVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ModelVehicleResource;

class EditModelVehicle extends EditRecord
{
    protected static string $resource = ModelVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
