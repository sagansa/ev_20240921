<?php

namespace App\Services;

use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Support\Facades\Cache;

/**
 * Agregasi data pasar kendaraan untuk API publik /vehicle-market/*.
 *
 * Cache 24 jam dengan version-key: importer menaikkan versi lewat flush()
 * sehingga angka langsung segar setelah import baru tanpa menunggu TTL.
 */
class VehicleMarketService
{
    public const TTL = 86400;

    public function summary(): array
    {
        return $this->cached('summary', function () {
            $annual = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->selectRaw('year, powertrain, SUM(units) as units')
                ->groupBy('year', 'powertrain')
                ->get();

            $official = $this->officialTotalsByYear();
            $monthCoverage = $this->monthCoverageByYear();

            $years = [];
            foreach ($annual->pluck('year')->unique()->sort()->values() as $year) {
                $byPower = $annual->where('year', $year);
                $bev = (int) $byPower->where('powertrain', 'BEV')->sum('units');
                $phev = (int) $byPower->where('powertrain', 'PHEV')->sum('units');
                $hev = (int) $byPower->where('powertrain', 'HEV')->sum('units');
                $ice = (int) $byPower->where('powertrain', 'ICE')->sum('units');
                $parsed = $bev + $phev + $hev + $ice;
                $officialTotal = $official[$year]['total'] ?? null;
                $base = $officialTotal ?? $parsed;

                $years[] = [
                    'year' => (int) $year,
                    'total_units' => $parsed,
                    'official_total' => $officialTotal,
                    'bev_units' => $bev,
                    'phev_units' => $phev,
                    'hev_units' => $hev,
                    'ice_units' => $ice,
                    'bev_share' => $base > 0 ? round($bev / $base, 4) : null,
                    'is_full_year' => ($monthCoverage[$year] ?? 0) >= 12,
                ];
            }

            // Ringkasan tahun terbaru + pertumbuhan YoY (hanya bila tahun penuh —
            // membandingkan tahun parsial dengan tahun penuh menyesatkan).
            $latest = null;
            if ($years !== []) {
                $latestYear = $years[count($years) - 1];
                $prevIndex = count($years) - 2;
                $growth = null;
                if ($prevIndex >= 0 && $latestYear['is_full_year'] && $latestYear['bev_units'] > 0 && $years[$prevIndex]['bev_units'] > 0) {
                    $growth = round(
                        ($latestYear['bev_units'] - $years[$prevIndex]['bev_units']) / $years[$prevIndex]['bev_units'],
                        4
                    );
                }
                $latest = [
                    'year' => $latestYear['year'],
                    'bev_units' => $latestYear['bev_units'],
                    'bev_share' => $latestYear['bev_share'],
                    'bev_yoy_growth' => $growth,
                    'is_full_year' => $latestYear['is_full_year'],
                ];
            }

            return ['years' => $years, 'latest' => $latest];
        });
    }

    public function trend(int $year): array
    {
        return $this->cached("trend:{$year}", function () use ($year) {
            $monthly = VehicleSalesStat::query()
                ->latestImports()
                ->monthly()
                ->where('year', $year)
                ->selectRaw('month, powertrain, SUM(units) as units')
                ->groupBy('month', 'powertrain')
                ->get();

            $officialMonths = $this->officialTotalsByYear()[$year]['months'] ?? [];

            $months = [];
            foreach (range(1, 12) as $m) {
                $byPower = $monthly->where('month', $m);
                $bev = (int) $byPower->where('powertrain', 'BEV')->sum('units');
                $phev = (int) $byPower->where('powertrain', 'PHEV')->sum('units');
                $hev = (int) $byPower->where('powertrain', 'HEV')->sum('units');
                $ice = (int) $byPower->where('powertrain', 'ICE')->sum('units');
                if ($bev + $phev + $hev + $ice === 0 && ! isset($officialMonths[$m])) {
                    continue;
                }
                $months[] = [
                    'month' => $m,
                    'bev_units' => $bev,
                    'phev_units' => $phev,
                    'hev_units' => $hev,
                    'ice_units' => $ice,
                    'market_total' => $officialMonths[$m] ?? ($bev + $phev + $hev + $ice),
                ];
            }

            return ['year' => $year, 'months' => $months];
        });
    }

    public function top(?int $year, ?string $powertrain, int $limit = 10): array
    {
        $year ??= (int) VehicleSalesStat::query()->max('year');
        $powertrain = strtoupper($powertrain ?? 'BEV');

        return $this->cached("top:{$year}:{$powertrain}:{$limit}", function () use ($year, $powertrain, $limit) {
            $base = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->powertrain($powertrain);

            $brands = (clone $base)
                ->selectRaw('raw_brand as brand, SUM(units) as units, COUNT(DISTINCT model_vehicle_id) as models')
                ->groupBy('raw_brand')
                ->orderByDesc('units')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['brand' => $r->brand, 'units' => (int) $r->units, 'models' => (int) $r->models])
                ->values();

            $models = (clone $base)
                ->selectRaw('raw_brand as brand, raw_model as model, model_vehicle_id, SUM(units) as units')
                ->groupBy('raw_brand', 'raw_model', 'model_vehicle_id')
                ->orderByDesc('units')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => [
                    'brand' => $r->brand,
                    'model' => $r->model,
                    'model_vehicle_id' => $r->model_vehicle_id,
                    'units' => (int) $r->units,
                ])
                ->values();

            return ['year' => $year, 'powertrain' => $powertrain, 'brands' => $brands, 'models' => $models];
        });
    }

    /** Naikkan versi cache — dipanggil importer setelah import baru. */
    public function flush(): void
    {
        Cache::increment($this->versionKey()) || Cache::put($this->versionKey(), (int) (Cache::get($this->versionKey()) ?: 1) + 1, now()->addYear());
    }

    /** @return array<int, array{total: int, months: array<int, int>}> */
    protected function officialTotalsByYear(): array
    {
        $out = [];
        SalesImport::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('sales_imports')->groupBy('year');
            })
            ->get()
            ->each(function (SalesImport $import) use (&$out) {
                $meta = $import->meta ?? [];
                $grand = $meta['official']['grand'] ?? null;
                if ($grand === null) {
                    return;
                }
                $out[$import->year] = [
                    'total' => (int) ($grand['total'] ?? 0),
                    'months' => collect($grand['months'] ?? [])
                        ->map(fn ($u) => (int) $u)
                        ->reject(fn ($u) => $u === 0)
                        ->all(),
                ];
            });

        return $out;
    }

    /** @return array<int, int> jumlah bulan berisi data per tahun */
    protected function monthCoverageByYear(): array
    {
        return VehicleSalesStat::query()
            ->latestImports()
            ->monthly()
            ->selectRaw('year, COUNT(DISTINCT month) as months')
            ->where('units', '>', 0)
            ->groupBy('year')
            ->pluck('months', 'year')
            ->map(fn ($m) => (int) $m)
            ->all();
    }

    protected function cached(string $key, \Closure $fn): array
    {
        $version = Cache::rememberForever($this->versionKey(), fn () => 1);

        return Cache::remember("vehicle-market:v{$version}:{$key}", self::TTL, $fn);
    }

    protected function versionKey(): string
    {
        return 'vehicle-market:version';
    }
}
