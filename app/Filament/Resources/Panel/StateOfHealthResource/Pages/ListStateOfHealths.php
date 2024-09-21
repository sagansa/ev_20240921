<?php

namespace App\Filament\Resources\Panel\StateOfHealthResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\StateOfHealthResource;

class ListStateOfHealths extends ListRecords
{
    protected static string $resource = StateOfHealthResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
