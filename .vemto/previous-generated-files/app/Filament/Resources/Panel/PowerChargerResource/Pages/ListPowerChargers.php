<?php

namespace App\Filament\Resources\Panel\PowerChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\PowerChargerResource;

class ListPowerChargers extends ListRecords
{
    protected static string $resource = PowerChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
