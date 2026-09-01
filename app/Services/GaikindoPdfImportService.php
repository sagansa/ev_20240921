<?php

namespace App\Services;

use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/**
 * Import PDF wholesales GAIKINDO (file asli dari GAIKINDO) — versi GRID-STATIS.
 *
 * Template cetak GAIKINDO memiliki grid kolom bulan yang MENERUS menembus
 * semua kategori (tick vertikal count penuh) dan header terulang per kategori.
 * Maka: batas interval kolom bulan diderive sekali per halaman dari median
 * posisi token bulan, lalu tiap baris teks diiris per interval — tanpa lagi
 * deteksi header yang rawan salah. Nilai kerning ("4 .123") dirapikan dengan
 * membuang spasi; total YTD/tahunan diambil dari interval setelah DEC.
 *
 * Guard identik importer xlsx: tolak bila rekap resmi tak terbaca / hasil
 * parse >110% dari resmi / raw_brand tercemar artefak.
 */
class GaikindoPdfImportService
{
    protected VehicleSalesMatcher $matcher;

    protected int $importYear;

    public function importFromFile(string $pdfPath, ?int $year = null, string $source = 'gaikindo-pdf'): array
    {
        if (! file_exists($pdfPath)) {
            throw new \InvalidArgumentException("File tidak ditemukan: {$pdfPath}");
        }

        $this->importYear = $year ?? $this->detectYear($pdfPath);
        $this->matcher = new VehicleSalesMatcher;

        $pages = $this->extractRows($pdfPath);

        $parsedRows = [];
        $official = [];
        $pageSummaries = [];

        foreach ($pages as $page) {
            $result = $this->parsePage($page, $pageSummaries);
            $parsedRows = array_merge($parsedRows, $result['rows']);
            foreach ($result['official'] as $key => $value) {
                $official[$key] = $value;
            }
        }

        if ($parsedRows === []) {
            throw new \RuntimeException('Tidak ada baris model terparse dari PDF — layout tidak dikenali.');
        }

        $parsedTotal = array_sum(array_column($parsedRows, 'total'));
        $officialTotal = $official['grand']['total'] ?? null;
        if ($officialTotal === null || $officialTotal <= 0) {
            throw new \RuntimeException('Rekap resmi (DOMESTIC SALES TOTAL) tidak terbaca dari PDF — import dibatalkan.');
        }
        $coverage = round($parsedTotal / $officialTotal, 4);
        if ($coverage > 1.10) {
            throw new \RuntimeException(sprintf(
                'Hasil parse %.1f%% dari total resmi (%s vs %s) — PDF gagal direkonstruksi faithful. Import dibatalkan.',
                $coverage * 100,
                number_format($parsedTotal),
                number_format($officialTotal),
            ));
        }

        $status = 'processed';
        $warnings = [];
        if ($coverage < 0.9) {
            $status = 'partial';
            $warnings[] = sprintf('Coverage %.1f%% < 90%%.', $coverage * 100);
        }

        $dirtyBrands = collect($parsedRows)
            ->filter(fn ($row) => preg_match('/CC\s*[<≤>]|^[A-Z]{1,2}\d|\d{1,3}[.,]\d{3}/', $this->matcher->normalize($row['brand'])))
            ->count();
        if ($dirtyBrands > max(10, intdiv(count($parsedRows), 20))) {
            $status = 'partial';
            $warnings[] = sprintf('%d/%d baris raw_brand tercemar artefak konversi.', $dirtyBrands, count($parsedRows));
        }

        return DB::connection($this->connectionName())->transaction(function () use ($pdfPath, $source, $parsedRows, $official, $coverage, $warnings, $pageSummaries, $status, $parsedTotal, $officialTotal) {
            $import = SalesImport::create([
                'file_name' => basename($pdfPath),
                'source' => $source,
                'year' => $this->importYear,
                'period_start' => sprintf('%04d-01-01', $this->importYear),
                'period_end' => sprintf('%04d-12-01', $this->importYear),
                'status' => $status,
                'meta' => [
                    'format' => 'pdf',
                    'pages' => $pageSummaries,
                    'official' => $official,
                    'parsed_total' => $parsedTotal,
                    'official_total' => $officialTotal,
                    'coverage' => $coverage,
                    'warnings' => array_values(array_unique($warnings)),
                ],
            ]);

            $statCount = $this->persistStats($import->id, $parsedRows);

            app(VehicleMarketService::class)->flush();

            return [
                'import_id' => $import->id,
                'year' => $this->importYear,
                'status' => $status,
                'model_rows' => count($parsedRows),
                'stat_rows' => $statCount,
                'parsed_total' => $parsedTotal,
                'official_total' => $officialTotal,
                'coverage' => $coverage,
                'official' => $official,
                'matcher' => $this->matcher->summary(),
                'warnings' => $warnings,
            ];
        });
    }

