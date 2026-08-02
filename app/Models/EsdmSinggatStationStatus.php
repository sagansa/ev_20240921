<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Agregat status real-time per stasiun ESDM (derived dari konektor).
 * Di-update oleh poller tiap 10 menit.
 */
class EsdmSinggatStationStatus extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_station_status';

    protected $fillable = [
        'station_esdm_id',
        'station_id',
        'total_connectors',
        'available_count',
        'charging_count',
        'finishing_count',
        'unavailable_count',
        'unknown_count',
        'availability_level',
        'aggregated_at',
    ];

    protected $casts = [
        'station_esdm_id' => 'integer',
        'station_id' => 'integer',
        'total_connectors' => 'integer',
        'available_count' => 'integer',
        'charging_count' => 'integer',
        'finishing_count' => 'integer',
        'unavailable_count' => 'integer',
        'unknown_count' => 'integer',
        'aggregated_at' => 'datetime',
    ];

    /**
     * Relasi ke master stasiun ESDM.
     *
     * Pakai station_esdm_id → esdm_id (selalu terisi oleh poller), BUKAN
     * station_id (PK lokal) yang bisa NULL bila poller jalan sebelum
     * station_id di-resolve. Ini membuat nama stasiun selalu resolve di
     * Filament terlepas dari urutan pipeline (poll sebelum import master).
     */
    public function station()
    {
        return $this->belongsTo(EsdmSinggatSpkluStation::class, 'station_esdm_id', 'esdm_id');
    }

    /** True bila stasiun punya minimal 1 slot bebas. */
    public function getIsAvailableAttribute(): bool
    {
        return $this->available_count > 0;
    }

    /** True bila semua konektor sedang mengisi (penuh). */
    public function getIsOccupiedAttribute(): bool
    {
        return $this->total_connectors > 0
            && $this->available_count === 0
            && $this->finishing_count === 0
            && $this->charging_count > 0;
    }
}
