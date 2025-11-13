<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PowerCharger extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'name',
        'type_charger_id',
    ];

    public function typeCharger()
    {
        return $this->belongsTo(TypeCharger::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
