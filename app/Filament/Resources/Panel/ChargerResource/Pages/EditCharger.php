<?php

namespace App\Filament\Resources\Panel\ChargerResource\Pages;

use App\Filament\Resources\Panel\ChargerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCharger extends EditRecord
{
    protected static string $resource = ChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
