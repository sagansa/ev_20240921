<?php

namespace App\Services;

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
}