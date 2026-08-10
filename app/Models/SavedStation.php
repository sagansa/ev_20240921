<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Bookmark lokasi SPKLU oleh user (Fase 3 — Peta User).
 *
 * Referensi ke charging_stations.id — bukan duplikasi data. Saat serve
 * "Peta Saya", controller JOIN ke charging_stations utk ambil info pin
 * (nama/lat/lng/provider) agar data selalu segar.
 *
 * Tidak gated source — semua station (PLN/ESDM) bisa di-bookmark.
 */
class SavedStation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'saved_stations';

    protected $fillable = [
        'user_id',
        'charging_station_id',
    ];

    public function station()
    {
        // Soft-link ke charging_stations.id (no FK — canonical bisa rehydrate).
        return $this->belongsTo(ChargingStation::class, 'charging_station_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
