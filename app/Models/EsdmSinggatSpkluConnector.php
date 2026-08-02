<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * ESDM Singgat — konektor (plug individual) di instalasi SPKLU.
 * response.spklu[].instalasi[].konektor[]
 *
 * Catatan: img_konektor (base64 PNG) TIDAK disimpan di DB. Hanya 7 gambar
 * unik (1 per tipe plug) — di-ekstrak ke public/storage/esdm/konektor_unique/
 * dan di-upload manual. img_path menyimpan path relatif tsb.
 */
class EsdmSinggatSpkluConnector extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'esdm_singgat_spklu_connectors';

    protected $fillable = [
        'esdm_id',
        'installation_id',
        'installation_esdm_id',
        'nama_konektor',
        'status',
        'status_konektor',
        'img_path',
    ];

    protected $casts = [
        'esdm_id' => 'integer',
        'installation_id' => 'integer',
        'installation_esdm_id' => 'integer',
    ];

    public function installation()
    {
        return $this->belongsTo(EsdmSinggatSpkluInstallation::class, 'installation_id');
    }
}
