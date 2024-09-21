<?php

namespace App\Filament\Resources\Panel\ChargeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ChargeResource;

class ListCharges extends ListRecords
{
    protected static string $resource = ChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
