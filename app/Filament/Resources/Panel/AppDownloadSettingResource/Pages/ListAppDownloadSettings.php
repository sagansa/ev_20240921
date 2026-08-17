<?php

namespace App\Filament\Resources\Panel\AppDownloadSettingResource\Pages;

use App\Filament\Resources\Panel\AppDownloadSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppDownloadSettings extends ListRecords
{
    protected static string $resource = AppDownloadSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
