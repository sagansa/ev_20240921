<?php

namespace App\Services;

use App\Models\EsdmSinggatSpkluStation;
use Illuminate\Support\Facades\Http;

/**
 * Verifikasi geolokasi stasiun ESDM SPKLU — layer DI ATAS cleaning (geo_status).
 *
 * Cleaning menormalkan koordinat dari *_raw (digit hilang, tukar, dll). Verifikasi
 * memvalidasi apakah koordinat hasil cleaning benar-benar masuk akal:
 *
 *   Step 1 (bbox, instant)   : koordinat di luar bbox kode_provinsi →
 *                              geo_verification = province_mismatch
 *   Step 2 (OSM Nominatim)   : forward search "{nama_stasiun}" (fallback
 *                              "{alamat_spklu}"), hitung jarak ke koordinat cleaned:
 *                                <200m    → verified
 *                                200m–2km → drift_minor  (simpan koordinat OSM)
 *                                >2km     → drift_major  (simpan koordinat OSM)
 *                                tidak ketemu → not_found
 *   Step 3 (manual, Filament): admin buka Google Maps, koreksi koordinat via
 *                              EditAction → manual_fixed
 *
 * Koordinat terbaik untuk hydrate (getBestCoordinate):
 *   manual_fixed > verified > drift_minor (OSM candidate) > cleaned > raw
 */
class GeoVerificationService
{
    /** Bbox per provinsi (BPS code → [minLat, maxLat, minLng, maxLng]). */
    public const PROVINCE_BBOX = [
        '11' => [-6.0, 6.0, 94.0, 100.0],
        '12' => [-2.0, 6.0, 97.0, 101.0],
        '13' => [-2.0, 1.0, 98.0, 102.0],
        '14' => [-1.0, 3.0, 100.0, 104.0],
        '15' => [-3.0, 2.0, 101.0, 104.0],
        '16' => [-5.0, -1.0, 101.0, 106.0],
        '17' => [-5.0, -1.0, 101.0, 104.0],
        '18' => [-6.0, -3.0, 103.0, 106.0],
        '19' => [-4.0, -1.0, 104.0, 109.0],
        '21' => [-1.0, 4.0, 103.0, 108.0],
        '31' => [-7.0, -5.0, 106.0, 107.5],
        '32' => [-8.0, -5.0, 105.0, 109.0],
        '33' => [-8.0, -6.0, 108.0, 111.5],
        '34' => [-8.5, -7.0, 109.5, 111.0],
        '35' => [-9.0, -6.5, 111.0, 115.0],
        '36' => [-7.5, -5.5, 105.0, 107.0],
        '51' => [-9.0, -7.5, 114.0, 115.8],
        '52' => [-9.5, -8.0, 115.5, 117.0],
        '53' => [-11.0, -8.0, 118.5, 124.5],
        '61' => [-2.0, 2.0, 108.0, 111.0],
        '62' => [-3.5, 1.5, 110.0, 115.0],
        '63' => [-4.5, -1.0, 114.0, 117.0],
        '64' => [-2.0, 3.0, 115.0, 118.5],
        '65' => [-2.0, 4.5, 116.0, 118.0],
        '71' => [0.0, 5.0, 121.0, 126.0],
        '72' => [-2.0, 2.0, 119.5, 123.5],
        '73' => [-6.0, -1.5, 118.5, 121.5],
        '74' => [-6.0, -3.0, 120.5, 123.5],
        '75' => [-1.0, 2.0, 122.0, 123.5],
        '76' => [-4.0, -1.5, 118.5, 120.0],
        '81' => [-8.0, -2.0, 126.0, 133.0],
        '82' => [-1.0, 4.0, 126.0, 130.0],
        '91' => [-5.0, -0.5, 130.0, 142.0],
        '92' => [-10.0, -1.0, 133.0, 142.0],
    ];

    /** Threshold jarak (meter) OSM vs koordinat cleaned. */
    public const DIST_VERIFIED_M = 200;
    public const DIST_MINOR_M = 2000;

    public const UA = 'Sagansa EV/1.0 (contact@sagansaev.com)';

