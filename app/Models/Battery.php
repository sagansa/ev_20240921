<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Battery extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'label',
        'serial_number',
        'capacity_kwh',
        'installed_at',
        'installed_km',
        'removed_at',
        'removed_km',
        'status',
        'note',
    ];

    protected $casts = [
        'capacity_kwh' => 'float',
        'installed_km' => 'integer',
        'removed_km' => 'integer',
        'installed_at' => 'date',
        'removed_at' => 'date',
        'status' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    public function stateOfHealths()
    {
        return $this->hasMany(StateOfHealth::class);
    }

    /**
     * Baterai yang sedang aktif (terpasang & belum pensiun).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('removed_at')->where('status', 1);
    }

    /**
     * Baterai aktif terbaru utk kendaraan (auto-assign sesi/SoH baru).
     * Caller wajib sudah memastikan ownership kendaraan.
     */
    public static function activeForVehicle(string $vehicleId): ?self
    {
        return static::query()
            ->where('vehicle_id', $vehicleId)
            ->active()
            ->orderByDesc('installed_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Baterai yang aktif pada suatu titik KM (utk auto-assign SoH historis):
     * battery milik vehicle dgn installed_km <= km AND (removed_km null ATAU
     * removed_km >= km), diurutkan installed_km desc. Fallback: bila tidak ada
     * yang cocok (mis. km di bawah installed_km semua), kembalikan baterai
     * aktif terbaru kendaraan.
     */
    public static function resolveForKm(string $vehicleId, float $km): ?self
    {
        $matched = static::query()
            ->where('vehicle_id', $vehicleId)
            ->where(fn ($q) => $q->whereNull('removed_km')->orWhere('removed_km', '>=', $km))
            ->where(fn ($q) => $q->whereNull('installed_km')->orWhere('installed_km', '<=', $km))
            ->orderByDesc('installed_km')
            ->orderByDesc('installed_at')
            ->first();

        if ($matched) {
            return $matched;
        }

        return static::activeForVehicle($vehicleId);
    }

    /**
     * Jumlah siklus pengisian: (total finish% - total start%) / 100.
     * Dihitung dari sesi charging milik baterai ini.
     */
    public function getCycleCountAttribute(): int
    {
        $sumFinish = (float) $this->charges()->sum('finish_charging_now');
        $sumStart = (float) $this->charges()->sum('start_charging_now');

        return (int) max(round(($sumFinish - $sumStart) / 100), 0);
    }

    /**
     * Total jarak tempuh selama baterai terpasang:
     * km tertinggi pada sesi charging - km saat dipasang.
     */
    public function getTotalKmAttribute(): int
    {
        $maxKm = (float) $this->charges()->max('km_now');
        $installedKm = (float) ($this->installed_km ?? 0);

        return (int) max($maxKm - $installedKm, 0);
    }

    /**
     * Label ramah user; fallback ke "Battery {urutan}" bila kosong.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: 'Battery';
    }
}
