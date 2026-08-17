<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft-deletes support to users so an account deletion can anonymize the
     * row while keeping foreign-key data (charges, vehicles, etc.) intact.
     *
     * Tabel `users` berada di koneksi `sagansa_user` (DB auth pusat — lihat
     * App\Models\User::$connection), bukan di koneksi default `ev`, sehingga
     * migration ini WAJIB menarget koneksi tersebut. Skema DB sagansa_user
     * dikelola terpusat di repo sagansa/services/migration (mirror:
     * 2026_08_17_000001_add_soft_deletes_to_users_table.php); file ini guard
     * hasColumn agar idempoten bila kolom sudah dibuat dari service migrasi.
     */
    public function up(): void
    {
        Schema::connection('sagansa_user')->table('users', function (Blueprint $table) {
            if (! Schema::connection('sagansa_user')->hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('sagansa_user')->table('users', function (Blueprint $table) {
            if (Schema::connection('sagansa_user')->hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
