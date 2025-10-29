<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandVehicle extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'image',
        'name',
    ];

    public function modelVehicles()
    {
        return $this->hasMany(ModelVehicle::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
