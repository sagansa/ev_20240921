<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static \Illuminate\Database\Eloquent\Builder latestImports() hanya baris dari import terbaru per tahun
 */

class VehicleSalesStat extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev';

    protected $fillable = [
        'sales_import_id',
        'raw_brand',
        'raw_model',
        'brand_vehicle_id',
        'model_vehicle_id',
        'segment',
        'powertrain',
        'year',
        'month',
        'units',
        'origin_country',
    ];

    public function import()
    {
        return $this->belongsTo(SalesImport::class, 'sales_import_id');
    }

    public function brandVehicle()
    {
        return $this->belongsTo(BrandVehicle::class);
    }

    public function modelVehicle()
    {
        return $this->belongsTo(ModelVehicle::class);
    }

    /** Hanya baris granular bulanan (bukan agregat tahunan). */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->whereNotNull('month');
    }

    /**
     * Hanya baris dari import TERBARU per tahun — re-import file yang lebih
     * bersih otomatis menggantikan angka import kasar lama di seluruh agregasi,
     * tanpa menghitung ganda (import tetap append-only; baris lama tidak
     * ikut disum, cukup dihapus dari admin bila mau).
     */
    public function scopeLatestImports(Builder $query): Builder
    {
        return $query->whereIn('sales_import_id', function ($sub) {
            $sub->selectRaw('max(id)')
                ->from((new SalesImport)->getTable())
                ->groupBy('year');
        });
    }

    public function scopePowertrain(Builder $query, ?string $powertrain): Builder
    {
        if ($powertrain && strtoupper($powertrain) !== 'ALL') {
            $allowed = ['BEV', 'PHEV', 'HEV', 'ICE'];

            return in_array(strtoupper($powertrain), $allowed)
                ? $query->where('powertrain', strtoupper($powertrain))
                : $query->whereIn('powertrain', ['BEV', 'PHEV']); // default "EV"
        }

        return $query;
    }
}
