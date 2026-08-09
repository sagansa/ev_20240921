<?php

namespace Tests\Feature\Api;

use App\Models\ChargerLocation;
use App\Models\City;
use App\Models\Provider;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class UserChargerLocationControllerTest extends ApiTestCase
{
    public function test_it_creates_custom_location_with_region_and_home_flag(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Jl. Sudirman, Jakarta Selatan, DKI Jakarta, Indonesia',
                'address' => [
                    'state' => 'DKI Jakarta',
                    'city' => 'Jakarta Selatan',
                ],
            ]),
        ]);

        $provider = Provider::factory()->create(['name' => 'PLN']);

        $response = $this->postJson('/api/v1/my/charging-locations', [
            'name' => 'Home Charging Wallbox',
            'address' => 'Jl. Sudirman No. 10',
            'latitude' => -6.2251234,
            'longitude' => 106.8015678,
            'provider_id' => $provider->id,
            'is_home_charging' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Home Charging Wallbox',
                    'is_home_charging' => true,
                    'provider_id' => $provider->id,
                    'provider_name' => 'PLN',
                    'province_name' => 'DKI Jakarta',
                    'city_name' => 'Jakarta Selatan',
                ],
            ]);

        // Province & city lazy-created, FK terisi.
        $province = Province::where('name', 'DKI Jakarta')->first();
        $this->assertNotNull($province);
        $city = City::where('name', 'Jakarta Selatan')->first();
        $this->assertNotNull($city);
        $this->assertSame($province->id, $city->province_id);

        $this->assertDatabaseHas('charger_locations', [
            'user_id' => $this->authUser->id,
            'name' => 'Home Charging Wallbox',
            'location_on' => 2,
            'provider_id' => $provider->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => 'DKI Jakarta',
            'city_name' => 'Jakarta Selatan',
        ]);
    }

    public function test_it_requires_a_provider(): void
    {
        // provider_id wajib — permintaan tanpa provider_id ditolak (422).
        // ApiTestCase memakai withoutExceptionHandling(), kembalikan utk test
        // validasi agar ValidationException di-render jadi 422 JSON response.
        $this->withExceptionHandling();

        $response = $this->postJson('/api/v1/my/charging-locations', [
            'name' => 'Kantor Cabang',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider_id']);
    }

    public function test_it_creates_custom_location_with_provider_when_geocoding_fails(): void
    {
        // Reverse-geocode gagal → FK region null (valid krn sudah nullable),
        // tapi provider_id tetap wajib & terisi. non-home → location_on 1.
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 500)]);

        $provider = Provider::factory()->create(['name' => 'ALVA']);

        $response = $this->postJson('/api/v1/my/charging-locations', [
            'name' => 'Kantor Cabang',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'provider_id' => $provider->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Kantor Cabang',
                    'is_home_charging' => false,
                    'provider_id' => $provider->id,
                    'provider_name' => 'ALVA',
                ],
            ]);

        $this->assertDatabaseHas('charger_locations', [
            'user_id' => $this->authUser->id,
            'name' => 'Kantor Cabang',
            'location_on' => 1,
            'provider_id' => $provider->id,
            'province_id' => null,
            'city_id' => null,
        ]);
    }

    public function test_it_lists_only_authenticated_users_locations(): void
    {
        $other = User::factory()->create();
        ChargerLocation::create([
            'user_id' => $this->authUser->id,
            'name' => 'Rumah Saya',
            'location_on' => 2,
            'status' => 1,
            'data_source' => 'user_custom',
        ]);
        ChargerLocation::create([
            'user_id' => $other->id,
            'name' => 'Rumah Orang Lain',
            'location_on' => 2,
            'status' => 1,
            'data_source' => 'user_custom',
        ]);

        $response = $this->getJson('/api/v1/my/charging-locations');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Rumah Saya'])
            ->assertJsonMissing(['name' => 'Rumah Orang Lain']);
    }

    public function test_it_prevents_updating_another_users_location(): void
    {
        $other = User::factory()->create();
        $location = ChargerLocation::create([
            'user_id' => $other->id,
            'name' => 'Lokasi Milik B',
            'location_on' => 1,
            'status' => 1,
            'data_source' => 'user_custom',
        ]);

        $response = $this->putJson("/api/v1/my/charging-locations/{$location->id}", [
            'name' => 'Diubah',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_it_updates_own_location(): void
    {
        $location = ChargerLocation::create([
            'user_id' => $this->authUser->id,
            'name' => 'Rumah Saya',
            'location_on' => 1,
            'status' => 1,
            'data_source' => 'user_custom',
        ]);

        $response = $this->putJson("/api/v1/my/charging-locations/{$location->id}", [
            'name' => 'Rumah (Baru)',
            'is_home_charging' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Rumah (Baru)',
                    'is_home_charging' => true,
                ],
            ]);

        $this->assertDatabaseHas('charger_locations', [
            'id' => $location->id,
            'name' => 'Rumah (Baru)',
            'location_on' => 2,
        ]);
    }

    public function test_it_prevents_deleting_another_users_location(): void
    {
        $other = User::factory()->create();
        $location = ChargerLocation::create([
            'user_id' => $other->id,
            'name' => 'Lokasi Milik B',
            'location_on' => 1,
            'status' => 1,
            'data_source' => 'user_custom',
        ]);

        $this->deleteJson("/api/v1/my/charging-locations/{$location->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('charger_locations', ['id' => $location->id]);
    }
}
