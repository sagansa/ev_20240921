<?php

namespace App\Filament\Resources\Panel\MerkChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\MerkChargerResource;

class ListMerkChargers extends ListRecords
{
    protected static string $resource = MerkChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
