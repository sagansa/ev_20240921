<?php

namespace App\Filament\Resources\Panel\MerkChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\MerkChargerResource;

class EditMerkCharger extends EditRecord
{
    protected static string $resource = MerkChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
