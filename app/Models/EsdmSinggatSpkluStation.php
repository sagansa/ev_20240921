<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — stasiun SPKLU (pengisian kendaraan listrik mobil).
 *
 * Sumber: POST https://gatrik.esdm.go.id/singgat/api/api/get-lokasi
 *         (response.spklu[]).
 *
 * Pipeline ESDM berdiri sendiri — tidak ada relasi ke spklu_locations maupun
 * spklu_scrape_raw. Lihat migration 2026_08_02_000001 untuk detail field.
 */
class EsdmSinggatSpkluStation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spklu_stations';

    protected $fillable = [
        'esdm_id',
        'nama_stasiun',
        'alamat_spklu',
        'kode_provinsi',
        'kode_kota',
        'nama_badan_usaha',
        'latitude_spklu_raw',
        'longitude_spklu_raw',
        'count_konektor',
        'estimasi',
        'estimasi_menit',
        'encrypt_id',
        'latitude',
        'longitude',
        'geo_status',
        'geo_notes',
        'geo_verification',
        'geo_verified_lat',
        'geo_verified_lng',
        'geo_distance_m',
        'geo_verified_source',
        'fasilitas',
        'raw_payload',
        'import_batch',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'count_konektor' => 'integer',
        'estimasi' => 'float',
        'estimasi_menit' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'geo_verified_lat' => 'float',
        'geo_verified_lng' => 'float',
        'geo_distance_m' => 'integer',
        'fasilitas' => 'array',
        'raw_payload' => 'array',
    ];

    public function installations()
    {
        return $this->hasMany(EsdmSinggatSpkluInstallation::class, 'station_id');
    }

    /** Semua konektor lintas instalasi (helper untuk query cepat). */
    public function connectors()
    {
        return $this->hasManyThrough(
            EsdmSinggatSpkluConnector::class,
            EsdmSinggatSpkluInstallation::class,
            'station_id',       // foreign on installations
            'installation_id',  // foreign on connectors
            'id',               // local on stations
            'id'                // local on installations
        );
    }
}
