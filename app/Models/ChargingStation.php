<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * CANONICAL charging station — sumber data publik/mobile yang source-agnostic.
 *
 * Denormalized read model: lokasi + info charging + status real-time agregat
 * dalam satu tabel. Di-hydrate dari source adaptor (ESDM saat ini) oleh
 * CanonicalStationHydrateService / command `esdm:hydrate-canonical`. id publik
 * yang stabil menjadi target relasi user-data masa depan (bookmark, logbook,
 * review) — bukan id source.
 */
class ChargingStation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'charging_stations';

    protected $fillable = [
        'source',
        'source_station_id',
        'nama_lokasi',
        'alamat',
        'latitude',
        'longitude',
        'kode_provinsi',
        'provinsi',
        'kabupaten_kota',
        'type_charge',
        'watt',
        'total_charger',
        'total_konektor',
        'nama_badan_usaha',
        'provider_id',
        'provider_name',
        'harga_pengisian',
        'harga_layanan',
        'estimasi',
        'estimasi_menit',
        'jarak',
        'availability_level',
        'available_count',
        'charging_count',
        'finishing_count',
        'status_updated_at',
        'raw_payload',
    ];

    protected $casts = [
        'source_station_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'total_charger' => 'integer',
        'total_konektor' => 'integer',
        'estimasi' => 'float',
        'estimasi_menit' => 'float',
        'jarak' => 'float',
        'available_count' => 'integer',
        'charging_count' => 'integer',
        'finishing_count' => 'integer',
        'status_updated_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function chargers()
    {
        return $this->hasMany(ChargingStationCharger::class, 'station_id');
    }

    /** Alias agar SpkluLocationResource bisa serialisasi tanpa perubahan. */
    public function chargerBoxes()
    {
        return $this->hasMany(ChargingStationCharger::class, 'station_id');
    }
}
