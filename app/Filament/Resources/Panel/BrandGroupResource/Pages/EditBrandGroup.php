<?php

namespace App\Filament\Resources\Panel\BrandGroupResource\Pages;

use App\Filament\Resources\Panel\BrandGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandGroup extends EditRecord
{
    protected static string $resource = BrandGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
