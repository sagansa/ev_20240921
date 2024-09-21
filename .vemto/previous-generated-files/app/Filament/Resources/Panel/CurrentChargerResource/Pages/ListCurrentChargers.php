<?php

namespace App\Filament\Resources\Panel\CurrentChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\CurrentChargerResource;

class ListCurrentChargers extends ListRecords
{
    protected static string $resource = CurrentChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
