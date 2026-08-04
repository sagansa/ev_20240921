<?php

namespace App\Filament\Resources\Panel\PlnEsdmStationMatchResource\Pages;

use App\Filament\Resources\Panel\PlnEsdmStationMatchResource;
use Filament\Resources\Pages\ListRecords;

class ListPlnEsdmStationMatches extends ListRecords
{
    protected static string $resource = PlnEsdmStationMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
