<?php

namespace App\Filament\Resources\Panel\PowerChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\PowerChargerResource;

class EditPowerCharger extends EditRecord
{
    protected static string $resource = PowerChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
