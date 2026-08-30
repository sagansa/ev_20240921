<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use App\Services\VehicleSalesMatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Mapping eksplisit nama mentah laporan → katalog (brand/model/type).
 * Lapisan pertama VehicleSalesMatcher — keputusan mapping manusia yang
 * tersimpan sebagai data (bukan alias hardcoded / efek samping fuzzy).
 */
class VehicleNameMapping extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $fillable = [
        'raw_brand',
        'raw_model',
        'raw_brand_norm',
        'raw_model_norm',
        'brand_vehicle_id',
        'model_vehicle_id',
        'type_vehicle_id',
        'catatan',
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

    /**
     * Buat/perbarui mapping (idempoten, kunci = pasangan ternormalisasi).
     * Nama katalog wajib existing — mapping tidak pernah membuat katalog.
     */
    public static function record(
        string $rawBrand,
        string $rawModel,
        string $brandName,
        string $modelName,
        ?string $typeName = null,
        ?string $catatan = null,
    ): ?self {
        $matcher = app(VehicleSalesMatcher::class);

        $brand = BrandVehicle::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($brandName))])
            ->first();
        $model = $brand !== null
            ? ModelVehicle::query()
                ->where('brand_vehicle_id', $brand->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($modelName))])
                ->first()
            : null;

        if ($brand === null || $model === null) {
            return null; // katalog belum ada — pemanggil melaporkan ke user
        }

        $typeId = null;

        if ($typeName !== null && trim($typeName) !== '') {
            $typeId = TypeVehicle::query()
                ->where('model_vehicle_id', $model->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($typeName))])
                ->value('id');
        }

        return static::updateOrCreate(
            [
                'raw_brand_norm' => $matcher->normalize($rawBrand),
                'raw_model_norm' => $matcher->normalize($rawModel),
            ],
            [
                'raw_brand' => trim($rawBrand),
                'raw_model' => trim($rawModel),
                'brand_vehicle_id' => $brand->id,
                'model_vehicle_id' => $model->id,
                'type_vehicle_id' => $typeId,
                'catatan' => $catatan,
            ],
        );
    }
}
