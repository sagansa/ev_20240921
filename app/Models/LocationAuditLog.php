<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationAuditLog extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'location_id',
        'user_id',
        'action',
        'old_data',
        'new_data',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class, 'location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}