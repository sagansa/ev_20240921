<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advertisement extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev';

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'target_url',
        'platform',
        'position',
        'is_active',
        'start_date',
        'end_date',
        'impression_count',
        'click_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $attributes = [
        'impression_count' => 0,
        'click_count' => 0,
        'is_active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeForPlatform($query, $platform)
    {
        return $query->where(function ($query) use ($platform) {
            $query->where('platform', $platform)
                ->orWhere('platform', 'both');
        });
    }
}