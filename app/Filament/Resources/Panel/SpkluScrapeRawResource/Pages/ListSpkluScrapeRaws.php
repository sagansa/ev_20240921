<?php

namespace App\Filament\Resources\Panel\SpkluScrapeRawResource\Pages;

use App\Filament\Resources\Panel\SpkluScrapeRawResource;
use Filament\Resources\Pages\ListRecords;

class ListSpkluScrapeRaws extends ListRecords
{
    protected static string $resource = SpkluScrapeRawResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
