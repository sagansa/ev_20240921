<?php

namespace App\Filament\Resources\Panel\StateOfHealthResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\StateOfHealthResource;

class CreateStateOfHealth extends CreateRecord
{
    protected static string $resource = StateOfHealthResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
