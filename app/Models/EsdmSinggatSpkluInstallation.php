<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — instalasi (mesin charger) di stasiun SPKLU.
 * response.spklu[].instalasi[]
 */
class EsdmSinggatSpkluInstallation extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spklu_installations';

    protected $fillable = [
        'esdm_id',
        'station_id',
        'station_esdm_id',
        'nomor_identitas',
        'merek_mesin',
        'nomor_seri_mesin',
        'jenis_pengisian_spklu',
        'harga_pengisian_raw',
        'harga_layanan_raw',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'station_id' => 'integer',
        'station_esdm_id' => 'integer',
    ];

    public function station()
    {
        return $this->belongsTo(EsdmSinggatSpkluStation::class, 'station_id');
    }

    public function connectors()
    {
        return $this->hasMany(EsdmSinggatSpkluConnector::class, 'installation_id');
    }
}
