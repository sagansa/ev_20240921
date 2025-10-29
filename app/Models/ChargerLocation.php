<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class ChargerLocation extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'image',
        'name',
        'provider_id',
        'location_on',
        'status',
        'description',
        'latitude',
        'longitude',
        'parking',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'postal_code_id',
        'user_id',
        'location',
    ];

    protected $appends = [
        'name',
        'location',
    ];

    protected $withCount = ['charges'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->resolveMediaPath($value) ?? '/images/ev-station.png',
        );
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class);
    }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    public function discountHomeChargings()
    {
        return $this->hasMany(DiscountHomeCharging::class);
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

    protected function resolveMediaPath($path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        if (Str::startsWith($trimmed, ['http://', 'https://'])) {
            return $trimmed;
        }

        if (Str::startsWith($trimmed, ['/'])) {
            return $trimmed;
        }

        if (Str::startsWith($trimmed, ['storage/'])) {
            return '/' . ltrim($trimmed, '/');
        }

        return '/storage/' . ltrim($trimmed, '/');
    }

    /**
     * Scope to get charger locations near a given point, ordered by distance
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param int $distanceInKm
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNear($query, $latitude, $longitude, $distanceInKm = 10)
    {
        return $query->selectRaw("
            *,
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )) AS distance
        ", [$latitude, $longitude, $latitude])
        ->whereRaw("
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )) < ?", [$latitude, $longitude, $latitude, $distanceInKm])
        ->orderBy('distance');
    }
}