    /**
     * Jalankan pipeline penuh: bbox → OSM. Return ringkasan counts.
     *
     * @param  callable(string $message, int $done, int $total): void  $progress
     */
    public function verifyAll(bool $dryRun = false, bool $bboxOnly = false, bool $osmOnly = false, ?callable $progress = null): array
    {
        $summary = [
            'processed' => 0,
            'province_mismatch' => 0,
            'verified' => 0,
            'drift_minor' => 0,
            'drift_major' => 0,
            'not_found' => 0,
            'skipped' => 0,
        ];

        $query = EsdmSinggatSpkluStation::query();

        if ($osmOnly) {
            $query->whereNotNull('latitude')->whereNotNull('longitude');
        }

        $stations = $query->get();
        $total = $stations->count();
        $done = 0;

        foreach ($stations as $station) {
            $done++;
            if ($progress !== null) {
                $progress("memproses stasiun {$done}/{$total}", $done, $total);
            }

            if (! $osmOnly && $this->verifyProvinceBbox($station)) {
                $summary['province_mismatch']++;
                $this->persist($station, $dryRun);
                $summary['processed']++;

                continue;
            }

            if ($osmOnly || ! $bboxOnly) {
                if ($station->latitude === null || $station->longitude === null) {
                    $summary['skipped']++;
                    $this->persist($station, $dryRun);

                    continue;
                }

                $result = $this->verifyViaOsm($station);
                if ($result !== null) {
                    $summary[$result['level']]++;
                } else {
                    $summary['not_found']++;
                }
                $summary['processed']++;
                $this->persist($station, $dryRun);
            } else {
                // bbox-only dan lolos bbox → status tetap null (tidak verified tanpa OSM)
                $summary['processed']++;
                $this->persist($station, $dryRun);
            }

            // Nominatim policy: max 1 request/detik. Jeda antar request.
            if (! $bboxOnly) {
                usleep(1_000_000);
            }
        }

        return $summary;
    }

    /**
     * Step 1 — cek koordinat cleaned terhadap bbox kode_provinsi.
     * Set status province_mismatch bila di luar bbox; tanpa overwrite bila sudah
     * punya status lebih "kuat" (manual_fixed). Return true bila di-flag.
     */
    public function verifyProvinceBbox(EsdmSinggatSpkluStation $station): bool
    {
        if ($station->geo_verification === 'manual_fixed' || $station->geo_verification === 'verified') {
            return false;
        }

        $bbox = self::PROVINCE_BBOX[(string) $station->kode_provinsi] ?? null;
        if ($bbox === null || $station->latitude === null || $station->longitude === null) {
            return false;
        }

        [$minLat, $maxLat, $minLng, $maxLng] = $bbox;

        $inside = $station->latitude >= $minLat
            && $station->latitude <= $maxLat
            && $station->longitude >= $minLng
            && $station->longitude <= $maxLng;

        if (! $inside && $station->geo_verification === null) {
            $station->geo_verification = 'province_mismatch';
            $station->geo_verified_source = 'bbox';

            return true;
        }

        return false;
    }

    /**
     * Step 2 — forward-search OSM Nominatim by nama_stasiun (fallback alamat).
     * Bila ketemu: simpan koordinat OSM sebagai geo_verified_*, hitung jarak,
     * dan klasifikasikan verified / drift_minor / drift_major.
     *
     * @return array{level: string, distance_m: int, lat: float, lng: float}|null
     */
    public function verifyViaOsm(EsdmSinggatSpkluStation $station): ?array
    {
        if ($station->geo_verification === 'manual_fixed') {
            return null;
        }

        $queries = $this->buildOsmQueries((string) $station->nama_stasiun, (string) $station->alamat_spklu);

        foreach ($queries as $query) {
            $geo = $this->osmSearch($query);
            if ($geo === null) {
                continue;
            }

            $lat = (float) $station->latitude;
            $lng = (float) $station->longitude;
            $distance = $this->distanceM($lat, $lng, $geo['latitude'], $geo['longitude']);

            $level = match (true) {
                $distance < self::DIST_VERIFIED_M => 'verified',
                $distance <= self::DIST_MINOR_M => 'drift_minor',
                default => 'drift_major',
            };

            $station->geo_verified_lat = $geo['latitude'];
            $station->geo_verified_lng = $geo['longitude'];
            $station->geo_distance_m = $distance;
            $station->geo_verified_source = 'osm';

            // province_mismatch (dari step bbox) di-overwrite oleh temuan OSM
            // bila OSM ternyata berada di dalam bbox provinsi. Bila tetap di
            // luar, status bbox dipertahankan + catat drift di distance.
            $inside = $this->coordinateInsideProvince($geo['latitude'], $geo['longitude'], (string) $station->kode_provinsi);
            $station->geo_verification = $inside ? $level : 'province_mismatch';

            return [
                'level' => $station->geo_verification,
                'distance_m' => $distance,
                'lat' => $geo['latitude'],
                'lng' => $geo['longitude'],
            ];
        }

        // Tidak ada query yang cocok — hanya set bila belum berstatus.
        if ($station->geo_verification === null) {
            $station->geo_verification = 'not_found';
        }

        return null;
    }

