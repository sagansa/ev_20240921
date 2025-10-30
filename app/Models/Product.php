<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'sagansa'; // Use the sagansa database connection
    protected $table = 'products'; // Table name in the sagansa database

    protected $guarded = [];
}
