<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PowerCharger extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];

    public function typeCharger()
    {
        return $this->belongsTo(TypeCharger::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
