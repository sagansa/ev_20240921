<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;

/**
 * Tester funnel (Closed Testing Play Console).
 *
 * Soft-link user_id (tanpa FK) — user hidup di connection `sagansa_user`.
 * Email di-copy saat register supaya panel tidak join lintas DB.
 * Status: `registered` → `store_active` (ping dari build store).
 */
class Tester extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'testers';

    protected $fillable = [
        'user_id',
        'email',
        'platform',
        'source',
        'device_id',
        'status',
        'first_store_ping_at',
        'last_ping_at',
        'last_ping_version_code',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'first_store_ping_at' => 'datetime',
        'last_ping_at' => 'datetime',
    ];

    public function pings()
    {
        return $this->hasMany(TesterPing::class, 'tester_id');
    }

    /**
     * Jumlah hari berbeda saat tester aktif (dari tester_pings.created_at).
     * Dipakai kolom "Hari Aktif" di panel & verifikasi syarat 14 hari.
     */
    public function getActiveDaysAttribute(): int
    {
        return (int) $this->pings()
            ->selectRaw('count(distinct date(created_at)) as active_days')
            ->value('active_days');
    }
}
