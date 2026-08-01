<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

class SpkluScrapeRawCharger extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'spklu_scrape_raw_chargers';

    protected $fillable = [
        'scrape_raw_id',
        'connector_type',
        'power_kw',
        'watt',
        'type_charge',
        'jumlah_charger',
        'jumlah_konektor',
    ];

    protected $casts = [
        'power_kw' => 'integer',
        'jumlah_charger' => 'integer',
    ];

    public function scrapeRaw()
    {
        return $this->belongsTo(SpkluScrapeRaw::class, 'scrape_raw_id');
    }
}
