<?php

namespace App\Filament\Resources\Panel\BatteryResource\Pages;

use App\Filament\Resources\Panel\BatteryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBattery extends ViewRecord
{
    protected static string $resource = BatteryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
