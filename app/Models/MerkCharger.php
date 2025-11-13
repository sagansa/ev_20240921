<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MerkCharger extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'name',
    ];

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
