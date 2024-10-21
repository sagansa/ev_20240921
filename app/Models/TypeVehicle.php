<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeVehicle extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];

    public function modelVehicle()
    {
        return $this->belongsTo(ModelVehicle::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function typeCharger()
    {
        return $this->belongsTo(TypeCharger::class);
    }

    protected $casts = [
        'type_charger' => 'array',
    ];
}
