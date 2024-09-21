<?php

namespace App\Filament\Resources\Panel\DiscountHomeChargingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\DiscountHomeChargingResource;
use Illuminate\Support\Facades\Auth;

class CreateDiscountHomeCharging extends CreateRecord
{
    protected static string $resource = DiscountHomeChargingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
