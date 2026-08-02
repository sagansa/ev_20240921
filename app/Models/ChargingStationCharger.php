<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * CANONICAL charging station charger — child "charger box" level.
 *
 * Match shape `charger_boxes` contract mobile. `nama` dipakai sebagai
 * nama_chargerbox (di ESDM = merek_mesin).
 */
class ChargingStationCharger extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'charging_station_chargers';

    protected $fillable = [
        'station_id',
        'chargerbox_id',
        'type_charge',
        'nama',
        'watt',
        'jumlah_charger',
        'jumlah_konektor',
        'icon',
        'gambar',
        'harga_pengisian',
        'harga_layanan',
    ];

    protected $casts = [
        'jumlah_charger' => 'integer',
        'jumlah_konektor' => 'integer',
    ];

    public function station()
    {
        return $this->belongsTo(ChargingStation::class, 'station_id');
    }

    /** Serialization compat: SpkluChargerBoxResource membaca nama_chargerbox. */
    public function getNamaChargerboxAttribute(): ?string
    {
        return $this->nama;
    }
}
