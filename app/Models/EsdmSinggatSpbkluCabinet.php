<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — kabinet (lemari penukaran baterai) di stasiun SPBKLU.
 * response.spbklu[].kabinet[]
 */
class EsdmSinggatSpbkluCabinet extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spbklu_cabinets';

    protected $fillable = [
        'esdm_id',
        'station_id',
        'station_esdm_id',
        'merek_kabinet',
        'status_instalasi',
        'kapasitas_raw',
        'harga_penukaran_baterai_raw',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'station_id' => 'integer',
        'station_esdm_id' => 'integer',
    ];

    public function station()
    {
        return $this->belongsTo(EsdmSinggatSpbkluStation::class, 'station_id');
    }

    public function batteries()
    {
        return $this->hasMany(EsdmSinggatSpbkluBattery::class, 'cabinet_id');
    }
}
