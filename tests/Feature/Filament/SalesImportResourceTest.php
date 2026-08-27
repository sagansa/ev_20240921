<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Panel\SalesImportResource;
use App\Filament\Resources\Panel\SalesImportResource\Pages\ListSalesImports;
use App\Models\SalesImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesImportResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_halaman_list_import_render(): void
    {
        SalesImport::create([
            'file_name' => 'dummy2026.xlsx',
            'source' => 'gaikindo',
            'year' => 2026,
            'status' => 'processed',
            'meta' => ['coverage' => 0.998, 'official_total' => 517742, 'warnings' => []],
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListSalesImports::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(SalesImport::all());
    }

    public function test_resource_terdaftar_dengan_label_navigasi(): void
    {
        $this->assertSame('Import Penjualan (GAIKINDO)', SalesImportResource::getNavigationLabel());
        $this->assertFalse(SalesImportResource::canCreate());
    }
}
