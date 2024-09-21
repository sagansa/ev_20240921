<?php

namespace App\Filament\Resources\Panel\CurrentChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\CurrentChargerResource;

class ViewCurrentCharger extends ViewRecord
{
    protected static string $resource = CurrentChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
