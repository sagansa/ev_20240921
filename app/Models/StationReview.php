<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Review lokasi SPKLU (Fase 1) — anonim, multiple review diizinkan.
 *
 * Soft-link ke charging_stations.id (lapisan kanonik mobile). user_id hanya
 * dipakai utk gate eligibility & moderasi admin; TIDAK diekspos ke publik
 * (resource anonim: {id, rating, comment, created_at}).
 */
class StationReview extends Model
{
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'station_reviews';

    protected $fillable = [
        'charging_station_id',
        'user_id',
        'rating',
        'comment',
        'is_anonymous',
    ];

    protected $casts = [
        'charging_station_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
    ];

    public function station()
    {
        return $this->belongsTo(ChargingStation::class, 'charging_station_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
