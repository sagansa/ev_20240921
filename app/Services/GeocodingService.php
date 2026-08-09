<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocode an address to get its coordinates
     *
     * @param string $address
     * @return array|null
     */
    public function geocodeAddress(string $address): ?array
    {
        // Using OpenStreetMap Nominatim API which is free to use with proper attribution
        // Please review https://operations.osmfoundation.org/policies/nominatim/ for usage guidelines
        $response = Http::withHeaders([
            'User-Agent' => 'Sagansa EV/1.0 (contact@sagansaev.com)' // Required by Nominatim
        ])->get("https://nominatim.openstreetmap.org/search", [
            'q' => $address,
            'format' => 'json',
            'limit' => 1
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (!empty($data) && is_array($data) && isset($data[0])) {
                return [
                    'latitude' => (float) $data[0]['lat'],
                    'longitude' => (float) $data[0]['lon'],
                    'formatted_address' => $data[0]['display_name'],
                    'place_id' => $data[0]['place_id'],
                ];
            }
        }

        return null;
    }

    /**
     * Reverse geocode coordinates to get an address
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        // Using OpenStreetMap Nominatim API which is free to use with proper attribution
        // Please review https://operations.osmfoundation.org/policies/nominatim/ for usage guidelines
        $response = Http::withHeaders([
            'User-Agent' => 'Sagansa EV/1.0 (contact@sagansaev.com)' // Required by Nominatim
        ])->get("https://nominatim.openstreetmap.org/reverse", [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['display_name'])) {
                return [
                    'formatted_address' => $data['display_name'],
                    'address_components' => $data, // Nominatim returns structured data
                    'place_id' => $data['place_id'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Reverse geocode → hanya butuh nama provinsi & kota/kabupaten untuk mengisi
     * charger_locations (province_id/city_id FK + province_name/city_name).
     *
     * Nominatim (OpenStreetMap) gratis tanpa API key. Honor usage policy:
     *  - User-Agent yang mengidentifikasi aplikasi.
     *  - Cache per (lat,lng) rounded 4 desimal selama 30 hari → hindari hit
     *    berulang (rate limit 1 req/detik).
     *
     * @param float $lat
     * @param float $lng
     * @return array{province: string|null, city: string|null, formatted_address: string|null}
     */
    public function resolveRegion(float $lat, float $lng): array
    {
        $roundedLat = round($lat, 4);
        $roundedLng = round($lng, 4);
        $cacheKey = 'geocoding.region.'.(string) $roundedLat.','.(string) $roundedLng;

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lng) {
            $response = Http::withHeaders([
                'User-Agent' => 'Sagansa EV/1.0 (contact@sagansaev.com)',
                'Accept-Language' => 'id',
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if (! $response->successful()) {
                return ['province' => null, 'city' => null, 'formatted_address' => null];
            }

            $data = $response->json();
            $address = $data['address'] ?? [];

            // Province: address.state; fallback ke region.
            $province = $address['state'] ?? $address['region'] ?? null;
            // City/kabupaten: prioritas city → county → town → municipality.
            $city = $address['city']
                ?? $address['county']
                ?? $address['town']
                ?? $address['municipality']
                ?? null;

            return [
                'province' => is_string($province) ? trim($province) ?: null : null,
                'city' => is_string($city) ? trim($city) ?: null : null,
                'formatted_address' => isset($data['display_name']) ? $data['display_name'] : null,
            ];
        });
    }
}