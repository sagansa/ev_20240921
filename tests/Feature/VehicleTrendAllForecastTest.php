<?php

namespace Tests\Feature;

use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tren bulanan year=all (pola musiman: rata-rata share bulan terhadap total
 * tahun yang sama) + prediksi bulan sisa tahun berjalan (seasonal historis,
 * fallback run-rate). Kontrak: respons lama tetap sah, field baru additive.
 */
class VehicleTrendAllForecastTest extends TestCase
{
    use RefreshDatabase;

    private int $import2024;

    private int $import2025;

    private int $import2026;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Revisi 2, poin 4: endpoint vehicle-market auth required.
        Sanctum::actingAs(\App\Models\User::factory()->create(), abilities: ['*']);

        $mk = fn (string $file, int $year) => SalesImport::create([
            'file_name' => $file, 'source' => 'gaikindo',
            'year' => $year, 'status' => 'processed', 'meta' => [],
        ])->id;
        $this->import2024 = $mk('gaikindo-2024.csv', 2024);
        $this->import2025 = $mk('gaikindo-2025.csv', 2025);
        $this->import2026 = $mk('gaikindo-2026.csv', 2026);

        // 2024 & 2025 PENUH: bulan m = 10m dan 90m (share bulan identik = m/78).
        foreach ([[$this->import2024, 2024, 10], [$this->import2025, 2025, 90]] as [$import, $year, $base]) {
            foreach (range(1, 12) as $m) {
                VehicleSalesStat::create([
                    'sales_import_id' => $import, 'year' => $year, 'month' => $m,
                    'raw_brand' => 'Daihatsu', 'raw_model' => 'Terano',
                    'powertrain' => 'BEV', 'units' => $base * $m,
                ]);
            }
        }

        // 2026 PARSIAL s.d. Juli: bulan m = 100m → YTD = 2800.
        foreach (range(1, 7) as $m) {
            VehicleSalesStat::create([
                'sales_import_id' => $this->import2026, 'year' => 2026, 'month' => $m,
                'raw_brand' => 'Daihatsu', 'raw_model' => 'Terano',
                'powertrain' => 'BEV', 'units' => 100 * $m,
            ]);
        }
    }

    public function test_trend_year_all_avg_share_rata_rata_lintas_tahun(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/trend?year=all');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertNull($data['year']);
        $this->assertCount(12, $data['months']);

        $months = collect($data['months'])->keyBy('month');

        // Jan: share 2024=10/780, 2025=90/7020, 2026=100/2800 → avg ≈ 0.0205.
        $this->assertSame(0.0205, $months[1]['avg_share']);
        $this->assertSame(3, $months[1]['years_counted']);
        // Avg units Jan = (10 + 90 + 100) / 3 ≈ 67.
        $this->assertSame(67, $months[1]['avg_units']);

        // Agu: hanya 2024 & 2025 (2026 belum berdata) → share = 80/780 ≈ 0.1026.
        $this->assertSame(0.1026, $months[8]['avg_share']);
        $this->assertSame(2, $months[8]['years_counted']);

        // Σ unit lintas tahun tetap diisi (backward compat): Jan BEV = 200.
        $this->assertSame(200, $months[1]['bev_units']);
        $this->assertSame(200, $months[1]['market_total']);
    }

    public function test_trend_tahun_berjalan_forecast_seasonal(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/trend?year=2026');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertSame(2026, $data['year']);

        $forecast = $data['forecast'];
        $this->assertNotNull($forecast);
        $this->assertSame('seasonal', $forecast['method']);
        $this->assertSame(7, $forecast['last_data_month']);
        // w_m = m/78; Σw(Jan–Jul) = 28/78; projected = 2800 / (28/78) = 7800.
        $this->assertSame(7800, $forecast['projected_total']);
        // forecast_m = round(7800 × m/78) = 100m.
        $fc = collect($forecast['months'])->keyBy('month');
        $this->assertSame(800, $fc[8]['units']);
        $this->assertSame(900, $fc[9]['units']);
        $this->assertSame(1200, $fc[12]['units']);
        $this->assertCount(5, $forecast['months']); // Agu–Des

        // Bulan sisa TIDAK muncul di months aktual.
        $this->assertNull(collect($data['months'])->firstWhere('month', 8));
    }

    public function test_trend_tahun_penuh_tanpa_forecast(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/trend?year=2025');

        $res->assertOk();
        $this->assertNull($res->json('data.forecast'));
        $this->assertCount(12, $res->json('data.months'));
    }

    public function test_trend_forecast_fallback_runrate_tanpa_histori_penuh(): void
    {
        // Dataset hanya 2026 parsial (hapus tahun penuh) → w_m tak ada → runrate.
        VehicleSalesStat::whereIn('sales_import_id', [$this->import2024, $this->import2025])->delete();
        Cache::flush();

        $res = $this->getJson('/api/v1/vehicle-market/trend?year=2026');

        $res->assertOk();
        $forecast = $res->json('data.forecast');
        $this->assertSame('runrate', $forecast['method']);
        // projected = round(2800 / 7 × 12) = 4800; tiap bulan = round(4800/12) = 400.
        $this->assertSame(4800, $forecast['projected_total']);
        $this->assertSame(400, $forecast['months'][0]['units']);
        $this->assertCount(5, $forecast['months']);
    }

    public function test_trend_year_tidak_dikenal_fallback_tahun_terbaru(): void
    {
        $this->getJson('/api/v1/vehicle-market/trend?year=abcd')
            ->assertOk()->assertJsonPath('data.year', 2026);
    }
}
