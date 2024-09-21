<?php

namespace App\Filament\Resources\Panel\CurrentChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\CurrentChargerResource;

class EditCurrentCharger extends EditRecord
{
    protected static string $resource = CurrentChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
