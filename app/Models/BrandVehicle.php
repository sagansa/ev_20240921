<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandVehicle extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'image',
        'name',
        'brand_group_id',
    ];

    /** Induk perusahaan / grup industri (nullable — brand bisa mandiri). */
    public function brandGroup()
    {
        return $this->belongsTo(BrandGroup::class, 'brand_group_id');
    }

    public function modelVehicles()
    {
        return $this->hasMany(ModelVehicle::class);
    }

    /**
     * Kendaraan milik user di bawah brand ini — through model (tabel vehicles
     * memakai model_vehicle_id, bukan brand_vehicle_id).
     */
    public function vehicles()
    {
        return $this->hasManyThrough(Vehicle::class, ModelVehicle::class, 'brand_vehicle_id', 'model_vehicle_id');
    }

    /**
     * URL publik untuk logo brand (mis. /storage/images/brand/byd.png).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        $trimmed = trim($this->image);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '//')) {
            return $trimmed;
        }

        $clean = ltrim($trimmed, '/');
        if (! str_starts_with($clean, 'storage/')) {
            $clean = 'storage/' . $clean;
        }

        return '/' . $clean;
    }
}
