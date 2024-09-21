<?php

namespace App\Filament\Resources\Panel\TypeChargerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\TypeChargerResource;

class ViewTypeCharger extends ViewRecord
{
    protected static string $resource = TypeChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
