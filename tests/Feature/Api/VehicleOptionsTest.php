<?php

namespace Tests\Feature\Api;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_diperkaya_sales_units_dan_powertrain(): void
    {
        $byd = BrandVehicle::create(['name' => 'BYD']);
        $honda = BrandVehicle::create(['name' => 'Honda']);
        $atto = ModelVehicle::create(['brand_vehicle_id' => $byd->id, 'name' => 'Atto 1', 'powertrain' => 'BEV']);
        $brio = ModelVehicle::create(['brand_vehicle_id' => $honda->id, 'name' => 'Brio Satya', 'powertrain' => 'ICE']);
        TypeVehicle::create(['model_vehicle_id' => $atto->id, 'name' => 'Standar', 'type_charger' => [], 'battery_capacity' => 51.8]);

        $import = SalesImport::create([
            'file_name' => 'x.xlsx', 'source' => 'gaikindo', 'year' => 2026,
            'period_start' => '2026-01-01', 'period_end' => '2026-07-31', 'status' => 'processed', 'meta' => [],
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import->id, 'raw_brand' => 'BYD', 'raw_model' => 'Atto 1 Dynamic',
            'brand_vehicle_id' => $byd->id, 'model_vehicle_id' => $atto->id, 'segment' => 'Sedan',
            'powertrain' => 'BEV', 'year' => 2026, 'month' => null, 'units' => 14300,
        ]);

        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');

        $res = $this->getJson('/api/v1/vehicles/options');
        $res->assertOk();

        $brands = collect($res->json('data.brands'))->keyBy('name');
        $this->assertEquals(14300, $brands['BYD']['sales_units']);
        $this->assertEquals(0, $brands['Honda']['sales_units']);

        $models = collect($res->json('data.models'))->keyBy('name');
        $this->assertEquals(14300, $models['Atto 1']['sales_units']);
        $this->assertSame('BEV', $models['Atto 1']['powertrain']);
        $this->assertSame('ICE', $models['Brio Satya']['powertrain']);
        $this->assertEquals(0, $models['Brio Satya']['sales_units']);

        // Type tetap terkirim (auto-fill baterai mobile).
        $types = collect($res->json('data.types'));
        $this->assertCount(1, $types);
        $this->assertEquals(51.8, (float) $types->first()['battery_capacity']);
    }

    public function test_options_tanpa_data_penjualan_tetap_berfungsi(): void
    {
        BrandVehicle::create(['name' => 'Wuling']);
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');

        $res = $this->getJson('/api/v1/vehicles/options');
        $res->assertOk();
        $this->assertEquals(0, $res->json('data.brands.0.sales_units'));
    }
}
