<?php

namespace App\Support;

/**
 * Taksonomi kategori kendaraan (level MODEL) untuk katalog & Pasar EV.
 * Tujuan utama: memisahkan truk & bus dari mobil penumpang.
 *
 * size_class hanya berlaku untuk kategori ber-ukuran (SIZABLE) — Small/
 * Medium/Large didefinisikan konvensi pasar (panjang/kelas wheelbase),
 * bukan dari data GAIKINDO. Grup (Penumpang/Komersial) tidak disimpan di
 * DB — cukup map ini untuk pemisahan di UI/filter.
 */
final class VehicleCategories
{
    /** Semua kategori valid (urutan tampil). */
    public const CATEGORIES = [
        'City Car',
        'Hatchback',
        'Sedan',
        'MPV',
        'Crossover',
        'SUV',
        'Off-Road',
        'Sport',
        'Van/Minibus',
        'Pickup',
        'Truk Ringan',
        'Truk Berat',
        'Bus',
        'Lainnya',
    ];

    /** Kategori yang memiliki size_class. */
    public const SIZABLE = ['MPV', 'Sedan', 'SUV', 'Hatchback'];

    public const SIZES = ['Small', 'Medium', 'Large'];

    /** Kategori termasuk mobil penumpang (bukan komersial). */
    public const PASSENGER = [
        'City Car', 'Hatchback', 'Sedan', 'MPV', 'Crossover',
        'SUV', 'Off-Road', 'Sport',
    ];

    /** Kategori komersial (truk, bus, van kargo, pickup). */
    public const COMMERCIAL = [
        'Van/Minibus', 'Pickup', 'Truk Ringan', 'Truk Berat', 'Bus',
    ];

    /** @return array{group: ?string} grup kategori (null bila tak dikenal). */
    public static function groupOf(?string $category): ?string
    {
        if (in_array($category, self::PASSENGER, true)) {
            return 'Penumpang';
        }

        if (in_array($category, self::COMMERCIAL, true)) {
            return 'Komersial';
        }

        return null;
    }

    /** Normalisasi nilai kategori dari CSV; null bila tidak valid. */
    public static function normalizeCategory(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (self::CATEGORIES as $category) {
            if (strcasecmp($value, $category) === 0) {
                return $category;
            }
        }

        return null;
    }

    /** Normalisasi size_class; null bila tidak valid. */
    public static function normalizeSize(?string $value): ?string
    {
        $value = ucfirst(strtolower(trim((string) $value)));

        return in_array($value, self::SIZES, true) ? $value : null;
    }
}
