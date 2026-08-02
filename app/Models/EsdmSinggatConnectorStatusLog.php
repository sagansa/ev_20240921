<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * History transisi status konektor ESDM — append-only, hanya saat status BERUBAH.
 */
class EsdmSinggatConnectorStatusLog extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_connector_status_log';

    protected $fillable = [
        'connector_esdm_id',
        'connector_id',
        'station_esdm_id',
        'from_status',
        'to_status',
        'from_status_konektor',
        'to_status_konektor',
        'observed_at',
        'poll_batch',
    ];

    protected $casts = [
        'connector_esdm_id' => 'integer',
        'connector_id' => 'integer',
        'station_esdm_id' => 'integer',
        'observed_at' => 'datetime',
    ];

    public function connector()
    {
        return $this->belongsTo(EsdmSinggatSpkluConnector::class, 'connector_id');
    }
}
