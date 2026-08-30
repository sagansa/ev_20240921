<?php

namespace App\Services;

use RuntimeException;

/**
 * Pembaca CSV penjualan GAIKINDO (kolom BRAND, TYPE MODEL, CC, TRANS, FUEL,
 * JAN..DEC, UNITS opsional) — dipakai bersama oleh import asli, preview
 * command, dan halaman admin Preview Impor. Murni parsing, tanpa efek DB.
 */
class VehicleSalesCsvReader
{
    /** @var list<string> */
    public const MONTH_COLUMNS = [
        'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
        'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC',
    ];

    /**
     * @return array{0: list<array{brand: string, type_model: string, fuel: ?string, cells: array<int, int>, units: ?int}>, 1: int}
     *
     * @throws RuntimeException bila header tidak memenuhi kebutuhan periode.
     */
    public function read(string $filePath, ?int $month): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException("File tidak bisa dibuka: {$filePath}");
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
            throw new RuntimeException('Header CSV wajib memuat kolom BRAND dan TYPE MODEL.');
        }

        if ($month === null && in_array(null, $monthI, true)) {
            fclose($handle);
            throw new RuntimeException('Import tahunan butuh kolom JAN..DEC (file bulanan? gunakan --month).');
        }

        if ($month !== null && $unitsI === null && $monthI[$month] === null) {
            fclose($handle);
            throw new RuntimeException('Import bulanan butuh kolom UNITS atau kolom '.self::MONTH_COLUMNS[$month - 1].'.');
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
    public function parseUnits(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return 0;
        }

        $value = str_replace([',', ' '], '', $value);

        return is_numeric($value) ? (int) round((float) $value) : 0;
    }
}
