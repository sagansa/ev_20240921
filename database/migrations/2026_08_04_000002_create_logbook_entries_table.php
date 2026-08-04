<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOGBOOK ENTRIES — catatan sesi charging milik user, ter-snapshot.
 *
 * Tabel ini hidup di connection `ev` (server EV data), sementara `users`
 * berada di connection `sagansa_user` yang berbeda — jadi `user_id` adalah
 * soft-link (tanpa FK) ke `users.id`. `charging_station_id` juga soft-link ke
 * `charging_stations.id` agar fitur user-data (logbook) tidak mengunci row
 * canonical yang bisa di-rehydrate/berubah. Kolom `station_*` adalah snapshot
 * denormalized sehingga riwayat user tetap utuh walau station diubah/dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('logbook_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Soft-link (tanpa FK — lintas connection / canonical table)
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('charging_station_id')->nullable();

            // Snapshot station (denormalized dari charging_stations saat store)
            $table->string('station_name');
            $table->text('station_address')->nullable();
            $table->decimal('station_latitude', 12, 8)->nullable();
            $table->decimal('station_longitude', 12, 8)->nullable();
            $table->string('station_provider')->nullable();
            $table->string('station_type_charge')->nullable();

            // Data sesi
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

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('logbook_entries');
    }
};
