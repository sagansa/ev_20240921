<?php

namespace App\Filament\Resources\Panel\BatteryResource\Pages;

use App\Filament\Resources\Panel\BatteryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBattery extends CreateRecord
{
    protected static string $resource = BatteryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
