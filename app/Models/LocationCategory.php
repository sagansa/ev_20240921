<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;

class LocationCategory extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'location_categories';
    protected $fillable = ['name'];
}
