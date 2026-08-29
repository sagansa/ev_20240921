<?php

namespace App\Console\Commands;

use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\VehicleSalesStat;
use App\Services\VehicleNameSplitter;
use App\Services\VehicleSalesMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Import CSV penjualan wholesales GAIKINDO (kolom BRAND, TYPE MODEL, CC,
 * TRANS, FUEL opsional, JAN..DEC, TOTAL) ke sales_imports +
 * vehicle_sales_stats, lalu match ke katalog brand/model/type kendaraan.
 *
 * PENTING — REPLACE per periode: command ini MENGHAPUS dulu seluruh
 * vehicle_sales_stats milik periode yang sama (import tahunan: satu tahun;
 * import bulanan: satu bulan pada tahun itu), baru menulis ulang. Re-upload
 * file yang diperbaiki bersih menggantikan angka lama (idempotent), tidak
 * pernah menumpuk dua kali. Baris sales_imports tetap append-only sebagai
 * riwayat file.
 *
 * Format nilai bulan: angka, atau "-" (tidak ada/nol). Import tahunan
 * menghasilkan baris per bulan bernilai > 0 plus SATU baris agregat
 * (month = NULL) berisi jumlah bulan tersebut. Import bulanan (--month)
 * mengambil angka dari kolom UNITS (file bulanan) atau kolom bulan terkait
 * (file format tahunan).
 *
 * Contoh:
 *   php artisan vehicle-sales:import-csv docs/csv/GAIKINDO_2022.csv
 *   php artisan vehicle-sales:import-csv GAIKINDO_2026-01.csv --month=1
 */
class ImportVehicleSalesCsv extends Command
{
    /** @var list<string> */
    protected const MONTH_COLUMNS = [
        'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
        'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC',
    ];

    protected $signature = 'vehicle-sales:import-csv
        {file : Path ke file CSV GAIKINDO}
        {--year= : Tahun periode (default: dideteksi dari nama file)}
        {--month= : Bulan periode 1-12 (kosong = import tahunan JAN..DEC)}
        {--source=gaikindo-csv : Sumber data}';

    protected $description = 'Import CSV wholesales GAIKINDO per tahun/bulan ke vehicle_sales_stats + match katalog brand/model/type (stats periode terkait DIGANTI, bukan ditumpuk).';

