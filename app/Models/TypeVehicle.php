<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeVehicle extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'name',
        'model_vehicle_id',
        'type_charger',
        'battery_capacity',
    ];

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
