<?php

namespace App\Filament\Resources\Panel\EsdmSpbkluStationResource\Pages;

use App\Filament\Resources\Panel\EsdmSpbkluStationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEsdmSpbkluStation extends ViewRecord
{
    protected static string $resource = EsdmSpbkluStationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
