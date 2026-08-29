<?php

namespace Tests\Unit;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Services\VehicleNameSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleNameSplitterTest extends TestCase
{
    use RefreshDatabase;

    private VehicleNameSplitter $splitter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->splitter = new VehicleNameSplitter;
    }

    /** @param array{model:string,type:string,powertrain:string,confidence:string} $r */
    private function assertSplit(string $brand, string $typeModel, ?string $fuel, array $expected): void
    {
        $result = $this->splitter->split($brand, $typeModel, $fuel);

        $this->assertSame($expected['model'], $result['model'], "MODEL untuk [$brand / $typeModel]");
        $this->assertSame($expected['type'], $result['type'], "TYPE untuk [$brand / $typeModel]");
        if (isset($expected['powertrain'])) {
            $this->assertSame($expected['powertrain'], $result['powertrain'], "POWERTRAIN untuk [$brand / $typeModel]");
        }
    }

    public function test_model_family_stops_at_first_spec_token(): void
    {
        // Aturan user: "Agya 1.2 G AT" → MODEL cukup "Agya".
        $this->assertSplit('TOYOTA', 'Agya 1.2 G AT', 'G', [
            'model' => 'Agya',
            'type' => 'Agya 1.2 G AT',
            'powertrain' => 'ICE',
        ]);
    }

    public function test_marketing_prefix_belongs_to_type_not_model(): void
    {
        // Aturan user: "All New Avanza ..." → MODEL "Avanza", awalan jadi bagian TYPE.
        $this->assertSplit('TOYOTA', 'All New Avanza 1.5 G AT', 'G', [
            'model' => 'Avanza',
            'type' => 'All New Avanza 1.5 G AT',
            'powertrain' => 'ICE',
        ]);
    }

    public function test_fuel_bev_classifies_powertrain(): void
    {
        $this->assertSplit('HYUNDAI', 'Ioniq EV Prime', 'BEV', [
            'model' => 'Ioniq',
            'type' => 'Ioniq EV Prime',
            'powertrain' => 'BEV',
        ]);
    }

    public function test_hybrid_fuel_variants_map_to_hev(): void
    {
        $this->assertSplit('TOYOTA', 'Kijang Innova Zenix Hybrid', 'HYBRID', [
            'model' => 'Kijang Innova Zenix',
            'type' => 'Kijang Innova Zenix Hybrid',
            'powertrain' => 'HEV',
        ]);
    }

    public function test_catalog_model_wins_over_derivation(): void
    {
        $brand = BrandVehicle::create(['name' => 'AION']);
        ModelVehicle::create(['name' => 'AION UT', 'brand_vehicle_id' => $brand->id, 'powertrain' => 'BEV']);

        $this->assertSplit('AION', 'AION UT Premium', 'BEV', [
            'model' => 'AION UT',
            'type' => 'AION UT Premium',
            'powertrain' => 'BEV',
        ]);
    }

    public function test_leading_digit_model_keeps_family_tokens(): void
    {
        // "5 GT Ignite 1.5L" → keluarga "5 GT" (Ignite = trim, 1.5L = spec).
        $this->assertSplit('MORRIS GARAGE', '5 GT Ignite 1.5L', 'G', [
            'model' => '5 GT',
            'type' => '5 GT Ignite 1.5L',
            'powertrain' => 'ICE',
        ]);
    }

    public function test_brand_prefix_inside_string_is_stripped(): void
    {
        // Pola CONNECTING: "AION AION UT Standard" (brand dobel di dalam string).
        $this->assertSplit('AION', 'AION AION UT Standard', 'BEV', [
            'model' => 'AION UT',
            'type' => 'AION AION UT Standard',
            'powertrain' => 'BEV',
        ]);
    }

    public function test_missing_fuel_uses_dash_cc_as_bev_signal(): void
    {
        // Sheet 2026 tanpa FUEL; CC '-' menandakan listrik.
        $this->assertSplit('BMW', 'i7 xDrive60 Limousine RHD AT', null, [
            'model' => 'i7 xDrive60 Limousine',
            'powertrain' => 'BEV',
            'type' => 'i7 xDrive60 Limousine RHD AT',
        ]);
    }

    public function test_fuel_aliases_are_normalized(): void
    {
        $this->assertSplit('AUDI', 'A4 2.0 TFSI AT', 'G', [
            'model' => 'A4',
            'type' => 'A4 2.0 TFSI AT',
            'powertrain' => 'ICE',
        ]);

        $result = $this->splitter->split('BMW', 'CBU i4 eDrive35 Gran Coupe AT', 'BEV');
        $this->assertSame('BEV', $result['powertrain']);
        $this->assertSame('CBU', $result['model']); // CBU = spec token → keluarga kosong → token pertama
    }

    public function test_junk_rows_are_flagged(): void
    {
        $result = $this->splitter->split('TOTAL', 'TOTAL ', null);

        $this->assertSame('junk', $result['flag']);
    }
}
