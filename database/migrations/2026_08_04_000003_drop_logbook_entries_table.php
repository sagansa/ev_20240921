<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DROP logbook_entries — fitur logbook dihapus karena repetisi sistem Charge.
 * Sistem Charge (charges + ChargingSessionController + Filament dashboard)
 * diadaptasi untuk mobile sebagai pengganti; lihat migration adaptasi charges.
 *
 * Tidak ada data untuk dimigrasi: logbook_entries belum pernah diisi (fitur
 * masih placeholder saat keputusan eliminasi diambil).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->dropIfExists('logbook_entries');
    }

    public function down(): void
    {
        // Recreate tabel logbook_entries jika di-rollback.
        // Lihat migration 2026_08_04_000002_create_logbook_entries_table.php
        // untuk skema asli (dipertahankan di history git).
        Schema::connection('ev')->create('logbook_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('charging_station_id')->nullable();
            $table->string('station_name');
            $table->text('station_address')->nullable();
            $table->decimal('station_latitude', 12, 8)->nullable();
            $table->decimal('station_longitude', 12, 8)->nullable();
            $table->string('station_provider')->nullable();
            $table->string('station_type_charge')->nullable();
            $table->dateTime('session_at');
            $table->decimal('odometer_km', 12, 2)->nullable();
            $table->decimal('distance_driven_km', 12, 2)->nullable();
            $table->decimal('energy_kwh', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->decimal('parking_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'session_at']);
        });
    }
};
