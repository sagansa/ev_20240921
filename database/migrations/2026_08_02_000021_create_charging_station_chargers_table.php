<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CANONICAL charging station chargers — child "charger box" level.
 *
 * Satu stasiun punya banyak charger box (di ESDM = instalasi/mesin charger).
 * Match dengan shape `charger_boxes` di contract mobile via
 * SpkluChargerBoxResource. Column `nama` dipakai sebagai nama_chargerbox
 * (ESDM merek_mesin, sumber lain bisa nama_chargerbox).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('charging_station_chargers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('station_id');

            $table->string('chargerbox_id')->nullable();
            $table->string('type_charge')->nullable();
            $table->string('nama')->nullable();
            $table->string('watt')->nullable();
            $table->unsignedInteger('jumlah_charger')->default(0);
            $table->unsignedInteger('jumlah_konektor')->default(0);
            $table->string('icon')->nullable();
            $table->string('gambar')->nullable();

            // Tarif per unit (raw dari source)
            $table->string('harga_pengisian')->nullable();
            $table->string('harga_layanan')->nullable();

            $table->timestamps();

            $table->foreign('station_id')
                ->references('id')
                ->on('charging_stations')
                ->cascadeOnDelete();

            $table->index('station_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('charging_station_chargers');
    }
};
