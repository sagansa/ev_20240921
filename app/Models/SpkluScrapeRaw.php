<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

class SpkluScrapeRaw extends Model
{
    use UsesDefaultConnectionWhenTesting;

    public const STATUS_NEW = 0;

    public const STATUS_DUPLICATE = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_REJECTED = 3;

    protected $connection = 'ev';

    protected $table = 'spklu_scrape_raw';

    protected $fillable = [
        'place_id',
        'nama_lokasi',
        'alamat',
        'latitude',
        'longitude',
        'rating',
        'total_reviews',
        'phone',
        'opening_hours',
        'website',
        'provider_name',
        'guessed_provider_id',
        'type_charge',
        'max_kw',
        'total_charger',
        'total_konektor',
        'raw_payload',
        'dedup_hash',
        'status',
        'linked_spklu_location_id',
        'scrape_session',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'total_reviews' => 'integer',
        'max_kw' => 'integer',
        'total_charger' => 'integer',
        'total_konektor' => 'integer',
        'raw_payload' => 'array',
        'status' => 'integer',
    ];

    public function chargers()
    {
        return $this->hasMany(SpkluScrapeRawCharger::class, 'scrape_raw_id');
    }

    public function guessedProvider()
    {
        return $this->belongsTo(Provider::class, 'guessed_provider_id');
    }

    public function linkedLocation()
    {
        return $this->belongsTo(SpkluLocation::class, 'linked_spklu_location_id');
    }
}
