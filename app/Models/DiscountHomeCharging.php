<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountHomeCharging extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];

    protected $fillable = [
        'charger_location_id',
        'month',
        'total_kWh',
        'discount_kWh',
        'discount_total',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class);
    }
}
