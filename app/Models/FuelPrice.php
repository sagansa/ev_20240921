<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';
    protected $table = 'fuel_prices';

    protected $fillable = [
        'effective_date',
        'price_per_liter',
        'fuel_name',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'price_per_liter' => 'float',
    ];
}
