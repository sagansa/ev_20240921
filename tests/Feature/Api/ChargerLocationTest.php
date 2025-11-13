<?php

namespace Tests\Feature\Api;

use App\Models\ChargerLocation;
use App\Models\City;
use App\Models\District;
use App\Models\PostalCode;
use App\Models\Provider;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;

class ChargerLocationTest extends ApiTestCase
{
    public function test_it_gets_charger_locations_list(): void
    {
        $chargerLocations = ChargerLocation::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('api.charger-locations.index'));

        $response->assertOk()->assertSee($chargerLocations[0]->name);
    }

    public function test_it_stores_the_charger_location(): void
    {
        $data = ChargerLocation::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.charger-locations.store'), $data);

        unset($data['address'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['name'], $data['location']);

        $this->assertDatabaseHas('charger_locations', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    public function test_it_updates_the_charger_location(): void
    {
        $chargerLocation = ChargerLocation::factory()->create();

        $provider = Provider::factory()->create();
        $province = Province::factory()->create();
        $city = City::factory()->create();
        $district = District::factory()->create();
        $subdistrict = Subdistrict::factory()->create();
        $postalCode = PostalCode::factory()->create();
        $user = User::factory()->create();

        $data = [
            'image' => fake()->optional(),
            'name' => fake()->name(),
            'location_on' => fake()->numberBetween(1, 2),
            'status' => fake()->numberBetween(1, 2),
            'description' => fake()->sentence(15),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'parking' => fake()->boolean(),
            'provider_id' => $provider->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'subdistrict_id' => $subdistrict->id,
            'postal_code_id' => $postalCode->id,
            'user_id' => $user->id,
        ];

        $response = $this->putJson(
            route('api.charger-locations.update', $chargerLocation),
            $data
        );

        unset($data['address'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['name'], $data['location']);

        $data['id'] = $chargerLocation->id;

        $this->assertDatabaseHas('charger_locations', $data);

        $response->assertStatus(200)->assertJsonFragment($data);
    }

    public function test_it_deletes_the_charger_location(): void
    {
        $chargerLocation = ChargerLocation::factory()->create();

        $response = $this->deleteJson(
            route('api.charger-locations.destroy', $chargerLocation)
        );

        $this->assertSoftDeleted($chargerLocation);

        $response->assertNoContent();
    }
}

