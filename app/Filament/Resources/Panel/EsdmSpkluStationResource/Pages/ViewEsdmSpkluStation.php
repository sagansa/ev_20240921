<?php

namespace App\Filament\Resources\Panel\EsdmSpkluStationResource\Pages;

use App\Filament\Resources\Panel\EsdmSpkluStationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEsdmSpkluStation extends ViewRecord
{
    protected static string $resource = EsdmSpkluStationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
