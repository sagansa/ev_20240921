<?php

namespace App\Filament\Resources\Panel\VehicleNameMappingResource\Pages;

use App\Filament\Resources\Panel\VehicleNameMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleNameMapping extends CreateRecord
{
    protected static string $resource = VehicleNameMappingResource::class;

    /** Normalisasi kunci unik sebelum simpan (sumber: matcher). */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $matcher = app(\App\Services\VehicleSalesMatcher::class);
        $data['raw_brand_norm'] = $matcher->normalize($data['raw_brand'] ?? '');
        $data['raw_model_norm'] = $matcher->normalize($data['raw_model'] ?? '');

        return $data;
    }
}
