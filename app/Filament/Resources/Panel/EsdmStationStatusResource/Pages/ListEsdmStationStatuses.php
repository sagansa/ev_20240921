<?php

namespace App\Filament\Resources\Panel\EsdmStationStatusResource\Pages;

use App\Filament\Resources\Panel\EsdmStationStatusResource;
use Filament\Resources\Pages\ListRecords;

class ListEsdmStationStatuses extends ListRecords
{
    protected static string $resource = EsdmStationStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
