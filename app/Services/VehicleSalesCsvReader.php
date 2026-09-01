<?php

namespace App\Services;

use App\Models\BrandVehicle;
use RuntimeException;
use Throwable;

/**
 * Pembaca CSV penjualan GAIKINDO (kolom BRAND, TYPE MODEL, CC, TRANS, FUEL,
 * JAN..DEC, UNITS opsional) — dipakai bersama oleh import asli, preview
 * command, dan halaman admin Preview Impor. Murni parsing, tanpa efek DB.
 *
 * Dua format header diterima:
 *  - terpisah: BRAND, TYPE MODEL (atau MODEL TYPE)
 *  - gabungan: satu kolom "BRAND MODEL TYPE" berisi nama lengkap
 *    (mis. "MERCEDES BENZ PC CLA 200") — dipecah memakai daftar nama brand
 *    (katalog + alias matcher, prefix terpanjang menang).
 */
class VehicleSalesCsvReader
{
    public function __construct(
        /** @var list<string>|null nama brand tambahan utk pemecah kolom gabungan */
        protected ?array $brandNames = null,
    ) {
    }

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
        $combinedI = $idx('BRAND MODEL TYPE');
        $fuelI = $idx('FUEL');
        $unitsI = $idx('UNITS');

        $monthI = [];
        foreach (self::MONTH_COLUMNS as $position => $name) {
            $monthI[$position + 1] = $idx($name);
        }

        if ($combinedI === null && ($brandI === null || $typeModelI === null)) {
            fclose($handle);
            throw new RuntimeException('Header CSV wajib memuat kolom BRAND + TYPE MODEL, atau satu kolom gabungan BRAND MODEL TYPE.');
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
            if ($combinedI !== null && ($brandI === null || $typeModelI === null)) {
                [$brand, $typeModel] = $this->splitCombined($row[$combinedI] ?? '');
            } else {
                $brand = trim((string) ($row[$brandI] ?? ''));
                $typeModel = trim((string) ($row[$typeModelI] ?? ''));
            }
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

    /** @var list<string>|null cache kandidat nama brand (ternormalisasi, terpanjang dulu) */
    protected ?array $splitCandidates = null;

    /**
     * Pecah "BRAND MODEL TYPE" jadi [brand, type_model]: cari nama brand
     * yang paling panjang sebagai prefix (kandidat: katalog DB + alias
     * matcher + override konstruktor). Tanpa kecocokan → brand = token pertama.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitCombined(mixed $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return ['', ''];
        }

        $norm = $this->normalizeForSplit($raw);
        $normTokens = preg_split('/\s+/', $norm) ?: [];
        $rawTokens = null;
        $best = null;

        foreach ($this->splitCandidates() as $candidate) {
            $candTokens = preg_split('/\s+/', $candidate) ?: [];
            $count = count($candTokens);

            if ($count === 0 || count($normTokens) < $count) {
                continue;
            }

            $head = implode(' ', array_slice($normTokens, 0, $count));

            if ($head !== $candidate) {
                continue;
            }

            $rawTokens ??= preg_split('/\s+/', $raw) ?: [];

            // Satu token raw bisa menyumbang >1 token normal (mis. "MERCEDES-
            // BENZ" → 2 token) — hitung kontribusinya, jangan cuma 1 per sel.
            $consumed = 0;
            $seen = 0;

            foreach ($rawTokens as $token) {
                $consumed++;
                $seen += count(array_filter(
                    preg_split('/\s+/', (string) preg_replace('/[^A-Z0-9]+/u', ' ', mb_strtoupper($token))) ?: [],
                    fn (string $t): bool => $t !== '',
                ));

                if ($seen >= $count) {
                    break;
                }
            }

            $brand = implode(' ', array_slice($rawTokens, 0, $consumed));
            $rest = implode(' ', array_slice($rawTokens, $consumed));
            $best ??= [$brand, $rest];

            // Lewati kandidat yang sisanya diawari angka murni/desimal
            // ("TOYOTA VIOS 1.5 E" → jangan potong di "TOYOTA VIOS"; "LEXUS
            // ES 300 h" → jangan potong di "LEXUS ES"). Kode model berhuruf
            // di depan (C180, H6) TIDAK termasuk — brand terpanjang tetap
            // menang. Tanpa alternatif, pakai kandidat terpanjang.
            $restHead = preg_split('/\s+/', $this->normalizeForSplit($rest)) ?: [];
            $restFirst = $restHead[0] ?? '';

            if ($restFirst !== '' && preg_match('/^\d+([.,]\d+)?$/', $restFirst) === 1) {
                continue;
            }

            return [$brand, $rest];
        }

        if ($best !== null) {
            return $best;
        }

        $space = strpos($raw, ' ');

        if ($space === false) {
            return [$raw, ''];
        }

        return [trim(mb_substr($raw, 0, $space)), trim(mb_substr($raw, $space + 1))];
    }

    /** @return list<string> */
    protected function splitCandidates(): array
    {
        if ($this->splitCandidates !== null) {
            return $this->splitCandidates;
        }

        $names = $this->brandNames ?? [];

        // Kandidat lengkap: kunci & nilai alias matcher + nama brand katalog.
        // Kunci alias mencakup konvensi kolom brand GAIKINDO terbaru yang
        // berpola brand+model (TOYOTA VIOS, HONDA CITY) — pemecah mengikuti
        // pemisahan kolom asli, matcher mengkanonikalisasi lanjut.
        foreach (array_merge(
            array_keys(VehicleSalesMatcher::BRAND_ALIASES),
            array_values(VehicleSalesMatcher::BRAND_ALIASES),
            array_values(VehicleSalesMatcher::BRAND_CONTAINS_ALIASES),
        ) as $name) {
            $names[] = $name;
        }

        try {
            foreach (BrandVehicle::query()->pluck('name') as $name) {
                $names[] = $name;
            }
        } catch (Throwable) {
            // Lingkungan tanpa DB (uji parsing murni) — alias const saja.
        }

        $candidates = array_filter(array_unique(array_map(
            fn (string $name): string => $this->normalizeForSplit($name),
            $names,
        )), fn (string $c): bool => $c !== '');
        $candidates = array_values($candidates);
        usort($candidates, fn (string $a, string $b): int => mb_strlen($b) - mb_strlen($a));

        return $this->splitCandidates = $candidates;
    }

    protected function normalizeForSplit(string $value): string
    {
        $upper = mb_strtoupper(trim($value));

        return mb_strtoupper(preg_replace('/[^A-Z0-9]+/u', ' ', $upper) ?? $upper);
    }
}
