<?php

namespace App\Services;

use App\Models\City;
use App\Models\Province;

/**
 * Lazy-populate tabel wilayah (provinces/cities) berdasarkan nama hasil
 * reverse-geocode. Tabel ini saat ini kosong; kita isi sesuai kebutuhan saat
 * user membuat lokasi custom — tidak perlu seed massal.
 */
class RegionResolver
{
    /**
     * Resolve nama provinsi → id. Lazily create bila belum ada.
     */
    public function resolveProvince(?string $name): ?int
    {
        $name = $this->clean($name);
        if ($name === null) {
            return null;
        }

        return Province::firstOrCreate(['name' => $name])->id;
    }

    /**
     * Resolve nama kota/kabupaten → id, di-scope ke provinsi bila diketahui.
     * Lazily create bila belum ada.
     */
    public function resolveCity(?string $name, ?int $provinceId): ?int
    {
        $name = $this->clean($name);
        // cities.province_id NOT NULL → tanpa provinsi tidak bisa membuat record
        // valid. City_name tetap tersimpan denormalized utk display.
        if ($name === null || $provinceId === null) {
            return null;
        }

        $query = City::where('name', $name);
        if ($provinceId !== null) {
            $query->where('province_id', $provinceId);
        }

        $city = $query->first();
        if ($city !== null) {
            return $city->id;
        }

        return City::create([
            'name' => $name,
            'province_id' => $provinceId,
        ])->id;
    }

    private function clean(?string $value): ?string
    {
        $trimmed = $value === null ? null : trim($value);
        return ($trimmed === null || $trimmed === '') ? null : $trimmed;
    }
}
