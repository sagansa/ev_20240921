<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * LOGBOOK ENTRY — catatan sesi charging milik user.
 *
 * `user_id` soft-link ke `users.id` (connection `sagansa_user`), sedangkan
 * kolom `station_*` adalah snapshot denormalized dari `charging_stations`
 * saat entri dibuat — riwayat user stabil meski station canonical berubah.
 */
class LogbookEntry extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'logbook_entries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'charging_station_id',
        'station_name',
        'station_address',
        'station_latitude',
        'station_longitude',
        'station_provider',
        'station_type_charge',
        'session_at',
        'odometer_km',
        'distance_driven_km',
        'energy_kwh',
        'total_cost',
        'parking_cost',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'charging_station_id' => 'integer',
        'station_latitude' => 'float',
        'station_longitude' => 'float',
        'session_at' => 'datetime',
        'odometer_km' => 'float',
        'distance_driven_km' => 'float',
        'energy_kwh' => 'float',
        'total_cost' => 'float',
        'parking_cost' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (empty($entry->id)) {
                $entry->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /** Soft-link ke station canonical (nullable). */
    public function chargingStation()
    {
        return $this->belongsTo(ChargingStation::class, 'charging_station_id');
    }
}
