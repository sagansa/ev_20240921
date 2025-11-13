<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContributorProfile extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'user_id',
        'credibility_score',
        'total_contributions',
        'approved_contributions',
        'rejected_contributions',
        'badges',
        'is_trusted',
        'trust_level',
        'last_contribution_at',
    ];

    protected $casts = [
        'badges' => 'array',
        'is_trusted' => 'boolean',
        'last_contribution_at' => 'datetime',
    ];

    protected $attributes = [
        'credibility_score' => 0,
        'total_contributions' => 0,
        'approved_contributions' => 0,
        'rejected_contributions' => 0,
        'is_trusted' => false,
        'trust_level' => 'novice',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLocationUpdates()
    {
        return $this->hasMany(LocationUpdate::class, 'contributor_id');
    }

    public function getLocationReports()
    {
        return $this->hasMany(LocationReport::class, 'reporter_id');
    }

    public function getCommunityLocations()
    {
        return $this->hasMany(ChargerLocation::class, 'user_id');
    }
}