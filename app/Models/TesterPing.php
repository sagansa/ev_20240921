<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Ping tester append-only (channel ias / store). Dipakai menghitung
 * "hari aktif" dan mengetahui build testing yang terpakai.
 */
class TesterPing extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'tester_pings';

    protected $fillable = [
        'tester_id',
        'device_id',
        'channel',
        'version_code',
        'platform',
    ];

    protected $casts = [
        'tester_id' => 'integer',
    ];

    public function tester()
    {
        return $this->belongsTo(Tester::class, 'tester_id');
    }
}
