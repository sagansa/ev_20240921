<?php

namespace App\Filament\Resources\Panel\TypeVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\TypeVehicleResource;

class ListTypeVehicles extends ListRecords
{
    protected static string $resource = TypeVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
