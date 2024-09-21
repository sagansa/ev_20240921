<?php

namespace App\Filament\Resources\Panel\DiscountHomeChargingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\DiscountHomeChargingResource;

class ListDiscountHomeChargings extends ListRecords
{
    protected static string $resource = DiscountHomeChargingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
