<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentCharger extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function typeChargers()
    {
        return $this->hasMany(TypeCharger::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
