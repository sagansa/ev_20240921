<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;

class ClusterIsland extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev'; // Use the sagansa database connection
    protected $table = 'cluster_islands';
    protected $fillable = ['name'];
}
