<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SpkluCsvImportService
{
    private array $provinceMap = [
        'ACEH' => 1,
        'SUMATERA UTARA' => 2,
        'SUMATERA BARAT' => 3,
        'RIAU' => 4,
        'JAMBI' => 5,
        'SUMATERA SELATAN' => 6,
        'BENGKULU' => 7,
        'LAMPUNG' => 8,
        'KEP. BANGKA BELITUNG' => 9,
        'KEPULAUAN BANGKA BELITUNG' => 9,
        'KEPULAUAN RIAU' => 10,
        'DKI JAKARTA' => 11,
        'JAKARTA' => 11,
        'JAWA BARAT' => 12,
        'JAWA TENGAH' => 13,
        'DI YOGYAKARTA' => 14,
        'JAWA TIMUR' => 15,
        'BANTEN' => 16,
        'BALI' => 17,
        'NUSA TENGGARA BARAT' => 18,
        'NUSA TENGGARA TIMUR' => 19,
        'KALIMANTAN BARAT' => 20,
        'KALIMANTAN TENGAH' => 21,
        'KALIMANTAN SELATAN' => 22,
        'KALIMANTAN TIMUR' => 23,
        'KALIMANTAN UTARA' => 24,
        'SULAWESI UTARA' => 25,
        'SULAWESI TENGAH' => 26,
        'SULAWESI SELATAN' => 27,
        'SULAWESI TENGGARA' => 28,
        'GORONTALO' => 29,
        'SULAWESI BARAT' => 30,
        'MALUKU' => 31,
        'MALUKU UTARA' => 32,
        'PAPUA' => 33,
        'PAPUA BARAT' => 34,
    ];

    private array $merkMap = [
        'ABB' => 2,
        'ALTRO' => 3,
        'ATESS' => 4,
        'AURORA' => 5,
        'AUTEL' => 6,
        'BENY' => 7,
        'CHARGECORE' => 8,
        'CIRCONTROL' => 9,
        'CORNERSTONE' => 10,
        'DELTA' => 11,
        'EV' => 12,
        'EV CITY' => 13,
        'EV POWER' => 14,
        'EXICOM' => 15,
        'FASTROOM' => 16,
        'HIMEL' => 17,
        'HVT' => 18,
        'INJECT' => 20,
        'MARVEL' => 21,
        'PHIHONG' => 22,
        'PROTEKSINDO' => 23,
        'SCHNEIDER' => 24,
        'SIGNET' => 25,
        'SINO' => 26,
        'SSKE' => 27,
        'STARCHARGE' => 28,
        'STARVO' => 29,
        'TEISON' => 30,
        'TERRA' => 31,
        'TIAR' => 32,
        'VOLTRON' => 33,
        'WALLBOX' => 34,
        'WULING' => 35,
        'ZEROVA' => 36,
        'MOBI' => null,
        'OPINTEH' => null,
        'CBI' => null,
    ];

    private array $chargingTypeMap = [
        'FAST CHARGING' => 1,
        'MEDIUM CHARGING' => 2,
        'STANDARD CHARGING' => 3,
        'ULTRA FAST CHARGING' => 4,
    ];

    private array $clusterIslandSeed = [
        '' => 'Tidak Diketahui',
        'JAWA' => 'Jawa',
        'SUMATERA' => 'Sumatera',
        'BALI' => 'Bali & Nusa Tenggara',
        'KALIMANTAN' => 'Kalimantan',
        'SULAWESI' => 'Sulawesi',
        'NUSA TENGGARA' => 'Nusa Tenggara',
        'PAPUA' => 'Papua & Maluku',
        'MALUKU' => 'Papua & Maluku',
    ];

    private array $clusterIslandMap = [];

    private array $resolvedChargingTypes = [];

    private array $resolvedMerkChargers = [];

    private array $requiredHeaders = [
        'Chargebox ID',
        'ID Spklu',
        'Merek',
        'Nama Chargebox',
        'Daya Chargebox',
        'Kategori',
        'Is Active',
        'Tgl Integrasi',
        'Nama Spklu',
        'Alamat Spklu',
        'Latitude',
        'Longitude',
        'Propinsi',
        'Cluster Pulau',
        'Kepemilikan Mesin',
    ];

    public function import(string $filePath, bool $replaceExisting = true, bool $dryRun = false): array
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File tidak ditemukan: {$filePath}");
        }

        $rows = $this->parseCsv($filePath);
        $grouped = $this->groupRowsBySpkluId($rows);

        $summary = [
            'total_rows' => count($rows),
            'locations' => count($grouped),
            'details' => array_sum(array_map('count', $grouped)),
            'deleted_locations' => 0,
            'deleted_details' => 0,
            'inserted_locations' => 0,
            'inserted_details' => 0,
            'skipped_rows' => count($rows) - array_sum(array_map('count', $grouped)),
            'replace_existing' => $replaceExisting,
            'dry_run' => $dryRun,
        ];

        if ($dryRun) {
            return $summary;
        }

        return DB::connection('ev')->transaction(function () use ($grouped, $replaceExisting, $summary) {
            $this->ensureClusterIslands();

            if ($replaceExisting) {
                $summary['deleted_details'] = DB::connection('ev')
                    ->table('pln_charger_location_details')
                    ->delete();

                $summary['deleted_locations'] = DB::connection('ev')
                    ->table('pln_charger_locations')
                    ->delete();
            }

            foreach ($grouped as $spkluId => $chargeboxes) {
                $firstRow = $chargeboxes[0];
                $existingLocation = null;

                if (! $replaceExisting) {
                    $existingLocation = DB::connection('ev')
                        ->table('pln_charger_locations')
                        ->where('pln_id', $spkluId)
                        ->first();
                }

                $locationId = $existingLocation?->id;
                $locationData = $this->buildLocation($spkluId, $firstRow);

                if ($existingLocation) {
                    DB::connection('ev')
                        ->table('pln_charger_locations')
                        ->where('id', $locationId)
                        ->update($locationData);

                    DB::connection('ev')
                        ->table('pln_charger_location_details')
                        ->where('pln_charger_location_id', $locationId)
                        ->delete();
                } else {
                    $locationData['created_at'] = now();
                    $locationId = DB::connection('ev')
                        ->table('pln_charger_locations')
                        ->insertGetId($locationData);
                    $summary['inserted_locations']++;
                }

                foreach ($chargeboxes as $chargebox) {
                    DB::connection('ev')
                        ->table('pln_charger_location_details')
                        ->insert($this->buildDetail($locationId, $chargebox));
                    $summary['inserted_details']++;
                }
            }

            return $summary;
        });
    }

    public function preview(string $filePath): array
    {
        return $this->import($filePath, true, true);
    }

    private function ensureClusterIslands(): void
    {
        $existing = DB::connection('ev')->table('cluster_islands')->get()->keyBy('name');

        foreach ($this->clusterIslandSeed as $csvKey => $name) {
            $row = $existing->get($name);

            if (! $row) {
                $id = DB::connection('ev')->table('cluster_islands')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = $row->id;
            }

            $this->clusterIslandMap[$csvKey] = $id;
        }
    }

    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 0, ',');

        if (! $headers) {
            fclose($handle);
            throw new InvalidArgumentException('CSV tidak memiliki header.');
        }

        $headers[0] = preg_replace('/[\x{FEFF}]/u', '', $headers[0]);
        $headers = array_map('trim', $headers);
        $missingHeaders = array_values(array_diff($this->requiredHeaders, $headers));

        if ($missingHeaders) {
            fclose($handle);
            throw new InvalidArgumentException('Header CSV tidak lengkap: '.implode(', ', $missingHeaders));
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function groupRowsBySpkluId(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $spkluId = trim($row['ID Spklu'] ?? '');

            if ($spkluId === '' || ! is_numeric($spkluId)) {
                continue;
            }

            $grouped[$spkluId][] = $row;
        }

        return $grouped;
    }

    private function buildLocation(string $spkluId, array $row): array
    {
        $clusterPulau = strtoupper(trim($row['Cluster Pulau'] ?? ''));
        $clusterIslandId = $this->clusterIslandMap[$clusterPulau]
            ?? $this->clusterIslandMap['']
            ?? null;

        return [
            'pln_id' => $spkluId,
            'name' => trim($row['Nama Spklu'] ?? ''),
            'address' => trim($row['Alamat Spklu'] ?? ''),
            'latitude' => $this->parseCoord($row['Latitude'] ?? ''),
            'longitude' => $this->parseCoord($row['Longitude'] ?? ''),
            'province_id' => $this->mapProvince($row['Propinsi'] ?? ''),
            'cluster_island_id' => $clusterIslandId,
            'owner_machine' => trim($row['Kepemilikan Mesin'] ?? ''),
            'provider_id' => null,
            'location_category_id' => null,
            'updated_at' => now(),
        ];
    }

    private function buildDetail(int $locationId, array $row): array
    {
        $operationDate = $this->parseDate($row['Tgl Integrasi'] ?? '');

        return [
            'pln_charger_location_id' => $locationId,
            'chargebox_id' => trim($row['Chargebox ID'] ?? ''),
            'chargebox_name' => trim($row['Nama Chargebox'] ?? ''),
            'power' => (string) ($this->parsePower($row['Daya Chargebox'] ?? '') ?? ''),
            'is_active_charger' => strtoupper(trim($row['Is Active'] ?? 'N')) === 'Y' ? 'Y' : 'N',
            'operation_date' => $operationDate,
            'charging_type_id' => $this->resolveChargingTypeId($row['Kategori'] ?? ''),
            'merk_charger_id' => $this->resolveMerkChargerId($row['Merek'] ?? ''),
            'category_charger_id' => null,
            'count_connector_charger' => 1,
            'year' => $operationDate ? (int) Carbon::parse($operationDate)->format('Y') : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function mapProvince(string $name): ?int
    {
        return $this->provinceMap[strtoupper(trim($name))] ?? null;
    }

    private function resolveChargingTypeId(string $name): ?int
    {
        $normalizedName = strtoupper(trim($name));

        if ($normalizedName === '') {
            return null;
        }

        if (array_key_exists($normalizedName, $this->resolvedChargingTypes)) {
            return $this->resolvedChargingTypes[$normalizedName];
        }

        $mappedId = $this->chargingTypeMap[$normalizedName] ?? null;

        if ($mappedId) {
            return $this->resolvedChargingTypes[$normalizedName] = $mappedId;
        }

        $existingId = DB::connection('ev')
            ->table('charging_types')
            ->whereRaw('UPPER(name) = ?', [$normalizedName])
            ->value('id');

        if ($existingId) {
            return $this->resolvedChargingTypes[$normalizedName] = (int) $existingId;
        }

        $id = DB::connection('ev')->table('charging_types')->insertGetId([
            'name' => $normalizedName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->resolvedChargingTypes[$normalizedName] = (int) $id;
    }

    private function resolveMerkChargerId(string $name): int|string|null
    {
        $normalizedName = strtoupper(trim($name));

        if ($normalizedName === '') {
            return null;
        }

        if (array_key_exists($normalizedName, $this->resolvedMerkChargers)) {
            return $this->resolvedMerkChargers[$normalizedName];
        }

        if (array_key_exists($normalizedName, $this->merkMap)) {
            return $this->resolvedMerkChargers[$normalizedName] = $this->merkMap[$normalizedName];
        }

        $existingId = DB::connection('ev')
            ->table('merk_chargers')
            ->whereRaw('UPPER(name) = ?', [$normalizedName])
            ->value('id');

        if ($existingId) {
            return $this->resolvedMerkChargers[$normalizedName] = $existingId;
        }

        $idColumnType = Schema::connection('ev')->getColumnType('merk_chargers', 'id');
        $isStringKey = in_array($idColumnType, ['char', 'string', 'varchar'], true);

        if ($isStringKey) {
            $id = (string) Str::uuid();

            DB::connection('ev')->table('merk_chargers')->insert([
                'id' => $id,
                'name' => $normalizedName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->resolvedMerkChargers[$normalizedName] = $id;
        }

        $id = DB::connection('ev')->table('merk_chargers')->insertGetId([
            'name' => $normalizedName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->resolvedMerkChargers[$normalizedName] = (int) $id;
    }

    private function parseCoord(string $value): ?float
    {
        $value = str_replace(',', '.', trim($value));

        return is_numeric($value) ? (float) $value : null;
    }

    private function parsePower(string $value): ?float
    {
        $value = str_replace(',', '.', $value);
        preg_match('/[\d.]+/', $value, $matches);

        return isset($matches[0]) ? (float) $matches[0] : null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
