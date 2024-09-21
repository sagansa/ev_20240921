<?php

namespace App\Filament\Resources\Panel\ChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ChargerResource;

class ListChargers extends ListRecords
{
    protected static string $resource = ChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
