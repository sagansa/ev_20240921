<?php

namespace Tests\Feature\Services;

use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GeocodingService
    {
        return new GeocodingService();
    }

    public function test_resolve_region_parses_state_and_city(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Jl. A. Yani, Surabaya, Jawa Timur, Indonesia',
                'address' => [
                    'state' => 'Jawa Timur',
                    'city' => 'Surabaya',
                ],
            ]),
        ]);

        $result = $this->service()->resolveRegion(-7.25, 112.75);

        $this->assertSame('Jawa Timur', $result['province']);
        $this->assertSame('Surabaya', $result['city']);
    }

    public function test_resolve_region_falls_back_to_county_town(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Somewhere, Bogor, Jawa Barat, Indonesia',
                'address' => [
                    'state' => 'Jawa Barat',
                    'county' => 'Kabupaten Bogor',
                ],
            ]),
        ]);

        $result = $this->service()->resolveRegion(-6.6, 106.8);

        $this->assertSame('Jawa Barat', $result['province']);
        $this->assertSame('Kabupaten Bogor', $result['city']);
    }

    public function test_resolve_region_caches_30_days_and_avoids_repeated_hits(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Jakarta, Indonesia',
                'address' => ['state' => 'DKI Jakarta', 'city' => 'Jakarta Pusat'],
            ]),
        ]);

        $service = $this->service();
        $first = $service->resolveRegion(-6.18, 106.83);
        $second = $service->resolveRegion(-6.18, 106.83);

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_resolve_region_returns_nulls_on_failure(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([], 500),
        ]);

        $result = $this->service()->resolveRegion(-6.2, 106.8);

        $this->assertNull($result['province']);
        $this->assertNull($result['city']);
    }
}
