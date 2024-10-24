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
            'not verified' => Tab::make()->query(fn($query) => $query->where('status', '1')),
            'verified' => Tab::make()->query(fn($query) => $query->where('status', '2')),
            'closed' => Tab::make()->query(fn($query) => $query->where('status', '3')),
            'external' => Tab::make()->query(fn($query) => $query->where('status', '4')),
        ];
    }
}
