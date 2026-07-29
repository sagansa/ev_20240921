<?php

namespace App\Filament\Resources\Panel\AdvertisementResource\Pages;

use App\Filament\Resources\Panel\AdvertisementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdvertisement extends EditRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
