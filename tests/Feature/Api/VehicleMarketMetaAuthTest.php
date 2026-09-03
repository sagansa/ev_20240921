<?php

namespace Tests\Feature\Api;

use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisi 2 (poin 4 & 5): seluruh endpoint vehicle-market wajib login dan
 * endpoint meta memberi klien penanda murah untuk cek data baru
 * (last_import_at + data_version) tanpa fetch payload penuh.
 */
class VehicleMarketMetaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_endpoint_menolak_tamu_dengan_401(): void
    {
        foreach (['summary', 'trend', 'top', 'catalog', 'composition', 'meta'] as $ep) {
            $this->getJson("/api/v1/vehicle-market/{$ep}")->assertUnauthorized();
        }
        $this->getJson('/api/v1/vehicle-market/model-history?brand=BYD&model=X')
            ->assertUnauthorized();
    }

    public function test_meta_mengembalikan_penanda_data_terbaru(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');

        $import = SalesImport::create([
            'file_name' => 'gaikindo-2026.csv', 'source' => 'gaikindo',
            'year' => 2026, 'status' => 'processed', 'meta' => [],
        ]);
        foreach ([1 => 500, 2 => 600, 3 => 700] as $m => $units) {
            VehicleSalesStat::create([
                'sales_import_id' => $import->id, 'raw_brand' => 'BYD', 'raw_model' => 'Seal',
                'powertrain' => 'BEV', 'year' => 2026, 'month' => $m, 'units' => $units,
            ]);
        }

        $res = $this->getJson('/api/v1/vehicle-market/meta');
        $res->assertOk()->assertJsonPath('success', true);

        $this->assertSame($import->id, $res->json('data.latest_import_id'));
        $this->assertSame(2026, $res->json('data.latest_year'));
        $this->assertSame(3, $res->json('data.latest_month'));
        $this->assertNotNull($res->json('data.last_import_at'));
        $this->assertIsInt($res->json('data.data_version'));
    }

    public function test_flush_menaikkan_data_version_agar_klien_sinkron_ulang(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(), 'sanctum');

        $before = (int) $this->getJson('/api/v1/vehicle-market/meta')->json('data.data_version');

        app(\App\Services\VehicleMarketService::class)->flush();

        $after = (int) $this->getJson('/api/v1/vehicle-market/meta')->json('data.data_version');
        $this->assertGreaterThan($before, $after);
    }
}
