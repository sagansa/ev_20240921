<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Charging station connector — plug individual (level paling granular).
 *
 * Status real-time per-plug di-fold dari esdm_singgat_connector_status oleh
 * poller. Tampil di detail stasiun mobile: "CCS2: available, CHAdeMO: charging".
 */
class ChargingStationConnector extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'charging_station_connectors';

    protected $fillable = [
        'charger_id',
        'source_connector_id',
        'nama_konektor',
        'img_path',
        'status_konektor',
        'status',
        'status_updated_at',
    ];

    protected $casts = [
        'charger_id' => 'integer',
        'source_connector_id' => 'integer',
        'status_updated_at' => 'datetime',
    ];

    public function charger()
    {
        return $this->belongsTo(ChargingStationCharger::class, 'charger_id');
    }

    /** Badge text utk tampilan: warna ikut status_konektor. */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status_konektor) {
            'available' => 'success',
            'charging' => 'danger',
            'finishing' => 'warning',
            'unavailable' => 'gray',
            default => 'gray',
        };
    }
}
