<?php

namespace App\Filament\Resources\Panel\DiscountHomeChargingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\DiscountHomeChargingResource;

class EditDiscountHomeCharging extends EditRecord
{
    protected static string $resource = DiscountHomeChargingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