    // ------------------------------------------------------------- ekstraksi

    /** Jalankan skrip python rekonstruksi grid dan kembalikan daftar halaman. */
    protected function extractRows(string $pdfPath): array
    {
        $script = base_path('scripts/gaikindo_pdf_rows.py');
        $result = Process::timeout(600)->run(['python3', $script, $pdfPath]);

        if (! $result->successful()) {
            throw new \RuntimeException('Rekonstruksi PDF gagal (python3 + pdfplumber dibutuhkan): '.mb_substr($result->errorOutput(), 0, 300));
        }

        $json = json_decode($result->output(), true);
        if (! is_array($json) || ! isset($json['pages'])) {
            throw new \RuntimeException('Output rekonstruksi PDF tidak valid.');
        }

        return $json['pages'];
    }

    /**
     * Parse satu halaman hasil rekonstruksi grid-statis.
     *
     * @return array{rows: array<int, array>, official: array<string, array>}
     */
    protected function parsePage(array $page, array &$pageSummaries): array
    {
        $bounds = $page['month_bounds'] ?? [];
        if ($bounds === []) {
            return ['rows' => [], 'official' => []]; // halaman tanpa grid bulan
        }

        $gates = ['summary' => 0, 'no_brand' => 0, 'no_model' => 0, 'zero' => 0, 'accepted' => 0];
        $parsedRows = [];
        $official = [];
        $currentSegment = null;
        $currentBrand = null;

        foreach ($page['rows'] as $row) {
            $leftCells = $row['left_cells'] ?? [];
            $months = array_map(fn ($v) => $v ?? 0, $row['months'] ?? []);
            $ytd = $row['ytd'] ?? null;

            [$label, $brandCell, $model, $leadingSegment] = $this->splitLabel($leftCells);
            if ($leadingSegment !== null) {
                $currentSegment = $leadingSegment;
            }

            if ($label === '' || preg_match('/CUMULATIVE/i', $label)) {
                $gates['summary']++;

                continue;
            }

            // Rekap resmi: label kiri + 12 bulan + total YTD exact dari PDF.
            $type = null;
            if (preg_match('/DOMESTIC.*(TOTAL|SALES)|TOTAL.*DOMESTIC|GRAND\s*TOTAL/i', $label)) {
                $type = 'grand';
            } elseif (preg_match('/PASSENGER.*(CAR|TOTAL)/i', $label) && ! str_contains(mb_strtoupper($label), 'COMMERCIAL')) {
                $type = 'passenger';
            } elseif (preg_match('/COMMERCIAL.*(VEHICLE|TOTAL)/i', $label)) {
                $type = 'commercial';
            }
            if ($type !== null && ! isset($official[$type])) {
                $candidate = $this->officialFromMonths($months, $ytd);
                if ($candidate !== null) {
                    $official[$type] = ['label' => mb_substr($label, 0, 80)] + $candidate;
                }
                $gates['summary']++;

                continue;
            }

            if (preg_match('/^(TOTAL|CUM|JUMLAH|PASSENGER|COMMERCIAL|DOMESTIC|GRAND|©|BRAND|TYPE)/i', $label)) {
                $gates['summary']++;

                continue;
            }

            // Brand PDF di-merge vertikal: hanya baris pertama memuat sel brand.
            if ($brandCell !== null) {
                $brand = trim(preg_replace('/(?:^|\s)-+(?=\s|$)/', ' ', $brandCell) ?? '');
                if ($brand !== '') {
                    $currentBrand = $brand;
                }
            } else {
                $brand = $currentBrand ?? '';
            }

            $model = trim(preg_replace('/(?:^|\s)-+(?=\s|$)/', ' ', $model) ?? '');
            if ($brand === '') {
                $gates['no_brand']++;

                continue;
            }
            if ($model === '') {
                $gates['no_model']++;

                continue;
            }

            $total = $ytd ?? array_sum($months);
            if ($total === 0 && array_sum($months) === 0) {
                $gates['zero']++;

                continue;
            }
            $gates['accepted']++;

            $modelText = trim($model);
            $parsedRows[] = [
                'brand' => $brand,
                'model' => $model,
                'segment' => $currentSegment,
                'powertrain' => $this->classifyPowertrain($modelText),
                'kwh' => VehicleSalesMatcher::extractKwh($modelText),
                'months' => $months,
                'total' => $total,
            ];
        }

        $pageSummaries[] = ['rows' => count($parsedRows), 'gates' => $gates];

        return ['rows' => $parsedRows, 'official' => $official];
    }

