<?php

namespace App\Filament\Resources\Panel\ChargerLocationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ChargerLocationResource;
use Filament\Resources\Components\Tab;

class ListChargerLocations extends ListRecords
{
    protected static string $resource = ChargerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'public' => Tab::make()->query(fn ($query) => $query->where('location_on', '1')),
            'private' => Tab::make()->query(fn ($query) => $query->where('location_on', '2')),
            'dealer' => Tab::make()->query(fn ($query) => $query->where('location_on', '3')),
            'closed' => Tab::make()->query(fn ($query) => $query->where('location_on', '4')),
        ];
    }
}
