<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlnChargerLocation extends Model
{
    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'pln_charger_locations'; // Specify the table name

    protected $fillable = [
        'id',
        'name',
        'address',
        'provider_id',
        'owner_machine',
        'latitude',
        'longitude',
        'location_category_id',
        'province_id',
        'cluster_island_id',
    ];

    // Define relationships if needed
    public function clusterIsland()
    {
        return $this->belongsTo(ClusterIsland::class, 'cluster_island_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function locationCategory()
    {
        return $this->belongsTo(LocationCategory::class, 'location_category_id');
    }

    public function plnChargerLocationDetails()
    {
        return $this->hasMany(PlnChargerLocationDetail::class, 'pln_charger_location_id');
    }

    // Define the accessor
    public function getNameAttribute()
    {
        // Return the name attribute or any transformation you need
        return $this->attributes['name'] ?? 'Default Name';
    }

    public function getLocationAttribute(): array
    {
        return [
            "lat" => (float)$this->latitude,
            "lng" => (float)$this->longitude,
        ];
    }

    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'];
            $this->attributes['longitude'] = $location['lng'];
            unset($this->attributes['location']);
        }
    }

    public function getCoordinateAttribute()
    {
        return number_format($this->latitude, 5) . ', ' . number_format($this->longitude, 5);
    }

    public static function getComputedLocation(): string
    {
        return 'location';
    }

    public function getChargerLocationNameAttribute()
    {
        return $this->provider->name . ' - ' . $this->name;
    }
}
