<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ElectricCurrent extends Model
{
    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];
}
