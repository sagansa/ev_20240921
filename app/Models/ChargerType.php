<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChargerType extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $guarded = [];
}
