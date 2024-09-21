<?php

namespace App\Filament\Resources\Panel\ChargerLocationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ChargerLocationResource;

class EditChargerLocation extends EditRecord
{
    protected static string $resource = ChargerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
