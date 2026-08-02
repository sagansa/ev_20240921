<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPKLU — instalasi (charger box / mesin).
 *
 * Satu stasiun (esdm_singgat_spklu_stations) punya banyak instalasi
 * (response.spklu[].instalasi[]). Tiap instalasi merepresentasikan satu unit
 * mesin charger dengan merek, jenis charging, dan tarif tertentu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spklu_installations', function (Blueprint $table) {
            $table->id();
            // Catatan: esdm_id di data ESDM TIDAK unik (5 ID duplikat lintas stasiun),
            // jadi pakai index biasa, bukan unique. Relasi internal tetap via PK 'id'.
            $table->unsignedBigInteger('esdm_id')->index()->comment('instalasi.id dari ESDM (bisa duplikat)');
            $table->unsignedBigInteger('station_id')->comment('FK ke esdm_singgat_spklu_stations.id');
            $table->unsignedBigInteger('station_esdm_id')->nullable()->index()->comment('spklu_lokasi_id dari ESDM (untuk cross-check)');

            $table->string('nomor_identitas')->nullable()->index();
            $table->string('merek_mesin')->nullable();
            $table->string('nomor_seri_mesin')->nullable();
            $table->string('jenis_pengisian_spklu')->nullable()->index()->comment('Slow|Medium|Fast|Ultra Fast Charging');

            // Tarif — disimpan sebagai string persis dari ESDM (format Rp/kWh perlu klarifikasi unit)
            $table->string('harga_pengisian_raw', 32)->nullable()->comment('string asli dari ESDM');
            $table->string('harga_layanan_raw', 32)->nullable()->comment('string asli dari ESDM');

            $table->timestamps();

            $table->foreign('station_id')
                ->references('id')
                ->on('esdm_singgat_spklu_stations')
                ->cascadeOnDelete();

            $table->index('station_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spklu_installations');
    }
};
