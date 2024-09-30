<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MerkCharger extends Model
{
    use HasUuids;
    use HasFactory;

    protected $guarded = [];

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }
}
