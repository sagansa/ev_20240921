<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleReview extends Model
{
    use UsesDefaultConnectionWhenTesting;
    use HasFactory;
    use SoftDeletes;
    use HasUuids;

    protected $connection = 'ev';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'rating',
        'pros',
        'cons',
        'body',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
