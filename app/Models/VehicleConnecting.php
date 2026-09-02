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
        'raw_gabungan_key',
        'fuel',
        'brand_name',
        'model_name',
        'type_name',
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

    protected static function booted(): void
    {
        // raw_gabungan_key selalu diturunkan dari raw_gabungan (squash:
        // huruf+angka saja) di jalur penyimpanan mana pun — CLI, GUI import,
        // CRUD admin, maupun edit manual — supaya pencocokan berbasis key
        // tidak pernah kehilangan baris.
        static::saving(function (self $row): void {
            // Raw kosong (baris buatan kode lama) direkonstruksi dari nama
            // BRAND + MODEL + TYPE yang tersimpan.
            if (trim((string) $row->raw_gabungan) === '') {
                $row->raw_gabungan = trim(preg_replace('/\s+/', ' ',
                    trim((string) $row->brand_name).' '.
                    trim((string) $row->model_name).' '.
                    trim((string) $row->type_name),
                ));
            }

            $gabungan = trim((string) $row->raw_gabungan);

            if ($gabungan !== '') {
                $row->raw_gabungan_key = preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper($gabungan));
            }
        });
    }
}
