<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];

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
