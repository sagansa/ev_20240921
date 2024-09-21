<?php

namespace App\Filament\Resources\Panel\StateOfHealthResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\StateOfHealthResource;

class EditStateOfHealth extends EditRecord
{
    protected static string $resource = StateOfHealthResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
