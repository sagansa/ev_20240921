<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charger extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];

    protected $withCount = ['charges'];

    public function currentCharger()
    {
        return $this->belongsTo(CurrentCharger::class);
    }

    public function typeCharger()
    {
        return $this->belongsTo(TypeCharger::class);
    }

    public function powerCharger()
    {
        return $this->belongsTo(PowerCharger::class);
    }

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class);
    }

    public function charge()
    {
        return $this->hasMany(Charge::class, 'charger_id');
    }

    public function merkCharger()
    {
        return $this->belongsTo(MerkCharger::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getChargerNameAttribute()
    {
        return $this->currentCharger->name .
            ' - ' .
            $this->typeCharger->name .
            ' - ' .
            $this->powerCharger->name;
    }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }
}
