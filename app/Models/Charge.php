<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charge extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the ev database connection
    protected $table = 'charges';

    protected $fillable = [
        'image_start',
        'image_finish',
        'vehicle_id',
        'date',
        'charger_location_id',
        'charger_id',
        'km_now',
        'is_finish_charging',
        'start_charging_now',
        'finish_charging_now',
        'parking',
        'kWh',
        'street_lighting_tax',
        'value_added_tax',
        'admin_cost',
        'total_cost',
        'user_id',
        'km_before',
        'finish_charging_before',
        'is_kwh_measured',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charger()
    {
        return $this->belongsTo(Charger::class, 'charger_id');
    }

    public function currentCharger()
    {
        return $this->charger->currentCharger();
    }
}
