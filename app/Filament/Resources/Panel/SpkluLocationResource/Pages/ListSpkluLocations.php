<?php

namespace App\Filament\Resources\Panel\SpkluLocationResource\Pages;

use App\Filament\Resources\Panel\SpkluLocationResource;
use Filament\Resources\Pages\ListRecords;

class ListSpkluLocations extends ListRecords
{
    protected static string $resource = SpkluLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