    /**
     * Pisahkan sel kiri menjadi [label, brandSel|NULL, model, segmenJudul].
     * Baris pertama tiap segmen membawa sel judul (SEDAN / CC < 1.500 / …)
     * menempel di depan; sel spesifikasi diputus lewat gap/keyword.
     *
     * @param array<int, array{x: float, text: string}> $cells
     *
     * @return array{0: string, 1: string|null, 2: string, 3: string|null}
     */
    protected function splitLabel(array $cells): array
    {
        $textCells = [];
        foreach ($cells as $cell) {
            $text = trim($cell['text']);
            if ($text !== '') {
                $textCells[] = ['x' => (float) $cell['x'], 'text' => $text];
            }
        }
        if ($textCells === []) {
            return ['', null, '', null];
        }

        $leadingSegment = null;
        $start = 0;
        while ($start < count($textCells) && $start < 4) {
            $text = $textCells[$start]['text'];
            if (preg_match('/^\d{1,3}[.)]?$/', $text)) {
                $start++; // kolom NO
                continue;
            }
            $segment = $this->leadingSegment($text);
            if ($segment !== null) {
                $leadingSegment = $segment;
                $start++;
                continue;
            }
            if (preg_match('/^CC\b/i', $text) || preg_match('/^[<≤>]\s*\d/', $text)) {
                $start++;
                continue;
            }
            break;
        }

        $brandCell = null;
        $modelIndex = null;
        foreach ($textCells as $i => $cell) {
            if ($i < $start) {
                continue;
            }
            $upper = mb_strtoupper($cell['text']);
            if (in_array($upper, ['BRAND', 'TYPE'], true)) {
                continue; // header kolom tersisa di baris header
            }
            $brandCell = $cell['text'];
            $modelIndex = $i + 1;

            break;
        }

        $modelParts = [];
        if ($modelIndex !== null) {
            for ($i = $modelIndex; $i < count($textCells); $i++) {
                $cell = $textCells[$i];
                if ($i > $modelIndex
                    && ($cell['x'] - ($textCells[$i - 1]['x1'] ?? $cell['x']) > 40
                        || preg_match('/^\d/', trim($cell['text']))
                        || in_array(mb_strtoupper(trim($cell['text'])), ['AT', 'MT', 'CBU', 'CKD', 'FF', 'AWD', 'INA', 'CHINA', 'JAPANG', 'THAILAND', 'KOREA', 'SWEDIA', 'JERMAN', 'USA'], true))) {
                    break;
                }
                $modelParts[] = trim($cell['text']);
            }
        }

        $model = trim(implode(' ', $modelParts));
        $label = trim(implode(' ', array_map(fn ($c) => $c['text'], $textCells)));

        return [$label, $brandCell, $model, $leadingSegment];
    }

    /** Kenali kata judul section di sel terdepan → nama segmen. */
    protected function leadingSegment(string $text): ?string
    {
        $t = mb_strtoupper(trim(preg_replace('/^\d+\.\s*/', '', $text) ?? ''));
        $map = [
            'SEDAN' => 'Sedan', '4X2' => '4X2', '4X4' => '4X4', 'KBM' => 'KBM',
            'PICK' => 'PU/Truck', 'TRUCK' => 'PU/Truck', 'DOUBLE' => 'Double Cabin',
            'AFFORD' => 'LCGC', 'LCGC' => 'LCGC', 'BUS' => 'Bus', 'MPV' => 'MPV', 'SUV' => 'SUV',
        ];
        foreach ($map as $needle => $segment) {
            if (str_starts_with($t, $needle)) {
                return $segment;
            }
        }

        return null;
    }

