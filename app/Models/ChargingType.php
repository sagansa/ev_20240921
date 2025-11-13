<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;

class ChargingType extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $table = 'charging_types';
    protected $fillable = ['name'];
    protected $connection = 'ev'; // Use the sagansa database connection
}
