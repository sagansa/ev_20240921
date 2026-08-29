<?php

namespace App\Filament\Pages;

use App\Models\VehicleSalesStat;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Laporan Penjualan per Brand & Model (statistik GAIKINDO yang sudah
 * di-match ke katalog).
 *
 * Prinsip aman-angka (wajib dipertahankan):
 * - Hanya import TERBARU per tahun yang dihitung (scope latestImports).
 * - Hanya baris agregat TAHUNAN (month IS NULL) — baris bulanan jangan ikut
 *   disum, kalau tidak angka jadi dobel.
 */
class VehicleSalesReport extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-chart-bar';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $title = 'Laporan Penjualan per Brand & Model';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.vehicle-sales-report';

    /** Batas baris tabel per model (spec: top 100). */
    public const MODEL_ROW_LIMIT = 100;

    #[Url]
    public ?int $year = null;

    #[Url]
    public string $powertrain = 'ALL';

    public function mount(): void
    {
        $this->year ??= static::latestYear();
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'years' => static::availableYears(),
            'brandRows' => static::brandRows($this->year, $this->powertrain),
            'modelRows' => static::modelRows($this->year, $this->powertrain),
            'totalUnits' => static::totalUnits($this->year, $this->powertrain),
            'modelRowLimit' => static::MODEL_ROW_LIMIT,
        ]);
    }

    /** Tahun-tahun yang punya data (dari import terbaru per tahun). */
    public static function availableYears(): array
    {
        return VehicleSalesStat::query()
            ->latestImports()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->map(fn ($year) => (int) $year)
            ->all();
    }

    public static function latestYear(): ?int
    {
        return VehicleSalesStat::query()
            ->latestImports()
            ->max('year');
    }

    /** Base query tahunan: import terbaru + baris agregat tahunan saja. */
    private static function annualStatsQuery(?int $year, ?string $powertrain): \Illuminate\Database\Eloquent\Builder
    {
        return VehicleSalesStat::query()
            ->latestImports()
            ->whereNull('month')
            ->powertrain($powertrain)
            ->when($year !== null, fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('year', $year));
    }

    /**
     * Agregat per brand (katalog) + satu baris "(tidak ter-match)" untuk
     * brand_vehicle_id IS NULL. Urut total unit desc.
     *
     * @return list<array{brand: string, total_units: int, model_count: int, type_count: int}>
     */
    public static function brandRows(?int $year, ?string $powertrain): array
    {
        $base = static::annualStatsQuery($year, $powertrain);

        $matched = (clone $base)
            ->from('vehicle_sales_stats')
            ->join('brand_vehicles', 'brand_vehicles.id', '=', 'vehicle_sales_stats.brand_vehicle_id')
            ->whereNotNull('vehicle_sales_stats.brand_vehicle_id')
            ->groupBy('vehicle_sales_stats.brand_vehicle_id', 'brand_vehicles.name')
            ->orderByDesc('total_units')
            ->orderBy('brand_vehicles.name')
            ->get([
                'brand_vehicles.name as brand',
                DB::raw('sum(vehicle_sales_stats.units) as total_units'),
                DB::raw('count(distinct vehicle_sales_stats.model_vehicle_id) as model_count'),
                DB::raw('count(distinct vehicle_sales_stats.type_vehicle_id) as type_count'),
            ])
            ->map(fn ($row) => [
                'brand' => (string) $row->brand,
                'total_units' => (int) $row->total_units,
                'model_count' => (int) $row->model_count,
                'type_count' => (int) $row->type_count,
            ])
            ->all();

        $unmatched = (clone $base)
            ->whereNull('brand_vehicle_id')
            ->get([
                DB::raw('sum(vehicle_sales_stats.units) as total_units'),
                DB::raw('count(distinct vehicle_sales_stats.raw_model) as model_count'),
                DB::raw('count(distinct vehicle_sales_stats.type_vehicle_id) as type_count'),
            ])
            ->first();

        if ($unmatched !== null && (int) $unmatched->total_units > 0) {
            $matched[] = [
                'brand' => '(tidak ter-match)',
                'total_units' => (int) $unmatched->total_units,
                'model_count' => (int) $unmatched->model_count,
                'type_count' => (int) $unmatched->type_count,
            ];
        }

        return $matched;
    }

    /**
     * Agregat per model (katalog) + satu baris "(tidak ter-match)" untuk
     * model_vehicle_id IS NULL. Urut total unit desc, dibatasi 100 baris.
     *
     * @return list<array{brand: string, model: string, powertrain: string, total_units: int, type_count: int}>
     */
    public static function modelRows(?int $year, ?string $powertrain): array
    {
        $base = static::annualStatsQuery($year, $powertrain);

        $matched = (clone $base)
            ->from('vehicle_sales_stats')
            ->join('model_vehicles', 'model_vehicles.id', '=', 'vehicle_sales_stats.model_vehicle_id')
            ->join('brand_vehicles', 'brand_vehicles.id', '=', 'model_vehicles.brand_vehicle_id')
            ->whereNotNull('vehicle_sales_stats.model_vehicle_id')
            ->groupBy('vehicle_sales_stats.model_vehicle_id', 'brand_vehicles.name', 'model_vehicles.name', 'model_vehicles.powertrain')
            ->orderByDesc('total_units')
            ->orderBy('brand_vehicles.name')
            ->orderBy('model_vehicles.name')
            ->limit(static::MODEL_ROW_LIMIT + 1) // +1 untuk deteksi terpotong
            ->get([
                'brand_vehicles.name as brand',
                'model_vehicles.name as model',
                DB::raw('coalesce(model_vehicles.powertrain, \'—\') as powertrain'),
                DB::raw('sum(vehicle_sales_stats.units) as total_units'),
                DB::raw('count(distinct vehicle_sales_stats.type_vehicle_id) as type_count'),
            ])
            ->take(static::MODEL_ROW_LIMIT)
            ->map(fn ($row) => [
                'brand' => (string) $row->brand,
                'model' => (string) $row->model,
                'powertrain' => (string) $row->powertrain,
                'total_units' => (int) $row->total_units,
                'type_count' => (int) $row->type_count,
            ])
            ->all();

        $unmatched = (clone $base)
            ->whereNull('model_vehicle_id')
            ->get([
                DB::raw('sum(vehicle_sales_stats.units) as total_units'),
                DB::raw('count(distinct vehicle_sales_stats.type_vehicle_id) as type_count'),
            ])
            ->first();

        if ($unmatched !== null && (int) $unmatched->total_units > 0) {
            $matched[] = [
                'brand' => '—',
                'model' => '(tidak ter-match)',
                'powertrain' => '—',
                'total_units' => (int) $unmatched->total_units,
                'type_count' => (int) $unmatched->type_count,
            ];
        }

        return $matched;
    }

    public static function totalUnits(?int $year, ?string $powertrain): int
    {
        return (int) static::annualStatsQuery($year, $powertrain)->sum('units');
    }
}
