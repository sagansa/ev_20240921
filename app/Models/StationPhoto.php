<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Foto lokasi SPKLU (Fase 2) — galeri per lokasi, terpisah dari review.
 *
 * `path` menyimpan path relatif di disk `public` (mis. "station-photos/42/abc.jpg").
 * `url()` meng-resolve ke URL publik "/storage/...". user_id hanya utk gate &
 * moderasi admin; TIDAK diekspos ke resource publik (anonim, sama dgn review).
 */
class StationPhoto extends Model
{
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $table = 'station_photos';

    protected $fillable = [
        'charging_station_id',
        'user_id',
        'path',
    ];

    protected $casts = [
        'charging_station_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'user_id', // never expose uploader identity
    ];

    public function station()
    {
        return $this->belongsTo(ChargingStation::class, 'charging_station_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL publik foto — path relatif di disk `public` di-prefix "/storage/".
     * Bila path sudah absolut (mulai http/https atau /), return apa adanya.
     */
    public function url(): string
    {
        $path = $this->path;
        if (preg_match('#^https?://#', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Hapus file fisik saat record di-delete permanen (forceDelete). Soft-delete
     * biasa TIDAK menghapus file (foto tetap tampil walau record trashed saat
     * admin unpublish). Override forceDelete utk bersihkan storage.
     */
    public function forceDelete(): bool
    {
        if ($this->path && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }

        return parent::forceDelete();
    }
}
