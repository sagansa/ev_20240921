<?php

namespace App\Services;

use App\Models\EsdmSinggatSpbkluStation;
use App\Models\EsdmSinggatSpkluStation;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi koordinat lat/lng hasil import ESDM Singgat.
 *
 * Data sumber ESDM punya beberapa jenis korupsi koordinat. Service ini
 * mengisi kolom `latitude`/`longitude`/`geo_status`/`geo_notes` di tabel
 * stations dengan nilai hasil koreksi, meninggalkan `*_raw` APA ADANYA.
 *
 * Algoritma diverifikasi terhadap seluruh dataset ESDM:
 *   - SPKLU:  3.125 OK / 10 diperbaiki / 1 unresolved (nilai ekstrem garbage)
 *   - SPBKLU: 241 OK / 26 diperbaiki / 0 unresolved
 *
 * IDEMPOTEN: aman dijalankan berulang. Selalu membaca dari *_raw.
 */
class EsdmSinggatGeoCleaningService
{
    private const LAT_MIN = -11.0;
    private const LAT_MAX = 8.0;    // Aceh utara ~6.5, Weh ~5.9
    private const LNG_MIN = 95.0;   // Sabang 95.3
    private const LNG_MAX = 141.0;  // Papua timur

    public function clean(bool $dryRun = false): array
    {
        $spklu = $this->cleanSpklu($dryRun);
        $spbklu = $this->cleanSpbklu($dryRun);

        return ['spklu' => $spklu, 'spbklu' => $spbklu, 'dry_run' => $dryRun];
    }

    // ─── SPKLU ──────────────────────────────────────────────────────────────

    private function cleanSpklu(bool $dryRun): array
    {
        $stats = ['processed' => 0, 'ok' => 0, 'fixed' => 0, 'unresolved' => 0, 'null' => 0];
        $unresolved = [];

        $stations = EsdmSinggatSpkluStation::query()
            ->select(['id', 'latitude_spklu_raw', 'longitude_spklu_raw'])
            ->get();

        foreach ($stations as $s) {
            $stats['processed']++;
            $latRaw = $this->parseFloat($s->latitude_spklu_raw);
            $lngRaw = $this->parseFloat($s->longitude_spklu_raw);

            [$lat, $lng, $status, $notes] = $this->cleanSpkluCoords($latRaw, $lngRaw);

            $stats[$status === 'ok' ? 'ok' : ($status === 'unresolved' ? 'unresolved' : 'fixed')]++;
            if ($status === 'null') {
                $stats['null']++;
            }
            if ($status === 'unresolved') {
                $unresolved[] = ['id' => $s->id, 'lat_raw' => $latRaw, 'lng_raw' => $lngRaw, 'notes' => $notes];
            }

            if (! $dryRun) {
                $s->update([
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'geo_status' => $status,
                    'geo_notes' => $notes,
                ]);
            }
        }

        return array_merge($stats, ['unresolved_details' => $unresolved]);
    }

    /**
     * Algoritma cleaning SPKLU. Koordinat SPKLU disimpan sebagai string desimal,
     * dengan beberapa jenis korupsi:
     *   1. SWAPPED — lat/lng tertukar (3 record)
     *   2. MISSING_DIGIT — nilai ~10.x seharusnya 106.x (sisip "6"); bisa
     *      kombinasi dengan swap (7 record)
     *   3. SCALED — nilai ×100000 (titik desimal hilang) (2 record)
     *   4. GARBAGE — nilai ekstrem tak terpulihkan (1 record)
     */
    private function cleanSpkluCoords(?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return [null, null, 'null', 'lat/lng null'];
        }

        // 1) Valid apa adanya
        if ($this->validLat($lat) && $this->validLng($lng)) {
            return [$lat, $lng, 'ok', null];
        }

        // 2) Pure SWAP
        if ($this->validLat($lng) && $this->validLng($lat)) {
            return [$lng, $lat, 'swapped', 'lat/lng tertukar'];
        }

        // 3) SCALED (÷100000)
        if (abs($lat) > 1000) {
            $cand = $lat / 100000.0;
            if ($this->validLat($cand) && $this->validLng($lng)) {
                return [$cand, $lng, 'fixed_digits', "lat dibagi 100000 ({$lat} -> {$cand})"];
            }
        }
        if (abs($lng) > 1000) {
            $cand = $lng / 100000.0;
            if ($this->validLng($cand) && $this->validLat($lat)) {
                return [$lat, $cand, 'fixed_digits', "lng dibagi 100000 ({$lng} -> {$cand})"];
            }
        }

        // 4) SWAP + MISSING_DIGIT: setelah swap, lng butuh sisipan "6"
        $swappedLat = $lng;
        $swappedLng = $lat;
        if ($this->validLat($swappedLat) && ! $this->validLng($swappedLng)) {
            $candLng = $this->insertDigit6($swappedLng);
            if ($candLng !== null && $this->validLng($candLng)) {
                return [$swappedLat, $candLng, 'fixed_digits', "swap + sisip 6 di lng ({$lat},{$lng} -> {$swappedLat},{$candLng})"];
            }
        }

