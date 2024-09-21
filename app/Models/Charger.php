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

    protected $guarded = [];

    public function currentCharger()
    {
        return $this->belongsTo(CurrentCharger::class, 'current_charger_id');
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
        return $this->hasMany(Charge::class);
    }

    public function getChargerNameAttribute()
    {
        return $this->currentCharger->name .
            ' - ' .
            $this->typeCharger->name .
            ' - ' .
            $this->powerCharger->name;
    }
}
