<?php

namespace Tests\Feature\Services;

use App\Models\ChargingStation;
use App\Models\PlnChargerLocation;
use App\Services\CanonicalStationHydrateService;
use App\Services\SpkluCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpkluCsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'Chargebox ID', 'ID Spklu', 'Merek', 'Nama Chargebox', 'Daya Chargebox', 'Kategori',
        'Is Active', 'Tgl Integrasi', 'Nama Spklu', 'Alamat Spklu', 'Latitude', 'Longitude',
        'Propinsi', 'Cluster Pulau', 'Kepemilikan Mesin', 'Kategori Tol', 'Kategori2',
        'Kategori3', 'Cluster Peruntukan',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // provinceMap memakai id eksplisit 1..34 (FK pln_charger_locations.province_id).
        $names = [
            9 => 'Kep. Bangka Belitung',
            10 => 'Kepulauan Riau',
            12 => 'Jawa Barat',
            16 => 'Banten',
        ];
        foreach ($names as $id => $name) {
            DB::table('provinces')->insert(['id' => $id, 'name' => $name]);
        }
    }

    public function test_import_merges_by_pln_id_and_keeps_location_id_stable(): void
    {
        $service = new SpkluCsvImportService;

        $path1 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL', chargebox: 'CB-0001'),
            $this->row(plnId: 2, name: 'SPKLU PLN BANDUNG', chargebox: 'CB-0002'),
        ]);
        $service->import($path1, replaceExisting: false);

        $this->assertDatabaseCount('pln_charger_locations', 2);
        $this->assertDatabaseCount('pln_charger_location_details', 2);

        $idsByPln = PlnChargerLocation::pluck('id', 'pln_id');

        $path2 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL RENOVASI', chargebox: 'CB-0001'),
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL RENOVASI', chargebox: 'CB-0001-B'),
            $this->row(plnId: 2, name: 'SPKLU PLN BANDUNG', chargebox: 'CB-0002'),
            $this->row(plnId: 3, name: 'SPKLU PLN BOGOR', chargebox: 'CB-0003'),
        ]);
        $summary = $service->import($path2, replaceExisting: false);

        $this->assertSame(1, $summary['inserted_locations']);
        $this->assertDatabaseCount('pln_charger_locations', 3);
        $this->assertDatabaseCount('pln_charger_location_details', 4);

        $idsAfter = PlnChargerLocation::pluck('id', 'pln_id');
        $this->assertSame($idsByPln[1], $idsAfter[1], 'id lokasi pln_id=1 harus stabil saat merge');
        $this->assertSame($idsByPln[2], $idsAfter[2], 'id lokasi pln_id=2 harus stabil saat merge');
        $this->assertNotNull($idsAfter[3]);
        $this->assertSame('SPKLU PLN SENTUL RENOVASI', PlnChargerLocation::find($idsAfter[1])->name);

        // Detail di-replace per lokasi (tidak terduplikasi di merge).
        $details1 = DB::table('pln_charger_location_details')
            ->where('pln_charger_location_id', $idsAfter[1])
            ->pluck('chargebox_id')
            ->all();
        $this->assertEqualsCanonicalizing(['CB-0001', 'CB-0001-B'], $details1);
    }

    public function test_import_maps_province_aliases_babel_and_riau_kepri(): void
    {
        $service = new SpkluCsvImportService;

        $path = $this->writeCsv([
            $this->row(plnId: 11, name: 'SPKLU BANGKA', province: 'BABEL'),
            $this->row(plnId: 12, name: 'SPKLU TANJUNGPINANG', province: 'RIAU DAN KEPRI'),
        ]);
        $service->import($path, replaceExisting: false);

        $this->assertSame(9, PlnChargerLocation::where('pln_id', 11)->value('province_id'));
        $this->assertSame(10, PlnChargerLocation::where('pln_id', 12)->value('province_id'));
    }

    public function test_import_nullifies_coordinates_outside_indonesia_bounds(): void
    {
        $service = new SpkluCsvImportService;

        $path = $this->writeCsv([
            $this->row(plnId: 250, name: 'SPKLU PLN UP3 TERNATE', lat: '41260', lng: '41260'),
            $this->row(plnId: 251, name: 'SPKLU PLN JAKARTA', lat: '-6.20', lng: '106.80'),
        ]);
        $service->import($path, replaceExisting: false);

        $this->assertNull(PlnChargerLocation::where('pln_id', 250)->value('latitude'));
        $this->assertNull(PlnChargerLocation::where('pln_id', 250)->value('longitude'));
        $this->assertSame(-6.2, (float) PlnChargerLocation::where('pln_id', 251)->value('latitude'));
        $this->assertSame(106.8, (float) PlnChargerLocation::where('pln_id', 251)->value('longitude'));
    }

    public function test_import_prune_removes_stale_locations_and_details(): void
    {
        $service = new SpkluCsvImportService;

        $path1 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL', chargebox: 'CB-0001'),
            $this->row(plnId: 2, name: 'SPKLU PLN BANDUNG', chargebox: 'CB-0002'),
            $this->row(plnId: 3, name: 'SPKLU PLN BOGOR', chargebox: 'CB-0003'),
        ]);
        $service->import($path1, replaceExisting: false, prune: true);
        $this->assertDatabaseCount('pln_charger_locations', 3);

        $prunedId = PlnChargerLocation::where('pln_id', 3)->value('id');

        $path2 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL', chargebox: 'CB-0001'),
            $this->row(plnId: 2, name: 'SPKLU PLN BANDUNG', chargebox: 'CB-0002'),
        ]);
        $summary = $service->import($path2, replaceExisting: false, prune: true);

        $this->assertSame(1, $summary['pruned_locations']);
        $this->assertDatabaseCount('pln_charger_locations', 2);
        $this->assertDatabaseMissing('pln_charger_locations', ['id' => $prunedId]);
        $this->assertDatabaseMissing('pln_charger_location_details', ['pln_charger_location_id' => $prunedId]);
    }

    public function test_hydrate_prunes_stale_pln_stations_and_esdm_matches(): void
    {
        $service = new SpkluCsvImportService;
        $hydrator = app(CanonicalStationHydrateService::class);

        $path1 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL', chargebox: 'CB-0001'),
            $this->row(plnId: 2, name: 'SPKLU PLN BANDUNG', chargebox: 'CB-0002'),
        ]);
        $service->import($path1, replaceExisting: false, prune: true);
        $hydrator->hydrateFromPln();

        $this->assertSame(2, ChargingStation::where('source', 'pln')->count());

        $stale = ChargingStation::where('source', 'pln')->where('source_station_id', PlnChargerLocation::where('pln_id', 2)->value('id'))->firstOrFail();
        DB::table('pln_esdm_station_matches')->insert([
            'pln_station_id' => $stale->id,
            'esdm_station_id' => 999,
            'pln_source_station_id' => $stale->source_station_id,
            'esdm_source_station_id' => '999',
            'match_status' => 'approved',
            'match_method' => 'auto_geo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseCount('pln_esdm_station_matches', 1);

        // Periode berikutnya: lokasi pln_id=2 hilang dari CSV.
        $path2 = $this->writeCsv([
            $this->row(plnId: 1, name: 'SPKLU PLN SENTUL', chargebox: 'CB-0001'),
        ]);
        $service->import($path2, replaceExisting: false, prune: true);
        $stats = $hydrator->hydrateFromPln();

        $this->assertSame(1, $stats['pruned']);
        $this->assertSame(1, ChargingStation::where('source', 'pln')->count());
        $this->assertDatabaseMissing('charging_stations', ['id' => $stale->id]);
        $this->assertDatabaseCount('pln_esdm_station_matches', 0);

        // Stasiun yang tersisa tetap punya child charger utuh.
        $remaining = ChargingStation::where('source', 'pln')->firstOrFail();
        $this->assertSame(1, $remaining->chargers()->count());
    }

    public function test_hydrate_derives_type_charge_from_power_kw(): void
    {
        $this->seedChargingTypes();

        $service = new SpkluCsvImportService;
        $hydrator = app(CanonicalStationHydrateService::class);

        $path = $this->writeCsv([
            $this->row(plnId: 101, name: 'SPKLU SLOW', chargebox: 'CB-101', power: '7', kategori: 'ULTRA FAST CHARGING'),
            $this->row(plnId: 102, name: 'SPKLU MEDIUM', chargebox: 'CB-102', power: '22'),
            $this->row(plnId: 103, name: 'SPKLU FAST', chargebox: 'CB-103', power: '30'),
            $this->row(plnId: 104, name: 'SPKLU ULTRA 60', chargebox: 'CB-104', power: '60'),
            $this->row(plnId: 105, name: 'SPKLU ULTRA 180', chargebox: 'CB-105', power: '180'),
            $this->row(plnId: 106, name: 'SPKLU FALLBACK', chargebox: 'CB-106', power: '', kategori: 'ULTRA FAST CHARGING'),
        ]);
        $service->import($path, replaceExisting: false);

        $hydrator->hydrateFromPln();

        $this->assertSame('Slow Charging', $this->plnStationType(101));
        $this->assertSame('Medium Charging', $this->plnStationType(102));
        $this->assertSame('Fast Charging', $this->plnStationType(103));
        $this->assertSame('Ultra Fast Charging', $this->plnStationType(104));
        $this->assertSame('Ultra Fast Charging', $this->plnStationType(105));
        $this->assertSame('Ultra Fast Charging', $this->plnStationType(106), 'fallback ke label saat daya tidak valid');

        // Per-charger box ikut turunan power yang sama.
        $slowCharger = ChargingStation::where('source', 'pln')
            ->where('source_station_id', PlnChargerLocation::where('pln_id', 101)->value('id'))
            ->firstOrFail()
            ->chargers()
            ->firstOrFail();
        $this->assertSame('Slow Charging', $slowCharger->type_charge);
        $this->assertSame('≤7 kW AC', $slowCharger->watt);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function plnStationType(int $plnId): ?string
    {
        $locationId = PlnChargerLocation::where('pln_id', $plnId)->value('id');

        return ChargingStation::where('source', 'pln')
            ->where('source_station_id', $locationId)
            ->value('type_charge');
    }

    private function row(
        int $plnId,
        string $name,
        string $chargebox = 'CB-X',
        string $power = '50',
        string $kategori = 'FAST CHARGING',
        string $province = 'BANTEN',
        string $lat = '-6.20',
        string $lng = '106.80'
    ): array {
        return [
            'Chargebox ID' => $chargebox,
            'ID Spklu' => (string) $plnId,
            'Merek' => 'ABB',
            'Nama Chargebox' => 'ABB DC '.$chargebox,
            'Daya Chargebox' => $power,
            'Kategori' => $kategori,
            'Is Active' => 'Y',
            'Tgl Integrasi' => '1/1/2025',
            'Nama Spklu' => $name,
            'Alamat Spklu' => 'Jl. Test '.$name,
            'Latitude' => $lat,
            'Longitude' => $lng,
            'Propinsi' => $province,
            'Cluster Pulau' => 'JAWA',
            'Kepemilikan Mesin' => 'PLN',
            'Kategori Tol' => 'NON TOL',
            'Kategori2' => '',
            'Kategori3' => '',
            'Cluster Peruntukan' => '',
        ];
    }

    private function writeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'spklu').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $this->headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($this->headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);

        return $path;
    }

    private function seedChargingTypes(): void
    {
        foreach ([1 => 'FAST CHARGING', 2 => 'MEDIUM CHARGING', 3 => 'STANDARD CHARGING', 4 => 'ULTRA FAST CHARGING'] as $id => $name) {
            DB::table('charging_types')->insert(['id' => $id, 'name' => $name]);
        }
    }
}