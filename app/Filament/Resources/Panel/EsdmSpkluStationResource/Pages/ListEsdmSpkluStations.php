<?php

namespace App\Filament\Resources\Panel\EsdmSpkluStationResource\Pages;

use App\Filament\Resources\Panel\EsdmSpkluStationResource;
use Filament\Resources\Pages\ListRecords;

class ListEsdmSpkluStations extends ListRecords
{
    protected static string $resource = EsdmSpkluStationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
