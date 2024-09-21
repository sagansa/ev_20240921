<?php

namespace App\Filament\Resources\Panel\ChargerLocationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ChargerLocationResource;

class ListChargerLocations extends ListRecords
{
    protected static string $resource = ChargerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
