<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPBKLU — kabinet (lemari penukaran baterai) + baterai.
 *
 * Satu stasiun SPBKLU punya banyak kabinet (response.spbklu[].kabinet[]), dan
 * tiap kabinet punya banyak baterai (kabinet[].baterai[]). Di sini digabung
 * menjadi 2 tabel: kabinet (header) + baterai (item), dengan FK berjalan.
 *
 * Dipisah dari migration kabinet karena ini 2 entitas berbeda. Tabel baterai
 * dibuat di migration 000006.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spbklu_cabinets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esdm_id')->unique()->comment('kabinet.id dari ESDM');
            $table->unsignedBigInteger('station_id')->comment('FK ke esdm_singgat_spbklu_stations.id');
            $table->unsignedBigInteger('station_esdm_id')->nullable()->index()->comment('spbklu_lokasi_id dari ESDM (cross-check)');

            $table->string('merek_kabinet')->nullable()->index()->comment('SWAP, dll');
            $table->string('status_instalasi')->nullable()->index()->comment('Beroperasi / dll');
            $table->string('kapasitas_raw', 16)->nullable()->comment('string asli dari ESDM');
            $table->string('harga_penukaran_baterai_raw', 32)->nullable()->comment('string asli');

            $table->timestamps();

            $table->foreign('station_id')
                ->references('id')
                ->on('esdm_singgat_spbklu_stations')
                ->cascadeOnDelete();

            $table->index('station_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spbklu_cabinets');
    }
};
