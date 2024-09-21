<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\BrandVehicleResource;

class EditBrandVehicle extends EditRecord
{
    protected static string $resource = BrandVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
