<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentCharger extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'name',
    ];

    public function typeChargers()
    {
        return $this->hasMany(TypeCharger::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
