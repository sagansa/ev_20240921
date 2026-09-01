<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris CONNECTING — master mapping "BRAND MODEL TYPE" (teks mentah
 * laporan) → katalog. Dipakai sebagai acuan sumber: katalog boleh di-rename
 * (nama berubah), teks gabungan tetap karena mengikuti laporan.
 */
class VehicleConnecting extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $fillable = [
        'raw_gabungan',
        'fuel',
        'brand_vehicle_id',
        'model_vehicle_id',
        'type_vehicle_id',
        'powertrain',
        'category',
        'size_class',
    ];

    protected $casts = [
        'powertrain' => 'string',
        'category' => 'string',
        'size_class' => 'string',
    ];

    public function brandVehicle()
    {
        return $this->belongsTo(BrandVehicle::class);
    }

    public function modelVehicle()
    {
        return $this->belongsTo(ModelVehicle::class);
    }

    public function typeVehicle()
    {
        return $this->belongsTo(TypeVehicle::class);
    }
}
