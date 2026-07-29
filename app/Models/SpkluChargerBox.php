<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

class SpkluChargerBox extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';
    protected $table = 'spklu_charger_boxes';

    protected $fillable = [
        'spklu_location_id',
        'chargerbox_id',
        'type_charge',
        'nama_chargerbox',
        'watt',
        'jumlah_charger',
        'jumlah_konektor',
        'icon',
        'gambar',
    ];

    protected $casts = [
        'jumlah_charger' => 'integer',
    ];

    public function spkluLocation()
    {
        return $this->belongsTo(SpkluLocation::class, 'spklu_location_id');
    }
}
