<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlnChargerLocationDetail extends Model
{
    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'pln_charger_location_details';
    protected $fillable = [
        'pln_charger_location_id',
        'power',
        'is_active_charger',
        'count_connector_charger',
        'operation_date',
        'year',
        'category_charger_id',
        'merk_charger_id',
        'charging_type_id',
        ];

    public function plnChargerLocation()
    {
        return $this->belongsTo(PlnChargerLocation::class, 'pln_charger_location_id');
    }
    public function chargerCategory()
    {
        return $this->belongsTo(ChargerCategory::class, 'charger_category_id');
    }
    public function merkCharger()
    {
        return $this->belongsTo(MerkCharger::class, 'merk_charger_id');
    }
}
