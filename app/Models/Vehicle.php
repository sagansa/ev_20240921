<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'user_id',
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

    public function batteries()
    {
        return $this->hasMany(Battery::class);
    }

    /**
     * Baterai aktif terbaru (terpasang, belum pensiun) — dipakai utk
     * auto-assign battery_id saat sesi charging / SoH dibuat tanpa eksplisit.
     */
    public function activeBattery()
    {
        return $this->hasOne(Battery::class)
            ->active()
            ->orderByDesc('installed_at');
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
