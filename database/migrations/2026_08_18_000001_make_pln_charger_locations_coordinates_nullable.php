<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buat latitude/longitude pln_charger_locations nullable.
 *
 * CSV master PLN berisi koordinat rusak/blank (mis. "41260/41260" Ternate,
 * 14 baris Propinsi kosong). Import memvalidasi koordinat terhadap bound
 * Indonesia dan menulis NULL di luar bound. DB production sudah nullable
 * (diubah manual) — migration ini menyamakan fresh install + test sqlite.
 */
return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            $table->decimal('latitude', 12, 8)->nullable()->change();
            $table->decimal('longitude', 12, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Mengembalikan ke NOT NULL berisiko bila ada data NULL — biarkan nullable.
    }
};
