<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mencatat asal login user EV (Google/Apple) dan platform (Android/iOS/Web).
 *
 * Berada di koneksi `sagansa_ev` — terpisah dari `sagansa_user`.
 * Tidak ada FK ke `users` karena lintas koneksi database.
 */
class AppUser extends Model
{
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev';

    protected $fillable = [
        'user_id',
        'provider',
        'platform',
        'source',
        'login_count',
        'first_login_at',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'login_count' => 'integer',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke User (lintas koneksi).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rekam event login: create baris baru atau update baris yang ada.
     *
     * - Baris pertama: first_login_at diisi, login_count = 1.
     * - Login berikutnya: login_count di-increment, last_login_at di-update.
     */
    public static function recordLogin(int $userId, string $provider, ?string $platform): void
    {
        $existing = static::where('user_id', $userId)->first();

        if ($existing) {
            $existing->update([
                'provider' => $provider,
                'platform' => $platform,
                'login_count' => $existing->login_count + 1,
                'last_login_at' => now(),
            ]);

            return;
        }

        static::create([
            'user_id' => $userId,
            'provider' => $provider,
            'platform' => $platform,
            'source' => 'login',
            'login_count' => 1,
            'first_login_at' => now(),
            'last_login_at' => now(),
        ]);
    }
}
