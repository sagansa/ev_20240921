<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ImportSpkluCsv extends Command
{
    protected $signature = 'spklu:import
                            {file? : Path to CSV file (default: storage/app/public/PENGUSAHAAN SPKLU PER APRIL 2026(MASTER DATA).csv)}
                            {--dry-run : Preview without writing to database}
                            {--skip-existing : Skip rows where ID Spklu already exists}';

    protected $description = 'Import SPKLU master data CSV into pln_charger_locations and pln_charger_location_details';

    // ─── Province name → province_id ─────────────────────────────────────────
    private array $provinceMap = [
        'ACEH'                     => 1,
        'SUMATERA UTARA'           => 2,
        'SUMATERA BARAT'           => 3,
        'RIAU'                     => 4,
        'JAMBI'                    => 5,
        'SUMATERA SELATAN'         => 6,
        'BENGKULU'                 => 7,
        'LAMPUNG'                  => 8,
        'KEP. BANGKA BELITUNG'     => 9,
        'KEPULAUAN BANGKA BELITUNG'=> 9,
        'BABEL'                    => 9,
        'KEPULAUAN RIAU'           => 10,
        'RIAU DAN KEPRI'           => 10,
        'DKI JAKARTA'              => 11,
        'JAKARTA'                  => 11,
        'JAWA BARAT'               => 12,
        'JAWA TENGAH'              => 13,
        'DI YOGYAKARTA'            => 14,
        'JAWA TIMUR'               => 15,
        'BANTEN'                   => 16,
        'BALI'                     => 17,
        'NUSA TENGGARA BARAT'      => 18,
        'NUSA TENGGARA TIMUR'      => 19,
        'KALIMANTAN BARAT'         => 20,
        'KALIMANTAN TENGAH'        => 21,
        'KALIMANTAN SELATAN'       => 22,
        'KALIMANTAN TIMUR'         => 23,
        'KALIMANTAN UTARA'         => 24,
        'SULAWESI UTARA'           => 25,
        'SULAWESI TENGAH'          => 26,
        'SULAWESI SELATAN'         => 27,
        'SULAWESI TENGGARA'        => 28,
        'GORONTALO'                => 29,
        'SULAWESI BARAT'           => 30,
        'MALUKU'                   => 31,
        'MALUKU UTARA'             => 32,
        'PAPUA'                    => 33,
        'PAPUA BARAT'              => 34,
    ];

    // ─── Merk name → merk_charger_id ─────────────────────────────────────────
    private array $merkMap = [
        'ABB'           => 2,
        'ALTRO'         => 3,
        'ATESS'         => 4,
        'AURORA'        => 5,
        'AUTEL'         => 6,
        'BENY'          => 7,
        'CHARGECORE'    => 8,
        'CIRCONTROL'    => 9,
        'CORNERSTONE'   => 10,
        'DELTA'         => 11,
        'EV'            => 12,
        'EV CITY'       => 13,
        'EV POWER'      => 14,
        'EXICOM'        => 15,
        'FASTROOM'      => 16,
        'HIMEL'         => 17,
        'HVT'           => 18,
        'INJECT'        => 20,
        'MARVEL'        => 21,
        'PHIHONG'       => 22,
        'PROTEKSINDO'   => 23,
        'SCHNEIDER'     => 24,
        'SIGNET'        => 25,
        'SINO'          => 26,
        'SSKE'          => 27,
        'STARCHARGE'    => 28,
        'STARVO'        => 29,
        'TEISON'        => 30,
        'TERRA'         => 31,
        'TIAR'          => 32,
        'VOLTRON'       => 33,
        'WALLBOX'       => 34,
        'WULING'        => 35,
        'ZEROVA'        => 36,
        // Aliases / variations
        'MOBI'          => null, // tidak ada di DB
        'OPINTEH'       => null,
        'CBI'           => null,
    ];

    // ─── Kategori → charging_type_id ─────────────────────────────────────────
    private array $chargingTypeMap = [
        'FAST CHARGING'       => 1,
        'MEDIUM CHARGING'     => 2,
        'STANDARD CHARGING'   => 3,
        'ULTRA FAST CHARGING' => 4,
    ];

    // ─── Cluster Pulau text (CSV) → cluster_island name ──────────────────────
    // The CSV 'Cluster Pulau' column contains text values like JAWA, SUMATERA, etc.
    private array $clusterIslandSeed = [
        ''              => 'Tidak Diketahui',
        'JAWA'          => 'Jawa',
        'SUMATERA'      => 'Sumatera',
        'BALI'          => 'Bali & Nusa Tenggara',
        'KALIMANTAN'    => 'Kalimantan',
        'SULAWESI'      => 'Sulawesi',
        'NUSA TENGGARA' => 'Nusa Tenggara',
        'PAPUA'         => 'Papua & Maluku',
        'MALUKU'        => 'Papua & Maluku',
    ];

    private array $clusterIslandMap = []; // populated at runtime


    public function handle(): int
    {
        $filePath = $this->argument('file')
            ?? base_path('storage/app/public/PENGUSAHAAN SPKLU PER APRIL 2026(MASTER DATA).csv');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');

        if ($isDryRun) {
            $this->warn('⚡ DRY RUN — tidak ada data yang ditulis ke database.');
        }

        // ── 1. Pastikan cluster_islands terisi ────────────────────────────────
        $this->ensureClusterIslands($isDryRun);

        // ── 2. Baca CSV ───────────────────────────────────────────────────────
        $this->info("Membaca file CSV...");
        $rows = $this->parseCsv($filePath);
        $this->info("Total baris data: " . count($rows));

        // ── 3. Group by ID Spklu (parent) ────────────────────────────────────
        $grouped = [];
        foreach ($rows as $row) {
            $spkluId = trim($row['ID Spklu']);
            if ($spkluId === '' || !is_numeric($spkluId)) continue;
            $grouped[$spkluId][] = $row;
        }

        $this->info("Jumlah lokasi SPKLU unik: " . count($grouped));

        $newLocations = 0;
        $newDetails   = 0;
        $skipped      = 0;
        $errors       = [];

        $bar = $this->output->createProgressBar(count($grouped));
        $bar->start();

        foreach ($grouped as $spkluId => $chargeboxes) {
            // Ambil data lokasi dari baris pertama
            $firstRow = $chargeboxes[0];

            $existingLocation = DB::connection('ev')
                ->table('pln_charger_locations')
                ->where('pln_id', $spkluId)
                ->first();

            if ($existingLocation && $skipExisting) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $provinceId      = $this->mapProvince($firstRow['Propinsi']);
            $clusterPulau    = strtoupper(trim($firstRow['Cluster Pulau'] ?? ''));
            $clusterIslandId = $this->clusterIslandMap[$clusterPulau]
                             ?? $this->clusterIslandMap[''] // fallback ke 'Tidak Diketahui'
                             ?? null;

            $locationData = [
                'pln_id'               => $spkluId,
                'name'                 => trim($firstRow['Nama Spklu']),
                'address'              => trim($firstRow['Alamat Spklu']),
                'latitude'             => $this->parseCoord($firstRow['Latitude']),
                'longitude'            => $this->parseCoord($firstRow['Longitude']),
                'province_id'          => $provinceId,
                'cluster_island_id'    => $clusterIslandId,
                'owner_machine'        => trim($firstRow['Kepemilikan Mesin'] ?? ''),
                // provider_id is UUID, leave null for PLN imports (set manually or via seeder)
                'location_category_id' => null,
                'updated_at'           => now(),
            ];

            if (!$existingLocation) {
                $locationData['created_at'] = now();
            }

            if (!$isDryRun) {
                if ($existingLocation) {
                    DB::connection('ev')
                        ->table('pln_charger_locations')
                        ->where('pln_id', $spkluId)
                        ->update($locationData);
                    $locationId = $existingLocation->id;
                } else {
                    $locationId = DB::connection('ev')
                        ->table('pln_charger_locations')
                        ->insertGetId($locationData);
                    $newLocations++;
                }

                // Hapus detail lama lalu insert ulang (upsert bersih)
                DB::connection('ev')
                    ->table('pln_charger_location_details')
                    ->where('pln_charger_location_id', $locationId)
                    ->delete();

                foreach ($chargeboxes as $cb) {
                    $detail = $this->buildDetail($locationId, $cb);
                    DB::connection('ev')
                        ->table('pln_charger_location_details')
                        ->insert($detail);
                    $newDetails++;
                }
            } else {
                // Dry-run: tampilkan preview
                if ($newLocations < 5) {
                    $this->newLine();
                    $this->line("LOKASI [{$spkluId}]: " . $locationData['name']);
                    $this->line("  Provinsi: {$firstRow['Propinsi']} → province_id={$provinceId}");
                    $this->line("  Jumlah charger: " . count($chargeboxes));
                }
                $newLocations++;
                $newDetails += count($chargeboxes);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Selesai!");
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Lokasi SPKLU diproses', $newLocations],
                ['Detail Charger diproses', $newDetails],
                ['Dilewati (sudah ada)', $skipped],
                ['Error', count($errors)],
            ]
        );

        if ($errors) {
            $this->warn("Errors:");
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
        }

        return 0;
    }

    // ─── Seed cluster_islands jika kosong ────────────────────────────────────
    private function ensureClusterIslands(bool $isDryRun): void
    {
        $existing = DB::connection('ev')->table('cluster_islands')->get()->keyBy('id');

        // Build name-to-id map from existing data
        $nameToId = [];
        foreach ($existing as $row) {
            $nameToId[$row->name] = $row->id;
        }

        // Seed missing entries
        foreach ($this->clusterIslandSeed as $csvKey => $name) {
            if (!isset($nameToId[$name])) {
                if (!$isDryRun) {
                    $id = DB::connection('ev')->table('cluster_islands')->insertGetId([
                        'name'       => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nameToId[$name] = $id;
                } else {
                    $nameToId[$name] = 99; // dummy for dry-run
                }
            }
            $this->clusterIslandMap[$csvKey] = $nameToId[$name];
        }

        $count = $isDryRun ? 'dry-run' : count($this->clusterIslandMap);
        $this->info("cluster_islands siap ({$count} entri mapped).");
    }

    // ─── Parse CSV ───────────────────────────────────────────────────────────
    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 0, ',');
        // Bersihkan BOM jika ada
        if ($headers) {
            $headers[0] = preg_replace('/[\x{FEFF}]/u', '', $headers[0]);
            $headers = array_map('trim', $headers);
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);
        return $rows;
    }

    // ─── Build detail row ─────────────────────────────────────────────────────
    private function buildDetail(int $locationId, array $row): array
    {
        $merkName = strtoupper(trim($row['Merek'] ?? ''));
        // Try exact match first, then fallback to OPINTEH/MOBI as null
        $merkId = $this->merkMap[$merkName] ?? null;

        $chargingTypeId = $this->chargingTypeMap[strtoupper(trim($row['Kategori'] ?? ''))] ?? null;

        // Power: store as string e.g. "7" (kW) since column is varchar(255)
        $power    = (string) ($this->parsePower($row['Daya Chargebox'] ?? '') ?? '');
        // is_active_charger is varchar — store 'Y' or 'N'
        $isActive = strtoupper(trim($row['Is Active'] ?? 'N')) === 'Y' ? 'Y' : 'N';
        $opDate   = $this->parseDate($row['Tgl Integrasi'] ?? '');
        $year     = $opDate ? (int) Carbon::parse($opDate)->format('Y') : null;

        return [
            'pln_charger_location_id' => $locationId,
            'chargebox_id'            => trim($row['Chargebox ID'] ?? ''),
            'chargebox_name'          => trim($row['Nama Chargebox'] ?? ''),
            'power'                   => $power,
            'is_active_charger'       => $isActive,
            'operation_date'          => $opDate,
            'charging_type_id'        => $chargingTypeId,
            'merk_charger_id'         => $merkId,
            // Actual column name is category_charger_id (not charger_category_id)
            'category_charger_id'     => null,
            'count_connector_charger' => 1,
            'year'                    => $year,
            'created_at'              => now(),
            'updated_at'              => now(),
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function mapProvince(string $name): ?int
    {
        $name = strtoupper(trim($name));
        return $this->provinceMap[$name] ?? null;
    }

    private function parseCoord(string $val): ?float
    {
        $val = str_replace(',', '.', trim($val));
        return is_numeric($val) ? (float) $val : null;
    }

    private function parsePower(string $val): ?float
    {
        // "7 kW" → 7, "25 kW" → 25, "7,4 kW" → 7.4
        $val = str_replace(',', '.', $val);
        preg_match('/[\d.]+/', $val, $m);
        return isset($m[0]) ? (float) $m[0] : null;
    }

    private function parseDate(string $val): ?string
    {
        $val = trim($val);
        if (!$val) return null;
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
