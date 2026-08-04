<?php

namespace App\Filament\Resources\Panel\PlnEsdmStationMatchResource\Pages;

use App\Filament\Resources\Panel\PlnEsdmStationMatchResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPlnEsdmStationMatch extends ViewRecord
{
    protected static string $resource = PlnEsdmStationMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
