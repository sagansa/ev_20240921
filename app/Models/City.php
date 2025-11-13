<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    public $timestamps = false;

    protected $connection = 'mysql'; // Use the sagansa database connection
    protected $table = 'cities';

    protected $guarded = [];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function postalCodes()
    {
        return $this->hasMany(PostalCode::class);
    }

    public function chargerLocations()
    {
        return $this->hasMany(ChargerLocation::class);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class);
    }
}
