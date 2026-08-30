<?php

namespace App\Filament\Resources\Panel\VehicleNameMappingResource\Pages;

use App\Filament\Resources\Panel\VehicleNameMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleNameMapping extends EditRecord
{
    protected static string $resource = VehicleNameMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $matcher = app(\App\Services\VehicleSalesMatcher::class);
        $data['raw_brand_norm'] = $matcher->normalize($data['raw_brand'] ?? '');
        $data['raw_model_norm'] = $matcher->normalize($data['raw_model'] ?? '');

        return $data;
    }
}
