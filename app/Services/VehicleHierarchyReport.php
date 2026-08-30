<?php

namespace App\Services;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\VehicleSalesStat;
use App\Support\VehicleCategories;
use Illuminate\Support\Facades\DB;

/**
 * Data pohon hierarki brand → model → type + angka penjualan per node untuk
 * halaman admin "Hierarki Kendaraan".
 *
 * Prinsip aman-angka (sama dgn VehicleSalesReport): hanya import TERBARU per
 * tahun (latestImports) dan hanya baris agregat TAHUNAN (month IS NULL) —
 * baris bulanan tidak pernah ikut disum.
 */
class VehicleHierarchyReport
{
    /**
     * @return array{years: list<int>, year: int, brands: list<array<string, mixed>>,
     *               unlinked: list<array<string, mixed>>, orphanTypes: int,
     *               modelsWithoutCategory: int, totals: array{units: int, prevUnits: int, totalBrands: int, totalModels: int, totalTypes: int, maxBrandUnits: int}}
     */
    public function build(?int $year, string $powertrain = 'ALL', ?string $category = null, ?string $search = null, bool $onlyIssues = false): array
    {
        $years = VehicleSalesStat::query()
            ->latestImports()
            ->whereNull('month')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();

        $year ??= $years[0] ?? (int) now()->year;

        $models = ModelVehicle::query()
            ->with('brandVehicle:id,name')
            ->when($powertrain !== 'ALL', fn ($q) => $q->where('powertrain', $powertrain))
            ->when($category !== null, fn ($q) => $q->where('category', $category))
            ->orderBy('name')
            ->get();

        $unitsByModel = $this->unitsByColumn($year, 'model_vehicle_id');
        $prevUnitsByModel = $this->unitsByColumn($year - 1, 'model_vehicle_id');
        $unitsByType = $this->unitsByColumn($year, 'type_vehicle_id');

        $typeCounts = DB::connection((new ModelVehicle)->getConnectionName())
            ->table('type_vehicles')
            ->selectRaw('model_vehicle_id, COUNT(*) c')
            ->groupBy('model_vehicle_id')
            ->pluck('c', 'model_vehicle_id');

        // Bucket model by-id, lalu type ditempel sekali query (tanpa N+1).
        $modelsById = [];

        foreach ($models as $model) {
            $modelsById[$model->id] = [
                'id' => $model->id,
                'name' => $model->name,
                'category' => $model->category,
                'category_group' => VehicleCategories::groupOf($model->category),
                'size' => $model->size_class,
                'powertrain' => $model->powertrain,
                'units' => $unitsByModel[$model->id] ?? 0,
                'prev_units' => $prevUnitsByModel[$model->id] ?? 0,
                'type_count' => (int) ($typeCounts[$model->id] ?? 0),
                'has_issue' => $model->category === null,
                'types' => [],
                'brand_id' => $model->brandVehicle?->id,
                'brand_name' => $model->brandVehicle?->name,
            ];
        }

        $typeRows = DB::connection((new ModelVehicle)->getConnectionName())
            ->table('type_vehicles')
            ->whereIn('model_vehicle_id', array_keys($modelsById))
            ->orderBy('name')
            ->get(['id', 'name', 'model_vehicle_id']);

        foreach ($typeRows as $type) {
            $modelsById[$type->model_vehicle_id]['types'][] = [
                'id' => $type->id,
                'name' => $type->name,
                'units' => $unitsByType[$type->id] ?? 0,
            ];
        }

        // Kelompokkan per brand, urutkan unit desc.
        $brands = [];
        $totals = ['units' => 0, 'prevUnits' => 0, 'totalBrands' => 0, 'totalModels' => 0, 'totalTypes' => 0, 'maxBrandUnits' => 0];
        $modelsWithoutCategory = 0;
        $searchLower = $search !== null && trim($search) !== '' ? strtolower(trim($search)) : null;

        foreach ($modelsById as $bucket) {
            if ($bucket['brand_id'] === null) {
                continue; // model yatim
            }

            if ($bucket['category'] === null) {
                $modelsWithoutCategory++;
            }

            if ($onlyIssues && ! $bucket['has_issue']) {
                continue;
            }

            $brands[$bucket['brand_id']] ??= [
                'id' => $bucket['brand_id'],
                'name' => $bucket['brand_name'],
                'units' => 0,
                'prev_units' => 0,
                'total_types' => 0,
                'has_issue' => false,
                'models' => [],
            ];

            $brands[$bucket['brand_id']]['models'][] = $bucket;
            $brands[$bucket['brand_id']]['units'] += $bucket['units'];
            $brands[$bucket['brand_id']]['prev_units'] += $bucket['prev_units'];
            $brands[$bucket['brand_id']]['total_types'] += count($bucket['types']);
            if ($bucket['has_issue']) {
                $brands[$bucket['brand_id']]['has_issue'] = true;
            }

            $totals['units'] += $bucket['units'];
            $totals['prevUnits'] += $bucket['prev_units'];
            $totals['totalModels']++;
            $totals['totalTypes'] += count($bucket['types']);
        }

        // Filter search bila diisi
        if ($searchLower !== null) {
            $filteredBrands = [];
            foreach ($brands as $brandId => $brandData) {
                $brandMatches = str_contains(strtolower($brandData['name']), $searchLower);
                $matchedModels = [];

                foreach ($brandData['models'] as $m) {
                    $modelMatches = str_contains(strtolower($m['name']), $searchLower)
                        || ($m['category'] && str_contains(strtolower($m['category']), $searchLower))
                        || ($m['powertrain'] && str_contains(strtolower($m['powertrain']), $searchLower));

                    $matchedTypes = array_filter($m['types'], fn ($t) => str_contains(strtolower($t['name']), $searchLower));

                    if ($brandMatches || $modelMatches || count($matchedTypes) > 0) {
                        $matchedModels[] = $m;
                    }
                }

                if ($brandMatches || count($matchedModels) > 0) {
                    $brandData['models'] = $matchedModels;
                    $filteredBrands[$brandId] = $brandData;
                }
            }
            $brands = $filteredBrands;
        }

        $totals['totalBrands'] = count($brands);
        $totals['maxBrandUnits'] = ! empty($brands) ? max(array_column($brands, 'units')) : 0;

        usort($brands, fn ($a, $b) => $b['units'] <=> $a['units']);
        array_walk($brands, fn (&$b) => usort($b['models'], fn ($a, $c) => $c['units'] <=> $a['units']));

        // Stats tak ter-link (raw tanpa katalog) — hubungan yang "putus".
        $unlinked = VehicleSalesStat::query()
            ->latestImports()
            ->whereNull('month')
            ->where('year', $year)
            ->whereNull('model_vehicle_id')
            ->selectRaw('raw_brand, COUNT(DISTINCT raw_model) models, SUM(units) units')
            ->groupBy('raw_brand')
            ->orderByDesc('units')
            ->get()
            ->map(fn ($r) => [
                'brand' => $r->raw_brand,
                'models' => (int) $r->models,
                'units' => (int) $r->units,
            ])
            ->all();

        $orphanTypes = DB::connection((new ModelVehicle)->getConnectionName())
            ->table('type_vehicles')
            ->leftJoin('model_vehicles', 'model_vehicles.id', '=', 'type_vehicles.model_vehicle_id')
            ->whereNull('model_vehicles.id')
            ->count();

        return [
            'years' => $years,
            'year' => $year,
            'brands' => array_values($brands),
            'unlinked' => $unlinked,
            'orphanTypes' => (int) $orphanTypes,
            'modelsWithoutCategory' => $modelsWithoutCategory,
            'totals' => $totals,
        ];
    }

    /** @return array<int, int> kolomId → unit (baris agregat tahunan). */
    protected function unitsByColumn(int $year, string $column): array
    {
        if (! in_array($column, ['model_vehicle_id', 'type_vehicle_id'], true)) {
            return [];
        }

        return VehicleSalesStat::query()
            ->latestImports()
            ->whereNull('month')
            ->where('year', $year)
            ->whereNotNull($column)
            ->selectRaw("$column as id, SUM(units) units")
            ->groupBy($column)
            ->pluck('units', 'id')
            ->map(fn ($u) => (int) $u)
            ->all();
    }
}
