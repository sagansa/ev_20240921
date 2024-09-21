<?php

namespace App\Filament\Resources\Panel\ChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ChargerResource;

class EditCharger extends EditRecord
{
    protected static string $resource = ChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
