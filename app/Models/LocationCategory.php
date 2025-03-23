<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationCategory extends Model
{
    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'location_categories';
    protected $fillable = ['name'];
}
