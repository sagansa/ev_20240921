<?php

namespace App\Filament\Resources\Panel\TypeVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\TypeVehicleResource;

class EditTypeVehicle extends EditRecord
{
    protected static string $resource = TypeVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
