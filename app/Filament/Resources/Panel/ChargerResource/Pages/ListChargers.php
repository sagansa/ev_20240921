<?php

namespace App\Filament\Resources\Panel\ChargerResource\Pages;

use App\Filament\Resources\Panel\ChargerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListChargers extends ListRecords
{
    protected static string $resource = ChargerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'not verified' => Tab::make()->query(fn($query) => $query->where('status', '1')),
            'verified' => Tab::make()->query(fn($query) => $query->where('status', '2')),
            'closed' => Tab::make()->query(fn($query) => $query->where('status', '3')),
        ];
    }
}
