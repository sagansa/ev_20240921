<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelVehicle extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function brandVehicle()
    {
        return $this->belongsTo(BrandVehicle::class);
    }

    public function typeVehicles()
    {
        return $this->hasMany(TypeVehicle::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
