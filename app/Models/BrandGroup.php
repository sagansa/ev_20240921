<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Induk perusahaan / grup industri (mis. SAIC, Toyota Group). Anggotanya
 * brand di brand_vehicles — penggabungan hanya di agregasi Pasar EV,
 * bukan merge entitas brand.
 */
class BrandGroup extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $fillable = ['name'];

    public function brandVehicles()
    {
        return $this->hasMany(BrandVehicle::class, 'brand_group_id');
    }
}
