<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'sagansa'; // Use the sagansa database connection
    protected $table = 'products'; // Table name in the sagansa database

    protected $guarded = [];
}
