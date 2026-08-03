<?php

namespace App\Filament\Resources\Panel\PlnChargerLocationResource\Pages;

use App\Filament\Resources\Panel\PlnChargerLocationResource;
use Filament\Resources\Pages\ListRecords;

class ListPlnChargerLocations extends ListRecords
{
    protected static string $resource = PlnChargerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
