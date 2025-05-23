<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingType extends Model
{
    protected $table = 'charging_types';
    protected $fillable = ['name'];
    protected $connection = 'ev'; // Use the sagansa database connection
}
