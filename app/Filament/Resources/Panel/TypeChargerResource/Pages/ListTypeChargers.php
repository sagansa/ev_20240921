<?php

namespace App\Filament\Resources\Panel\TypeChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\TypeChargerResource;

class ListTypeChargers extends ListRecords
{
    protected static string $resource = TypeChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
