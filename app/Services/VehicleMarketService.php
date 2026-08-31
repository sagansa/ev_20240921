<?php

namespace App\Services;

use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use App\Models\TypeVehicle;
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
        $key = "top:v2:{$year}:{$powertrain}:" . ($brand ?? '-') . ":{$limit}";

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

            // Level keluarga model: baris ter-link digroup per katalog model
            // (nama keluarga, bukan varian/type). Belum ter-link → per raw.
            // Query terpisah dgn kolom powertrain TERKUALIFIKASI — setelah
            // join, kolom itu ada di dua tabel (ambiguous).
            $linkedModels = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->where('vehicle_sales_stats.powertrain', $powertrain)
                ->whereNotNull('vehicle_sales_stats.model_vehicle_id')
                ->join('model_vehicles', 'model_vehicles.id', '=', 'vehicle_sales_stats.model_vehicle_id')
                ->join('brand_vehicles', 'brand_vehicles.id', '=', 'model_vehicles.brand_vehicle_id')
                ->selectRaw('model_vehicles.id as model_id, model_vehicles.name as model, brand_vehicles.name as brand, SUM(vehicle_sales_stats.units) as units')
                ->groupBy('model_vehicles.id', 'model_vehicles.name', 'brand_vehicles.name')
                ->orderByDesc('units')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => [
                    'brand' => $r->brand,
                    'model' => $r->model,
                    'model_vehicle_id' => $r->model_id,
                    'units' => (int) $r->units,
                ])
                ->values();

            $unlinkedModels = (clone $base)
                ->whereNull('model_vehicle_id')
                ->selectRaw('raw_brand as brand, raw_model as model, SUM(units) as units')
                ->groupBy('raw_brand', 'raw_model')
                ->orderByDesc('units')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => [
                    'brand' => $r->brand,
                    'model' => $r->model,
                    'model_vehicle_id' => null,
                    'units' => (int) $r->units,
                ])
                ->values();

            $models = $linkedModels
                ->concat($unlinkedModels)
                ->sortByDesc('units')
                ->take($limit)
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

        return $this->cached("catalog:v5:{$year}", function () use ($year, $prevYear) {
            // Level KELUARGA MODEL (bukan varian/type): baris ter-link ke
            // katalog digroup per model_vehicle; yang belum ter-link tetap
            // ditampilkan per raw_model (agar data tidak hilang).
            $linked = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->whereIn('powertrain', ['BEV', 'PHEV'])
                ->whereNotNull('model_vehicle_id')
                ->selectRaw('model_vehicle_id, SUM(units) as units')
                ->groupBy('model_vehicle_id')
                ->get();

            $unlinked = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->where('year', $year)
                ->whereIn('powertrain', ['BEV', 'PHEV'])
                ->whereNull('model_vehicle_id')
                ->selectRaw('raw_brand, raw_model, SUM(units) as units')
                ->groupBy('raw_brand', 'raw_model')
                ->get();

            // Lookup katalog: nama keluarga, brand, kategori, ukuran.
            $modelIds = $linked->pluck('model_vehicle_id')->filter()->unique()->values();
            $modelsById = \App\Models\ModelVehicle::query()
                ->whereIn('id', $modelIds)
                ->with('brandVehicle:id,name')
                ->get(['id', 'name', 'category', 'size_class', 'brand_vehicle_id'])
                ->keyBy('id');

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
            $addModel = function (string $brandName, string $modelName, int $units, ?string $category, ?string $sizeClass) use (&$brands, &$prevYearSales) {
                if (! isset($brands[$brandName])) {
                    $brands[$brandName] = [
                        'brand' => $brandName,
                        'units' => 0,
                        'prev_units' => $prevYearSales[$brandName] ?? 0,
                        'models' => [],
                    ];
                }
                $brands[$brandName]['units'] += $units;
                $brands[$brandName]['models'][] = [
                    'model' => $modelName,
                    'units' => $units,
                    'category' => $category,
                    'size_class' => $sizeClass,
                ];
            };

            foreach ($linked as $row) {
                $model = $modelsById->get($row->model_vehicle_id);
                if ($model === null) {
                    continue;
                }
                $brandName = $model->brandVehicle?->name ?? $model->name;
                $addModel($brandName, $model->name, (int) $row->units, $model->category, $model->size_class);
            }

            foreach ($unlinked as $row) {
                $addModel($row->raw_brand, $row->raw_model, (int) $row->units, null, null);
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
        $key = 'model-history:v3:' . rawurlencode($brand) . ':' . rawurlencode($model);

        return $this->cached($key, function () use ($brand, $model) {
            // Level keluarga: cocokkan ke katalog model (brand+nama model,
            // case-insensitive). Fallback: raw exact utk baris tak ter-link.
            $catalogModel = \App\Models\ModelVehicle::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($model))])
                ->whereHas('brandVehicle', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($brand))]))
                ->first();

            $base = VehicleSalesStat::query()
                ->latestImports()
                ->whereNull('month')
                ->whereIn('powertrain', ['BEV', 'PHEV']);

            if ($catalogModel !== null) {
                $base->where('model_vehicle_id', $catalogModel->id);
            } else {
                $base->where('raw_brand', $brand)->where('raw_model', $model);
            }

            $rows = (clone $base)
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

            // Daftar TYPE milik model ini ("tipe ada di detail") + total unit.
            $types = [];
            if ($catalogModel !== null) {
                $typeUnits = (clone $base)
                    ->whereNotNull('type_vehicle_id')
                    ->selectRaw('type_vehicle_id, SUM(units) as units')
                    ->groupBy('type_vehicle_id')
                    ->pluck('units', 'type_vehicle_id');

                $types = TypeVehicle::query()
                    ->where('model_vehicle_id', $catalogModel->id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'battery_capacity'])
                    ->map(fn ($t) => [
                        'name' => $t->name,
                        'battery_capacity' => $t->battery_capacity,
                        'units' => (int) ($typeUnits[$t->id] ?? 0),
                    ])
                    ->values()
                    ->all();
            }

            return [
                'brand' => $brand,
                'model' => $model,
                'model_vehicle_id' => $catalogModel?->id,
                'total_units' => (int) $rows->sum('units'),
                'years' => $years,
                'types' => $types,
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
