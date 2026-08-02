<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — stasiun SPBKLU (penukaran baterai kendaraan roda 2/motor).
 * response.spbklu[]
 */
class EsdmSinggatSpbkluStation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spbklu_stations';

    protected $fillable = [
        'esdm_id',
        'nama_stasiun',
        'alamat_spbklu',
        'kode_provinsi',
        'kode_kota',
        'nama_badan_usaha',
        'nomor_identitas',
        'latitude_spbklu_raw',
        'longitude_spbklu_raw',
        'count_battery',
        'estimasi',
        'estimasi_menit',
        'encrypt_id',
        'latitude',
        'longitude',
        'geo_status',
        'geo_notes',
        'raw_payload',
        'import_batch',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'count_battery' => 'integer',
        'estimasi' => 'float',
        'estimasi_menit' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'raw_payload' => 'array',
    ];

    public function cabinets()
    {
        return $this->hasMany(EsdmSinggatSpbkluCabinet::class, 'station_id');
    }

    /** Semua baterai lintas kabinet (helper untuk query cepat). */
    public function batteries()
    {
        return $this->hasManyThrough(
            EsdmSinggatSpbkluBattery::class,
            EsdmSinggatSpbkluCabinet::class,
            'station_id',     // foreign on cabinets
            'cabinet_id',     // foreign on batteries
            'id',             // local on stations
            'id'              // local on cabinets
        );
    }
}
