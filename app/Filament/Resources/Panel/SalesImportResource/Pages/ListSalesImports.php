<?php

namespace App\Filament\Resources\Panel\SalesImportResource\Pages;

use App\Filament\Resources\Panel\SalesImportResource;
use Filament\Resources\Pages\ListRecords;

class ListSalesImports extends ListRecords
{
    protected static string $resource = SalesImportResource::class;

    protected function getHeaderActions(): array
    {
        // Import penjualan CSV kini hanya lewat "Preview Impor Penjualan"
        // (analisis dulu, impor hanya bila bersih). Format xlsx lama
        // dinonaktifkan.
        return [];
    }
}

