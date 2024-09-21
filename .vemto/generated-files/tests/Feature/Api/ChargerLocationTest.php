<?php

use App\Models\User;
use App\Models\City;
use App\Models\Provider;
use App\Models\Province;
use App\Models\District;
use App\Models\PostalCode;
use App\Models\Subdistrict;
use Laravel\Sanctum\Sanctum;
use App\Models\ChargerLocation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets charger_locations list', function () {
    $chargerLocations = ChargerLocation::factory()
        ->count(5)
        ->create();

    $response = $this->get(route('api.charger-locations.index'));

    $response->assertOk()->assertSee($chargerLocations[0]->name);
});

test('it stores the charger_location', function () {
    $data = ChargerLocation::factory()
        ->make()
        ->toArray();

    $response = $this->postJson(route('api.charger-locations.store'), $data);

    unset($data['address']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);
    unset($data['name']);
    unset($data['location']);

    $this->assertDatabaseHas('charger_locations', $data);

    $response->assertStatus(201)->assertJsonFragment($data);
});

test('it updates the charger_location', function () {
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

    unset($data['address']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);
    unset($data['name']);
    unset($data['location']);

    $data['id'] = $chargerLocation->id;

    $this->assertDatabaseHas('charger_locations', $data);

    $response->assertStatus(200)->assertJsonFragment($data);
});

test('it deletes the charger_location', function () {
    $chargerLocation = ChargerLocation::factory()->create();

    $response = $this->deleteJson(
        route('api.charger-locations.destroy', $chargerLocation)
    );

    $this->assertSoftDeleted($chargerLocation);

    $response->assertNoContent();
});
