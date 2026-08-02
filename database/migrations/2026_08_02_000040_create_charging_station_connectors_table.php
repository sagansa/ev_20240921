<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charging station connectors — level plug individual (paling granular).
 *
 * Hierarki: charging_stations → charging_station_chargers (mesin/instalasi)
 *           → charging_station_connectors (plug: CCS2/CHAdeMO/AC Tipe 2).
 *
 * Setiap konektor punya status real-time sendiri (available/charging/finishing/
 * unavailable) yang di-fold dari esdm_singgat_connector_status oleh poller.
 * Ini yang ditampilkan di detail stasiun mobile: "CCS2: available, CHAdeMO: charging".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('charging_station_connectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charger_id')->comment('FK ke charging_station_chargers.id');
            $table->unsignedBigInteger('source_connector_id')->nullable()->index()->comment('ESDM konektor esdm_id');

            $table->string('nama_konektor')->nullable()->index()->comment('CCS2, AC (Tipe 2), CHAdeMO, dll');
            $table->string('img_path')->nullable()->comment('relatif ke public/');

            // Status real-time per-plug (di-fold dari esdm_singgat_connector_status)
            $table->string('status_konektor', 24)->nullable()->index()->comment('available/charging/finishing/unavailable/null');
            $table->string('status', 32)->nullable()->comment('Beroperasi/Tidak Beroperasi (dari ESDM)');
            $table->timestamp('status_updated_at')->nullable();

            $table->timestamps();

            $table->foreign('charger_id')
                ->references('id')
                ->on('charging_station_chargers')
                ->cascadeOnDelete();

            $table->index('charger_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('charging_station_connectors');
    }
};
