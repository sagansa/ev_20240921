<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — baterai individual di dalam kabinet SPBKLU.
 * response.spbklu[].kabinet[].baterai[]
 */
class EsdmSinggatSpbkluBattery extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spbklu_batteries';

    protected $fillable = [
        'esdm_id',
        'cabinet_id',
        'cabinet_esdm_id',
        'merek_baterai',
        'tipe_baterai',
        'kapasitas_baterai_raw',
        'status_baterai',
        'persentase_raw',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'cabinet_id' => 'integer',
        'cabinet_esdm_id' => 'integer',
    ];

    public function cabinet()
    {
        return $this->belongsTo(EsdmSinggatSpbkluCabinet::class, 'cabinet_id');
    }
}