    /**
     * Susun kandidat query OSM, dari yang paling spesifik → paling longgar.
     * Nominatim sering gagal dengan alamat verbose (RT/RW, kecamatan, kode pos),
     * jadi diproduksi varian ringkas "jalan, kelurahan, kota" yang lebih akurat.
     *
     * @return string[]
     */
    public function buildOsmQueries(string $name, string $address): array
    {
        $queries = array_values(array_unique(array_filter([
            trim($name),
            trim($address),
        ], fn ($q) => $q !== '')));

        $parts = array_values(array_filter(
            array_map('trim', explode(',', $address)),
            fn ($p) => $p !== ''
        ));

        if (count($parts) >= 2) {
            // Ambil nama jalan (tanpa prefix "Jl."/jalan & nomor rumah).
            $street = $parts[0];
            $street = preg_replace('/^(jl\.?|jalan|ds?|jln)\s+/i', '', trim($street));
            $street = preg_replace('/\s*no\.?\s*[\dA-Za-z\-\/]+$/i', '', trim($street));

            // Cari segmen "Kecamatan X" dan "Kota/Kabupaten Y".
            $kecamatan = null;
            $kota = null;
            foreach ($parts as $part) {
                if (preg_match('/^kecamatan\s+/i', $part)) {
                    $kecamatan = preg_replace('/^kecamatan\s+/i', '', trim($part));
                } elseif (preg_match('/^kec\.?\s+/i', $part)) {
                    $kecamatan = preg_replace('/^kec\.?\s+/i', '', trim($part));
                }
                if (preg_match('/^(kota|kab\.?|kabupaten|daerah khusus ibukota)\s+/i', $part)) {
                    $kota = preg_replace('/\s+\d{5}$/i', '', trim($part));
                }
            }

            // Varian ringkas yang terbukti lebih sering match di Nominatim:
            // 1) "jalan, kecamatan, kota"
            // 2) "jalan, kota"
            if ($street !== '' && $kecamatan !== null) {
                $queries[] = trim($street.', '.$kecamatan.($kota !== null ? ', '.$kota : ''));
            }
            if ($street !== '' && $kota !== null) {
                $queries[] = trim($street.', '.$kota);
            }
            if ($street !== '') {
                $queries[] = $street;
            }
        }

        return array_values(array_unique(array_filter($queries, fn ($q) => $q !== '')));
    }

    /**
     * Forward search ke OSM Nominatim (countrycodes=id). Return null bila tidak
     * ada hasil. 1 request per panggilan — caller bertanggung jawab rate-limit.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function osmSearch(string $query): ?array
    {
        $response = Http::withHeaders(['User-Agent' => self::UA])
            ->timeout(15)
            ->retry(2, 300)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'id',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (empty($data) || ! isset($data[0]['lat']) || ! isset($data[0]['lon'])) {
            return null;
        }

        return [
            'latitude' => (float) $data[0]['lat'],
            'longitude' => (float) $data[0]['lon'],
        ];
    }

    /**
     * Koordinat terbaik untuk hydrate canonical.
     * Prioritas: manual_fixed > verified > drift_minor (OSM candidate) > cleaned.
     *
     * @return array{latitude: float|null, longitude: float|null}
     */
    public function getBestCoordinate(EsdmSinggatSpkluStation $station): array
    {
        if ($station->geo_verified_lat !== null && $station->geo_verified_lng !== null) {
            $useVerified = in_array($station->geo_verification, ['manual_fixed', 'verified', 'drift_minor'], true);
            if ($useVerified) {
                return [
                    'latitude' => (float) $station->geo_verified_lat,
                    'longitude' => (float) $station->geo_verified_lng,
                ];
            }
        }

        return [
            'latitude' => $station->latitude !== null ? (float) $station->latitude : null,
            'longitude' => $station->longitude !== null ? (float) $station->longitude : null,
        ];
    }

    /** Jarak haversine dalam meter antara 2 koordinat. */
    public function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /** Apakah koordinat berada di dalam bbox kode_provinsi? */
    public function coordinateInsideProvince(float $lat, float $lng, string $kodeProvinsi): bool
    {
        $bbox = self::PROVINCE_BBOX[$kodeProvinsi] ?? null;
        if ($bbox === null) {
            return true; // tanpa bbox, tidak bisa diverifikasi — asumsikan valid
        }

        [$minLat, $maxLat, $minLng, $maxLng] = $bbox;

        return $lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng;
    }

    /** Simpan ke DB (skip saat dry-run). */
    private function persist(EsdmSinggatSpkluStation $station, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $station->save();
    }
}
