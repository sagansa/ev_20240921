<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;

class ChargerCategory extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $fillable = ['name'];
    protected $table = 'charger_categories';
    protected $connection = 'ev'; // Use the sagansa database connection

    public function plnChargerLocations()
    {
        return $this->hasMany(PlnChargerLocation::class);
    }
}
