<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StateOfHealth extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev'; // Use the sagansa database connection

    protected $fillable = [
        'image',
        'date',
        'vehicle_id',
        'battery_id',
        'km',
        'percentage',
        'remaining_battery',
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'km' => 'integer',
        'percentage' => 'float',
        'remaining_battery' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function battery()
    {
        return $this->belongsTo(Battery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
