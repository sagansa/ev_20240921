<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationReport extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'location_id',
        'reporter_id',
        'report_type',
        'description',
        'evidence_photos',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'evidence_photos' => 'array',
        'processed_at' => 'datetime',
    ];

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class, 'location_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}