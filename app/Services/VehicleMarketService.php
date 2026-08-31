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

    public function trend(?int $year = null, ?string $brand = null, ?string $model = null): array
    {
        // Default: tahun terbaru yang BENAR-BENAR punya baris bulanan (di
        // scope filter bila ada) — bukan now()->year yang bisa kosong.
        $year ??= $this->latestMonthlyYear($brand, $model);
        $key = "trend:{$year}:" . ($brand ?? '-') . ':' . ($model ?? '-');

        return $this->cached($key, function () use ($year, $brand, $model) {
            $query = VehicleSalesStat::query()
                ->latestImports()
                ->monthly()
                ->where('year', $year);
            if ($brand !== null) {
                $query->where('raw_brand', $brand);
            }
            if ($model !== null) {
                $query->where('raw_model', $model);
            }
            $monthly = $query
                ->selectRaw('month, powertrain, SUM(units) as units')
                ->groupBy('month', 'powertrain')
                ->get();

            // Total resmi GAIKINDO hanya valid utk scope NASIONAL — saat
            // difilter per brand/model, market_total = hasil parse.
            $officialMonths = ($brand === null && $model === null)
                ? ($this->officialTotalsByYear()[$year]['months'] ?? [])
                : [];

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

    /** Tahun terbaru yang punya baris bulanan, opsional terbatas brand/model. */
    protected function latestMonthlyYear(?string $brand, ?string $model): int
    {
        $query = VehicleSalesStat::query()->latestImports()->monthly();
        if ($brand !== null) {
            $query->where('raw_brand', $brand);
        }
        if ($model !== null) {
            $query->where('raw_model', $model);
        }

        return (int) ($query->max('year') ?: now()->year);
    }

    public function top(?int $year = null, ?string $powertrain = null, ?string $brand = null, int $limit = 10): array
    {
        // Default: tahun terbaru yang punya baris LEVEL MODEL — tahun yang
        // hanya berisi rekap nasional menghasilkan ranking kosong.
        $year ??= $this->latestModelYear($brand);
        $powertrain = strtoupper($powertrain ?? 'BEV');
        $key = "top:{$year}:{$powertrain}:" . ($brand ?? '-') . ":{$limit}";

        return $this->cached($key, function () use ($year, $powertrain, $brand, $limit) {
            $base = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->powertrain($powertrain);
            if ($brand !== null) {
                $base->where('raw_brand', $brand);
            }

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

    /** Tahun terbaru yang punya baris level model; fallback tahun apa pun. */
    protected function latestModelYear(?string $brand): int
    {
        $query = VehicleSalesStat::query()
            ->latestImports()
            ->whereNull('month')
            ->whereNotNull('model_vehicle_id');
        if ($brand !== null) {
            $query->where('raw_brand', $brand);
        }
        $found = $query->max('year');

        return (int) ($found ?: VehicleSalesStat::query()->latestImports()->max('year'));
    }

    /**
     * Peta brand → model khusus kendaraan listrik (BEV / PHEV) untuk filter & katalog.
     * Brand diurutkan berdasarkan penjualan EV tahun sebelumnya (fallback tahun berjalan).
     */
    public function catalog(?int $year = null): array
    {
        $year ??= (int) VehicleSalesStat::query()->latestImports()->whereNull('month')->max('year');
        $prevYear = $year - 1;

        return $this->cached("catalog:v4:{$year}", function () use ($year, $prevYear) {
            // Hanya model & brand yang memiliki tipe EV (BEV/PHEV)
            $rows = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->whereIn('powertrain', ['BEV', 'PHEV'])
                ->selectRaw('raw_brand, raw_model, SUM(units) as units, MAX(model_vehicle_id) as model_vehicle_id')
                ->groupBy('raw_brand', 'raw_model')
                ->orderBy('raw_brand')
                ->orderByDesc('units')
                ->get();

            // Lookup category/size_class dari model_vehicles untuk setiap model ter-link
            $modelIds = $rows->pluck('model_vehicle_id')->filter()->unique()->values();
            $categoryById = collect();
            if ($modelIds->isNotEmpty()) {
                $categoryById = \App\Models\ModelVehicle::query()
                    ->whereIn('id', $modelIds)
                    ->get(['id', 'category', 'size_class'])
                    ->keyBy('id');
            }

            // Ambil penjualan tahun sebelumnya untuk pengurutan
            $prevYearSales = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $prevYear)
                ->whereIn('powertrain', ['BEV', 'PHEV'])
                ->selectRaw('raw_brand, SUM(units) as units')
                ->groupBy('raw_brand')
                ->pluck('units', 'raw_brand')
                ->map(fn ($u) => (int) $u)
                ->all();

            $brands = [];
            foreach ($rows as $row) {
                if (! isset($brands[$row->raw_brand])) {
                    $brands[$row->raw_brand] = [
                        'brand' => $row->raw_brand,
                        'units' => 0,
                        'prev_units' => $prevYearSales[$row->raw_brand] ?? 0,
                        'models' => [],
                    ];
                }
                $brands[$row->raw_brand]['units'] += (int) $row->units;
                $linked = $row->model_vehicle_id ? $categoryById->get($row->model_vehicle_id) : null;
                $brands[$row->raw_brand]['models'][] = [
                    'model' => $row->raw_model,
                    'units' => (int) $row->units,
                    'category' => $linked?->category,
                    'size_class' => $linked?->size_class,
                ];
            }

            // Urutkan brand berdasarkan penjualan tahun sebelumnya desc (fallback penjualan tahun ini)
            $sortedBrands = collect($brands)
                ->sort(function ($a, $b) {
                    if ($b['prev_units'] !== $a['prev_units']) {
                        return $b['prev_units'] <=> $a['prev_units'];
                    }
                    return $b['units'] <=> $a['units'];
                })
                ->values()
                ->all();

            return [
                'year' => $year,
                'brands' => $sortedBrands,
            ];
        });
    }

    /**
     * Histori penjualan per tahun untuk satu model kendaraan spesifik (brand + model),
     * khusus powertrain EV (BEV / PHEV).
     */
    public function modelHistory(string $brand, string $model): array
    {
        $key = 'model-history:v2:' . rawurlencode($brand) . ':' . rawurlencode($model);

        return $this->cached($key, function () use ($brand, $model) {
            $rows = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('raw_brand', $brand)
                ->where('raw_model', $model)
                ->whereIn('powertrain', ['BEV', 'PHEV'])
                ->selectRaw('year, powertrain, SUM(units) as units')
                ->groupBy('year', 'powertrain')
                ->orderBy('year')
                ->get();

            $years = [];
            foreach ($rows as $row) {
                $years[] = [
                    'year' => (int) $row->year,
                    'powertrain' => $row->powertrain,
                    'units' => (int) $row->units,
                ];
            }

            $totalUnits = (int) $rows->sum('units');

            return [
                'brand' => $brand,
                'model' => $model,
                'total_units' => $totalUnits,
                'years' => $years,
            ];
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
