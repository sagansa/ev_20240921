<?php

namespace App\Filament\Resources\Panel\AdvertisementResource\Pages;

use App\Filament\Resources\Panel\AdvertisementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
