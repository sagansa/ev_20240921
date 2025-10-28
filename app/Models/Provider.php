<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasUuids;
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $build = [
        'id',
        'name',
        'image',
        'status',
        'contact',
        'address',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'postal_code_id',
    ];

    public function chargerLocations()
    {
        return $this->hasMany(ChargerLocation::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function postalCode()
    {
        return $this->belongsTo(PostalCode::class);
    }
}
