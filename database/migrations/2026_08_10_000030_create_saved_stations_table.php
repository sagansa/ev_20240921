<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookmark lokasi PLN/ESDM oleh user (Fase 3 — Peta User).
 *
 * User "save" sebuah charging_stations.id agar muncul di peta "Peta Saya".
 * Berbeda dgn `charger_locations` (custom pin buat sendiri), saved_stations
 * adalah referensi ke station canonical yg sudah ada — tanpa duplikasi data.
 *
 * Unique (user_id, charging_station_id): satu user tidak bisa save station
 * yg sama dua kali. Tanpa FK constraint (cross-DB user; canonical rehydrate),
 * mengikuti preseden charges.charging_station_id.
 *
 * Tidak gated source — semua station (PLN/ESDM) bisa di-bookmark.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('saved_stations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('charging_station_id')->index();
            $table->timestamps();

            // Satu user tidak bisa bookmark station yg sama dua kali.
            $table->unique(['user_id', 'charging_station_id'], 'saved_stations_user_station_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('saved_stations');
    }
};
