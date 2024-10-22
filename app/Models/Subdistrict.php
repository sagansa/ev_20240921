<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subdistrict extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'mysql'; // Use the sagansa database connection
    protected $table = 'subdistricts';

    protected $guarded = [];

    public function district()
    {
        return $this->belongsTo(District::class);
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
