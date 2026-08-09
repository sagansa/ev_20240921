<?php

namespace Tests\Feature\Api;

use App\Models\Charger;
use App\Models\ChargerLocation;
use App\Models\CurrentCharger;
use App\Models\PowerCharger;
use App\Models\TypeCharger;

class ChargerLocationChargersTest extends ApiTestCase
{
    /**
     * Kontrak mobile: GET /api/v1/charging-locations/{id} (detail) harus
     * menyertakan relasi `chargers` lengkap dgn current/type/power charger —
     * ini dipakai form sesi utk dropdown AC/DC — kW saat charger_location
     * terpilih (langsung memanggil detailnya, tanpa endpoint terpisah).
     */
    public function test_charger_location_detail_includes_chargers_with_relations(): void
    {
        $chargerLocation = ChargerLocation::factory()->create();
        $currentCharger = CurrentCharger::factory()->create(['name' => 'AC']);
        $typeCharger = TypeCharger::factory()->create(['name' => 'Type 2']);
        $powerCharger = PowerCharger::factory()->create(['name' => '7kW']);

        $charger = Charger::factory()->create([
            'charger_location_id' => $chargerLocation->id,
            'current_charger_id' => $currentCharger->id,
            'type_charger_id' => $typeCharger->id,
            'power_charger_id' => $powerCharger->id,
        ]);

        $response = $this->getJson(
            '/api/v1/charging-locations/'.$chargerLocation->id
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $chargerLocation->id)
            ->assertJsonPath('data.chargers.0.id', $charger->id)
            ->assertJsonPath('data.chargers.0.current_charger.name', 'AC')
            ->assertJsonPath('data.chargers.0.type_charger.name', 'Type 2')
            ->assertJsonPath('data.chargers.0.power_charger.name', '7kW');
    }

    public function test_charger_location_detail_returns_empty_chargers_when_none(): void
    {
        $chargerLocation = ChargerLocation::factory()->create();

        $response = $this->getJson(
            '/api/v1/charging-locations/'.$chargerLocation->id
        );

        $response->assertOk();
        $this->assertCount(0, $response->json('data.chargers'));
    }
}
