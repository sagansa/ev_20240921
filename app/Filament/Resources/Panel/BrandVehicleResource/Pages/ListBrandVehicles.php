<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\Pages;

use App\Filament\Imports\VehicleHierarchyImporter;
use App\Filament\Resources\Panel\BrandVehicleResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListBrandVehicles extends ListRecords
{
    protected static string $resource = BrandVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(VehicleHierarchyImporter::class),
        ];
    }
}
