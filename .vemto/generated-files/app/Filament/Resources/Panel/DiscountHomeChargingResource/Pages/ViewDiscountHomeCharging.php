<?php

namespace App\Filament\Resources\Panel\DiscountHomeChargingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\DiscountHomeChargingResource;

class ViewDiscountHomeCharging extends ViewRecord
{
    protected static string $resource = DiscountHomeChargingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
