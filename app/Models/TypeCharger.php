<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeCharger extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'name',
        'current_charger_id',
    ];

    public function currentCharger()
    {
        return $this->belongsTo(CurrentCharger::class);
    }

    public function powerChargers()
    {
        return $this->hasMany(PowerCharger::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }

    public function typeVehicles()
    {
        return $this->hasMany(TypeVehicle::class);
    }
}
