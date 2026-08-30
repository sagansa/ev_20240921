<?php

namespace Tests\Feature;

use App\Filament\Pages\VehicleHierarchyExplorer;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\User;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleHierarchyExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BrandVehicle $wuling;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->wuling = BrandVehicle::create(['name' => 'Wuling']);
        $air = ModelVehicle::create([
            'name' => 'Air EV', 'brand_vehicle_id' => $this->wuling->id,
            'powertrain' => 'BEV', 'category' => 'City Car',
        ]);
        $cortez = ModelVehicle::create([
            'name' => 'Cortez', 'brand_vehicle_id' => $this->wuling->id,
            'powertrain' => 'BEV', 'category' => null, // tanpa kategori = issue
        ]);
        TypeVehicle::create(['name' => 'Air EV Standard', 'model_vehicle_id' => $air->id, 'type_charger' => []]);
        TypeVehicle::create(['name' => 'Cortez 1.5', 'model_vehicle_id' => $cortez->id, 'type_charger' => []]);

        $import = SalesImport::create([
            'file_name' => '2025.xlsx', 'source' => 'gaikindo', 'year' => 2025, 'status' => 'processed',
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import->id, 'raw_brand' => 'WULING', 'raw_model' => 'Air EV',
            'brand_vehicle_id' => $this->wuling->id, 'model_vehicle_id' => $air->id,
            'powertrain' => 'BEV', 'year' => 2025, 'month' => null, 'units' => 8000,
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import->id, 'raw_brand' => 'Mystery', 'raw_model' => 'Ghost',
            'powertrain' => 'BEV', 'year' => 2025, 'month' => null, 'units' => 500,
        ]);
    }

    public function test_halaman_render_brand_dan_indikator_kesehatan(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/vehicle-hierarchy-explorer')
            ->assertSuccessful()
            ->assertSee('Wuling')
            ->assertSee('Model tanpa kategori')
            ->assertSee('Stats tak ter-link')
            ->assertSee('Mystery');
    }

    public function test_buka_brand_menampilkan_model_dan_kategorinya(): void
    {
        Livewire::actingAs($this->admin)
            ->test(VehicleHierarchyExplorer::class)
            ->call('toggleBrand', $this->wuling->id)
            ->assertSee('Air EV')
            ->assertSee('City Car')
            ->assertSee('8,000')
            ->assertSee('tanpa kategori');
    }

    public function test_buka_model_menampilkan_type_dan_unitnya(): void
    {
        $air = ModelVehicle::where('name', 'Air EV')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(VehicleHierarchyExplorer::class, ['year' => 2025])
            ->call('toggleBrand', $this->wuling->id)
            ->call('toggleModel', $air->id)
            ->assertSee('Air EV Standard');
    }

    public function test_filter_kategori_menyembunyikan_model_lain(): void
    {
        Livewire::actingAs($this->admin)
            ->test(VehicleHierarchyExplorer::class, ['year' => 2025, 'category' => 'City Car'])
            ->call('toggleBrand', $this->wuling->id)
            ->assertSee('Air EV')
            ->assertDontSee('Cortez');
    }

    public function test_expand_all_membuka_semua_node(): void
    {
        Livewire::actingAs($this->admin)
            ->test(VehicleHierarchyExplorer::class, ['year' => 2025])
            ->call('expandAll')
            ->assertSee('Air EV')
            ->assertSee('Cortez')
            ->assertSee('Air EV Standard');
    }
}
