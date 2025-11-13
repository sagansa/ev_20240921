<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitorProfile extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'platform',
        'location_accessed',
        'visit_duration',
        'pages_viewed',
    ];

    protected $casts = [
        'location_accessed' => 'array',
    ];

    public function getRecentLocationsAccessed()
    {
        // This would return the locations accessed by this visitor
        // Implementation would depend on the specific use case
        return $this->location_accessed ? json_decode($this->location_accessed, true) : [];
    }
}