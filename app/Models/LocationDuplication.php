<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationDuplication extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'primary_location_id',
        'duplicate_location_id',
        'distance_meters',
        'similarity_score',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_action',
    ];

    protected $casts = [
        'distance_meters' => 'decimal:2',
        'similarity_score' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'detected',
        'similarity_score' => 0,
    ];

    public function primaryLocation()
    {
        return $this->belongsTo(ChargerLocation::class, 'primary_location_id');
    }

    public function duplicateLocation()
    {
        return $this->belongsTo(ChargerLocation::class, 'duplicate_location_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}