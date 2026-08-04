<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Link matching antara stasiun PLN dan stasiun ESDM (soft-link, tanpa FK).
 *
 * Produk dari PlnEsdmMatchService (command `pln:match-esdm`). Satu baris =
 * satu pasangan (pln_station_id, esdm_station_id). Relasi menunjuk ke
 * charging_stations.id — identitas bisa berubah saat rehydrate, jadi TIDAK ada
 * foreign key (preseden spklu_scrape_raw.linked_spklu_location_id).
 *
 * Hanya match ber-status `approved` yang di-fold ke serving (status ESDM
 * diteruskan ke stasiun PLN oleh applyStatusToCanonical()).
 */
class PlnEsdmStationMatch extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'pln_esdm_station_matches';

    protected $fillable = [
        'pln_station_id',
        'esdm_station_id',
        'pln_source_station_id',
        'esdm_source_station_id',
        'pln_name',
        'esdm_name',
        'match_status',
        'match_method',
        'similarity_pct',
        'distance_m',
        'ai_confidence',
        'ai_reasoning',
        'decided_by',
        'decided_at',
        'rejected_reason',
    ];

    protected $casts = [
        'pln_station_id' => 'integer',
        'esdm_station_id' => 'integer',
        'similarity_pct' => 'integer',
        'distance_m' => 'integer',
        'ai_confidence' => 'decimal:2',
        'ai_reasoning' => 'array',
        'decided_at' => 'datetime',
    ];

    public function plnStation()
    {
        return $this->belongsTo(ChargingStation::class, 'pln_station_id');
    }

    public function esdmStation()
    {
        return $this->belongsTo(ChargingStation::class, 'esdm_station_id');
    }
}
