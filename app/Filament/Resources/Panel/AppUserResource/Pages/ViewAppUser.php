<?php

namespace App\Filament\Resources\Panel\AppUserResource\Pages;

use App\Filament\Resources\Panel\AppUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAppUser extends ViewRecord
{
    protected static string $resource = AppUserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
