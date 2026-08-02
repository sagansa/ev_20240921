<?php

namespace App\Filament\Resources\Panel\EsdmSpbkluStationResource\Pages;

use App\Filament\Resources\Panel\EsdmSpbkluStationResource;
use Filament\Resources\Pages\ListRecords;

class ListEsdmSpbkluStations extends ListRecords
{
    protected static string $resource = EsdmSpbkluStationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