    public function handle(VehicleNameSplitter $splitter, VehicleSalesMatcher $matcher): int
    {
        $filePath = (string) $this->argument('file');

        if (! is_file($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $year = $this->resolveYear($filePath);

        if ($year === null) {
            $this->error('Tahun tidak diketahui: berikan --year atau sisipkan 4 digit tahun pada nama file (mis. GAIKINDO_2022.csv).');

            return self::FAILURE;
        }

        $month = null;

        if ($this->option('month') !== null && $this->option('month') !== '') {
            $month = (int) $this->option('month');

            if ($month < 1 || $month > 12) {
                $this->error('--month harus angka 1-12.');

                return self::FAILURE;
            }
        }

        $parsed = $this->readCsv($filePath, $month);

        if ($parsed === null) {
            return self::FAILURE; // sebabnya sudah dilaporkan
        }

        [$rows, $junkSkipped] = $parsed;

        try {
            $summary = DB::transaction(function () use ($rows, $junkSkipped, $filePath, $year, $month, $splitter, $matcher) {
                // REPLACE-first: bersihkan stats periode yang sama agar
                // re-upload idempotent — angka lama tidak pernah menumpuk.
                VehicleSalesStat::query()
                    ->where('year', $year)
                    ->when($month !== null, fn ($q) => $q->where('month', $month))
                    ->delete();

                $import = SalesImport::create([
                    'file_name' => basename($filePath),
                    'source' => (string) $this->option('source'),
                    'year' => $year,
                    'status' => 'processed',
                ]);

                $statsCreated = 0;
                $typesCreated = 0;
                $skipped = $junkSkipped;
                /** @var array<int, true> $brandIds */
                $brandIds = [];
                /** @var array<int, true> $modelIds */
                $modelIds = [];

                foreach ($rows as $row) {
                    $split = $splitter->split($row['brand'], $row['type_model'], $row['fuel']);

                    if ($split['flag'] === 'junk' || $split['model'] === '') {
                        $skipped++;

                        continue;
                    }

                    // ATURAN BEV-ONLY: katalog (brand/model/type) hanya untuk
                    // kendaraan BEV — mobile app khusus EV. Baris non-BEV
                    // tetap masuk statistik (konteks pasar) dengan link
                    // katalog NULL; raw_brand/raw_model tetap tersimpan.
                    $isBev = $split['powertrain'] === 'BEV';

                    // Match di level KELUARGA (hasil splitter), bukan varian penuh.
                    $match = $isBev
                        ? $matcher->match($row['brand'], $split['model'])
                        : ['brand_vehicle_id' => null, 'model_vehicle_id' => null, 'brand_created' => false, 'model_created' => false, 'battery_kwh' => null];

                    $typeId = null;

                    if ($isBev && $match['model_vehicle_id'] !== null && $split['type'] !== '') {
                        $typeId = $this->resolveType($match['model_vehicle_id'], $split['type'], $typesCreated);
                    }

                    $base = [
                        'sales_import_id' => $import->id,
                        'raw_brand' => $row['brand'],
                        'raw_model' => $row['type_model'],
                        'brand_vehicle_id' => $match['brand_vehicle_id'],
                        'model_vehicle_id' => $match['model_vehicle_id'],
                        'type_vehicle_id' => $typeId,
                        'powertrain' => $split['powertrain'],
                        'year' => $year,
                    ];

                    $createStat = function (int $units, ?int $statMonth) use ($base, &$statsCreated, &$brandIds, &$modelIds): void {
                        VehicleSalesStat::create($base + [
                            'month' => $statMonth,
                            'units' => $units,
                        ]);

                        $statsCreated++;

                        if ($base['brand_vehicle_id'] !== null) {
                            $brandIds[$base['brand_vehicle_id']] = true;
                        }

                        if ($base['model_vehicle_id'] !== null) {
                            $modelIds[$base['model_vehicle_id']] = true;
                        }
                    };

                    if ($month === null) {
                        // Tahunan: satu baris per bulan bernilai > 0 ...
                        $sum = 0;

                        foreach ($row['cells'] as $cellMonth => $units) {
                            if ($units > 0) {
                                $createStat($units, $cellMonth);
                                $sum += $units;
                            }
                        }

                        // ... plus SATU baris agregat tahunan (month = NULL).
                        if ($sum > 0) {
                            $createStat($sum, null);
                        }
                    } else {
                        // Bulanan: dari kolom UNITS (file bulanan) atau kolom
                        // bulan terkait (file format tahunan).
                        $units = $row['units'] ?? $row['cells'][$month];

                        if ($units > 0) {
                            $createStat($units, $month);
                        }
                    }
                }

                if ($statsCreated === 0) {
                    throw new RuntimeException(sprintf(
                        'Tidak ada statistik dihasilkan dari %d baris terbaca (semua nol/kosong/junk) — import dibatalkan, tidak ada yang ditulis.',
                        count($rows),
                    ));
                }

                return [
                    'rows_read' => count($rows),
                    'skipped' => $skipped,
                    'stats' => $statsCreated,
                    'brands' => count($brandIds),
                    'models' => count($modelIds),
                    'types' => $typesCreated,
                ];
            });
        } catch (RuntimeException $e) {
            $this->error('Import dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['File', basename($filePath)],
                ['Periode', $month === null ? "tahunan {$year}" : "bulan {$month} tahun {$year}"],
                ['Baris dibaca', $summary['rows_read']],
                ['Baris dilewati (junk/kosong)', $summary['skipped']],
                ['Baris stats dibuat', $summary['stats']],
                ['Brand ter-match ke katalog', $summary['brands']],
                ['Model ter-match ke katalog', $summary['models']],
                ['Type baru dibuat', $summary['types']],
                ['Matcher: brand/model baru', $matcher->summary()['created_brands'].' / '.$matcher->summary()['created_models']],
            ]
        );

        $this->info('✓ Import CSV berhasil (stats periode terkait telah diganti).');

        return self::SUCCESS;
    }

    /** Tahun dari --year, else 4 digit pertama yang cocok di nama file. */
    protected function resolveYear(string $filePath): ?int
    {
        $option = $this->option('year');

        if ($option !== null && $option !== '') {
            return (int) $option;
        }

        return preg_match('/(\d{4})/', basename($filePath), $m) ? (int) $m[1] : null;
    }

    /**
     * Baca CSV (fgetcsv, baris pertama = header). Kembalikan
     * [list<array{brand, type_model, fuel, cells, units}>, jumlah junk].
     *
     * @return ?array{0: list<array{brand: string, type_model: string, fuel: ?string, cells: array<int, int>, units: ?int}>, 1: int}
     */
    protected function readCsv(string $filePath, ?int $month): ?array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $this->error("File tidak bisa dibuka: {$filePath}");

            return null;
        }

        $header = array_map(
            static fn (string $cell): string => mb_strtoupper(trim($cell)),
            fgetcsv($handle) ?: [],
        );

        if ($header !== []) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0]; // BOM
        }

        $idx = static fn (string $name): ?int => ($i = array_search($name, $header, true)) === false ? null : $i;

        $brandI = $idx('BRAND');
        $typeModelI = $idx('TYPE MODEL') ?? $idx('MODEL TYPE');
        $fuelI = $idx('FUEL');
        $unitsI = $idx('UNITS');

        $monthI = [];
        foreach (self::MONTH_COLUMNS as $position => $name) {
            $monthI[$position + 1] = $idx($name);
        }

        if ($brandI === null || $typeModelI === null) {
            fclose($handle);
            $this->error('Header CSV wajib memuat kolom BRAND dan TYPE MODEL.');

            return null;
        }

        if ($month === null && in_array(null, $monthI, true)) {
            fclose($handle);
            $this->error('Import tahunan butuh kolom JAN..DEC (file bulanan? gunakan --month).');

            return null;
        }

        if ($month !== null && $unitsI === null && $monthI[$month] === null) {
            fclose($handle);
            $this->error('Import bulanan butuh kolom UNITS atau kolom '.self::MONTH_COLUMNS[$month - 1].'.');

            return null;
        }

        $rows = [];
        $junkSkipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $brand = trim((string) ($row[$brandI] ?? ''));
            $typeModel = trim((string) ($row[$typeModelI] ?? ''));
            $fuelRaw = $fuelI === null ? null : trim((string) ($row[$fuelI] ?? ''));
            $fuel = ($fuelRaw === null || $fuelRaw === '' || $fuelRaw === '-') ? null : $fuelRaw;

            if ($brand === '' && $typeModel === '') {
                continue; // baris kosong
            }

            $normBrand = mb_strtoupper(preg_replace('/\s+/', ' ', $brand) ?? '');
            $normTypeModel = mb_strtoupper(preg_replace('/\s+/', ' ', $typeModel) ?? '');

            if (in_array($normBrand, ['TOTAL', 'CUMULATIVE'], true)
                || in_array($normTypeModel, ['TOTAL', 'CUMULATIVE'], true)) {
                $junkSkipped++;

                continue;
            }

            $cells = [];
            foreach ($monthI as $cellMonth => $cellIndex) {
                $cells[$cellMonth] = $cellIndex === null ? 0 : $this->parseUnits($row[$cellIndex] ?? null);
            }

            $rows[] = [
                'brand' => $brand,
                'type_model' => $typeModel,
                'fuel' => $fuel,
                'cells' => $cells,
                'units' => $unitsI === null ? null : $this->parseUnits($row[$unitsI] ?? null),
            ];
        }

        fclose($handle);

        return [$rows, $junkSkipped];
    }

    /** Sel bulan CSV: integer, atau "-"/kosong = nol. Tolerir pemisah ribuan. */
    protected function parseUnits(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return 0;
        }

        $value = str_replace([',', ' '], '', $value);

        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    /**
     * TypeVehicle untuk varian PENUH di bawah model (nama exact case-
     * insensitive); dibuat bila belum ada — type_charger wajib diisi []
     * (kolom JSON NOT NULL di skema existing).
     */
    protected function resolveType(int $modelId, string $typeName, int &$created): int
    {
        $typeName = trim($typeName);

        $existing = TypeVehicle::query()
            ->where('model_vehicle_id', $modelId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($typeName)])
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        $created++;

        return TypeVehicle::create([
            'model_vehicle_id' => $modelId,
            'name' => $typeName,
            'type_charger' => [], // kolom json NOT NULL di skema existing
        ])->id;
    }
}
