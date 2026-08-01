<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

class SpkluLocation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';
    protected $table = 'spklu_locations';

    protected $fillable = [
        'external_id',
        'place_id',
        'provider_id',
        'provinsi',
        'kabupaten_kota',
        'nama_lokasi',
        'alamat',
        'latitude',
        'longitude',
        'type_charge',
        'watt',
        'status',
        'keterangan',
        'total_charger',
        'total_konektor',
    ];

    protected $casts = [
        'external_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => 'integer',
        'total_charger' => 'integer',
        'total_konektor' => 'integer',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function chargerBoxes()
    {
        return $this->hasMany(SpkluChargerBox::class, 'spklu_location_id');
    }
}
