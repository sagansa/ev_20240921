<?php

namespace App\Services;

use App\Models\SpkluChargerBox;
use App\Models\SpkluLocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SpkluJsonImportService
{
    /**
     * Import SPKLU data from a JSON file.
     *
     * @param string $filePath Absolute or relative path to the JSON file.
     * @param bool $replaceExisting Whether to delete existing locations & charger boxes before importing.
     * @return array Import summary statistics.
     */
    public function importFromFile(string $filePath, bool $replaceExisting = true, ?string $overrideProviderId = null): array
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File JSON tidak ditemukan: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $records = json_decode($content, true);

        if (! is_array($records)) {
            throw new InvalidArgumentException("Format file JSON tidak valid.");
        }

        return $this->importFromData($records, $replaceExisting, $overrideProviderId);
    }

    /**
     * Import SPKLU data from a parsed array of records.
     *
     * @param array $records List of location records from JSON.
     * @param bool $replaceExisting Whether to delete existing locations & charger boxes before importing.
     * @param string|null $overrideProviderId Optional provider ID to assign to all locations.
     * @return array Import summary statistics.
     */
    public function importFromData(array $records, bool $replaceExisting = true, ?string $overrideProviderId = null): array
    {
        return DB::connection('ev')->transaction(function () use ($records, $replaceExisting, $overrideProviderId) {
            $deletedLocations = 0;
            $deletedChargerBoxes = 0;

            if ($replaceExisting) {
                $deletedChargerBoxes = SpkluChargerBox::query()->delete();
                $deletedLocations = SpkluLocation::query()->delete();
            }

            $insertedLocations = 0;
            $insertedChargerBoxes = 0;

            foreach ($records as $record) {
                $externalId = $record['id'] ?? null;
                if ($externalId === null) {
                    continue;
                }

                $provinsi = isset($record['provinsi']) ? trim($record['provinsi']) : '';
                $latitude = isset($record['latitude']) && $record['latitude'] !== null ? (float) $record['latitude'] : null;
                $longitude = isset($record['longitude']) && $record['longitude'] !== null ? (float) $record['longitude'] : null;

                $totalCharger = (int) ($record['total_charger'] ?? 0);
                $totalKonektor = (int) ($record['total_konektor'] ?? 0);

                $chargerBoxes = $record['chargerboxes'] ?? [];
                if ($totalCharger === 0 && count($chargerBoxes) > 0) {
                    $totalCharger = array_sum(array_column($chargerBoxes, 'jumlah_charger'));
                }
                if ($totalKonektor === 0 && count($chargerBoxes) > 0) {
                    $totalKonektor = count($chargerBoxes);
                }

                $namaLokasi = $record['nama_lokasi'] ?? '';
                $providerId = $overrideProviderId ?? $this->resolveProviderId($namaLokasi);

                $locationData = [
                    'external_id' => $externalId,
                    'provider_id' => $providerId,
                    'provinsi' => $provinsi,
                    'kabupaten_kota' => $record['kabupaten_kota'] ?? null,
                    'nama_lokasi' => $namaLokasi,
                    'alamat' => $record['alamat'] ?? null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'type_charge' => $record['type_charge'] ?? null,
                    'watt' => $record['watt'] ?? null,
                    'status' => $record['status'] ?? 1,
                    'keterangan' => $record['keterangan'] ?? null,
                    'total_charger' => $totalCharger,
                    'total_konektor' => $totalKonektor,
                ];

                if ($replaceExisting) {
                    $location = SpkluLocation::create($locationData);
                } else {
                    $location = SpkluLocation::updateOrCreate(
                        ['external_id' => $externalId],
                        $locationData
                    );
                    SpkluChargerBox::where('spklu_location_id', $location->id)->delete();
                }

                $insertedLocations++;

                foreach ($chargerBoxes as $cb) {
                    SpkluChargerBox::create([
                        'spklu_location_id' => $location->id,
                        'chargerbox_id' => $cb['chargerbox_id'] ?? null,
                        'type_charge' => $cb['type_charge'] ?? null,
                        'nama_chargerbox' => $cb['nama_chargerbox'] ?? null,
                        'watt' => $cb['watt'] ?? null,
                        'jumlah_charger' => (int) ($cb['jumlah_charger'] ?? 1),
                        'jumlah_konektor' => (string) ($cb['jumlah_konektor'] ?? '1'),
                        'icon' => $cb['icon'] ?? null,
                        'gambar' => $cb['gambar'] ?? null,
                    ]);
                    $insertedChargerBoxes++;
                }
            }

            return [
                'total_records' => count($records),
                'inserted_locations' => $insertedLocations,
                'inserted_charger_boxes' => $insertedChargerBoxes,
                'deleted_locations' => $deletedLocations,
                'deleted_charger_boxes' => $deletedChargerBoxes,
            ];
        });
    }

    private function resolveProviderId(string $spkluName): ?string
    {
        $spkluNameUpper = strtoupper($spkluName);

        $providers = DB::connection('ev')->table('providers')->select(['id', 'name'])->get();

        foreach ($providers as $provider) {
            $pNameUpper = strtoupper($provider->name);
            if ($pNameUpper !== '' && str_contains($spkluNameUpper, $pNameUpper)) {
                return $provider->id;
            }
        }

        $plnProvider = $providers->first(function ($p) {
            return str_contains(strtoupper($p->name), 'PLN');
        });

        return $plnProvider?->id;
    }
}
