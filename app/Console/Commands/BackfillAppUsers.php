<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill tabel `app_users` dari user_id yang sudah ada di tabel-tabel EV.
 *
 * Jalankan setelah migrate:
 *   php artisan ev:backfill-app-users
 *
 * Idempotent — baris yang sudah ada (unique user_id) tidak diduplikasi.
 */
class BackfillAppUsers extends Command
{
    protected $signature = 'ev:backfill-app-users';

    protected $description = 'Populate app_users from existing user_id in EV tables';

    /**
     * Tabel-tabel EV yang punya kolom user_id (kecuali app_users itu sendiri).
     */
    private const TABLES = [
        'saved_stations',
        'station_reviews',
        'station_photos',
        'vehicles',
        'user_subscriptions',
        'testers',
        'batteries',
        'charges',
        'charger_locations',
        'state_of_healths',
        'discount_home_chargings',
        'contributor_profiles',
        'location_audit_logs',
    ];

    public function handle(): int
    {
        $userIds = $this->collectUserIds();

        if ($userIds->isEmpty()) {
            $this->info('Tidak ada user_id ditemukan di tabel EV.');

            return 0;
        }

        $this->info("Ditemukan {$userIds->count()} user_id unik. Memproses...");

        $created = 0;

        foreach ($userIds as $userId) {
            $existing = AppUser::where('user_id', $userId)->first();

            if ($existing) {
                continue;
            }

            AppUser::create([
                'user_id' => $userId,
                'provider' => null,
                'platform' => null,
                'source' => 'backfill',
                'login_count' => 0,
                'first_login_at' => null,
                'last_login_at' => null,
            ]);

            $created++;
        }

        $this->info("Selesai. {$created} baris baru ditambahkan ({$userIds->count()} total user).");

        return 0;
    }

    private function collectUserIds(): \Illuminate\Support\Collection
    {
        $allIds = collect();

        foreach (self::TABLES as $table) {
            if (! DB::connection('ev')->getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $columnExists = DB::connection('ev')->getSchemaBuilder()->hasColumn($table, 'user_id');
            if (! $columnExists) {
                continue;
            }

            $ids = DB::connection('ev')
                ->table($table)
                ->whereNotNull('user_id')
                ->where('user_id', '>', 0)
                ->distinct()
                ->pluck('user_id');

            $allIds = $allIds->merge($ids);
        }

        return $allIds->unique()->values();
    }
}
