<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClusterIsland extends Model
{
    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'cluster_islands';
    protected $fillable = ['name'];
}
