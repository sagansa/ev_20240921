<?php

namespace App\Filament\Resources\Panel\MerkChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\MerkChargerResource;

class ViewMerkCharger extends ViewRecord
{
    protected static string $resource = MerkChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
