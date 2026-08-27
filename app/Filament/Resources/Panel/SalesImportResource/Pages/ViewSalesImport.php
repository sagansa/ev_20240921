<?php

namespace App\Filament\Resources\Panel\SalesImportResource\Pages;

use App\Filament\Resources\Panel\SalesImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesImport extends ViewRecord
{
    protected static string $resource = SalesImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
