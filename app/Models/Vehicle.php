<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'image',
        'license_plate',
        'brand_vehicle_id',
        'model_vehicle_id',
        'type_vehicle_id',
        'ownership',
        'status',
    ];

    public function brandVehicle()
    {
        return $this->belongsTo(BrandVehicle::class);
    }

    public function modelVehicle()
    {
        return $this->belongsTo(ModelVehicle::class);
    }

    public function typeVehicle()
    {
        return $this->belongsTo(TypeVehicle::class);
    }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    public function stateOfHealths()
    {
        return $this->hasMany(StateOfHealth::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMaxKmNowAttribute()
    {
        return $this->charges->max('km_now');
    }
}