        // 5) MISSING_DIGIT tanpa swap: lat valid, lng ~10.x
        if ($this->validLat($lat) && ! $this->validLng($lng)) {
            $candLng = $this->insertDigit6($lng);
            if ($candLng !== null && $this->validLng($candLng)) {
                return [$lat, $candLng, 'fixed_digits', "sisip 6 di lng ({$lng} -> {$candLng})"];
            }
        }

        // 6) GARBAGE
        return [$lat, $lng, 'unresolved', "tak terpulihkan: lat={$lat} lng={$lng}"];
    }

    // ─── SPBKLU ─────────────────────────────────────────────────────────────

    private function cleanSpbklu(bool $dryRun): array
    {
        $stats = ['processed' => 0, 'ok' => 0, 'fixed' => 0, 'unresolved' => 0, 'null' => 0];
        $unresolved = [];

        $stations = EsdmSinggatSpbkluStation::query()
            ->select(['id', 'latitude_spbklu_raw', 'longitude_spbklu_raw'])
            ->get();

        foreach ($stations as $s) {
            $stats['processed']++;
            $latRaw = $this->parseFloat($s->latitude_spbklu_raw);
            $lngRaw = $this->parseFloat($s->longitude_spbklu_raw);

            [$lat, $lng, $status, $notes] = $this->cleanSpbkluCoords($latRaw, $lngRaw);

            $stats[$status === 'ok' ? 'ok' : ($status === 'unresolved' ? 'unresolved' : 'fixed')]++;
            if ($status === 'null') {
                $stats['null']++;
            }
            if ($status === 'unresolved') {
                $unresolved[] = ['id' => $s->id, 'lat_raw' => $latRaw, 'lng_raw' => $lngRaw, 'notes' => $notes];
            }

            if (! $dryRun) {
                $s->update([
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'geo_status' => $status,
                    'geo_notes' => $notes,
                ]);
            }
        }

        return array_merge($stats, ['unresolved_details' => $unresolved]);
    }

    /**
     * Algoritma cleaning SPBKLU. Koordinat SPBKLU disimpan dalam 3 format campuran:
     *   1. Desimal valid (mayoritas ~241 record) — mis. -6.342568, 106.384641
     *   2. Integer ×1.000.000 tanpa leading zero (~20 record SWAP) — -6242764 = -6.242764
     *   3. Scaled lainnya (~6 record) — mis. 963.9709 = 96.39709 (÷10)
     *
     * Strategi: cek valid dulu, lalu coba pembagian berurutan.
     */
    private function cleanSpbkluCoords(?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return [null, null, 'null', 'lat/lng null'];
        }

        // 1) Sudah valid
        if ($this->validLat($lat) && $this->validLng($lng)) {
            return [$lat, $lng, 'ok', null];
        }

        // 2) Lat scaled (integer besar)
        if (abs($lat) > 100) {
            foreach ([1_000_000, 100_000, 10_000, 1000, 100, 10] as $div) {
                $cand = $lat / $div;
                if ($this->validLat($cand) && $this->validLng($lng)) {
                    return [$cand, $lng, 'fixed_digits', "lat dibagi {$div} ({$lat} -> {$cand})"];
                }
            }
        }

        // 3) Lng scaled
        if (abs($lng) > 1000) {
            foreach ([1_000_000, 100_000, 10_000, 1000, 100, 10] as $div) {
                $cand = $lng / $div;
                if ($this->validLng($cand) && $this->validLat($lat)) {
                    return [$lat, $cand, 'fixed_digits', "lng dibagi {$div} ({$lng} -> {$cand})"];
                }
            }
        }

        // 4) Keduanya scaled
        $clat = $this->tryDivisions($lat, fn ($x) => $this->validLat($x));
        $clng = $this->tryDivisions($lng, fn ($x) => $this->validLng($x));
        if ($clat !== null && $clng !== null) {
            return [$clat, $clng, 'fixed_digits', "kedua axis di-scale"];
        }

        return [$lat, $lng, 'unresolved', "tak terpulihkan: lat={$lat} lng={$lng}"];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function validLat(?float $x): bool
    {
        return $x !== null && $x >= self::LAT_MIN && $x <= self::LAT_MAX;
    }

    private function validLng(?float $x): bool
    {
        return $x !== null && $x >= self::LNG_MIN && $x <= self::LNG_MAX;
    }

    /**
     * Sisipkan digit '6' di akhir integer-part: 10.6812 -> 106.6812.
     * Untuk nilai Indonesia (lng 95-141) yang kehilangan digit leading.
     */
    private function insertDigit6(float $v): ?float
    {
        $s = (string) abs($v);
        $dotPos = strpos($s, '.');
        $intPart = $dotPos !== false ? substr($s, 0, $dotPos) : $s;
        $decPart = $dotPos !== false ? substr($s, $dotPos) : '';

        // Integer-part 2 digit diawali '1' (range 10-14) → sisip '6' di akhir
        if (strlen($intPart) === 2 && $intPart[0] === '1') {
            $newInt = $intPart.'6';  // "10" -> "106"
            $new = ($v < 0 ? '-' : '').$newInt.$decPart;

            return (float) $new;
        }

        return null;
    }

    private function tryDivisions(float $v, callable $validator): ?float
    {
        foreach ([1_000_000, 100_000, 10_000, 1000, 100, 10] as $div) {
            $cand = $v / $div;
            if ($validator($cand)) {
                return $cand;
            }
        }

        return null;
    }

    private function parseFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }
}
