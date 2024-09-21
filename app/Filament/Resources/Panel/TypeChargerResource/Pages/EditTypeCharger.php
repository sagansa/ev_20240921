<?php

namespace App\Filament\Resources\Panel\TypeChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\TypeChargerResource;

class EditTypeCharger extends EditRecord
{
    protected static string $resource = TypeChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
