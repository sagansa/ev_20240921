<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationUpdate extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'location_id',
        'contributor_id',
        'field_name',
        'old_value',
        'new_value',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class, 'location_id');
    }

    public function contributor()
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}