    /**
     * Rekap resmi dari 12 bulan + total YTD exact (kolom kanan PDF).
     * Prioritas total: YTD; fallback: batas rasio-jumlah; terakhir: jumlah.
     *
     * @param array<int, int> $months
     *
     * @return array{total: int, months: array<int, int>}|null
     */
    protected function officialFromMonths(array $months, ?int $ytd): ?array
    {
        $values = array_values($months);
        $nonZero = array_values(array_filter($values, fn ($v) => $v > 0));
        $clean = [];
        foreach ($values as $i => $v) {
            if ($v > 0) {
                $clean[$i + 1] = $v;
            }
        }

        if ($ytd !== null && $ytd >= 1000 && $ytd <= 50_000_000) {
            return ['total' => $ytd, 'months' => $clean];
        }

        if (count($nonZero) === 1) {
            $total = $nonZero[0];

            return ($total >= 1000 && $total <= 50_000_000)
                ? ['total' => $total, 'months' => []]
                : null;
        }

        $sumBefore = 0;
        $totalIndex = null;
        foreach ($values as $i => $value) {
            if ($i >= 4 && $sumBefore > 0 && $value >= $sumBefore - intdiv($sumBefore, 50)) {
                $totalIndex = $i;
                break;
            }
            $sumBefore += $value;
        }
        if ($totalIndex !== null && $totalIndex >= 4) {
            $total = $values[$totalIndex];
            if ($total >= 1000 && $total <= 50_000_000) {
                $clean = [];
                foreach (array_slice($values, 0, $totalIndex) as $i => $unit) {
                    $clean[$i + 1] = $unit;
                }

                return ['total' => $total, 'months' => $clean];
            }
        }

        $total = array_sum($nonZero);

        return $total >= 1000 ? ['total' => $total, 'months' => $clean] : null;
    }

    /** Klasifikasi powertrain dari sinyal nama model. */
    protected function classifyPowertrain(string $modelText): string
    {
        if (preg_match('/\bPHEV\b|REEV|\bSHS\b/i', $modelText)) {
            return 'PHEV';
        }
        if (preg_match('/\bHEV\b|HYBRID/i', $modelText)) {
            return 'HEV';
        }
        if (preg_match('/\bBEV\b|\bEV\b/i', $modelText)) {
            return 'BEV';
        }

        $patterns = [
            '/^AIR\s*EV/i', '/BINGUO/i', '/^ATTO/i', '/^SEAL(ION)?/i', '/DOLPHIN/i',
            '/^M6\b/i', '/^IONIQ/i', '/KONA\s*ELEC/i', '/^EV6/i', '/^BZ4X/i',
            '/^EX[0-9]/i', '/^J5\b/i', '/^E-?C3/i', '/^AION/i', '/^NETA/i',
            '/^MG4|^S5\s*EV|^S5\s*BEV/i', '/DARION/i', '/EKSION/i', '/MITRA\s*EV/i',
            '/^ICAR/i', '/OMODA\s*E/i', '/HYPTEC/i', '/ZEEKR/i', '/^E-?T6/i',
            '/^GELORA/i', '/^EC3[56]/i', '/VINFAST/i', '/^DENZA/i', '/^XEV/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $modelText)) {
                return 'BEV';
            }
        }

        return 'ICE';
    }

    // --------------------------------------------------------------- persist

    protected function persistStats(int $importId, array $rows): int
    {
        $now = now()->format('Y-m-d H:i:s');
        $batch = [];
        $count = 0;
        $matchCache = [];

        foreach ($rows as $row) {
            $key = $row['brand'].'||'.$row['model'];
            if (! isset($matchCache[$key])) {
                $matchCache[$key] = $this->matcher->match($row['brand'], $row['model'], $row['kwh']);
            }
            $match = $matchCache[$key];

            $base = [
                'sales_import_id' => $importId,
                'raw_brand' => mb_substr($row['brand'], 0, 255),
                'raw_model' => mb_substr($row['model'], 0, 255),
                'brand_vehicle_id' => $match['brand_vehicle_id'],
                'model_vehicle_id' => $match['model_vehicle_id'],
                'segment' => $row['segment'],
                'powertrain' => $row['powertrain'],
                'year' => $this->importYear,
                'origin_country' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($row['months'] as $month => $units) {
                if ($units == 0) {
                    continue;
                }
                $batch[] = $base + ['month' => $month, 'units' => $units];
                $count++;
            }
            $batch[] = $base + ['month' => null, 'units' => $row['total']];
            $count++;

            if (count($batch) >= 500) {
                $this->insertStats($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->insertStats($batch);
        }

        return $count;
    }

    protected function insertStats(array $batch): void
    {
        VehicleSalesStat::query()->getConnection()
            ->table((new VehicleSalesStat)->getTable())
            ->insert($batch);
    }

    protected function detectYear(string $filePath): int
    {
        if (preg_match('/(20\d{2})/', basename($filePath), $m)) {
            return (int) $m[1];
        }

        throw new \InvalidArgumentException('Tahun tidak terdeteksi dari nama file '.$filePath.' — gunakan --year=YYYY.');
    }

    protected function connectionName(): string
    {
        return app()->environment('testing') ? config('database.default') : 'ev';
    }
}
