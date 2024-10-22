<?php

namespace App\Filament\Resources\Panel\ChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\ChargerResource;

class ViewCharger extends ViewRecord
{
    protected static string $resource = ChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
