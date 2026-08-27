<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesImport extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasFactory;

    protected $connection = 'ev';

    protected $fillable = [
        'file_name',
        'source',
        'year',
        'period_start',
        'period_end',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function stats()
    {
        return $this->hasMany(VehicleSalesStat::class);
    }
}
