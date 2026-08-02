<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot status real-time konektor ESDM (nilai TERKINI).
 * Di-update oleh poller esdm:poll-status tiap 10 menit (hanya bila berubah).
 */
class EsdmSinggatConnectorStatus extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_connector_status';

    protected $fillable = [
        'connector_esdm_id',
        'connector_id',
        'station_esdm_id',
        'status',
        'status_konektor',
        'status_since',
        'last_seen_at',
    ];

    protected $casts = [
        'connector_esdm_id' => 'integer',
        'connector_id' => 'integer',
        'station_esdm_id' => 'integer',
        'status_since' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function connector()
    {
        return $this->belongsTo(EsdmSinggatSpkluConnector::class, 'connector_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(EsdmSinggatConnectorStatusLog::class, 'connector_esdm_id', 'connector_esdm_id');
    }
}
